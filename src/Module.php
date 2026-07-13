<?php

/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Altcha;

use Besnovatyj\Kernel\module\CmsModule;
use Besnovatyj\Contracts\module\DeclaresModule;
use Besnovatyj\Contracts\module\ProvidesAdminMenu;
use Besnovatyj\Contracts\module\ProvidesAppConfig;
use Besnovatyj\Contracts\module\ProvidesBootstrap;

class Module extends CmsModule implements
    DeclaresModule, ProvidesAdminMenu, ProvidesAppConfig, ProvidesBootstrap
{
    public const bool EDITABLE = true;
    public const string VERSION = '1.0.0';
    public const string MODULE_ID = 'Altcha';
    public static function moduleId(): string { return self::MODULE_ID; }
    public static function moduleVersion(): string { return self::VERSION; }
    public static function isEditable(): bool { return self::EDITABLE; }
    public static function adminMenu(): array { return require __DIR__.'/config/adminMenu.php'; }
    public static function moduleConfig(): array { return require __DIR__.'/config/config.php'; }
    public static function bootstrapClasses(): array { return [Bootstrap::class]; }

    /**
     * Роут выдачи challenge нужен гостю на странице входа бэкенда (altcha на форме логина), поэтому
     * модуль сам добавляет его в whitelist ядрового гейта — ядру знать про Altcha не нужно.
     */
    public static function appConfig(): array
    {
        return [
            'app-backend' => [
                'as access' => [
                    'allowActions' => [
                        'Altcha/backend/altcha-challenge/challenge',
                    ],
                ],
            ],
        ];
    }
}
