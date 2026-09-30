# Apps

Apps are ready-made dashboards for common applications. Each app needs a small number of feeds and does its own calculations, such as daily totals, self-consumption and COP.

The two main apps are **My Electric Flow** for electricity use, solar and batteries, and **My Heatpump** for heat pump performance.

## Add an app

1. Click **Apps > New** in the menu to open the **Available Apps** page.
2. Click an app. **Featured apps** are listed first.
3. Enter a name and click **Create**.
4. The app opens on its configuration page. Feeds with the default names are selected for you. Select any feeds that are not found and click **Launch app**.

To change the configuration later, click the spanner icon in the app.

```{tip}
Use the default feed names when you set up input processing, for example `use`, `solar` and `heatpump_elec`. Apps then find the feeds without manual selection.
```

## My Electric Flow

My Electric Flow shows how electricity moves between solar, battery, grid and home. It covers what the archived My Electric, My Solar and My Solar Battery apps did.

It shows:

- Live power for use, grid, solar and battery, with battery state of charge and time left.
- A power graph and a daily kWh bar graph.
- An energy flow breakdown for the selected period: solar to home, solar to grid, solar to battery, battery to home, grid to battery and grid to home. Self-consumption and self-sufficiency are shown as percentages.
- A **Tariff explorer** that costs each flow on a chosen tariff, including Octopus Agile and other UK time of use tariffs.

### Feeds

Tick **Has solar PV** and **Has battery** to match your system. Then select the power feeds in watts.

| Setting | Default name | Description |
|---|---|---|
| use | `use` | House or building use |
| solar | `solar` | Solar generation |
| battery | `battery_power` | Battery power, positive for discharge and negative for charge |
| grid | `grid` | Grid power, positive for import and negative for export |
| battery_soc | `battery_soc` | Battery state of charge in %, optional |

The app calculates one missing feed from the others. You need:

| System | Minimum feeds |
|---|---|
| Solar and battery | 3 of use, solar, battery, grid |
| Solar only | 2 of use, solar, grid |
| Battery only | 2 of use, battery, grid |
| Consumption only | use or grid |

### Flow history

The daily view and the tariff explorer use cumulative kWh feeds for each energy flow, for example `solar_to_load_kwh`. The app creates these feeds and fills them from your power feeds with the `solarbatterykwh` post processor. The app updates them each time it opens. The feeds are created under the node `solar_battery_kwh_flows`.

The post process module must be installed. It is included on the emonPi and emonBase.

### Other options

- **Show kW**: show power in kW in place of W.
- **Battery Capacity**: capacity in kWh, used for the time left estimate.
- **Flow allocation strategy**: **Solar first** or **Battery first**. Sets which source supplies the home when both are available.
- **Tariff**: region and tariff for the tariff explorer.

## My Heatpump

My Heatpump shows heat pump performance: electricity input, heat output and COP. It is used by many of the systems on [HeatpumpMonitor.org](https://heatpumpmonitor.org).

It shows:

- A daily bar graph of electricity input, heat output and COP. Click a day to open the power view for that day.
- A power view with flow, return, outside and room temperatures, flow rate and instantaneous COP.
- COP and SCOP for the full window, when running, space heating, water heating and cooling.
- Chart options: immersion, flow rate, hot water temperature, defrosts, simulated Carnot heat output, emitter and system volume calculation.

### Feeds

Only `heatpump_elec` and `heatpump_elec_kwh` are required. Add a heat meter to get heat output and COP.

| Setting | Description |
|---|---|
| `heatpump_elec` | Electricity input in watts |
| `heatpump_elec_kwh` | Cumulative electricity input in kWh |
| `heatpump_heat` | Heat output in watts |
| `heatpump_heat_kwh` | Cumulative heat output in kWh |
| `heatpump_flowT` | Flow temperature |
| `heatpump_returnT` | Return temperature |
| `heatpump_outsideT` | Outside temperature |
| `heatpump_roomT` | Room temperature |
| `heatpump_targetT` | Target room or flow temperature |
| `heatpump_flowrate` | Flow rate |
| `heatpump_dhw` | Hot water circuit status, non-zero when running |
| `heatpump_ch` | Central heating circuit status, non-zero when running |
| `heatpump_cooling` | Cooling status, 1 when cooling |
| `heatpump_dhwT` | Hot water temperature |
| `heatpump_dhwTargetT` | Hot water target temperature |
| `immersion_elec` | Immersion heater electricity in watts |
| `boiler_heat` | Boiler heat output in watts, for hybrid systems |

### Other options

- **Starting power**: power in watts above which the heat pump counts as running. Default 150 W.
- **Enable daily pre-processor**: splits daily totals into space heating and water heating.
- **Auto detect cooling**: detects cooling when no cooling status feed is set.
- **Start date**: ignore data before this date in all time totals.

See [Heat pump monitoring](../applications/heatpump.md) for hardware and feed setup.

## Other apps

| App | Use |
|---|---|
| My Boiler | Boiler fuel input, heat output and efficiency |
| My Solar Divert | Solar generation, household use and PV diversion |
| Time of use - flexible | Costs on multiple time of use tariffs |
| Profile | Average daily profiles for each month |
| UK Grid | UK grid fuel mix and wind and solar forecast |
| CO2 Monitor | Room air change rate from CO2 decay |
| Psychrometric Chart | Indoor temperature and humidity against comfort zones |

Older apps are listed under **Archived apps** on the **Available Apps** page. They still work but are no longer developed.

## Source code

The app module is on GitHub: [emoncms/app](https://github.com/emoncms/app).
