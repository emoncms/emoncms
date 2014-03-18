# que_input_controller.php

You may want to use this controller instead of the default controller in very high post rate, throughput applications.

With this redis based input que post rate from a sequential free running post benchmark went from around 97 req/s up to 138 req/s on 4 core 2.0Ghz 8GB machine.

To use it just switch the controller's around. Rename que_input_controller.php to input_controller.php 

You will also need to use the worker process which works through the queue.
Which can be found in the scripts directory in the root emoncms folder.
