    <div v-if="new_config_format">

        <div class="panel">
            <div class="panel-header panel-header-static">
                <span class="panel-accent"></span>
                <span class="panel-name"><?php echo _('Device'); ?></span>
            </div>
            <table>
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
                        <td class="col-primary">{{ device.hardware }}</td>
                        <td>{{ device.firmware }}</td>
                        <td>{{ device.firmware_version }}</td>
                        <td>{{ device.voltage }}</td>
                        <td>{{ device.emon_library }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <div class="panel-header panel-header-static">
                <span class="panel-accent"></span>
                <span class="panel-name"><?php echo _('Calibration'); ?></span>
            </div>

            <div class="panel-controls" v-if="device.hardware!='emonPi3'">
                <div class="input-group">
                    <span class="input-group-text"><?php echo _('Voltage calibration'); ?></span>
                    <input type="text" class="form-control input-75" v-model="device.vcal" @change="set_vcal" :disabled="!connected" />
                    <span class="input-group-text">%</span>
                </div>
            </div>

            <!-- Multi voltage calibration for emonPi3 -->
            <table v-if="device.hardware=='emonPi3'">
                <thead>
                    <tr>
                        <th><?php echo _('Active'); ?></th>
                        <th><?php echo _('Channel'); ?></th>
                        <th><?php echo _('Calibration'); ?></th>
                        <th>Phase Correction</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(vchannel,index) in device.vchannels" :key="index" :style="!vchannel.active ? { opacity: '0.45' } : {}">
                        <td><input type="checkbox" v-model="vchannel.active" :disabled="!connected" @change="set_vchannel(index)" /></td>
                        <td>V{{ index+1 }}</td>
                        <td>
                            <div class="input-group">
                                <input type="text" class="form-control input-75" v-model="vchannel.vcal" :disabled="!connected || !vchannel.active" @change="set_vchannel(index)" />
                                <span class="input-group-text">%</span>
                            </div>
                        </td>
                        <td><input type="text" class="form-control input-75" v-model="vchannel.vlead" :disabled="!connected || !vchannel.active" @change="set_vchannel(index)" /></td>
                    </tr>
                </tbody>
            </table>

            <!-- Current calibration table -->
            <table>
                <thead>
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
                </thead>
                <tbody>
                    <tr v-for="(channel,index) in device.ichannels" :key="index" :style="device.hardware=='emonPi3' && !channel.active ? { opacity: '0.45' } : {}">
                        <td v-if="device.hardware=='emonPi3'">
                            <input type="checkbox" v-model="channel.active" :disabled="!connected" @change="set_ical(index)" />
                        </td>
                        <td>CT {{ index+1 }}</td>
                        <td>
                            <select class="form-select input-105" v-model="channel.ical" @change="set_ical(index)" :disabled="!connected || (device.hardware=='emonPi3' && !channel.active)">
                                <option v-for="rating in cts_available" :key="rating" v-bind:value="rating">{{ rating }}A</option>
                            </select>
                        </td>
                        <td><input type="text" class="form-control input-75" v-model="channel.ilead" @change="set_ical(index)" :disabled="!connected || (device.hardware=='emonPi3' && !channel.active)" /></td>
                        <td v-if="device.hardware=='emonPi3'">
                            <select class="form-select input-75" v-model="channel.vchan1" :disabled="!connected || !channel.active" @change="set_ical(index)">
                                <option v-for="vchan in [1,2,3]" :key="vchan" v-bind:value="vchan">{{ vchan }}</option>
                            </select>
                        </td>
                        <td v-if="device.hardware=='emonPi3'">
                            <select class="form-select input-75" v-model="channel.vchan2" :disabled="!connected || !channel.active" @change="set_ical(index)">
                                <option v-for="vchan in [1,2,3]" :key="vchan" v-bind:value="vchan">{{ vchan }}</option>
                            </select>
                        </td>
                        <td>{{ channel.power }}</td>
                        <td>{{ channel.energy }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <div class="panel-header panel-header-static">
                <span class="panel-accent"></span>
                <span class="panel-name"><?php echo _('Radio, pulse and output'); ?></span>
            </div>
            <div class="panel-controls" v-if="device.hardware!='emonPi2'">
                <label class="d-inline-block"><input type="checkbox" v-model="device.RF" @change="set_radio" :disabled="!connected"> <?php echo _('Radio enabled'); ?></label>
            </div>
            <table v-if="device.hardware!='emonPi2' && device.RF">
                <thead>
                    <tr>
                        <th><?php echo _('Node ID'); ?></th>
                        <th><?php echo _('Group'); ?></th>
                        <th><?php echo _('Frequency'); ?></th>
                        <th><?php echo _('Format'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" class="form-control input-105" v-model="device.rfNode" @change="set_rfNode" :disabled="!connected" /></td>
                        <td><input type="text" class="form-control input-105" v-model="device.rfGroup" @change="set_rfGroup" :disabled="!connected" /></td>
                        <td><select class="form-select input-105" v-model="device.rfBand" @change="set_rfBand" :disabled="!connected">
                                <option value="0">433 MHz</option>
                                <option value="3">433.92 MHz</option>
                                <option value="1">868 Mhz</option>
                                <option value="2">915 MHz</option>
                            </select></td>
                        <td>{{ device.rfFormat }}</td>
                    </tr>
                </tbody>
            </table>
            <table>
                <thead>
                    <tr>
                        <th><?php echo _('Pulse enabled'); ?></th>
                        <th><?php echo _('Pulse period'); ?></th>
                        <th><?php echo _('Datalog'); ?></th>
                        <th><?php echo _('Serial format'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="checkbox" v-model="device.pulse" :disabled="!connected" @change="set_pulse" /></td>
                        <td><input type="text" class="form-control input-105" v-model="device.pulsePeriod" :disabled="!connected" @change="set_pulsePeriod" /></td>
                        <td><input type="text" class="form-control input-105" v-model="device.datalog" :disabled="!connected" @change="set_datalog" /></td>
                        <td><select class="form-select input-220" v-model="device.json" :disabled="!connected" @change="set_json">
                                <option value=0><?php echo _('Simple key:value pairs'); ?></option>
                                <option value=1><?php echo _('Full JSON'); ?></option>
                            </select></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="admin-buttons mb-3">
            <button v-if="changes" class="btn btn-primary" :disabled="!changes" @click="save"><?php echo _('Save changes'); ?></button>
            <button class="btn btn-danger ms-auto" @click="zero_energy_values" :disabled="!connected"><?php echo _('Zero energy values'); ?></button>
            <button class="btn btn-danger" @click="reset_to_defaults" :disabled="!connected"><?php echo _('Reset to default values'); ?></button>
        </div>
    </div>

    <div v-if="!config_received" class="alert alert-info"><?php echo _('Waiting for configuration from device...'); ?></div>

    <div class="alert alert-danger" v-if="upgrade_required"><?php echo _('<b>Firmware update required:</b> Looks like you are running an older firmware version on this device, please upgrade the device firmware to use this tool.<br><br>Alternatively, enter commands manually to configure, send command ? to list configuration commands and options.'); ?></div>

    <div class="input-group mb-2">
        <span class="input-group-text"><?php echo _('Console'); ?></span>
        <input class="form-control input-220" v-model="input" type="text" :disabled="!connected" />
        <button class="btn btn-default" @click="send_cmd" :disabled="!connected"><?php echo _('Send'); ?></button>
    </div>
