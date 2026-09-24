<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema): void {
        if (! $schema->hasTable('money_rewards')) {
            return;
        }

        // Point System stores whole points. Round legacy monetary values before
        // changing the column type so existing reward rows remain readable.
        $schema->getConnection()->statement(
            'UPDATE money_rewards SET amount = ROUND(amount)'
        );

        $schema->table('money_rewards', function (Blueprint $table): void {
            $table->unsignedInteger('amount')->change();
        });
    },
    'down' => function (Builder $schema): void {
        if (! $schema->hasTable('money_rewards')) {
            return;
        }

        $schema->table('money_rewards', function (Blueprint $table): void {
            $table->float('amount')->change();
        });
    },
];
