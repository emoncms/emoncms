<?php global $path, $session, $user; ?>

<style>
td:nth-of-type(1) { width:35%;}
td:nth-of-type(2) { width:4%;}
</style>

<h2><?php echo _('Input API'); ?></h2>
<h3><?php echo _('Apikey authentication'); ?></h3>
<p><?php echo _('If you want to call any of the following actions when you\'re not logged in, you can authenticate with your API key:'); ?></p>
<ul>
    <li><?php echo _('Use POST parameter (Recommended): "apikey=APIKEY"'); ?></li>
    <li><?php echo _('Add the HTTP header: "Authorization: Bearer APIKEY"'); ?></li>
    <li><?php echo _('Append on the URL of your request: &apikey=APIKEY'); ?></li>
</ul>

<p><?php echo _('Alternatively, use the encrypted input method to post data with higher security.'); ?><br>

<p><b><?php echo _('Read only:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>
<p><b><?php echo _('Read & Write:'); ?></b><br>
<input type="text" style="width:255px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3><?php echo _('Posting data to EmonCMS'); ?></h3>

<p><?php echo _('The EmonCMS HTTP input API provides three ways of sending data to EmonCMS:'); ?></p>
<ul>
    <li><?php echo _('<b>input/post</b> - Post a single update from a node as either one data item or as a JSON data structure.'); ?></li>
    <li><?php echo _('<b>input/bulk</b> - Bulk upload historic data from multiple nodes in a single update.'); ?></li>
    <li><?php echo _('<b>encryption</b> - An encrypted version of both of the above.'); ?></li>
</ul>

<p><?php echo _("If you're starting out with EmonCMS, 'input/post' is a good starting point for testing. This was emonCMS' original input method. The EmonPi/EmonBase uses the 'input/bulk' input method to post to a remote EmonCMS server as that method provides an option to efficiently upload buffered data after an internet connection outage. Combining multiple updates in a single input/bulk request also reduces bandwidth requirements. " ); ?></p>

<p><?php echo _("For applications where HTTPS or TLS is not available, EmonCMS offers an in-built transport layer encryption solution where the EmonCMS apikey is used as the pre-shared key for encrypting the data with AES-128-CBC." ); ?></p>

<h4><?php echo _('input/post'); ?></h4>

<ul>
    <li><?php echo _('The <b>fulljson</b> format is recommended for new integrations. It uses the PHP JSON decoder and the answer is also in json.');?></li>
    <li><?php echo _('The <b>json like</b> format is based on the CSV input parsing implementation and maintained for backward compatibility.'); ?></li>
    <li><?php echo _('The <b>node</b> parameter can be an unquoted string e.g: emontx or a number e.g: 10.'); ?></li>
    <li><?php echo _('Time is set as system time unless a <b>time</b> element is included. It can be either a parameter &time (unquoted) or as part of the JSON data structure. If both are included the parameter value will take precedence. Time is a UNIX timestamp and can be in seconds or a string PHP can decode (ISO8061 recommended). If you are having problems, ensure you are using seconds not milliseconds. If part of the JSON data structure is a string, the node value will report NULL'); ?></li>
    <li><?php echo _('The input/post API is compatible with both GET and POST request methods (POST examples given use curl).'); ?></li>
</ul>
<table class="table">
    <tr><th><?php echo _('Description'); ?></th><th><?php echo _('HTTP Method'); ?></th><th><?php echo _('Example'); ?></th></tr>
    
    <tr><td><?php echo _('JSON format'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/post?node=emontx&fulljson={%22power1%22:100,%22power2%22:200,%22power3%22:300}"><?php echo $path; ?>input/post?<b>node=emontx</b>&fulljson={"power1":100,"power2":200,"power3":300}</a></td></tr>

    <tr><td><?php echo _('JSON format - with time (as a string in this example)'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/post?node=emontx&fulljson={%22power1%22:100,%22power2%22:200,%22power3%22:300,%22time%22:%22<?php echo date(DATE_ATOM);?>%22}"><?php echo $path; ?>input/post?<b>node=emontx</b>&fulljson={"power1":100,"power2":200,"power3":300,"time":"<?php echo date(DATE_ATOM);?>"}</a></td></tr>
    
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
<li><?php echo _('The second number is the node id. This is the unique identifier for the wireless node.'); ?></li>
<li><?php echo _('All the numbers after the first two, are data values. The second node here (node 17) has two data values: 1437 and 3164.'); ?></li>
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

<h4><?php echo _('Encryption'); ?></h4>

<p><?php echo _("For applications where HTTPS or TLS is not available, EmonCMS offers an in-built transport layer encryption solution where the emoncms apikey is used as the pre-shared key for encrypting the data with AES-128-CBC." ); ?><br><?php echo _("There is a PHP example of how to generate an encrypted request here: "); ?><a href="https://github.com/emoncms/emoncms/blob/master/docs/input_encrypted.md">PHP Example source code.</a></p>

<p>
1. Start with a request string conforming with the API options above e.g: node=emontx&data={power1:100,power2:200,power3:300}<br>
2. Create an initialization vector.<br>
3. Encrypt using AES-128-CBC.<br>
4. Create a single string starting with the initialization vector followed by the cipher-text result of the AES-128-CBC encryption.<br>
5. Convert to a base64 encoded string.<br>
6. Generate a HMAC_HASH of the data string together, using the EmonCMS apikey for authorization.<br>
7. Send the encrypted string in the POST body of a request to either input/post or input/bulk with headers properties 'Content-type' and 'Authorization' set as below<br>
8. Verify the result. The result is a base64 encoded sha256 hash of the json data string.
</p>



<table class="table">
<tr><th><?php echo _('Description'); ?></th><th><?php echo _('Method'); ?></th><th><?php echo _('Example'); ?></th></tr>
<tr><td></td><td>GET/POST</td><td>URL: /input/post or /input/bulk<br>HEADER: Authorization: USERID:HMAC_HASH, Content-Type: aes128cbc<br>POST BODY: IV+CIPHERTEXT</td></tr>

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
<tr><td><?php echo _('Get inputs configuration (last time and value not included)'); ?></td><td>GET</td><td><a href="<?php echo $path; ?>input/getinputs"><?php echo $path; ?>input/getinputs</a></td></tr>
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
