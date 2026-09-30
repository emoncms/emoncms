# Global variables

`index.php` sets up these globals before it runs a controller. Declare the ones a module needs:

```php
global $mysqli, $redis, $session, $settings;
```

See `Modules/input/input_controller.php` for a typical list.

## $settings

Settings array. `process_settings.php` merges `settings.ini` (or `settings.php`) over `default-settings.php`. See `example.settings.ini` for the sections.

## $path

Base URL of the Emoncms install, for example `http://localhost/emoncms/`. `view()` also passes it to every view.

## $mysqli

A [mysqli](https://www.php.net/manual/en/class.mysqli.php) connection to the Emoncms database, from `$settings['sql']`. Use prepared statements.

## $redis

A `Redis` instance when `$settings['redis']['enabled']` is true, otherwise `false`. Emoncms uses Redis to cache input and feed metadata and API keys, and to buffer feed writes on low-write systems. Code that uses Redis must work when `$redis` is `false`.

If Redis is enabled but the connection fails, `index.php` stops with an error. `prefix`, `auth` and `dbnum` in `$settings['redis']` set the key prefix, password and database number.

## $route

A `Route` object, defined in `route.php`. For the URL:

```
http://server/controller/action/subaction/subaction2.format?name=value
```

the properties are:

```php
$route->controller   // controller
$route->action       // action
$route->subaction    // subaction
$route->subaction2   // subaction2
$route->format       // format: html (default), json, text, md or csv
$route->method       // GET, POST, ...
```

## $session

Array describing the current session. Set by `User::emon_session_start()` for a web login, or by `User::apikey_session()` for an API key.

| Key | Meaning |
|---|---|
| `userid` | User id |
| `username` | Username. `API` for an API key session |
| `read` | 1 if the session can read |
| `write` | 1 if the session can write |
| `admin` | 1 for an admin session |
| `lang` | Language. `en` for API key sessions |
| `timezone` | User timezone |
| `startingpage` | Page to open after login |
| `gravatar` | Gravatar address |
| `cookielogin` | 1 if logged in with the remember me cookie |
| `emailverified` | Email verification state, when email verification is on |
| `apikey` | 1 for an API key session |
| `public_userid`, `public_username` | Set when a page is viewed through a public username URL, such as `username/dashboard/view` |

## $user

A `User` object from `Modules/user/user_model.php`. Use it for login, API keys and user details such as username, email, language and timezone.

## $log

An `EmonLogger`. Create one per file with `new EmonLogger(__FILE__)`. Logs go to `emoncms.log` in `$settings['log']['location']`.

## $param

A `Param` object from `param.php`. Reads request parameters, including decrypted parameters from an encrypted input request.

## Translations

`tr()` and `ctx_tr()` read `$GLOBALS['language']` and `$GLOBALS['translations']`. See [Developing a new module](developing-a-new-module.md#translations).
