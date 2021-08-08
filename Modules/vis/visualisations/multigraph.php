<!--All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
-->

<?php
    defined('EMONCMS_EXEC') or die('Restricted access');
    //global $path;
    //$embed = (int)(get("embed"));
    //$mid = intval(get("mid"));
?>
<script>
//still needed ?
var srcIeScript = path+"Lib/flot/excanvas.min.js";
document.write('<!--[if IE]><script language="javascript" type="text/javascript" src="'+srcIeScript+'"><\/script><![endif]-->');

//srcScripts includes all the needed js libraries
var srcScripts = [];
srcScripts.push(path+"Lib/flot/jquery.flot.merged.js");
srcScripts.push(path+"Lib/flot/jquery.flot.togglelegend.min.js");
srcScripts.push(path+"Lib/flot/jquery.flot.stack.min.js");
srcScripts.push(path+"Modules/vis/visualisations/common/api.js");
srcScripts.push(path+"Modules/vis/visualisations/common/vis.helper.js");
srcScripts.push(path+"Modules/vis/visualisations/multigraph/multigraph.js");
srcScripts.push(path+"Lib/bootstrap-datetimepicker-0.0.11/js/bootstrap-datetimepicker.min.js");
srcScripts.push(path+"Lib/bootstrap/js/bootstrap.js");
srcScripts.forEach(function(srcScript){
  document.write('<script language="javascript" type="text/javascript" src="'+srcScript+'"><\/script>');
});

//srcLinks includes all the needed css
var srcLinks = [];
srcLinks.push(path+"Lib/bootstrap/css/bootstrap.min.css");
srcLinks.push(path+"Lib/bootstrap/css/bootstrap-responsive.min.css");
srcLinks.push(path+"Lib/bootstrap-datetimepicker-0.0.11/css/bootstrap-datetimepicker.min.css");
srcLinks.forEach(function(srcLink){
  document.write('<link href="'+srcLink+'" rel="stylesheet">');
});
</script>

<div id="multigraph"></div>

<script id="source" language="javascript" type="text/javascript">
    //we start by fetching some of the url parameters
    //for user logged in emoncms, the url is like /vis/multigraph?mid=1&embed=0
    //for visitors, the url can be /vis/multigraph?mid=1&embed=1&apikey=apikey_read
    
    //we use the urlParams var provided by the helper : Modules/vis/visualisations/common/vis_helper.js
    //working on firefox,chrome,edge
    //ALTERNATIVE : use of the generic js function URLSearchParams
    //does not work with IE/Edge !
    //const url_Params = new URLSearchParams(window.location.search);
    //console.log(window.location.search);
    //console.log(urlParams);
    var mid = urlParams.mid;
    var embed = urlParams.embed;
    var apikey = "" ;
    if (urlParams.apikey) {apikey= urlParams.apikey;}
    //var apikey="";
    //if (url_Params.has("apikey")){
    //  apikey = url_Params.get("apikey");
    //}
    var multigraphFeedlist = {};
    
    if (mid===0) $("body").css('background-color','#eee');

    $.ajax({
        url: path+"vis/multigraph/get.json",
        data: "&id="+mid,
        dataType: 'json',
        async: true,
        success: function(data)
        {
            if (data['feedlist'] !== "undefined") {multigraphFeedlist = data['feedlist'];}
            multigraphInit("#multigraph");
            visFeedData();
        }
    });

</script>
