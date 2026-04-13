Понял кейс. При таком трафике и “набегами на месяц” чаще всего выигрывает не “самая умная капча”, а **комбинация из 2–3 простых барьеров**, которые дешёвые в поддержке и не бесят живых.

Ниже — варианты именно под **Yii2 advanced**.

---

## Самый простой и бесплатный вариант без внешних сервисов: встроенная Yii2 CAPTCHA

Это **полностью бесплатная self-hosted** альтернатива reCAPTCHA, работает на вашем сервере.

### 1) Контроллер (frontend)

```php
// frontend/controllers/SiteController.php
use yii\captcha\CaptchaAction;

public function actions()
{
    return [
        'captcha' => [
            'class' => CaptchaAction::class,
            'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
        ],
    ];
}
```

### 2) Модель формы

```php
public $verifyCode;

public function rules()
{
    return [
        // ...
        ['verifyCode', 'captcha', 'captchaAction' => 'site/captcha'],
    ];
}
```

### 3) Вьюха

```php
use yii\captcha\Captcha;

echo $form->field($model, 'verifyCode')->widget(Captcha::class, [
    'captchaAction' => 'site/captcha',
    'template' => '{image} {input}',
]);
```

**Плюсы:** бесплатно, быстро, без внешних API.
**Минусы:** иногда боты решают простые капчи (но у вас атаки редкие — чаще хватит).

---

## Чтобы “месячный долбёж” не убивал — добавьте 2 лёгких барьера (сильно эффективнее капчи)

### A) Rate limit по IP именно на атакуемые экшены (самое важное)

Сделайте ограничение частоты POST’ов на формы (логин/регистрация/контакты/комменты).

Простой фильтр через cache (подойдёт file cache, лучше redis/memcached, если есть):

```php
// common/components/IpRateLimitFilter.php
namespace common\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\TooManyRequestsHttpException;

class IpRateLimitFilter extends ActionFilter
{
    public int $limit = 20;     // запросов
    public int $window = 60;    // секунд

    public function beforeAction($action)
    {
        $req = Yii::$app->request;
        if (!$req->isPost) return true;

        $ip = $req->userIP ?: 'unknown';
        $key = 'rl:' . $action->uniqueId . ':' . $ip;

        $cache = Yii::$app->cache;
        $count = (int)$cache->get($key);

        if ($count === 0) {
            $cache->set($key, 1, $this->window);
        } else {
            $count++;
            $cache->set($key, $count, $this->window);
        }

        if ($count > $this->limit) {
            throw new TooManyRequestsHttpException('Слишком много запросов. Попробуйте позже.');
        }

        return true;
    }
}
```

Подключение в нужном контроллере (только на проблемные экшены):

```php
public function behaviors()
{
    return [
        'ipRate' => [
            'class' => \common\components\IpRateLimitFilter::class,
            'only' => ['login', 'signup', 'contact', 'comment-create'],
            'limit' => 10,
            'window' => 60,
        ],
    ];
}
```

👉 Это резко снижает “долбят месяц”: вы “режете” на входе, и атака становится бессмысленной.

---

### B) Honeypot (скрытое поле-ловушка)

80% тупых ботов на формах отваливаются вообще без капчи.

**В модели:**

```php
public $website; // honeypot

public function rules()
{
    return [
        ['website', 'string'],
        ['website', 'validateHoneypot'],
    ];
}

public function validateHoneypot($attribute)
{
    if (!empty($this->$attribute)) {
        $this->addError($attribute, 'Spam detected');
    }
}
```

**В форме (скрыть стилем):**

```php
echo $form->field($model, 'website')->textInput([
    'autocomplete' => 'off',
    'tabindex' => -1,
    'style' => 'position:absolute;left:-9999px;top:-9999px;height:1px;width:1px;opacity:0;',
])->label(false);
```

---

### C) “Минимальное время заполнения” (очень дёшево, хорошо против скриптов)

Добавьте скрытое поле с timestamp на рендере, и на сервере отклоняйте слишком быстрые отправки (< 2–3 сек).

---

## Что я бы поставил у вас (практичный минимум)

1. **Rate limit по IP на POST** для атакуемых форм (10–20/мин на IP)
2. **Honeypot**
3. Если после этого что-то всё ещё пролезает — **встроенная Yii2 CAPTCHA** только на “подозрительных” (например, показывать капчу после 2–3 ошибок/попыток с IP)
