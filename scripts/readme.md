# feedwriter.php
'Low write' service will write feed data to engine in batch at certain times


# phpmqtt_input.php
MQTT input interface script, subscribes to topic emoncms/input with the format: 
    Topics:                     Value:
    emoncms/input/10            100,200,300
    emoncms/input/10/1          100
    emoncms/input/10/power      250
    emoncms/input/house/power   2500
Must set user id on the file $mqttsettings variable.


# input_queue_processor.php
To be used in association with que_input_controller.php see Modules > Input > readme.md