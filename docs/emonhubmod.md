## Optional Emonhub modifications

1. Modifying emonhub to post to emoncms node module for remote node decoding
2. Modifying emonhub to work with the emoncms packetgen module for sending out control packets over the rfm12/69 network.

### Using Emonhub with the optional emoncms node module

Emonhub has a powerfull node decoder built into it that allows for the decoding of node data with non-default packet structures. This decoder can be configured from emonhub.conf. There are ideas for how it could be possible to set the node decoders in the emonhub remotely but this is still a feature in early development. See emonhub issue 64:

[https://github.com/emonhub/emonhub/issues/64](https://github.com/emonhub/emonhub/issues/64)

In the meantime if you wish to carry out the node decoding within a remote emoncms installation rather than use the standard local emonhub based node decoding a couple of small modifications to emonhub can be made to achieve this:

In emonhub.conf under interfacers -> runtimesettings add the line

    datacode = b

In src/emonhub_reporter.py change [line 275](https://github.com/emonhub/emonhub/blob/development/src/emonhub_reporter.py#L275)

    post_url = self._settings['url']+'/input/bulk'+'.json?apikey='

to

    post_url = self._settings['url']+'/node/multiple'+'.json?apikey='
    
and change [line 289](https://github.com/emonhub/emonhub/blob/development/src/emonhub_reporter.py#L289)

    if reply == 'ok':
    
to

    if reply == 'true':

Restart emonhub to finish:

    sudo service emonhub restart
    
Check that there are no errors in the log:

    tail -f /var/log/emonhub/emonhub.log
    
If the emoncms node module is not present in your emoncms installation (if your using the bufferedwrite branch) then the node module can be installed from git by running in your emoncms/Modules folder:

    git clone https://github.com/emoncms/node.git
    
Complete the node module installation by running db update from within the admin panel of your emoncms account.

### Using Emonhub with the emoncms PacketGen module

The emoncms packetgen module can be used to construct control packets to be send out over the rfm12/69 network. The control packet is a register of properties that any node can pick and choose from. These properties could be room set point temperatures for radiator control nodes to make use of or lighting on/off etc. 

Development of control functionality within emoncms is at an early stage and we are currently discussing the best way to interface emonhub with emoncms for control, see: [https://github.com/emonhub/emonhub/issues/64](https://github.com/emonhub/emonhub/issues/64). The following is a quick modification that can be done to emonhub to get control working using packetgen until a more permanent solution is reached:

Start by installing packetgen by running the following in your emoncms/Modules folder:

    git clone https://github.com/emoncms/packetgen.git
    
Complete the packetgen module installation by running db update from within the admin panel of your emoncms account

We can modify emonhub to poll the packetgen module periodically and send the packetgen packet state over serial to the rfm12/69.

    cd /home/pi/emonhub/src
    
    nano emonhub_interfacer.py
    
Add just below import select [~line 16](https://github.com/emonhub/emonhub/blob/development/src/emonhub_interfacer.py#L16) the line:

    import urllib2
    
Add just below self._interval_timestamp = 0 [~line 50](https://github.com/emonhub/emonhub/blob/development/src/emonhub_interfacer.py#L50) the line:

    self._control_timestamp = time.time() + 30
    
In class EmonHubJeeInterfacer, method run, add just below: now = time.time() [~line 483](https://github.com/emonhub/emonhub/blob/development/src/emonhub_interfacer.py#L483)
Take care to make sure the code is correctly indented as shown below, this is needed for python

    if now - self._control_timestamp > 5:
        self._control_timestamp = now
        packet = urllib2.urlopen("http://localhost/emoncms/packetgen/rfpacket.json?apikey=APIKEY").read()
        packet = packet[1:-1]
        self._log.debug(self.name + " broadcasting control packet " + packet + "s")
        self._ser.write(packet+"s")
        
Set your emoncms location (it can be localhost or a remote server) and apikey in the URL string.

Save and exit [Ctrl + X] then [Enter]
        
Restart emonhub to finish:

    sudo service emonhub restart
    
Check that there are no errors in the log:

    tail -f /var/log/emonhub/emonhub.log
