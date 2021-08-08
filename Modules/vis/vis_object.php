<?php

    /* Types:
      0 - feed realtime or daily
      1 - feed realtime
      2 - feed daily
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
            array('colourbg',dgettext('vis_messages','colourbg'),9,'ffffff'),
            array('kw',dgettext('vis_messages','kW'),4,false),
            )
        ),
        
        // Hex colour EDC240 is the default color for flot. since we want existing setups to not change, we set the default value to it manually now,
        'rawdata'=> array('label'=>dgettext('vis_messages','RawData'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1),
            array('fill',dgettext('vis_messages','fill'),7,0),
            array('colour',dgettext('vis_messages','colour'),9,'EDC240'),
            array('colourbg',dgettext('vis_messages','colourbg'),9,'ffffff'),
            array('units',dgettext('vis_messages','units'),5,''),
            array('dp',dgettext('vis_messages','dp'),7,'2'),
            array('scale',dgettext('vis_messages','scale'),6,'1'))
        ),
        
        'bargraph'=> array('label'=>dgettext('vis_messages','BarGraph'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),0),
            array('colour',dgettext('vis_messages','colour'),9,'EDC240'),
            array('colourbg',dgettext('vis_messages','colourbg'),9,'ffffff'),
            array('interval',dgettext('vis_messages','interval'),7,'86400'),
            array('units',dgettext('vis_messages','units'),5,''),
            array('dp',dgettext('vis_messages','dp'),7,'1'),
            array('scale',dgettext('vis_messages','scale'),6,'1'),
            array('delta',dgettext('vis_messages','delta'),4,'0'),
            array('mode',dgettext('vis_messages','mode'),7,'0')
            )
        ),
        
        'zoom'=> array('label'=>dgettext('vis_messages','Zoom'), 'options'=>array(
            array('power',dgettext('vis_messages','power'),1),
            array('kwhd',dgettext('vis_messages','kwhd'),0),
            array('currency',dgettext('vis_messages','currency'),5,'&pound;'),
            array('currency_after_val',dgettext('vis_messages','currency_after_val'),7, 0),
            array('pricekwh',dgettext('vis_messages','pricekwh'),6,0.14),
            array('delta',dgettext('vis_messages','delta'),4,0)
        )),
        
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
        'smoothie'=> array('label'=>dgettext('vis_messages','Smoothie'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1),
            array('ufac',dgettext('vis_messages','ufac'),6))
        ),

        'compare' => array ('label'=>dgettext('vis_messages','Compare'), 'action'=>'compare', 'options'=>array(
            array('feedA',dgettext('vis_messages','Feed A'),1),
            array('feedB',dgettext('vis_messages','Feed B'),1)
        )),
        
        'timecompare'=> array('label'=>dgettext('vis_messages','Time Comparison'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1),
            array('fill',dgettext('vis_messages','fill'),7,1),
            array('depth',dgettext('vis_messages','depth'),7,3),
            array('npoints',dgettext('vis_messages','data points'),7,800)
        )),
        
        'graph'=> array('label'=>dgettext('vis_messages','Graph (Deprecated)'), 'options'=>array(
            array('feedid',dgettext('vis_messages','feed'),1)
        )),
		
        // --------------------------------------------------------------------------------
        // psychrographic diagrams to appreciate summer confort
        // --------------------------------------------------------------------------------
        'psychrograph' => array ('label'=>dgettext('vis_messages','Psychrometric Diagram'), 'action'=>'psychrograph', 'options'=>array(
            array('mid',dgettext('vis_messages','mid'),8),
            array('hrtohabs',dgettext('vis_messages','% to abso.'),4, 1),
            array('givoni',dgettext('vis_messages','givoni style?'),4)
        ))
        // --------------------------------------------------------------------------------     
    );
