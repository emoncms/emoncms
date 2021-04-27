# CLI

Bundled with emoncms is a simple CLI tool that right now can be used to update the configured database to ensure the schema is up to date.

## Print usage

Running the CLI without any arguments will print all available commands that can be executed.

```bash
./emoncms-cli
```

## Perform database update

Running this command will run any pending migrations that need to be run. Typically this is done after upgrades that have changes to the database schema.

```bash
./emoncms-cli dbupgrade
```
