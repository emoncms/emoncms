# Admin

The **Admin** pages show system status, run updates and manage users. They are available to the admin user only. Open them from **Setup > Admin**.

Some features work only on an emonPi, emonBase or other emonSD install: **Pi Control**, **Full Update**, firmware update, **Serial Config** and **Components**.

## System Info

**System Info** shows the state of the system:

- **Services**: status of `emonhub`, `emoncms_mqtt`, `feedwriter`, `service-runner`, `redis-server`, `mosquitto` and other services. Each can be started, stopped or restarted.
- **Emoncms**: version, installed components, and feed points waiting to be written.
- **Server**, **Memory**, **Disk**: system load, memory and free space on each partition.
- **HTTP**, **MySQL**, **Redis**, **MQTT Server**, **PHP**: versions and status.
- **Pi**: model, CPU temperature and emonSD version.
- **Pi Control**: **Reboot** and **Shutdown**. After shutdown, wait 30 seconds before disconnecting the power.

When you ask for help on the forum, click **Copy as Markdown** and paste the result into your post.

## Update

**Full Update** updates the operating system packages, emonHub, Emoncms and its modules. It does not update firmware. See [Update](update.md).

**Update Database** checks the database after a manual update or a new module, and lists any changes to apply.

**Update Firmware Only** flashes firmware to the emonPi, emonTx or other hardware. Choose the serial port, hardware and radio format, then **Update Firmware**. Or upload a custom firmware file.

The **Update Log** shows progress.

## Components

**Components** lists Emoncms and each module with its version and branch. Update one component, update all, or switch branch.

## Serial Config

**Serial Config** opens a serial console to hardware such as the emonTx4. emonHub must be stopped while it is in use. Click **Stop EmonHub** first and **Start EmonHub** when you finish.

## Emoncms Log

**Emoncms Log** shows recent log entries. Use **Download Log** to save it.

## Users

**Users** lists all accounts, with the number of feeds for each. Click **Add new user** to create an account. Click **View** to switch to a user's account.
