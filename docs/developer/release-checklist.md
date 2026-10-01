# Release checklist

Steps for releasing Emoncms core and the modules maintained by OpenEnergyMonitor. `master` holds development. `stable` is the release branch that emonPi and emonBase systems update from.

## Code

- [ ] Every repo in the release is on `master` with all changes committed and pushed. Several `Modules/*` folders are separate repos.
- [ ] `version.json` in core has the new version.
- [ ] `module.json` in each changed module has the new version.
- [ ] Modules that need the new core say so in their release notes.

## Database

- [ ] Schema changes are in `*_schema.php`.
- [ ] **Update Database** on an existing install lists the expected changes and applies them.
- [ ] A fresh install creates all tables.

## Test

- [ ] **Full Update** on an emonPi or emonBase from the previous `stable` completes, and the services restart: `emonhub`, `emoncms_mqtt`, `feedwriter`, `service-runner`.
- [ ] Inputs update, feeds record, graphs, apps and dashboards load.
- [ ] Backup and restore work.

## Documentation

- [ ] User guide pages match changed screens and labels. See [STYLE.md](STYLE.md).
- [ ] Screenshots are captured again for changed screens. See [scripts/docs-screenshots](../../scripts/docs-screenshots/README.md):
  1. Refresh the demo data.
  2. `node setup-account.mjs`
  3. `node capture.mjs`
  4. Check the images and commit them.
- [ ] The user guide builds without warnings in the docs site repo.
- [ ] Release notes for module authors are in `docs/developer/notes/` if the release changes APIs, markup or CSS.

## Release

- [ ] Merge `master` into `stable` in core and each module.
- [ ] Tag the version, for example `12.0.0`.
- [ ] Publish a GitHub release with the change log.
- [ ] Update the docs site so it builds from the new user guide.
- [ ] Post on the community forum.
