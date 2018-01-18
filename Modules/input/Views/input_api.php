<?php global $path, $session, $user; ?>

<style>
td:nth-of-type(1) { width:35%;}
td:nth-of-type(2) { width:4%;}
</style>

<h2><?php echo _('Input API'); ?></h2>
<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when your not logged in you can authenticate with your API key:'); ?></p>
<ul>
    <li><?php echo _('Use POST parameter (Recommended): "apikey=APIKEY"'); ?></li>
    <li><?php echo _('Add the HTTP header: "Authorization: Bearer APIKEY"'); ?></li>
    <li><?php echo _('Append on the URL of your request: &apikey=APIKEY'); ?></li>
</ul>

<p><?php echo _('Alternatively use the encrypted input method to post data with higher security.'); ?><br>

<p><b><?php echo _('Read only:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>
<p><b><?php echo _('Read & Write:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _('Posting data to EmonCMS'); ?></h3>

<p><?php echo _('The EmonCMS input API provides two ways of sending data to emoncms:'); ?></p>
<ul>
    <li><?php echo _('<b>input/post</b> - Post a single update from a node.'); ?></li>
    <li><?php echo _('<b>input/bulk</b> - Bulk upload historic data from multiple nodes in a single update.'); ?></li>
</ul>

<p><?php echo _("If your starting out with EmonCMS 'input/post' is a good starting point for testing, this was the original input method when EmonCMS . The EmonPi/EmonBase uses the 'input/bulk' input method to post to a remote emoncms server as this method provides the option to efficiently bulk upload buffered data after an internet connection outage. Combining multiple updates in a single input/bulk request also reduces bandwidth requirements. " ); ?></p>

<h4><?php echo _('input/post'); ?></h4>

<p><?php echo _('The "fulljson" format is recommended for new integrations, it uses the PHP JSON decoder and answer is also in json.<br>The "json like" format is based on the CSV input parsing implementation and maintained for backwards compatibility.'); ?><br><?php echo _('A node name can be a name e.g: emontx or a number e.g: 10.'); ?><br><?php echo _('The input/post API is compatible with both GET and POST request methods (POST examples given use curl).'); ?></p>

<table class="table">
    <tr><th><?php echo _('Description'); ?></th><th><?php echo _('Method'); ?></th><th><?php echo _('Example'); ?></th></tr>
    
    <tr><td><?php echo _('JSON format'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/post?node=emontx&fulljson={%22power1%22:100,%22power2%22:200,%22power3%22:300}"><?php echo $path; ?>input/post?<b>node=emontx</b>&fulljson={"power1":100,"power2":200,"power3":300}</a></td></tr>
    
    <tr><td><?php echo _('JSON like format'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/post?node=emontx&json={power1:100,power2:200,power3:300}"><?php echo $path; ?>input/post?<b>node=emontx</b>&json={power1:100,power2:200,power3:300}</a></td></tr>
    
    <tr><td><?php echo _('CSV format'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/post?node=mynode&csv=100,200,300"><?php echo $path; ?>input/post?<b>node=mynode</b>&csv=100,200,300</a></td></tr>
    
    <tr><td><?php echo _('Set the input entry time manually'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/post?time=<?php echo time(); ?>&node=1&csv=100,200,300"><?php echo $path; ?>input/post?<b>time=<?php echo time(); ?></b>&node=1&csv=100,200,300</a></td></tr>
    
    <tr><td><?php echo _('Node name as sub-action'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/post/emontx?fulljson={%22power1%22:100,%22power2%22:200,%22power3%22:300}"><?php echo $path; ?>input/post<b>/emontx</b>?fulljson={"power1":100,"power2":200,"power3":300}</a></td></tr>

    <tr><td><?php echo _('To post data from a remote device you will need to include in the request url your write apikey. This give your device write access to your emoncms account, allowing it to post data.'); ?> <?php echo _('For example using the first json type request above just add the apikey to the end like this:'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/post?node=emontx&fulljson={%22power1%22:100,%22power2%22:200,%22power3%22:300}&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>"><?php echo $path; ?>input/post?node=emontx&fulljson={"power1":100,"power2":200,"power3":300}<b>&apikey=<?php echo $user->get_apikey_write($session['userid']); ?></b></a></td></tr>

    <tr><td><?php echo _('JSON format:'); ?></td><td>POST</td><td>curl --data "node=1&data={power1:100,power2:200,power3:300}&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>" "<?php echo $path; ?>input/post"</td></tr>
    <tr><td><?php echo _('CSV format:'); ?></td><td>POST</td><td>curl --data "node=1&data=100,200,300&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>" "<?php echo $path; ?>input/post"</td></tr>
</table>

<h4><?php echo _('input/bulk'); ?></h4>

<p><?php echo _('Efficiently upload multiple updates from multiple nodes.'); ?></p>

<table class="table">

<tr><th><?php echo _('Description'); ?></th><th><?php echo _('Method'); ?></th><th><?php echo _('Example'); ?></th></tr>
    
<tr><td><?php echo _('Example request:'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/bulk?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]"><?php echo $path; ?>input/bulk?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]</a></td></tr>
</table>

<ul>
<li><?php echo _('The first number of each node is the time offset (see below).'); ?></li>
<li><?php echo _('The second number is the node id, this is the unique identifier for the wireless node.'); ?></li>
<li><?php echo _('All the numbers after the first two are data values. The second node here (node 17) has two data values: 1437 and 3164.'); ?></li>
<li><?php echo _('Optional offset and time parameters allow the sender to set the time reference for the packets. If none is specified, it is assumed that the last packet just arrived. The time for the other packets is then calculated accordingly.'); ?></li>
</ul>

<table class="table">
<tr><td><?php echo _('Legacy default format (4 is now, 2 is -2 seconds and 0 is -4 seconds to now):'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/bulk?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]"><?php echo $path; ?>input/bulk?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]</a></td></tr>

<tr><td><?php echo _('Time offset format (-6 is -16 seconds to now):'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/bulk?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10"><?php echo $path; ?>input/bulk?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]<b>&offset=-10</b></a></td></tr>

<tr><td><?php echo _('Sentat format: (useful for sending as positive increasing time index)'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/bulk?data=[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]&sentat=543"><?php echo $path; ?>input/bulk?data=[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]<b>&sentat=543</b></b></a></td></tr>

<tr><td><?php echo _('Absolute time format (-6 is 1387730121 seconds since 1970-01-01 00:00:00 UTC))'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/bulk?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=<?php echo time(); ?>"><?php echo $path; ?>input/bulk?data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]<b>&time=<?php echo time(); ?></b></a></td></tr>

<tr><td><?php echo _('Named feeds (similar to the main example but updates the keys "data" and "anotherData" for node 19)'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/bulk?data=[[0,16,1137],[2,17,1437,3164],[4,19,{%22data%22:1412},{%22anotherData%22:3077}]]"><?php echo $path; ?>input/bulk?data=[[0,16,1137],[2,17,1437,3164],[4,19,{"data":1412},{"anotherData":3077}]]</a></td></tr>
    
<tr><td><?php echo _('Legacy format:'); ?></td><td>POST</td><td>curl --data "data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]]&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>" "<?php echo $path; ?>input/bulk"</td></tr>

<tr><td><?php echo _('Time offset format:'); ?></td><td>POST</td><td>curl --data "data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&offset=-10&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>" "<?php echo $path; ?>input/bulk"</td></tr>

<tr><td><?php echo _('Sentat format:'); ?></td><td>POST</td><td>curl --data "data=[[520,16,1137],[530,17,1437,3164],[535,19,1412,3077]]&sentat=543&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>" "<?php echo $path; ?>input/bulk"</td></tr>

<tr><td><?php echo _('Absolute time format:'); ?></td><td>POST</td><td>curl --data "data=[[-10,16,1137],[-8,17,1437,3164],[-6,19,1412,3077]]&time=<?php echo time(); ?>&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>" "<?php echo $path; ?>input/bulk"</td></tr>
</table>

<br>

<h3><?php echo _('Fetching inputs, updating meta data and other actions'); ?></h3>

<br>
<p><b><?php echo _('Input get'); ?></b></p>
<table class="table">
<tr><td><?php echo _('List all nodes and associated inputs:'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/get"><?php echo $path; ?>input/get</a></td></tr>
<tr><td><?php echo _('List inputs for specific node:'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/get/emontx"><?php echo $path; ?>input/get/emontx</a></td></tr>
<tr><td><?php echo _('Fetch specific input from node:'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/get/emontx/power1"><?php echo $path; ?>input/get/emontx/power1</a></td></tr>
</table>

<br>
<p><b><?php echo _('Input actions'); ?></b></p>
<table class="table">
<tr><td><?php echo _('List of inputs with latest data'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/list"><?php echo $path; ?>input/list</a></td></tr>
<tr><td><?php echo _('Get inputs configuration (last time and value not included)'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/get_inputs"><?php echo $path; ?>input/get_inputs</a></td></tr>
<tr><td><?php echo _('Set input fields'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/set?inputid=0&fields={'description':'Input Description'}"><?php echo $path; ?>input/set?inputid=0&fields={'description':'Input Description'}</a></td></tr>
<tr><td><?php echo _('Delete an input'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/delete?inputid=0"><?php echo $path; ?>input/delete?inputid=0</a></td></tr>
<tr><td><?php echo _('Clean inputs without a process list'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/clean"><?php echo $path; ?>input/clean</a></td></tr>
</table>

<p><b><?php echo _('Input process actions'); ?></b></p>
<table class="table">
    <tr><td><?php echo _('Get input process list'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/process/get?inputid=1"><?php echo $path; ?>input/process/get?inputid=1</a></td></tr>
    <tr><td><?php echo _('Set input process list'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/process/set?inputid=0&processlist=0:0"><?php echo $path; ?>input/process/set?inputid=0&processlist=0:0</a></td></tr>
    <tr><td><?php echo _('Reset input process list'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/process/reset?inputid=0"><?php echo $path; ?>input/process/reset?inputid=0</a></td></tr>
</table>
