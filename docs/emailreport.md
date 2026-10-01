# Email reports

The email report module sends a weekly summary of home electricity use by email, with solar generation if you have solar. Open it from **Setup > Email Reports**.

## Set up a report

1. Go to **Setup > Email Reports**.
2. Choose the report:
   - **Home Energy Consumption**: electricity use.
   - **Solar PV & Self consumption**: use, solar generation and self-consumption.
3. Tick **Enable weekly email report**.
4. Enter an **Email title**. Use it to tell reports from different accounts apart.
5. Enter up to 5 email addresses.
6. Select the cumulative kWh feeds: consumption, and solar for the solar report. These are the same feeds the apps use. See [Daily kWh](daily-kwh.md).
7. Tick **Show UK energy statistics** to include UK wind, solar and hydro generation for the week.
8. Click **Save**.

Click **Send test email** to check the setup. The **Email preview** panel shows the report.

Reports are sent at about 09:00 UTC each Monday and cover the previous week.

## Requirements

The module is not installed by default on the emonPi and emonBase. To install it, see [emoncms/emailreport](https://github.com/emoncms/emailreport).

Emoncms must be able to send email. Set the `[email]` and `[smtp]` sections in `settings.ini`.
