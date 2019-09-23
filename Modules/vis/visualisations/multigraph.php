<!--All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->

<?php
    global $path;
    //$embed = (int)(get("embed"));
    //$mid = intval(get("mid"));
?>

<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $path;?>Lib/flot/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.merged.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.togglelegend.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/flot/jquery.flot.stack.min.js"></script>

<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/api.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/common/vis.helper.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Modules/vis/visualisations/multigraph/multigraph.js"></script>

<link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo $path; ?>Lib/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
<link href="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo $path; ?>Lib/bootstrap/js/bootstrap.js"></script>

<div id="multigraph"></div>

<script id="source" language="javascript" type="text/javascript">
    //there is a urlParams var in Modules/vis/visualisations/common/vis_helper.js with a custom function
    //anyway, the use of the generic function URLSearchParams could be a simplier solution
    //does not work with IE but IE is a kind of deprecated
    //console.log(window.location.search);
    //for user logged in emoncms, the url is like /vis/multigraph?mid=1&embed=0
    //for visitors, the url can be /vis/multigraph?mid=1&embed=1&apikey=apikey_read
    const url_Params = new URLSearchParams(window.location.search);
    var mid = url_Params.get("mid");
    var embed = url_Params.get("embed");
    var apikey="";
    if (url_Params.has("apikey")){
      apikey = url_Params.get("apikey");
    }
    var multigraphFeedlist = {};
    
    if (mid===0) $("body").css('background-color','#eee');

    $.ajax({
        url: path+"vis/multigraph/get.json",
        data: "&id="+mid,
        dataType: 'json',
        async: true,
        success: function(data)
        {
            if (data['feedlist'] !== undefined) {multigraphFeedlist = data['feedlist'];}
            multigraphInit("#multigraph");
            visFeedData();
        }
    });

</script>
