# Devices

A device groups the inputs of one node. A device template sets up the inputs, input processing and feeds for a known type of hardware in one step.

Open the page from **Setup > Devices**. The device module is installed on the emonPi and emonBase.

## Set up a device from a template

1. On the **Inputs** page, click the cog icon on the node header. Or, on the **Devices** page, click **New device**.
2. In the **Configure Device** dialog, choose a template from the list on the left. Templates are grouped by maker and type, for example OpenEnergyMonitor > emonTx4.
3. Check **Node** and **Name**. **Location** is optional.
4. Click **Save & Initialize**.
5. The **Initialize device** dialog lists the inputs and feeds the template creates. Each item is tagged:
   - **Create** or **Set**: new.
   - **Override**: replaces an existing process list.
   - **Exists**: already set up, left unchanged.
6. Untick any items you do not want and confirm.

Processes that depend on an unticked input or feed are skipped.

Run **Initialize** again later to create only the missing inputs and feeds.

## Devices page

The **Devices** page lists each device with **Node**, **Name**, **Type**, **IP**, **Device key** and **Updated**. Use **Configure** to change the template and **Delete** to remove a device.

Deleting a device does not delete its inputs or feeds.

## Device key

A device key lets a device post data to its own node only, in place of the account API key. Generate one with **New** next to **Device Key** in the **Configure Device** dialog. Show it with the lock icon on the **Inputs** page.

## Create a template

To save your own setup as a template, open **Configure Device** and click **Generate template from existing inputs, input processing and feeds**. Templates are JSON files in the device module. See [emoncms/device](https://github.com/emoncms/device).
