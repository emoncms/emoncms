# Developing a new module

A module adds a feature to Emoncms in its own folder under `Modules/`. Only the controller is required. The core `schedule` module is a small, complete example.

## Files

For a module called `mymodule`:

```
Modules/mymodule/
    module.json               name and version
    mymodule_controller.php   handles requests (required)
    mymodule_model.php        data and logic, as a class
    mymodule_menu.php         menu entries
    mymodule_schema.php       database tables
    Views/                    HTML views, with their JS and CSS
    locale/                   translations, <lang>.json
```

`module.json`:

```json
{
    "name": "My module",
    "version": "1.0.0"
}
```

A module with background scripts is usually installed in `/opt/emoncms/modules/mymodule`, with the web part symlinked into `Modules/`.

## Requests

All requests go through `index.php`. The URL is:

```
http://server/controller/action/subaction.format?name=value
```

- `controller`: the module, for example `mymodule` loads `Modules/mymodule/mymodule_controller.php`.
- `action`, `subaction`: what to do. Both optional. `subaction2` is also available.
- `format`: `html` (default), `json`, `text`, `md` or `csv`.

For example, `input/post.json?node=5&fulljson={"power":200}` runs the `post` action of the input module and returns JSON.

See [Global variables](global-variables.md) for `$route` and `$session`.

## Controller

The controller is a function named after the module. It returns the page content or data. `index.php` sends a `json` result as JSON, and wraps an `html` result in the theme.

```php
<?php
defined('EMONCMS_EXEC') or die('Restricted access');

function mymodule_controller()
{
    global $mysqli, $redis, $session, $route;

    require_once "Modules/mymodule/mymodule_model.php";
    $mymodule = new MyModule($mysqli, $redis);

    if ($route->format == 'html') {
        if ($route->action == 'view' && $session['write']) {
            return view("Modules/mymodule/Views/mymodule_view.php", array("title" => "My module"));
        }
    }

    if ($route->format == 'json') {
        if ($route->action == 'list' && $session['read']) {
            return $mymodule->get_list($session['userid']);
        }
    }

    return false;
}
```

A controller can return a value or `array('content' => $value)`. Other values are wrapped as `content`. A request that matches no controller gets a "not found" response.

Check `$session['read']`, `$session['write']` or `$session['admin']` before each action. The session may come from a web login or from an API key.

Actions that change data and are called from the web interface must be POST only. A GET can be fired by any tag that loads a URL, including stored content such as a dashboard, and the browser sends the session cookie with it. Check the method in the controller:

```php
if ($route->action == 'delete' && $session['write'] && $route->method == 'POST') {
    return $mymodule->delete($session['userid'], (int) post('id'));
}
```

Actions called by devices and scripts with an API key in the request may stay GET.

## Direct access

Controllers, models and views start with:

```php
defined('EMONCMS_EXEC') or die('Restricted access');
```

`index.php` defines `EMONCMS_EXEC`, so the file does nothing when requested directly, for example `http://server/Modules/mymodule/mymodule_controller.php`. Menu and schema files are only included by core.

## Request parameters

Read parameters with the helpers in `core.php`:

- `get($name, $error_if_missing = false, $default = null)`
- `post($name, $error_if_missing = false, $default = null)`
- `prop($name, ...)`: from GET or POST
- `put($name)`, `delete($name)`: from the request body

Validate every parameter before use. Cast ids with `(int)`. Filter names to the characters you allow, for example:

```php
$name = preg_replace('/[^\p{N}\p{L}_\s\-:]/u', '', get('name'));
```

Use prepared statements for all SQL:

```php
$stmt = $this->mysqli->prepare("SELECT id, name FROM mymodule WHERE userid = ?");
$stmt->bind_param("i", $userid);
$stmt->execute();
```

## Model

Put reusable logic in a model class. Pass the connections it needs to the constructor:

```php
class MyModule
{
    private $mysqli;
    private $redis;

    public function __construct($mysqli, $redis)
    {
        $this->mysqli = $mysqli;
        $this->redis = $redis;
    }
}
```

`$redis` is `false` when Redis is disabled in settings. Code that uses Redis must also work without it. See `Modules/feed/feed_model.php` for an example that uses both.

To install Redis: `sudo apt install redis-server php-redis`.

## Views

`view($filepath, $args)` renders a PHP file and returns the output. Each key in `$args` becomes a variable in the view. `$path`, the base URL, is always set.

Load scripts and styles with `load_js()` and `load_css()`. They add a version to the URL from the file time, so browsers load the new file after an update.

A view is a PHP file for the markup and a JS file for the behaviour. PHP renders the static text and one block of initial data. Vue renders everything that changes.

```php
<?php
defined('EMONCMS_EXEC') or die('Restricted access');
load_js("Lib/js/vue.global.prod-3.5.22.min.js");
load_css("Modules/mymodule/Views/mymodule.css");
?>
<div id="mymodule-app" class="panel-page" v-cloak>
    <h3><?php echo htmlspecialchars(tr("My module")); ?></h3>
    <div v-for="item in items">
        {{ item.name }}
        <button class="btn btn-default btn-sm" @click="remove(item)"><?php echo htmlspecialchars(tr("Delete")); ?></button>
    </div>
</div>
<script>
var mymodule_init = {
    items: <?php echo json_encode($items); ?>,
    strings: <?php echo json_encode(array("Request failed" => tr("Request failed"))); ?>
};
</script>
<?php load_js("Modules/mymodule/Views/mymodule.js"); ?>
```

`mymodule.js`:

```js
Vue.createApp({
    data: function() {
        return { items: mymodule_init.items };
    },
    methods: {
        remove: function(item) {
            fetch(path + "mymodule/delete.json", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id=" + encodeURIComponent(item.id)
            }).then((response) => response.json()).then((result) => {
                if (!result.success) return alert(result.message);
                this.items = this.items.filter((i) => i.id !== item.id);
            }).catch(() => alert(mymodule_init.strings["Request failed"]));
        }
    }
}).mount("#mymodule-app");
```

Rules for views:

- **One owner per page.** Mount Vue on the page root, with the page's modals inside it. Do not change the DOM inside the Vue root with jQuery. Pages without Vue may use jQuery.
- **PHP output.** Escape text with `htmlspecialchars()`. Pass data, and the translated strings that JS needs, with `json_encode()`. Do not add `JSON_UNESCAPED_SLASHES` in a script block, as escaped slashes stop a `</script>` in the data from ending the block. Do not build JS code or Vue attributes in PHP strings.
- **JS in `.js` files.** Only the data block is inline. Inline scripts are not linted.
- **State in Vue data.** Do not keep state in DOM elements or in globals.
- **Requests.** Use `fetch`. Do not use synchronous requests. Check `success` in the response and show `message` on failure.
- **Writes.** Send writes as POST, see [Controller](#controller).
- **Text from the server or the user.** Show it with `{{ }}`. Do not insert it with `v-html` or jQuery `.html()`.
- **Components.** Use Bootstrap 5 components and the theme classes. Open modals with `bootstrap.Modal`. See the [CSS guide](css-guide.md).
- **Check.** Load the page in a browser and check the console for errors.

`Modules/user/profile/` is an example of a page that follows these rules.

## Menu

`mymodule_menu.php` adds entries to the `$menu` array. Top level sections are `setup`, `app`, `dashboards` and others. `l2` is the second level and `l3` the third:

```php
<?php
global $session;
if ($session['write']) {
    $menu["setup"]["l2"]['mymodule'] = array(
        "name"  => tr("My module"),
        "href"  => "mymodule/view",
        "order" => 10,
        "icon"  => "list"
    );
}
```

See `Modules/feed/feed_menu.php`, and `Modules/admin/admin_menu.php` for a menu with `l3` entries.

## Database

`mymodule_schema.php` defines the tables:

```php
<?php
$schema['mymodule'] = array(
    'id'     => array('type' => 'int', 'Null' => 'NO', 'Key' => 'PRI', 'Extra' => 'auto_increment'),
    'userid' => array('type' => 'int'),
    'name'   => array('type' => 'varchar(64)')
);
```

After installing a module or changing its schema, run **Setup > Admin > Update > Update Database** and apply the changes, or run `./scripts/emoncms-cli admin:dbupdate`. See `Modules/schedule/schedule_schema.php` and `Modules/user/user_schema.php`.

## Translations

Wrap text in `tr()`:

```php
echo tr("My module");
```

Add translations to `Modules/mymodule/locale/<lang>.json`, for example `de_DE.json`:

```json
{
    "My module": "Mein Modul"
}
```

The controller loads the module's locale files. For strings shared with a JavaScript file, load them with a context and use `ctx_tr()`:

```php
load_language_files("Modules/mymodule/locale", "mymodule_messages");
echo ctx_tr("mymodule_messages", "My module");
```

See [Translation](../../../scripts/translation/readme.md).

## Logging

```php
$log = new EmonLogger(__FILE__);
$log->info("Started");
$log->warn("Something went wrong");
```

Logs go to `emoncms.log` in `$settings['log']['location']`, by default `/var/log/emoncms`.

## Settings

Module settings go in `settings.ini`, under a section for the module. `process_settings.php` merges `settings.ini` over `default-settings.php`. Read them from the global `$settings`.

## Examples

- `Modules/schedule`: small core module with a model, Vue view, menu, schema and API.
- `Modules/feed/feed_model.php` and `Modules/input/input_model.php`: larger models using MySQL and Redis.
