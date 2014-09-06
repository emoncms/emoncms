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
        
        'timestoredaily'=> array('options'=>array(
            array('feedid',1),
            array('units',5,'kWh'))
        ),
        
        'smoothie'=> array('options'=>array(
            array('feedid',1),
            array('ufac',6))
        ),
        
        'histgraph'=> array('options'=>array(
            array('feedid',3),
            array('barwidth',7,50),
            array('start',7,0),
            array('end',7,0))
        ),
        
        //'dailyhistogram'=> array('options'=>array(array('feedid',3))),
        'zoom'=> array('options'=>array(
            array('power',1),
            array('kwhd',2),
            array('currency',5,'&pound;'),
            array('currency_after_val', 7, 0),
            array('pricekwh',6,0.14))
        ),
        
        //'comparison'=> array('options'=>array(array('feedid',3))),
        'stacked'=> array('options'=>array(
            array('bottom',2),
            array('top',2))
        ),
        
        'stackedsolar'=> array('options'=>array(
            array('solar',2),
            array('consumption',2))
        ),
        
        'threshold'=> array('options'=>array(
            array('feedid',3),
            array('thresholdA',6,500),
            array('thresholdB',6,2500))
        ),
        
        'simplezoom'=> array('options'=>array(
            array('power',1),
            array('kwhd',0)
        )),
        
        'orderbars'=> array('options'=>array(
            array('feedid',2)
        )),
        
        'orderthreshold'=> array('options'=>array(
            array('feedid',3),
            array('power',1),
            array('thresholdA',6,500),
            array('thresholdB',6,2500)
        )),
        
        'editrealtime'=> array('options'=>array(
            array('feedid',1)
        )),
        
        'editdaily'=> array('options'=>array(
            array('feedid',2)
        )),
        
        'multigraph' => array ('action'=>'multigraph', 'options'=>array(array('mid',7)) ),
        
        'compare' => array ('action'=>'compare', 'options'=>array(
            array('powerx',1),
            array('powery',1)
        ))
    );
