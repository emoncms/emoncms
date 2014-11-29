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
            array('feedid',_("feedid"),1))
        ),
        
        // Hex colour EDC240 is the default color for flot. since we want existing setups to not change, we set the default value to it manually now,
        'rawdata'=> array('options'=>array(
            array('feedid',_("feedid"),1),
            array('fill',_("fill"),7,0),
            array('colour',_("colour"),5,'EDC240'),
            array('units',_("units"),5,'W'),
            array('dp',_("dp"),7,'1'),
            array('scale',_("scale"),6,'1'))
        ),
        
        'bargraph'=> array('options'=>array(
            array('feedid',_("feedid"),0),
            array('colour',_("colour"),5,'EDC240'),
            array('interval',_("interval"),7,'86400'),
            array('units',_("units"),5,''),
            array('dp',_("dp"),7,'1'),
            array('scale',_("scale"),6,'1'),
            array('delta',_("delta"),7,'0'))
        ),
        
        'timestoredaily'=> array('options'=>array(
            array('feedid',_("feedid"),1),
            array('units',_("units"),5,'kWh'))
        ),
        
        'smoothie'=> array('options'=>array(
            array('feedid',_("feedid"),1),
            array('ufac',_("ufac"),6))
        ),
        
        'histgraph'=> array('options'=>array(
            array('feedid',_("feedid"),3),
            array('barwidth',_("barwidth"),7,50),
            array('start',_("start"),7,0),
            array('end',_("end"),7,0))
        ),
        
        //'dailyhistogram'=> array('options'=>array(array('feedid',3))),
        'zoom'=> array('options'=>array(
            array('power',_("power"),1),
            array('kwhd',_("kwhd"),2),
            array('currency',_("currency"),5,'&pound;'),
            array('currency_after_val',_("currency_after_val"),7, 0),
            array('pricekwh',_("pricekwh"),6,0.14))
        ),
        
        //'comparison'=> array('options'=>array(array('feedid',3))),
        'stacked'=> array('options'=>array(
            array('bottom',_("bottom"),2),
            array('top',_("top"),2))
        ),
        
        'stackedsolar'=> array('options'=>array(
            array('solar',_("solar"),2),
            array('consumption',_("consumption"),2))
        ),
        
        'threshold'=> array('options'=>array(
            array('feedid',_("feedid"),3),
            array('thresholdA',_("thresholdA"),6,500),
            array('thresholdB',_("thresholdB"),6,2500))
        ),
        
        'simplezoom'=> array('options'=>array(
            array('power',_("power"),1),
            array('kwhd',_("kwhd"),0)
        )),
        
        'orderbars'=> array('options'=>array(
            array('feedid',_("feedid"),2)
        )),
        
        'orderthreshold'=> array('options'=>array(
            array('feedid',_("feedid"),3),
            array('power',_("power"),1),
            array('thresholdA',_("thresholdA"),6,500),
            array('thresholdB',_("thresholdB"),6,2500)
        )),
        
        'editrealtime'=> array('options'=>array(
            array('feedid',_("feedid"),1)
        )),
        
        'editdaily'=> array('options'=>array(
            array('feedid',_("feedid"),2)
        )),
        
        'multigraph' => array ('action'=>'multigraph', 'options'=>array(array('mid',7)) ),
        
        'compare' => array ('action'=>'compare', 'options'=>array(
            array('powerx',_("powerx"),1),
            array('powery',_("powery"),1)
        ))
    );
