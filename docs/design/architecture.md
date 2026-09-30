# Architecture

Emoncms is a PHP web application with a front controller, modules in a model-view-controller layout, and background services for MQTT input and feed writing.

The server side is PHP. Controllers define the HTTP API and load views. Models are classes that handle storage and logic. Views are HTML pages that use jQuery and Vue 3 and call the API in JSON. Devices posting data use the same API.

## Directory structure

```
/var/www/emoncms/
    .htaccess              rewrites requests to index.php
    index.php              front controller
    core.php               shared functions: controller(), view(), get(), load_js() ...
    route.php              Route class, decodes the request path
    param.php              request parameters, including encrypted input
    locale.php             language selection
    process_settings.php   loads settings.ini over default-settings.php
    Modules/
        feed/
            module.json
            feed_controller.php
            feed_model.php
            feed_menu.php
            feed_schema.php
            engine/        storage engines
            Views/
            locale/
        input/ ...
    Lib/
        bootstrap5/        Bootstrap 5
        js/                jQuery, Vue, Flot and shared scripts
        EmonLogger.php
        dbschemasetup.php
    Theme/
        theme.php          page layout and menus
        embed.php          layout for embedded pages
        css/ js/ menu/
    scripts/
        services/          emoncms_mqtt, feedwriter, service-runner
```

Core modules are `admin`, `api`, `feed`, `input`, `process`, `schedule` and `user`. Other modules, such as graph, dashboard and app, are separate repositories cloned into `Modules/`, or into `/opt/emoncms/modules` and symlinked.

## Request flow

### Rewrite

`.htaccess` rewrites a request path to `index.php?q=path`, unless it is an existing file or under `Lib`, `Modules`, `Theme`, `scripts` or `docs`. The `B` flag escapes the path before it goes into `q`.

```
emoncms/feed/list.json  ->  emoncms/index.php?q=feed/list.json
```

### index.php

`index.php` runs these steps, each marked with a comment in the file:

1. Load settings and core scripts.
2. Connect to MySQL and, if enabled, Redis.
3. Start the session: web login, API key, device key or encrypted input.
4. Set the language.
5. Decode the route and load the module controller.
6. If no controller matches, try the path as a public username, then public dashboards and apps.
7. Output the result.

### Route

`Route` in `route.php` splits the path:

```
controller/action/subaction/subaction2.format
```

`format` is `html` by default. `json`, `text`, `md` and `csv` are also supported. See [Global variables](global-variables.md#route).

### Controller

`controller()` in `core.php` loads `Modules/<controller>/<controller>_controller.php` and the module's translations, then calls the function `<controller>_controller()`. The return value becomes `$output['content']`.

### Output

For `json`, `index.php` sends the content as JSON. For `html`, it builds the menu from each module's `*_menu.php` and wraps the content in `Theme/theme.php`, or `Theme/embed.php` when `embed=1`. Unknown formats return HTTP 406.

## Modules

A module is a folder with the files for one feature:

- **Controller**: handles requests and access checks, and returns data or a view.
- **Model**: a class for storage and logic. Models can be used by other modules.
- **Views**: HTML with jQuery or Vue 3, styled with Bootstrap 5.
- **Schema**: table definitions in `*_schema.php`. **Update Database** on the **Admin** page, or `scripts/emoncms-cli admin:dbupdate`, applies changes.
- **Menu**: entries in `*_menu.php`.
- **Locale**: translations in `locale/<lang>.json`.

See [Developing a new module](developing-a-new-module.md).

## Data flow

1. Data arrives by HTTP at `input/post` or `input/bulk`, or by MQTT through the `emoncms_mqtt` service.
2. The input model stores the latest value of each input, in Redis when enabled.
3. The input's process list runs. See [Input processing](input-processing.md).
4. Processes that log data write to feeds through a storage engine in `Modules/feed/engine/`.
5. With the low-write setting, writes go to a Redis buffer first. The `feedwriter` service writes them to disk at an interval.

## Storage engines

| Engine | Use |
|---|---|
| PHPFina | Fixed interval time series. See [Fixed interval](../timeseries/Fixed-interval.md) |
| PHPTimeSeries | Variable interval time series. See [Variable interval](../timeseries/Variable-interval.md) |
| VirtualFeed | Values calculated from other feeds when read |
| RedisBuffer | Buffers writes for `feedwriter` on low-write systems |
| MysqlTimeSeries, MysqlMemory | MySQL storage, hidden by default |

## Background services

| Service | Role |
|---|---|
| `emoncms_mqtt` | Subscribes to MQTT and posts values as inputs |
| `feedwriter` | Writes buffered feed data to disk |
| `service-runner` | Runs allowed scripts requested by the web interface, such as updates and backups |

Service scripts and install notes are in `scripts/services/`.

## Settings

`process_settings.php` merges `settings.ini` (or `settings.php`) over `default-settings.php`. See `example.settings.ini`.
