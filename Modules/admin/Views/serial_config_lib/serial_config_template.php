    <div v-if="new_config_format">

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th><?php echo _('Hardware'); ?></th>
                    <th><?php echo _('Firmware'); ?></th>
                    <th><?php echo _('Version'); ?></th>
                    <th><?php echo _('Voltage'); ?></th>
                    <th><?php echo _('Emon Library'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ device.hardware }}</td>
                    <td>{{ device.firmware }}</td>
                    <td>{{ device.firmware_version }}</td>
                    <td>{{ device.voltage }}</td>
                    <td>{{ device.emon_library }}</td>
                </tr>
            </tbody>
        </table>

        <div class="input-prepend input-append" v-if="device.hardware!='emonPi3'">
            <span class="add-on"><?php echo _('Voltage calibration'); ?></span>
            <input type="text" v-model="device.vcal" style="width:60px" @change="set_vcal" :disabled="!connected" />
            <span class="add-on">%</span>
        </div>

        <!-- Multi voltage calibration for emonPi3 -->
        <h4 v-if="device.hardware=='emonPi3'"><?php echo _('Voltage channels'); ?></h4>
        <table class="table" v-if="device.hardware=='emonPi3'">
            <tr>
                <th><?php echo _('Active'); ?></th>
                <th><?php echo _('Channel'); ?></th>
                <th><?php echo _('Calibration'); ?></th>
                <th>Phase Correction</th>
            </tr>
            <tr v-for="(vchannel,index) in device.vchannels"
 :style="!vchannel.active ? { opacity: '0.45' } : {}">
                <td>
                    <input type="checkbox" v-model="vchannel.active" :disabled="!connected" @change="set_vchannel(index)" />
                </td>
                <td>V{{ index+1 }}</td>
                <td>
                    <div class="input-append">
                        <input type="text" v-model="vchannel.vcal" style="width:60px" :disabled="!connected || !vchannel.active" @change="set_vchannel(index)" />
                        <span class="add-on">%</span>
                    </div>  
                </td>
                <td>
                    <input type="text" v-model="vchannel.vlead" style="width:50px" :disabled="!connected || !vchannel.active" @change="set_vchannel(index)" />
                </td>
            </tr>
        </table>

        <!-- Current calibration table -->
        <h4><?php echo _('Current channels'); ?></h4>
        <table class="table">
            <tr>
                <th v-if="device.hardware=='emonPi3'">Active</th>
                <th><?php echo _('Channel'); ?></th>
                <th><?php echo _('CT Type'); ?></th>
                <th><?php echo _('Phase Correction'); ?></th>
                <th v-if="device.hardware=='emonPi3'">V Chan 1</th>
                <th v-if="device.hardware=='emonPi3'">V Chan 2</th>
                <th><?php echo _('Power'); ?></th>
                <th><?php echo _('Energy'); ?></th>
            </tr>
            <tr v-for="(channel,index) in device.ichannels"
 :style="device.hardware=='emonPi3' && !channel.active ? { opacity: '0.45' } : {}">
                <td v-if="device.hardware=='emonPi3'">
                    <input type="checkbox" v-model="channel.active" :disabled="!connected" @change="set_ical(index)" />
                </td>
                <td>CT {{ index+1 }}</td>
                <td>
                    <select style="width:80px" v-model="channel.ical" @change="set_ical(index)" :disabled="!connected || (device.hardware=='emonPi3' && !channel.active)">
                        <option v-for="rating in cts_available" v-bind:value="rating">{{ rating }}A</option>
                    </select>
                </td>
                <td><input type="text" v-model="channel.ilead" @change="set_ical(index)" style="width:50px" :disabled="!connected || (device.hardware=='emonPi3' && !channel.active)" /></td>
                <td v-if="device.hardware=='emonPi3'">
                    <select style="width:80px" v-model="channel.vchan1" :disabled="!connected || !channel.active" @change="set_ical(index)">
                        <option v-for="vchan in [1,2,3]" v-bind:value="vchan">{{ vchan }}</option>
                    </select>
                </td>
                <td v-if="device.hardware=='emonPi3'">
                    <select style="width:80px" v-model="channel.vchan2" :disabled="!connected || !channel.active" @change="set_ical(index)">
                        <option v-for="vchan in [1,2,3]" v-bind:value="vchan">{{ vchan }}</option>
                    </select>
                </td>
                <td>{{ channel.power }}</td>
                <td>{{ channel.energy }}</td>
            </tr>
        </table>

        <div class="input-prepend input-append" v-if="device.hardware!='emonPi2'">
            <span class="add-on"><?php echo _('Radio enabled'); ?></span>
            <span class="add-on"><input type="checkbox" style="margin-top:2px" v-model="device.RF" @change="set_radio" :disabled="!connected"></span>
        </div><br>

        <table class="table table-bordered">
            <tr v-if="device.hardware!='emonPi2' && device.RF">
                <th><?php echo _('Node ID'); ?></th>
                <th><?php echo _('Group'); ?></th>
                <th><?php echo _('Frequency'); ?></th>
                <th><?php echo _('Format'); ?></th>
            </tr>
            <tr v-if="device.hardware!='emonPi2' && device.RF">
                <td><input type="text" v-model="device.rfNode" style="width:80px; margin:0" @change="set_rfNode" :disabled="!connected" /></td>
                <td><input type="text" v-model="device.rfGroup" style="width:80px; margin:0" @change="set_rfGroup" :disabled="!connected" /></td>
                <td><select style="width:100px; margin:0" v-model="device.rfBand" @change="set_rfBand" :disabled="!connected">
                        <option value="0">433 MHz</option>
                        <option value="3">433.92 MHz</option>
                        <option value="1">868 Mhz</option>
                        <option value="2">915 MHz</option>
                    </select></td>
                <td>{{ device.rfFormat }}</td>
            </tr>

            <tr>
                <th><?php echo _('Pulse enabled'); ?></th>
                <th><?php echo _('Pulse period'); ?></th>
                <th><?php echo _('Datalog'); ?></th>
                <th><?php echo _('Serial format'); ?></th>
            </tr>
            <tr>
                <td><input type="checkbox" v-model="device.pulse" style="width:80px" :disabled="!connected" @change="set_pulse" /></td>
                <td><input type="text" v-model="device.pulsePeriod" style="width:80px" :disabled="!connected" @change="set_pulsePeriod" /></td>
                <td><input type="text" v-model="device.datalog" style="width:80px" :disabled="!connected" @change="set_datalog" /></td>
                <td><select v-model="device.json" :disabled="!connected" @change="set_json">
                        <option value=0><?php echo _('Simple key:value pairs'); ?></option>
                        <option value=1><?php echo _('Full JSON'); ?></option>
                    </select></td>
            </tr>
        </table>

        <!-- reset to default values -->
        <button class="btn btn-primary" @click="reset_to_defaults" :disabled="!connected" style="float:right; margin-left:10px"><?php echo _('Reset to default values'); ?></button>
        <!-- zero energy values -->
        <button class="btn btn-info" @click="zero_energy_values" :disabled="!connected" style="float:right"><?php echo _('Zero energy values'); ?></button>
        <button v-if="changes" class="btn btn-warning" :disabled="!changes" @click="save"><?php echo _('Save changes'); ?></button>

        <br><br>
    </div>

    <div v-if="!config_received" class="alert alert-info"><?php echo _('Waiting for configuration from device...'); ?></div>

    <div class="alert alert-danger" v-if="upgrade_required"><?php echo _('<b>Firmware update required:</b> Looks like you are running an older firmware version on this device, please upgrade the device firmware to use this tool.<br><br>Alternatively, enter commands manually to configure, send command ? to list configuration commands and options.'); ?></div>

    <div class="input-prepend input-append">
        <span class="add-on"><b><?php echo _('Console'); ?></b></span>
        <input v-model="input" type="text" :disabled="!connected" />
        <button class="btn" @click="send_cmd" :disabled="!connected"><?php echo _('Send'); ?></button>
    </div>
