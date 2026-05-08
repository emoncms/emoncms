# Tests

Three PHPUnit suites covering different layers of the application.

## Requirements

- PHP 8.x with the `curl` and `mysqli` extensions
- MySQL running with the `emoncms` database set up
- The emoncms app served at `http://localhost/original` (for Feature tests only)
- Composer dependencies installed:
  ```
  php composer.phar install
  ```

## Suites

| Suite | What it tests | Needs DB | Needs HTTP |
|---|---|---|---|
| Unit | Pure logic, input validation, no I/O | No | No |
| Integration | Model methods against the real database | Yes | No |
| Feature | JSON API endpoints over real HTTP | Yes | Yes |

## Running

```bash
# Unit only (fast, no infrastructure required)
php vendor/bin/phpunit --configuration tests/phpunit.xml

# Integration (requires MySQL)
php vendor/bin/phpunit --configuration tests/phpunit.integration.xml

# Feature / API (requires MySQL + running web server)
php vendor/bin/phpunit --configuration tests/phpunit.feature.xml

# Via composer scripts
php composer.phar phpunit
php composer.phar phpunit-integration
php composer.phar phpunit-feature

# All suites in one command
php vendor/bin/phpunit --configuration tests/phpunit.xml && \
php vendor/bin/phpunit --configuration tests/phpunit.integration.xml && \
php vendor/bin/phpunit --configuration tests/phpunit.feature.xml

# Or via composer
php composer.phar phpunit-all
```

## Configuration

**Database** credentials are read from `settings.ini` (`[sql]` section) — no test-specific config needed.

**Feature test base URL** defaults to `http://localhost/emoncms`. Override with an environment variable:
```bash
EMONCMS_BASE_URL=http://myserver/emoncms php vendor/bin/phpunit --configuration tests/phpunit.feature.xml
```

**Rate limiting** must be disabled for the Feature and Integration suites to run reliably. Ensure `settings.ini` contains:
```ini
[interface]
disable_rate_limiting = true
```
This setting defaults to `false` in `default-settings.ini` and should never be enabled in production.

## Test data

Integration and Feature tests create users with the prefix `phpunittest` / `feat` respectively and delete them after each suite runs. The database is left clean after a successful run.