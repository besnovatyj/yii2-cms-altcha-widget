<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

namespace Besnovatyj\Altcha;

use common\components\module\BaseModule;

class Module extends BaseModule
{
    public const bool EDITABLE = true;
    public const string VERSION = '1.0.0';

    public static function getAdminMenu(): array
    {
        return require __DIR__ . '/config/adminMenu.php';
    }

    public static function getConfig(): array
    {
        return require __DIR__ . '/config/config.php';
    }

    public static function getOptions(): array
    {
        return [];
    }

}
