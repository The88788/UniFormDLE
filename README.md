# UniForm21

**Универсальные AJAX-формы для DataLife Engine 21.x** - адаптация модуля DLE-UniForm 2.0.0, приведённая в рабочее состояние под DLE 21 и PHP 8.x.

`DLE 21.x` · `PHP 8.0-8.3 (проверено на 8.3)` · `Лицензия MIT`

Формы отправляются без перезагрузки страницы, письма уходят штатным почтовым механизмом DLE, есть CSRF-защита, проверка IP по бан-листу движка, обязательные поля, вложения, копия письма отправителю и отправка автору новости.

---

## Благодарность

Оригинальный модуль **DLE UniForm** разработан **Павлом Белоусовым (ПафНутиЙ / pafnuty)** и распространяется по лицензии MIT. Большое спасибо автору за разработку! Шапки с его авторством сохранены во всех файлах кода, как того требует лицензия MIT.

## Что изменено по сравнению с 2.0.0

Адаптация под DLE 21.0 / PHP 8.3 (полный журнал правок вёлся на рабочей установке DLE 21.0):

**`engine/ajax/uniform.php`**
- Проверка `!empty($banned_info['ip'])` перед `check_ip()` (устранён undefined index в PHP 8)
- Санитизация `$_REQUEST['formConfig]` через `preg_replace` - защита от path traversal в пути к `config.tpl`

**`engine/modules/uniform/uniform.php`**
- Убрана `convert_unicode()` - функция удалена из DLE 21 (движок работает только в UTF-8)
- Добавлена проверка `isset($_POST['csrfToken'])` перед `checkToken()`
- Проверки `is_array($newsItem)` / `!empty($newsItem['id'])` после `super_query`
- `implode` для значений-массивов в тегах `{uf_field_*}`
- Проверка `is_array($_REQUEST['fields'])` при обработке скрытых полей
- **Action формы изменён:** `/engine/ajax/controller.php?mod=uniform` → `/index.php?controller=ajax&mod=uniform` - в DLE 21 прямой вызов `controller.php` запрещён, весь AJAX идёт через `index.php?controller=ajax` (см. `plugins.class.php`)
- **Убран прямой `require parse.class.php`** - он обходил `spl_autoload` DLE, который подгружает HTMLPurifier только при запросе класса `ParseFilter` (`plugins.class.php`), что приводило к Fatal error «Class HTMLPurifier_Config not found» при POST

**`engine/modules/uniform/functions.php`**
- Убрана `convert_unicode()` в `assignFiedls()`

**`engine/modules/uniform/sendmail.php`**
- `xfieldsload(true)` / `xfieldsdataload()` заменены на `DLEUserXFields::GETFields()` / `DLEUserXFields::xfieldsdataload()` (класс DLE 14.3+, подгружается автозагрузкой)
- Доступ к допполям по именованным ключам (`$value['name']`, `$value['private']`) вместо числовых
- Добавлены `is_array`/`isset`-проверки для `super_query`, `parse_url`, `$_SERVER['HTTP_REFERER']`, `$mailHeader`, `$arSendMail['email']`

**`templates/{THEME}/uniform/js/uniform.js`**
- URL AJAX-запросов: `dle_root + 'engine/ajax/controller.php'` → `dle_root + 'index.php?controller=ajax'` (в двух местах: открытие попапа Magnific Popup и инлайн-загрузка форм)

Совместимость остального кода подтверждена по исходникам DLE 21 без правок: `dle_mail`, `addAttachment`, `dle_template`, `dle_cache`/`create_cache`, `check_ip`, `get_vars`/`set_vars`, `SECURE_AUTH_KEY`, `use_admin_mail`.

## Установка

### Шаг 1. Файлы движка

Скопируйте содержимое папки `engine/` дистрибутива в корень сайта:

- `engine/ajax/uniform.php`
- `engine/modules/uniform/` (целиком, включая `.htaccess`)

### Шаг 2. Шаблоны форм

Скопируйте папку `uniform/` из `templates/{THEME}/` дистрибутива в папку **вашего шаблона**:

```
templates/ВАШ_ШАБЛОН/uniform/
```

Внутри - 5 форм (`callback`, `feedback`, `buy`, `inline`, `main`) и папки `css/` и `js/`.

### Шаг 3. Подключение CSS и JS в main.tpl

Подключите файлы **прямыми ссылками** в `main.tpl` вашего шаблона.

В `<head>`:

```html
<link href="{THEME}/uniform/css/uniform.css" type="text/css" rel="stylesheet">
```

Перед `</body>` (после подключения jQuery движка):

```html
<script src="{THEME}/uniform/js/jquery.ladda.min.js"></script>
<script src="{THEME}/uniform/js/jquery.form.min.js"></script>
<script src="{THEME}/uniform/js/jquery.magnificpopup.min.js"></script>
<script src="{THEME}/uniform/js/uniform.js"></script>
```

> ⚠️ **Важно:** минификатор `engine/classes/min` **удалён из DLE 18+**. Старые примеры подключения из документации UniForm 2.0.0 (через `{THEME}/css/...,uniform/css/uniform.css` и аналогичные пути минификатора) **не работают** - файлы будут отдавать 403. Используйте только прямые ссылки `{THEME}/...`, как показано выше.

### Шаг 4. Вызов форм

**Всплывающая форма** - атрибут `data-uniform` на любом элементе:

```html
<span data-uniform='{"formConfig": "callback"}'>Заказать звонок</span>

<button data-uniform='{"formConfig": "buy"}'>Купить</button>

<!-- скрытые поля передаются в том же JSON, их имена нужно разрешить параметром hidden в config.tpl -->
<span data-uniform='{"formConfig": "feedback", "newsid": "{news-id}"}'>Написать автору</span>
```

**Встроенная (inline) форма** - загружается сразу при открытии страницы:

```html
<div data-uniform-inline='{"formConfig": "inline"}'>
  <div class="uf-inline-loading"></div>
</div>
```

Вызовы можно вставлять в любой `.tpl` шаблона или прямо в текст новости/статической страницы (в режиме исходного кода).

### Шаг 5. Укажите свои e-mail

⚠️ **Обязательно!** Во всех пяти `config.tpl` стоят плейсхолдеры `emails = mail@mail.ru` (в feedback, buy, inline и main - ещё и `mail2@mail.ru`). Замените их на свои адреса, иначе заявки будут уходить в никуда.

## Готовые формы в комплекте

| Форма | Назначение | Обязательные поля |
|---|---|---|
| `callback` | Всплывающая форма «Заказать звонок» (телефон, имя, удобное время) | `phone` |
| `feedback` | Обратная связь, копия письма посетителю (`sendToSender`) | `textarea`, `email` |
| `buy` | Заказ товара (имя, телефон, e-mail, комментарий) | `email`, `name` |
| `inline` | Встроенная форма без попапа для любой страницы | `email` |
| `main` | Встроенная форма быстрого вопроса (например, для главной) | `email` |

Новая форма создаётся копированием любой из папок (`config.tpl` + `form.tpl` + `email.tpl`), сменой `templateFolder` в конфиге и вызовом по новому имени.

## Точка входа и защита

- Рабочий URL модуля: **`/index.php?controller=ajax&mod=uniform&formConfig=<имя_формы>`**
- Прямое обращение к `/engine/ajax/uniform.php` всегда отдаёт **403 «Hacking attempt!»** - это штатная защита модуля от вызова вне движка, а не ошибка.

## Поддержка и услуги

Адаптация выполнена командой **[The88788.com](https://the88788.com)**:

- разработка сайтов на DataLife Engine
- адаптация шаблонов под актуальные версии DLE
- разработка модулей и плагинов
- администрирование серверов

Вопросы и предложения по модулю - через Issues этого репозитория.

## Хостинг для вашего сайта на DLE

Ищете недорогой и быстрый сервер для сайта на DataLife Engine? Рекомендуем проверенный вариант - **[hip.hosting](https://hip.hosting/?code=1b890e361ec6ae343e57)**:

- **VPS от $2.4 в месяц** - 1 vCPU, 1 GB RAM, 10 GB SSD. Да, два сорок, и это полноценный KVM-сервер с root-доступом
- **Единая цена в любой локации** - у большинства провайдеров такие же ресурсы стоят $4-6, здесь цена одна для всех площадок
- **10 локаций** - Швеция, Нидерланды, Франция, Германия, Польша, Финляндия, Латвия, Эстония, Великобритания, США
- Быстрые NVMe-диски Intel/Samsung, канал 200 Mbps, безлимитный трафик
- Сервер готов меньше чем за минуту, любая ОС, масштабирование в пару кликов
- Uptime 99.8% - сайт работает круглосуточно

Такого VPS хватает для сайта на DLE с посещаемостью в десятки тысяч хитов в сутки. Для UniForm21 и любого сайта на DLE 21 - самое то.

**[Зарегистрироваться и забрать сервер за $2.4 →](https://hip.hosting/?code=1b890e361ec6ae343e57)**

## Лицензия

MIT - см. файл [LICENSE](LICENSE). Copyright (c) 2016 Павел Белоусов (pafnuty), адаптация для DLE 21 / PHP 8.x - The88788.com (2026).
