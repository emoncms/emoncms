# AI assistants

An AI assistant, such as Claude or ChatGPT, can work with your Emoncms data through the API. It can analyse your energy use, write scripts that send data, and help with setup and troubleshooting.

## What an assistant can do

- **Analyse data**: daily and monthly totals, heat pump COP, solar self-consumption, costs on a tariff, unusual use.
- **Write scripts**: send readings from a meter, inverter or sensor to Emoncms, or export data to another tool.
- **Help with setup**: suggest input processing, feed names and intervals for your hardware.
- **Troubleshoot**: read your emonHub and Emoncms logs and suggest causes.
- **Build**: write a custom dashboard page or app from the API.

## Point the assistant at the API reference

Every Emoncms install serves its API reference in a form assistants can read:

| URL | Contents |
|---|---|
| `/llms.txt` | Short index with links |
| `/llms-full.txt` | Full API reference as Markdown |
| `/api.md` | Same reference as `/llms-full.txt` |
| `/api.json` | Same reference as JSON |

For example, `http://emonpi.local/llms-full.txt` on an emonPi. Give the assistant this link, or paste the file into the chat, before asking it to write code.

## Give access to your data

Use the **Read Only API Key** from **My Account**. It can read feeds but cannot change or delete anything.

Useful requests:

```
http://emonpi.local/feed/list.json?apikey=READKEY
http://emonpi.local/feed/data.json?id=1&start=-1 week&end=now&interval=3600&average=1&apikey=READKEY
http://emonpi.local/feed/data.json?id=2&start=-1 year&end=now&interval=daily&delta=1&apikey=READKEY
```

The second request returns hourly averages for the last week. The third returns daily kWh for the last year from a cumulative kWh feed. Times are in milliseconds. Add `&timeformat=unix` for seconds.

An assistant that runs code, such as a coding agent on your computer, can call the API directly. A chat assistant usually cannot reach a local emonPi. Export CSV instead and upload the file. See [Export CSV](export-csv.md).

```{warning}
Only give the **Read & Write API Key** to a script or assistant that needs to post data. It can change and delete your data. If a key is shared by mistake, click **Generate New** on the **My Account** page.
```

Data you share with an online assistant leaves your home. Share only what you are happy to send, or use an assistant that runs locally.

## Example requests

- "Here is the Emoncms API reference and my read API key. List my feeds and show my daily electricity use for September as a table."
- "Using `heatpump_elec_kwh` and `heatpump_heat_kwh`, calculate the heat pump COP for each month this year."
- "Write a Python script that reads my inverter every 10 seconds and posts the values to Emoncms with `input/post`."
- "Here is my emonhub.log. Why are no inputs appearing in Emoncms?"

Check the results. An assistant can misread a feed name or unit.

## Report problems and contribute

Issues and pull requests written with the help of an AI agent are welcome. Please:

- Say that an agent was used.
- Check the issue yourself before posting. Include your Emoncms version and the steps to reproduce.
- Keep pull requests small, and test them on a running system.

Report security problems privately by email, not as a GitHub issue. See [SECURITY.md](https://github.com/emoncms/emoncms/blob/master/SECURITY.md).

Coding agents working on Emoncms should read [AGENTS.md](https://github.com/emoncms/emoncms/blob/master/AGENTS.md) in the repository.
