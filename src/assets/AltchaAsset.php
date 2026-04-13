<?php

declare(strict_types=1);

namespace Besnovatyj\Altcha\assets;

use yii\web\AssetBundle;

final class AltchaAsset extends AssetBundle
{

    public $sourcePath = __DIR__ . '/media';

    /**
     * По документации ALTCHA можно подключать через CDN:
     * https://cdn.jsdelivr.net/gh/altcha-org/altcha/dist/altcha.min.js
     */
    public bool $useCdn = false;

    /** Если self-host */
    public string $localUrl = 'altcha.min.js';

    public function init(): void
    {
        parent::init();

        $url = $this->useCdn
            ? 'https://cdn.jsdelivr.net/gh/altcha-org/altcha/dist/altcha.min.js'
            : $this->localUrl;

        $this->js = [$url];

        // ALTCHA скрипт — ES module.
        $this->jsOptions = [
            'type' => 'module',
            'async' => true,
            'defer' => true,
        ];
    }
}
