<?php

    /*
      0 - realtime or daily
      1 - realtime
      2 - daily
      3 - histogram
      4 - boolean (not used uncomment line 122)
      5 - text
      6 - float value
      7 - int value
    */

    $visualisations = array(
    
        'realtime' => array('options'=>array(
            array('feedid',1))
        ),
        
        // Hex colour EDC240 is the default color for flot. since we want existing setups to not change, we set the default value to it manually now,
        'rawdata'=> array('options'=>array(
            array('feedid',1),
            array('fill',7,0),
            array('colour',5,'EDC240'),
            array('units',5,'W'),
            array('dp',7,'1'),
            array('scale',6,'1'))
        ),
        
        'bargraph'=> array('options'=>array(
            array('feedid',0),
            array('colour',5,'EDC240'),
            array('interval',7,'86400'),
            array('units',5,''),
            array('dp',7,'1'),
            array('scale',6,'1'),
            array('delta',7,'0'))
        ),
        
        'multigraph' => array ('action'=>'multigraph', 'options'=>array(array('mid',7)) ),
        
        'graph'=> array('options'=>array(
            array('feedid',1)
        ))
    );
