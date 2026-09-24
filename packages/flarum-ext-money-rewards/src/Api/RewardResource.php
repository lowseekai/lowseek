<?php

namespace ClarkWinkelmann\MoneyRewards\Api;

use ClarkWinkelmann\MoneyRewards\Reward;
use Flarum\Api\Schema;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends AbstractDatabaseResource<Reward>
 */
class RewardResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'money-rewards';
    }

    public function model(): string
    {
        return Reward::class;
    }

    public function scope(Builder $query, \Tobyz\JsonApiServer\Context $context): void
    {
        $query->whereHas('post', fn (Builder $post) => $post->whereVisibleTo($context->getActor()));
    }

    public function endpoints(): array
    {
        return [];
    }

    public function fields(): array
    {
        return [
            Schema\Integer::make('amount'),
            Schema\Boolean::make('newMoney')
                ->property('new_money'),
            Schema\Str::make('comment')
                ->nullable(),
            Schema\DateTime::make('createdAt')
                ->property('created_at'),
            Schema\Relationship\ToOne::make('post')
                ->type('posts')
                ->includable(),
            Schema\Relationship\ToOne::make('giver')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('receiver')
                ->type('users')
                ->includable(),
        ];
    }
}
