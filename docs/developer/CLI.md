# CLI

`scripts/emoncms-cli` is a command line tool for Emoncms. It currently runs database updates.

Run it from the Emoncms root directory.

## Print usage

Run without arguments to list the commands.

```bash
./scripts/emoncms-cli
```

## Perform database update

Apply pending database changes. Run it after an update that changes the database schema. It does the same as **Update Database** on the **Admin** page.

```bash
./scripts/emoncms-cli admin:dbupdate
```
