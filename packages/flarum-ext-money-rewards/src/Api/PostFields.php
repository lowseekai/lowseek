<?php

namespace ClarkWinkelmann\MoneyRewards\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Post\Post;

class PostFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('rewardWithMoney')
                ->get(fn (Post $post, Context $context) => $context->getActor()->can('rewardWithMoney', $post)),

            Schema\Relationship\ToMany::make('moneyRewards')
                ->type('money-rewards')
                ->includable()
                ->get(fn (Post $post) => $post->moneyRewards->all()),
        ];
    }
}
