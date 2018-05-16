import time
import paho.mqtt.client as mqtt

userid = 2
mqtt_user = "test"
mqtt_passwd = "password"
mqtt_host = "localhost"
mqtt_port = 1883

basetopic = "user/"+str(userid)
           
def on_connect(client, userdata, flags, rc):
    # Initialisation string
    mqttc.subscribe(basetopic+"/#")
    pass

def on_message(client, userdata, msg):
    print msg.topic+": "+msg.payload

mqttc = mqtt.Client("smartplug")
mqttc.on_connect = on_connect
mqttc.on_message = on_message

# Connect
try:
    mqttc.username_pw_set(mqtt_user, mqtt_passwd)
    mqttc.connect(mqtt_host, mqtt_port, 60)
    mqttc.loop_start()
except Exception:
    print "Could not connect to MQTT"
else:
    print "Connected to MQTT"

time.sleep(1)

# Loop
while 1:
    topic = basetopic+"/power"
    value = 150

    print "publish: "+topic+":"+str(value)
    mqttc.publish(topic,value,2)
    time.sleep(5)

# Close
mqttc.loop_stop()
mqttc.disconnect()
