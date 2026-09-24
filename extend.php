<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extend;
use Flarum\Api\Resource\NotificationResource;
use Flarum\Discussion\Discussion;
use Flarum\Frontend\Document;
use Flarum\Settings\SettingsRepositoryInterface;
use FoF\Redis\Extend\Redis;
use ISeekUp\Discussion\IdSlugDriver;

require_once __DIR__.'/src/Discussion/IdSlugDriver.php';

return [
    (new Redis([
        'host' => 'redis',
        'password' => null,
        'port' => 6379,
        'database' => 1,
        'timeout' => 2.0,
        'read_timeout' => 2.0,
        'prefix' => 'iseekup:',
        'queue' => [
            'retry_after' => 600,
            'block_for' => 5,
            'after_commit' => true,
            'failed_ttl' => 604800,
            'queues' => ['default'],
        ],
    ]))
        ->useDatabaseWith('cache', 1)
        ->useDatabaseWith('queue', 2)
        ->useDatabaseWith('session', 3)
        ->disable(['settings']),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/site-custom/persistent-welcome-hero.js')
        ->content(function (Document $document) {
            $settings = resolve(SettingsRepositoryInterface::class);
            $value = $settings->get('search_cjk_mode', false);

            // Keep the welcome hero visible while removing the user-dismiss action.
            $document->preHead[] = '<script>try{window.localStorage.removeItem("welcomeHidden")}catch(e){}</script>';
            $document->head[] = '<style id="iseekup-persistent-welcome-hero-style">.WelcomeHero .Hero-close{display:none!important}</style>';

            $document->payload['settings'] = array_merge($document->payload['settings'] ?? [], [
                'search_cjk_mode' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ]);
        }),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ModelUrl(Discussion::class))
        ->addSlugDriver('ID', IdSlugDriver::class),

    /* Ignore notification subject models without a JSON:API resource. */
    (new Extend\ApiResource(NotificationResource::class))
        ->field('subject', function ($field) {
            $field->collection(array_values(array_filter(
                array_unique($field->collections ?? []),
                static fn ($type): bool => is_string($type) && $type !== ''
            )));

            return $field;
        }),
];
