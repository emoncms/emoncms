# Glossary

| Term | Meaning |
|---|---|
| API key | A key that lets a script or device read or write data without a password. Each account has a read only key and a read and write key. See [My Account](account.md). |
| App | A ready-made dashboard for one application, such as solar or a heat pump. See [Apps](apps.md). |
| Cumulative kWh feed | A feed of total energy that only rises, like a meter reading. Daily kWh is the difference between the values at the start and end of each day. See [Daily kWh](daily-kwh.md). |
| Dashboard | A page you build from widgets and charts. See [Dashboards](dashboards.md). |
| Device | A group of inputs from one node, set up from a template. See [Devices](devices.md). |
| Device key | A key that lets one device post data to its own node only. See [Devices](devices.md#device-key). |
| emonHub | Software on the emonPi and emonBase that reads data from the hardware and passes it to Emoncms. |
| emonSD | The software image for the emonPi and emonBase. |
| Engine | The storage format of a feed: fixed interval, variable interval or virtual. See [Feeds](feeds.md). |
| Feed | A stored time series of values. See [Feeds](feeds.md). |
| Input | The latest value received for one node and key. See [Inputs](inputs.md). |
| Input processing | A list of steps that runs on each new input value, such as scaling it or logging it to a feed. See [Inputs](inputs.md). |
| Interval | The time between values in a fixed interval feed, for example 10 seconds. |
| Key | The name of one value from a node, for example `P1` or `temperature`. |
| MQTT | A messaging protocol used to pass data between emonHub, Emoncms and other software. See [MQTT](postingdata.md#mqtt). |
| Node | The name of a device or source that sends data, for example `emontx4`. Inputs are grouped by node. |
| Post process | Processing applied to recorded feed data. See [Post process](postprocess.md). |
| Public feed | A feed anyone can read without logging in. |
| Schedule | A set of time windows used by input processing, for example for tariff periods. See [Schedules](schedule.md). |
| Tag | A label that groups feeds on the **Feeds** page. Often the node name. |
| Virtual feed | A feed that stores nothing and calculates values from other feeds when read. See [Virtual feeds](virtual-feeds.md). |
