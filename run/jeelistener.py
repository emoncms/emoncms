#!/usr/bin/python

"""

  This code is released under the GNU Affero General Public License.
  
  OpenEnergyMonitor project:
  http://openenergymonitor.org

"""

import mosquitto, time, json, serial
from configobj import ConfigObj

from tendo import singleton
me = singleton.SingleInstance() # will sys.exit(-1) if other instance is running

# Load configuration file
settings = ConfigObj("/var/www/emoncms/run/jeelistener.conf", file_error=True)

# Connect to serial port
ser = serial.Serial(settings['Serial']['port'], settings['Serial']['baud'])

def on_connect(mosq, obj, rc):
    # nodetx is the topic used for packets to send
    mosq.subscribe("nodetx")

# On receipt of message on nodetx mqtt topic
def on_message(mosq, obj, msg):
    # decode json
    d = json.loads(msg.payload)
    # compile csv,s command string
    txstr = ','.join(map(str, d))+",s"
    # sent the command over the serial port
    ser.write(txstr)
    print txstr
    
def on_readline(line):

    # Get an array out of the space separated string
    received = line.strip().split(' ')

    # Information message
    if ((received[0] == '>') or (received[0] == '->')):
	print "MSG: "+line
        
        sid = received[1][-1:]  # setting id
        val = received[1][:-1]  # setting value
        
        # This part checks if the radio settings are applied correctly
        # it could be extended to correct a misconfigured setting
        
        if sid=='b':
            if settings['Radio']['frequency']==val:
                print "frequency set correctly"
            else:
                print "frequency error "+val
            
        if sid=='g':
            if settings['Radio']['group']==val:
                print "group set correctly"
            else:
                print "group error "+val

        if sid=='i':
            if settings['Radio']['baseid']==val:
                print "baseid set correctly"
            else:
                print "baseid error "+val
                
    # Else, process frame
    else:
        try:
            # Only integers are expected
            received = [int(val) for val in received]
        except Exception:
            # print "Misformed RX frame: " + str(received)
            pass
        else:
        
            # time
            t = int(time.time())
            
            # Get node ID
            node = received[0]
            
            # Recombine transmitted chars into signed int
            values = []
            for i in range(1, len(received),1):
                value = received[i]
                values.append(value)
            
            # Construct json with received data
            jsonstr = json.dumps({'userid': settings['Emoncms']['user'], 'nodeid':node, 'time':t, 'data':values})
            
            # Publish recieved node on MQTT noderx topic 
            # Node processing is then attached to this topic in another process.
            mqttc.publish('noderx',jsonstr)
	    print "DATA: "+jsonstr

# Start MQTT (Mosquitto)
mqttc = mosquitto.Mosquitto('jeelistener')
mqttc.on_message = on_message
mqttc.on_connect = on_connect
mqttc.connect(settings['Mosquitto']['broker'], int(settings['Mosquitto']['port']), 60, True)

# Rather than use serial.readLine we capture the line's manually
# the readLine method waits for \n character which blocks the main loop
# by capturing the serial data manually we can have the loop continue
# with other things until a newline character is found
# linebuff is the buffer string for this
linebuff = ""

# Send out the initial radio seconds 3 seconds after the script starts
# so that the settings are sent after the RFM12Demo header is recieved
# seemed to be more reliable
t = 0
sent_radiosettings = False

# Main loop
while 1:

    # Send the settings 3 seconds after the script starts up
    if t>3.0 and not sent_radiosettings:
        ser.write(settings['Radio']['frequency']+"b")
        ser.write(settings['Radio']['group']+"g")
        ser.write(settings['Radio']['baseid']+"i")
        sent_radiosettings = True

    # A 'non blocking' readline 
    w = ser.inWaiting()
    if w:
        charbuf = ser.read(w)
        for char in charbuf:
            if char=='\n':
                on_readline(linebuff)
                linebuff = ""
            else:
                linebuff += char
    
    # A 'non blocking' call to mqtt loop
    mqttc.loop(0)
    
    # Main loop sleep control
    time.sleep(0.1)
    t += 0.1
