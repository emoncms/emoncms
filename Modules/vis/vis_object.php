<?php

    /* Types:
      0 - feed realtime or daily
      1 - feed realtime
      2 - feed daily
      3 - feed histogram
      4 - boolean (not used uncomment lines)
      5 - text
      6 - float value
      7 - int value
      8 - multigraph id
    */

    //CHAVEIRO TODO: only used in vis_main_view.php, should be php source for vis/widget/vis_render.js vis_widgetlist variable data

    $visualisations = array(
    
        'realtime' => array('label'=>_("RealTime"), 'options'=>array(
            array('feedid',_("feed"),1))
        ),
        
        // Hex colour EDC240 is the default color for flot. since we want existing setups to not change, we set the default value to it manually now,
        'rawdata'=> array('label'=>_("RawData"), 'options'=>array(
            array('feedid',_("feed"),1),
            array('fill',_("fill"),7,0),
            array('colour',_("colour"),5,'EDC240'),
            array('units',_("units"),5,''),
            array('dp',_("dp"),7,'2'),
            array('scale',_("scale"),6,'1'))
        ),
        
        'bargraph'=> array('label'=>_("BarGraph"), 'options'=>array(
            array('feedid',_("feed"),0),
            array('colour',_("colour"),5,'EDC240'),
            array('interval',_("interval"),7,'86400'),
            array('units',_("units"),5,''),
            array('dp',_("dp"),7,'1'),
            array('scale',_("scale"),6,'1'),
            array('delta',_("delta"),7,'0'))
        ),
        
        'timestoredaily'=> array('label'=>_("Daily from Multiple (BETA)"), 'options'=>array(
            array('feedid',_("feed"),1),
            array('units',_("units"),5,'kWh'))
        ),
        
        'smoothie'=> array('label'=>_("Smoothie"), 'options'=>array(
            array('feedid',_("feed"),1),
            array('ufac',_("ufac"),6))
        ),
        
        'histgraph'=> array('label'=>_("Histgraph"), 'options'=>array(
            array('feedid',_("feed"),3),
            array('barwidth',_("barwidth"),7,50),
            array('start',_("start"),7,0),
            array('end',_("end"),7,0))
        ),
        
        //'dailyhistogram'=> array('options'=>array(array('feedid',3))),
        'zoom'=> array('label'=>_("Zoom"), 'options'=>array(
            array('power',_("power"),1),
            array('kwhd',_("kwhd"),2),
            array('currency',_("currency"),5,'&pound;'),
            array('currency_after_val',_("currency_after_val"),7, 0),
            array('pricekwh',_("pricekwh"),6,0.14))
        ),
        
        //'comparison'=> array('options'=>array(array('feedid',3))),
        'stacked'=> array('label'=>_("Stacked"), 'options'=>array(
            array('bottom',_("bottom"),2),
            array('top',_("top"),2))
        ),
        
        'stackedsolar'=> array('label'=>_("StackedSolar"), 'options'=>array(
            array('solar',_("solar"),2),
            array('consumption',_("consumption"),2))
        ),
        
        'threshold'=> array('label'=>_("Threshold"), 'options'=>array(
            array('feedid',_("feed"),3),
            array('thresholdA',_("thresholdA"),6,500),
            array('thresholdB',_("thresholdB"),6,2500))
        ),
        
        'simplezoom'=> array('label'=>_("SimpleZoom"), 'options'=>array(
            array('power',_("power"),1),
            array('kwhd',_("kwhd"),0)
        )),
        
        'orderbars'=> array('label'=>_("OrderBars"), 'options'=>array(
            array('feedid',_("feed"),2)
        )),
        
        'orderthreshold'=> array('label'=>_("OrderThreshold"), 'options'=>array(
            array('feedid',_("feed"),3),
            array('power',_("power"),1),
            array('thresholdA',_("thresholdA"),6,500),
            array('thresholdB',_("thresholdB"),6,2500)
        )),
        
        'editrealtime'=> array('label'=>_("EditRealtime"), 'options'=>array(
            array('feedid',_("feed"),1)
        )),
        
        'editdaily'=> array('label'=>_("EditDaily"), 'options'=>array(
            array('feedid',_("feed"),2)
        )),
        
        'multigraph' => array ('label'=>_("MultiGraph"), 'action'=>'multigraph', 'options'=>array(
            array('mid',_("mid"),8)
        )),
        
        'compare' => array ('label'=>_("Compare"), 'action'=>'compare', 'options'=>array(
            array('feedA',_("Feed A"),1),
            array('feedB',_("Feed B"),1)
        )),
        
        'graph'=> array('label'=>_("Graph (BETA)"), 'options'=>array(
            array('feedid',_("feed"),1)
        ))
    );
