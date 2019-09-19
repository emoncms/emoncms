<?php

    /* Types:
      0 - feed realtime or daily
      1 - feed realtime
      2 - feed daily
      3 - feed histogram
      4 - boolean
      5 - text
      6 - float value
      7 - int value
      8 - multigraph id
      9 - colour
    */

    //CHAVEIRO TODO: only used in vis_main_view.php, should be php source for vis/widget/vis_render.js vis_widgetlist variable data

    $visualisations = array(
    
        'realtime' => array('label'=>dgettext('vis_messages','RealTime'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1),
            array('colour',dgettext('vis_messages','colour'),9,'EDC240'),
            array('kw',dgettext('vis_messages','kW'),4,false),
            )
        ),
        
        // Hex colour EDC240 is the default color for flot. since we want existing setups to not change, we set the default value to it manually now,
        'rawdata'=> array('label'=>dgettext('vis_messages','RawData'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1),
            array('fill',dgettext('vis_messages','fill'),7,0),
            array('colour',dgettext('vis_messages','colour'),9,'EDC240'),
            array('units',dgettext('vis_messages','units'),5,''),
            array('dp',dgettext('vis_messages','dp'),7,'2'),
            array('scale',dgettext('vis_messages','scale'),6,'1'))
        ),
        
        'bargraph'=> array('label'=>dgettext('vis_messages','BarGraph'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),0),
            array('colour',dgettext('vis_messages','colour'),9,'EDC240'),
            array('interval',dgettext('vis_messages','interval'),7,'86400'),
            array('units',dgettext('vis_messages','units'),5,''),
            array('dp',dgettext('vis_messages','dp'),7,'1'),
            array('scale',dgettext('vis_messages','scale'),6,'1'),
            array('delta',dgettext('vis_messages','delta'),4,'0'),
            array('mode',dgettext('vis_messages','mode'),7,'0')
            )
        ),
        
        //'dailyhistogram'=> array('options'=>array(array('feedid',3))),
        'zoom'=> array('label'=>dgettext('vis_messages','Zoom'), 'options'=>array(
            array('power',dgettext('vis_messages','power'),1),
            array('kwhd',dgettext('vis_messages','kwhd'),0),
            array('currency',dgettext('vis_messages','currency'),5,'&pound;'),
            array('currency_after_val',dgettext('vis_messages','currency_after_val'),7, 0),
            array('pricekwh',dgettext('vis_messages','pricekwh'),6,0.14),
            array('delta',dgettext('vis_messages','delta'),4,0)
        )),
        
        //'comparison'=> array('options'=>array(array('feedid',3))),
        'stacked'=> array('label'=>dgettext('vis_messages','Stacked'), 'options'=>array(
            array('bottom',dgettext('vis_messages','bottom'),0),
            array('top',dgettext('vis_messages','top'),0),
            array('colourt',dgettext('vis_messages','colourt'),9,'7CC9FF'),
            array('colourb',dgettext('vis_messages','colourb'),9,'0096FF'),
            array('delta',dgettext('vis_messages','delta'),4,0)
        )),
        
        'stackedsolar'=> array('label'=>dgettext('vis_messages','StackedSolar'), 'options'=>array(
            array('solar',dgettext('vis_messages','solar'),0),
            array('consumption',dgettext('vis_messages','consumption'),0),
            array('delta',dgettext('vis_messages','delta'),4,0)
        )),
        
        'simplezoom'=> array('label'=>dgettext('vis_messages','SimpleZoom'), 'options'=>array(
            array('power',dgettext('vis_messages','power'),1),
            array('kwhd',dgettext('vis_messages','kwh'),0),
            array('delta',dgettext('vis_messages','delta'),4,0)
        )),
        
        'orderbars'=> array('label'=>dgettext('vis_messages','OrderBars'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),0),
            array('delta',dgettext('vis_messages','delta'),4,0)
        )),
        
        'multigraph' => array ('label'=>dgettext('vis_messages','MultiGraph'), 'action'=>'multigraph', 'options'=>array(
            array('mid',dgettext('vis_messages','mid'),8)
        )),
        
        'editrealtime'=> array('label'=>dgettext('vis_messages','EditRealtime'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1)
        )),
        
        'editdaily'=> array('label'=>dgettext('vis_messages','EditDaily'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),2)
        )),
        
        // --------------------------------------------------------------------------------
        // Not currently available on emoncms.org
        // --------------------------------------------------------------------------------     
        'timestoredaily'=> array('label'=>dgettext('vis_messages','Daily from Multiple (BETA)'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1),
            array('units',dgettext('vis_messages','units'),5,'kWh'))
        ),
        
        'smoothie'=> array('label'=>dgettext('vis_messages','Smoothie'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1),
            array('ufac',dgettext('vis_messages','ufac'),6))
        ),
        
        'histgraph'=> array('label'=>dgettext('vis_messages','Histgraph'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),3),
            array('barwidth',dgettext('vis_messages','barwidth'),7,50),
            array('start',dgettext('vis_messages','start'),7,0),
            array('end',dgettext('vis_messages','end'),7,0))
        ),  

        'threshold'=> array('label'=>dgettext('vis_messages','Threshold'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),3),
            array('thresholdA',dgettext('vis_messages','thresholdA'),6,500),
            array('thresholdB',dgettext('vis_messages','thresholdB'),6,2500))
        ),      

        'orderthreshold'=> array('label'=>dgettext('vis_messages','OrderThreshold'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),3),
            array('power',dgettext('vis_messages','power'),1),
            array('thresholdA',dgettext('vis_messages','thresholdA'),6,500),
            array('thresholdB',dgettext('vis_messages','thresholdB'),6,2500)
        )),
                
        'compare' => array ('label'=>dgettext('vis_messages','Compare'), 'action'=>'compare', 'options'=>array(
            array('feedA',dgettext('vis_messages','Feed A'),1),
            array('feedB',dgettext('vis_messages','Feed B'),1)
        )),
        
        'graph'=> array('label'=>dgettext('vis_messages','Graph (BETA)'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1)
        )),
        
        'timecompare'=> array('label'=>dgettext('vis_messages','Time Comparison'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1),
            array('fill',dgettext('vis_messages','fill'),7,1),
            array('depth',dgettext('vis_messages','depth'),7,3),
            array('npoints',dgettext('vis_messages','data points'),7,800)
        ))
        // --------------------------------------------------------------------------------     
    );
