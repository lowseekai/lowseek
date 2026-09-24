<?php

namespace ClarkWinkelmann\MoneyRewards\Controllers;

use ClarkWinkelmann\MoneyRewards\Reward;
use Flarum\Http\RequestUtil;
use Flarum\User\UserRepository;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListUserRewardController extends AbstractJsonApiController
{
    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $user = $this->users->findOrFail(
            (int) Arr::get($request->getQueryParams(), 'id'),
            $actor
        );

        $actor->assertCan('seeMoneyRewardHistory', $user);

        $rewards = Reward::query()
            ->where(function ($query) use ($user) {
                $query
                    ->where('giver_user_id', $user->id)
                    ->orWhere('receiver_user_id', $user->id);
            })
            ->with([
                'post.discussion',
                'post.user',
                'giver',
                'receiver',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $included = [];
        $data = [];

        foreach ($rewards as $reward) {
            $data[] = $this->rewardResource($reward);

            if ($reward->giver) {
                $this->addIncluded($included, $this->userResource($reward->giver));
            }

            if ($reward->receiver) {
                $this->addIncluded($included, $this->userResource($reward->receiver));
            }

            if ($reward->post) {
                $this->addIncluded($included, $this->postResource($reward->post));

                if ($reward->post->discussion) {
                    $this->addIncluded($included, $this->discussionResource($reward->post->discussion));
                }
            }
        }

        return $this->response($data, $included);
    }
}
