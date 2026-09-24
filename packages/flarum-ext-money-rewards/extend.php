<?php

namespace ClarkWinkelmann\MoneyRewards;

use ClarkWinkelmann\MoneyRewards\Api\RewardResource;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Resource\PostResource;
use Flarum\Api\Resource\UserResource;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\User\User;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/less/forum.less'),

    (new Extend\Routes('api'))
        ->post('/posts/{id}/money-rewards', 'money-rewards.create', Controllers\CreateRewardController::class)
        ->get('/users/{id}/money-rewards', 'money-rewards.history', Controllers\ListUserRewardController::class),

    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\Settings())
        ->default('money-rewards.preselection', '')
        ->default('money-rewards.min', 1)
        ->default('money-rewards.max', 0),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(Api\ForumFields::class),

    (new Extend\ApiResource(PostResource::class))
        ->fields(Api\PostFields::class)
        ->endpoint(
            [Endpoint\Index::class, Endpoint\Show::class],
            fn (Endpoint\Index|Endpoint\Show $endpoint) => $endpoint
                ->addDefaultInclude([
                    'moneyRewards',
                    'moneyRewards.giver',
                    'moneyRewards.receiver',
                ])
                ->eagerLoad([
                    'moneyRewards.giver',
                    'moneyRewards.receiver',
                ])
        ),

    (new Extend\ApiResource(UserResource::class))
        ->fields(Api\UserFields::class),

    (new Extend\ApiResource(RewardResource::class)),

    (new Extend\Policy())
        ->modelPolicy(Post::class, Policies\PostPolicy::class)
        ->modelPolicy(User::class, Policies\UserPolicy::class),

    (new Extend\Model(Post::class))
        ->hasMany('moneyRewards', Reward::class, 'post_id'),
];
