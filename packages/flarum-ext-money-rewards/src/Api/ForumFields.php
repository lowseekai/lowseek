<?php

namespace ClarkWinkelmann\MoneyRewards\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Settings\SettingsRepositoryInterface;

class ForumFields
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Arr::make('moneyRewardsPreselection')
                ->get(fn () => $this->preselection()),

            Schema\Boolean::make('moneyRewardsCustomAmounts')
                ->get(fn ($forum, Context $context) => $context->getActor()->hasPermission('money-rewards.customAmounts')),

            Schema\Integer::make('moneyRewardsCustomAmountsMin')
                ->get(fn ($forum, Context $context) => $context->getActor()->hasPermission('money-rewards.customAmounts')
                    ? max(1, (int) $this->settings->get('money-rewards.min', 1))
                    : 0),

            Schema\Integer::make('moneyRewardsCustomAmountsMax')
                ->nullable()
                ->get(fn ($forum, Context $context) => $context->getActor()->hasPermission('money-rewards.customAmounts')
                    ? (($max = (int) $this->settings->get('money-rewards.max', 0)) > 0 ? $max : null)
                    : null),

            Schema\Boolean::make('moneyRewardsCreateMoney')
                ->get(fn ($forum, Context $context) => $context->getActor()->hasPermission('money-rewards.createMoney')),
        ];
    }

    /**
     * The point system stores whole points, so silently discard invalid
     * decimal values instead of exposing amounts the API cannot process.
     *
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
