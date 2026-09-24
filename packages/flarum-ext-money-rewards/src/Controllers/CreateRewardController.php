<?php

namespace ClarkWinkelmann\MoneyRewards\Controllers;

use ClarkWinkelmann\MoneyRewards\Reward;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Flarum\Post\PostRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramon\PointSystem\Repository\PointsRepository;

class CreateRewardController extends AbstractJsonApiController
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected PostRepository $posts,
        protected Factory $validation,
        protected TranslatorInterface $translator,
        protected PointsRepository $points,
        protected ConnectionInterface $db,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $post = $this->posts->findOrFail(
            (int) Arr::get($request->getQueryParams(), 'id'),
            $actor
        );
        $actor->assertCan('rewardWithMoney', $post);

        $attributes = (array) Arr::get($request->getParsedBody(), 'data.attributes', []);
        $amount = $this->amount(Arr::get($attributes, 'amount'));
        $comment = trim((string) Arr::get($attributes, 'comment', ''));
        $createMoney = filter_var(Arr::get($attributes, 'createMoney', false), FILTER_VALIDATE_BOOLEAN);

        $this->validation->make([
            'comment' => $comment,
        ], [
            'comment' => 'nullable|string|max:20000',
        ])->validate();

        $this->validateAmount($amount, $actor);

        if (! (bool) $this->settings->get('point-system.enabled', true)) {
            throw new ValidationException([
                'amount' => $this->translator->trans('lowseekai-money-rewards.api.error.systemDisabled'),
            ]);
        }

        if ($createMoney) {
            $actor->assertCan('money-rewards.createMoney');
        }

        $recipient = $post->user;
        if (! $recipient || $recipient->id === $actor->id) {
            throw new ValidationException([
                'post' => $this->translator->trans('lowseekai-money-rewards.api.error.invalidRecipient'),
            ]);
        }

        $this->db->transaction(function () use (
            $post,
            $actor,
            $recipient,
            $amount,
            $comment,
            $createMoney
        ): void {
            $reward = new Reward();
            $reward->post()->associate($post);
            $reward->giver()->associate($actor);
            $reward->receiver()->associate($recipient);
            $reward->amount = $amount;
            $reward->new_money = $createMoney;
            $reward->comment = $comment;
            $reward->save();

            if (! $createMoney) {
                try {
                    $this->points->deduct(
                        $actor,
                        $amount,
                        'money_reward.sent',
                        'money_reward',
                        $reward->id
                    );
                } catch (\DomainException $exception) {
                    throw new ValidationException([
                        'amount' => $this->translator->trans('lowseekai-money-rewards.api.error.notEnoughFunds'),
                    ]);
                }
            }

            $this->points->award(
                $recipient,
                $amount,
                $createMoney ? 'money_reward.created' : 'money_reward.received',
                'money_reward',
                $reward->id
            );
        });

        $post->load([
            'moneyRewards.giver',
            'moneyRewards.receiver',
        ]);

        $included = [];
        foreach ($post->moneyRewards as $postReward) {
            $this->addIncluded($included, $this->rewardResource($postReward));

            if ($postReward->giver) {
                $this->addIncluded(
                    $included,
                    $this->userResource(
                        $postReward->giver,
                        $postReward->giver->id === $actor->id
                            ? $this->points->getOrCreate($actor)->balance
                            : null
                    )
                );
            }

            if ($postReward->receiver) {
                $this->addIncluded(
                    $included,
                    $this->userResource(
                        $postReward->receiver,
                        $postReward->receiver->id === $recipient->id
                            ? $this->points->getOrCreate($recipient)->balance
                            : null
                    )
                );
            }
        }

        return $this->response([$this->postResource($post)], $included);
    }

    protected function amount(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || ! preg_match('/^[1-9][0-9]*$/', $value)) {
            throw new ValidationException([
                'amount' => $this->translator->trans('lowseekai-money-rewards.api.error.invalidAmount'),
            ]);
        }

        return min((int) $value, 1000000000);
    }

    protected function validateAmount(int $amount, \Flarum\User\User $actor): void
    {
        if (in_array($amount, $this->preselection(), true)) {
            return;
        }

        if (! $actor->hasPermission('money-rewards.customAmounts')) {
            throw new ValidationException([
                'amount' => $this->translator->trans('lowseekai-money-rewards.api.error.invalidAmount'),
            ]);
        }

        $rules = [
            'required',
            'integer',
            'min:' . max(1, (int) $this->settings->get('money-rewards.min', 1)),
        ];

        if (($max = (int) $this->settings->get('money-rewards.max', 0)) > 0) {
            $rules[] = 'max:' . $max;
        }

        $this->validation->make(['amount' => $amount], ['amount' => $rules])->validate();
    }

    /**
     * @return int[]
     */
    protected function preselection(): array
    {
        return collect(explode(',', (string) $this->settings->get('money-rewards.preselection', '')))
            ->map(static fn (string $value): int => (int) trim($value))
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
    }
}
