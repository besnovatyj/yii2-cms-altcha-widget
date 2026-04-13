Ниже — “подключил и забыл” интеграция **ALTCHA в Yii2 advanced**:

* виджет для формы (`AltchaWidget`)
* action, который выдаёт challenge JSON (`AltchaChallengeAction`)
* валидатор модели (`AltchaValidator`)
* сервис с DI + защита от replay-атак через cache (`AltchaService`)

ALTCHA-виджет — это web-component `<altcha-widget>` и ему нужен `challengeurl`, где ваш сервер возвращает новый
challenge. ([ALTCHA][1])
На сабмите он отправляет **Base64 JSON** (обычно в поле `altcha`), а сервер проверяет криптографически (без внешних
API). ([ALTCHA][2])
Для кастомного сервера (без Sentinel) проверка делается через `verifySolution` из PHP-библиотеки. ([ALTCHA][2])

---

## 1) Установка

### 1.1. PHP-библиотека (server-side)

```bash
composer require altcha-org/altcha
```

Библиотека официально для PHP **8.2+** (для старого PHP — у них есть ветка v0.x.x). ([GitHub][3])

### 1.2. JS-виджет (client-side)

ALTCHA предлагает CDN-скрипт (ES module): ([ALTCHA][1])
`https://cdn.jsdelivr.net/gh/altcha-org/altcha/dist/altcha.min.js`

В коде ниже я сделал AssetBundle с возможностью **переключить на локальный файл**, чтобы не зависеть от CDN.

---

## 2) Код (скопируй в проект)

Рекомендую создать папку:
`common/components/altcha/`

### 2.1. Value-object конфигурации

**`common/components/altcha/AltchaConfig.php`**

```php
<?php
declare(strict_types=1);

namespace common\components\altcha;

final class AltchaConfig
{
    public function __construct(
        public readonly string $hmacKey,
        public readonly int $maxNumber = 50000,
        public readonly int $expiresSeconds = 120,
        public readonly int $replayTtlSeconds = 600,
        public readonly string $cacheKeyPrefix = 'altcha:used:',
    ) {}
}
```

---

### 2.2. Интерфейс сервиса (SOLID: зависим от абстракции)

**`common/components/altcha/AltchaServiceInterface.php`**

```php
<?php
declare(strict_types=1);

namespace common\components\altcha;

interface AltchaServiceInterface
{
    /** Возвращает challenge в виде массива для JSON-ответа. */
    public function createChallenge(): array;

    /** Проверяет Base64 payload, пришедший из формы (поле altcha). */
    public function verifyBase64Payload(string $payloadBase64): bool;
}
```

---

### 2.3. Реализация сервиса (SRP: только ALTCHA-логика + replay через cache)

**`common/components/altcha/AltchaService.php`**

```php
<?php
declare(strict_types=1);

namespace common\components\altcha;

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\ChallengeOptions;
use DateInterval;
use DateTimeImmutable;
use Yii;
use yii\caching\CacheInterface;

final class AltchaService implements AltchaServiceInterface
{
    private Altcha $altcha;

    public function __construct(
        private readonly AltchaConfig $config,
        private readonly ?CacheInterface $cache = null,
    ) {
        $this->altcha = new Altcha($this->config->hmacKey);
    }

    public function createChallenge(): array
    {
        $expires = (new DateTimeImmutable())->add(new DateInterval('PT' . $this->config->expiresSeconds . 'S'));

        $options = new ChallengeOptions(
            maxNumber: $this->config->maxNumber,
            expires: $expires,
        );

        $challenge = $this->altcha->createChallenge($options);

        // Виджету нужен JSON challenge. Возвращаем как массив.
        return [
            'algorithm'  => $challenge->algorithm,
            'challenge'  => $challenge->challenge,
            'salt'       => $challenge->salt,
            'signature'  => $challenge->signature,
            'expires'    => $challenge->expires ?? null, // на случай если библиотека отдаёт это поле
        ];
    }

    public function verifyBase64Payload(string $payloadBase64): bool
    {
        $payloadBase64 = trim($payloadBase64);
        if ($payloadBase64 === '') {
            return false;
        }

        // Replay protection: одно и то же решение нельзя принять повторно.
        // (ALTCHA прямо рекомендует вести реестр использованных payload, чтобы избегать replay-атак.) :contentReference[oaicite:5]{index=5}
        if ($this->cache !== null) {
            $hash = hash('sha256', $payloadBase64);
            $key = $this->config->cacheKeyPrefix . $hash;

            if ($this->cache->get($key) !== false) {
                return false;
            }
        }

        $json = base64_decode($payloadBase64, true);
        if ($json === false) {
            return false;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return false;
        }

        // Минимальная форма payload для verifySolution (без Sentinel). :contentReference[oaicite:6]{index=6}
        foreach (['algorithm','challenge','number','salt','signature'] as $k) {
            if (!array_key_exists($k, $data)) {
                return false;
            }
        }

        $ok = $this->altcha->verifySolution($data, true);

        if ($ok && $this->cache !== null) {
            $hash = hash('sha256', $payloadBase64);
            $key = $this->config->cacheKeyPrefix . $hash;
            $this->cache->set($key, 1, $this->config->replayTtlSeconds);
        }

        return $ok;
    }
}
```

---

### 2.4. Action для выдачи challenge (GRASP: Controller → делегирует сервису)

**`common/components/altcha/AltchaChallengeAction.php`**

```php
<?php
declare(strict_types=1);

namespace common\components\altcha;

use Yii;
use yii\base\Action;
use yii\web\Response;

final class AltchaChallengeAction extends Action
{
    public function run(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var AltchaServiceInterface $service */
        $service = Yii::$container->get(AltchaServiceInterface::class);

        // Важно: не кэшировать этот endpoint на уровне CDN/прокси.
        Yii::$app->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $service->createChallenge();
    }
}
```

---

### 2.5. Валидатор для модели

**`common/components/altcha/AltchaValidator.php`**

```php
<?php
declare(strict_types=1);

namespace common\components\altcha;

use Yii;
use yii\validators\Validator;

final class AltchaValidator extends Validator
{
    public string $message = 'Проверка "не робот" не пройдена. Попробуйте ещё раз.';

    public function validateAttribute($model, $attribute): void
    {
        $value = (string)($model->$attribute ?? '');

        /** @var AltchaServiceInterface $service */
        $service = Yii::$container->get(AltchaServiceInterface::class);

        if (!$service->verifyBase64Payload($value)) {
            $this->addError($model, $attribute, $this->message);
        }
    }
}
```

---

### 2.6. AssetBundle (CDN по умолчанию, но можно self-host)

**`common/components/altcha/AltchaAsset.php`**

```php
<?php
declare(strict_types=1);

namespace common\components\altcha;

use yii\web\AssetBundle;

final class AltchaAsset extends AssetBundle
{
    /**
     * По документации ALTCHA можно подключать через CDN:
     * https://cdn.jsdelivr.net/gh/altcha-org/altcha/dist/altcha.min.js :contentReference[oaicite:7]{index=7}
     */
    public bool $useCdn = true;

    /** Если self-host: положите файл, например, в /frontend/web/js/altcha.min.js */
    public string $localUrl = '/js/altcha.min.js';

    public function init(): void
    {
        parent::init();

        $url = $this->useCdn
            ? 'https://cdn.jsdelivr.net/gh/altcha-org/altcha/dist/altcha.min.js'
            : $this->localUrl;

        $this->js = [$url];

        // ALTCHA скрипт — ES module. :contentReference[oaicite:8]{index=8}
        $this->jsOptions = [
            'type'  => 'module',
            'async' => true,
            'defer' => true,
        ];
    }
}
```

---

### 2.7. Виджет для формы

**`common/components/altcha/AltchaWidget.php`**

```php
<?php
declare(strict_types=1);

namespace common\components\altcha;

use yii\base\Widget;
use yii\helpers\Html;

final class AltchaWidget extends Widget
{
    /** Абсолютный или относительный URL endpoint'а, который отдаёт challenge JSON. */
    public string $challengeUrl;

    /** Имя поля, которое уйдёт в POST (по умолчанию altcha). :contentReference[oaicite:9]{index=9} */
    public string $name = 'altcha';

    /** Любые дополнительные атрибуты <altcha-widget> (например, floating, hidefooter и т.п.). */
    public array $options = [];

    /** Использовать CDN (true) или self-host (false) для altcha.min.js */
    public bool $useCdn = true;

    /** URL локального скрипта, если useCdn=false */
    public string $localScriptUrl = '/js/altcha.min.js';

    public function run(): string
    {
        $asset = new AltchaAsset();
        $asset->useCdn = $this->useCdn;
        $asset->localUrl = $this->localScriptUrl;
        $asset::register($this->view);

        $attrs = array_merge($this->options, [
            'challengeurl' => $this->challengeUrl,
            'name' => $this->name,
        ]);

        // Сам web component, встраивается прямо внутрь формы. :contentReference[oaicite:10]{index=10}
        return Html::tag('altcha-widget', '', $attrs);
    }
}
```

---

## 3) Подключение в Yii2 advanced

### 3.1. Секретный HMAC ключ

Нужно задать **ALTCHA_HMAC_KEY** (любой криптостойкий рандом, хранить как секрет).

Например, в `common/config/params-local.php`:

```php
return [
    // ...
    'altcha.hmacKey' => getenv('ALTCHA_HMAC_KEY') ?: 'CHANGE_ME_LONG_RANDOM_SECRET',
];
```

### 3.2. DI-конфигурация (common/config/main.php)

Добавьте в bootstrap (или в компонентную конфигурацию) биндинги контейнера:

```php
use common\components\altcha\AltchaConfig;
use common\components\altcha\AltchaService;
use common\components\altcha\AltchaServiceInterface;

Yii::$container->setSingleton(AltchaConfig::class, function () {
    return new AltchaConfig(
        hmacKey: Yii::$app->params['altcha.hmacKey'],
        maxNumber: 50000,
        expiresSeconds: 120,
        replayTtlSeconds: 600,
    );
});

Yii::$container->setSingleton(AltchaServiceInterface::class, function () {
    return new AltchaService(
        Yii::$container->get(AltchaConfig::class),
        Yii::$app->cache // можно null, но лучше включить cache
    );
});
```

---

### 3.3. Endpoint challengeurl (в любом контроллере, например SiteController)

```php
public function actions()
{
    return [
        'altcha-challenge' => [
            'class' => \common\components\altcha\AltchaChallengeAction::class,
        ],
    ];
}
```

---

### 3.4. Модель формы (добавить поле + правило)

Пример:

```php
public string $altcha = '';

public function rules(): array
{
    return [
        // ...
        ['altcha', \common\components\altcha\AltchaValidator::class],
    ];
}
```

---

### 3.5. Вьюха формы (подключить виджет)

```php
use yii\helpers\Url;
use common\components\altcha\AltchaWidget;

// внутри ActiveForm:
echo AltchaWidget::widget([
    'challengeUrl' => Url::to(['/Altcha/backend/challenge'], true),
    'name' => 'altcha',
    'options' => [
        // сюда можно класть любые атрибуты <altcha-widget>
        // например: 'hidefooter' => true,
    ],
]);
```

### 3.6. Правила ограничения доступа

Не забыть в форме авторизации и регистрации разрешить доступ незареганным юзерам. 

```php
   'as access' => [
        'class' => \modules\user\components\AccessControl::class,
        'allowActions' => [
            'user/auth/login',
            'user/auth/logout',
            'site/error',
            'Altcha/backend/altcha/challenge',
        ]
    ],
```

### 3.7 Nginx

CSP требует разрешения.

`worker-src 'self' blob:;` — это разрешит создавать воркеры из blob: URL.

```te
add_header Content-Security-Policy-Report-Only "
worker-src 'self' blob:;
";
```
---

## 4) Self-host JS (чтобы совсем без внешних вызовов)

ALTCHA официально разрешает “скачать скрипт” и подключить локально. ([ALTCHA][1])
Самый простой путь без npm:

1. скачайте `altcha.min.js` (можно с их CDN/репозитория)
2. положите в `frontend/web/js/altcha.min.js`
3. в виджете: `useCdn => false`, `localScriptUrl => '/js/altcha.min.js'`

---

## 5) Практические настройки под ваш кейс “редко, но месяц долбят”

* **expiresSeconds** поставьте 120–300 секунд (чтобы людям хватало времени)
* **replayTtlSeconds** 600–3600 (чтобы один и тот же payload не переиспользовали)
* endpoint `/site/altcha-challenge` обязательно без кеширования (я уже добавил `Cache-Control: no-store`)

---

[1]: https://altcha.org/docs/v2/widget-integration/ "Widget Integration | ALTCHA"

[2]: https://altcha.org/docs/v2/server-verification/?utm_source=chatgpt.com "Server Verification"

[3]: https://github.com/altcha-org/altcha-lib-php "GitHub - altcha-org/altcha-lib-php: A lightweight PHP library for creating and verifying ALTCHA challenges."
