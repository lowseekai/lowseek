<?php

namespace ClarkWinkelmann\MoneyRewards\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\User\User;

class UserFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canSeeMoneyRewardHistory')
                ->get(fn (User $user, Context $context) => $context->getActor()->can('seeMoneyRewardHistory', $user)),
        ];
    }
}
