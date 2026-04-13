<?php

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

// Есть в composer.json, в разделе "extra": "bootstrap": "Besnovatyj\\Altcha\\Bootstrap", поэтому yii2 сам бутстрапит
//    public static function getBootstrap(): string
//    {
//        return Bootstrap::class;
//    }

//    public static function setContainerConfig()
//    {
//        return (require __DIR__ . '/config/container.php')(\Yii::$container);
//    }
}
