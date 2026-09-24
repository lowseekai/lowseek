<?php

namespace ClarkWinkelmann\MoneyRewards\Controllers;

use ClarkWinkelmann\MoneyRewards\Reward;
use Flarum\Api\JsonApiResponse;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractJsonApiController implements RequestHandlerInterface
{
    protected function actor(ServerRequestInterface $request): User
    {
        return RequestUtil::getActor($request);
    }

    protected function response(array $data, array $included = [], array $meta = []): ResponseInterface
    {
        $document = ['data' => $data];

        if ($included !== []) {
            $document['included'] = array_values($included);
        }

        if ($meta !== []) {
            $document['meta'] = $meta;
        }

        return new JsonApiResponse($document);
    }

    protected function resource(
        string $type,
        Model $model,
        array $attributes = [],
        array $relationships = []
    ): array {
        $resource = [
            'type' => $type,
            'id' => (string) $model->getKey(),
            'attributes' => $attributes,
        ];

        if ($relationships !== []) {
            $resource['relationships'] = $relationships;
        }

        return $resource;
    }

    protected function identifier(string $type, Model $model): array
    {
        return [
            'type' => $type,
            'id' => (string) $model->getKey(),
        ];
    }

    protected function addIncluded(array &$included, array $resource): void
    {
        $key = $resource['type'] . ':' . $resource['id'];

        foreach ($included as $existing) {
            if ($existing['type'] . ':' . $existing['id'] === $key) {
                return;
            }
        }

        $included[] = $resource;
    }

    protected function userResource(User $user, ?int $pointBalance = null): array
    {
        $attributes = [
            'username' => $user->username,
            'displayName' => $user->display_name,
            'avatarUrl' => $user->avatar_url,
            'avatarSrcset' => $user->avatar_srcset,
            'slug' => $user->username,
        ];

        if ($pointBalance !== null) {
            $attributes['pointBalance'] = $pointBalance;
        }

        return $this->resource('users', $user, $attributes);
    }

    protected function rewardResource(Reward $reward): array
    {
        $relationships = [];

        if ($reward->post) {
            $relationships['post'] = ['data' => $this->identifier('posts', $reward->post)];
        }

        if ($reward->giver) {
            $relationships['giver'] = ['data' => $this->identifier('users', $reward->giver)];
        }

        if ($reward->receiver) {
            $relationships['receiver'] = ['data' => $this->identifier('users', $reward->receiver)];
        }

        return $this->resource('money-rewards', $reward, [
            'amount' => (int) $reward->amount,
            'newMoney' => (bool) $reward->new_money,
            'comment' => $reward->comment,
            'createdAt' => optional($reward->created_at)?->toIso8601String(),
        ], $relationships);
    }

    protected function postResource(Post $post): array
    {
        $relationships = [];

        if ($post->user) {
            $relationships['user'] = ['data' => $this->identifier('users', $post->user)];
        }

        if ($post->discussion) {
            $relationships['discussion'] = ['data' => $this->identifier('discussions', $post->discussion)];
        }

        if ($post->relationLoaded('moneyRewards')) {
            $relationships['moneyRewards'] = [
                'data' => $post->moneyRewards
                    ->map(fn (Reward $reward) => $this->identifier('money-rewards', $reward))
                    ->values()
                    ->all(),
            ];
        }

        return $this->resource('posts', $post, [
            'number' => (int) $post->number,
            'contentType' => $post->type,
            'createdAt' => optional($post->created_at)?->toIso8601String(),
        ], $relationships);
    }

    protected function discussionResource(Discussion $discussion): array
    {
        return $this->resource('discussions', $discussion, [
            'title' => $discussion->title,
            'slug' => $discussion->slug,
        ]);
    }
}
