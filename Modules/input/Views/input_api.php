<?php global $path, $session, $user; ?>

<h2>Input API</h2>

<h3>Apikey authentication</h3>
<p>If you want to call any of the following action's when your not logged in, add an apikey to the URL of your request: &apikey=APIKEY.</p>
<p><b>Read only:</b><br>
<input type="text" style="width:230px" readonly="readonly" value="<?php echo $user->get_apikey_read($session['userid']); ?>" />
</p>

<p><b>Read & Write:</b><br>
<input type="text" style="width:230px" readonly="readonly" value="<?php echo $user->get_apikey_write($session['userid']); ?>" />
</p>

<h3>Html</h3>
<p><a href="<?php echo $path; ?>input/node"><?php echo $path; ?>input/node</a> - The input list view</p>
<p><a href="<?php echo $path; ?>input/api"><?php echo $path; ?>input/api</a> - This page</p>
<p><a href="<?php echo $path; ?>input/process?inputid=1"><?php echo $path; ?>input/process?inputid=1</a> - Input processing configuration page</p>

<h3>JSON</h3>
<p>To use the json api the request url needs to include .json</p>

<p><b>Post data</b></p>
<p>JSON: <a href="<?php echo $path; ?>input/post.json?json={power:200}"><?php echo $path; ?>input/post.json?json={power:200}</a></p>
<p>CSV: <a href="<?php echo $path; ?>input/post.json?csv=100,200,300"><?php echo $path; ?>input/post.json?csv=100,200,300</a></p>
<p>Assign inputs to a node group:<br><a href="<?php echo $path; ?>input/post.json?node=1&csv=100,200,300"><?php echo $path; ?>input/post.json?<b>node=1</b>&csv=100,200,300</a></p>

<p>Set the input entry time manually:<br>
<a href="<?php echo $path; ?>input/post.json?time=<?php echo time(); ?>&node=1&csv=100,200,300"><?php echo $path; ?>input/post.json?<b>time=<?php echo time(); ?></b>&node=1&csv=100,200,300</a></p>

<p><b>APIKEY</b><br>
To post data from a remote device you will need to include in the request url your write apikey. This give your device write access to your emoncms account, allowing it to post data. For example using the first json type request above just add the apikey to the end like this:</p>
<p><a href="<?php echo $path; ?>input/post.json?json={power:200}&apikey=<?php echo $user->get_apikey_write($session['userid']); ?>"><?php echo $path; ?>input/post.json?json={power:200}<b>&apikey=<?php echo $user->get_apikey_write($session['userid']); ?></b></a></p>

<p><b>Bulk data</b><br>

<a href="<?php echo $path; ?>input/bulk.json?data=[[0,10,250,100,20],[2,12,1437,3164],[10,10,252,80,21]]"><?php echo $path; ?>input/bulk.json?data=[[0,10,250,100,20],[2,12,1437,3164],[10,10,252,80,21]]</a></p>

<p>The first number of each node is the time offset, so for the first node it is 0 which means the packet for the first node arrived at 0 seconds. The second node arrived at 2 seconds and 3rd 10 seconds. </p>

<p>The second number is the node id, this is the unqiue identifer for the wireless node.</p>

<p>All the numbers after the first two are data values. The first node here (node 10) has three data values: 250,100 and 20.</p>

<br>
<p>List of inputs<br>
<a href="<?php echo $path; ?>input/list.json"><?php echo $path; ?>input/list.json</a></p>

<p>Delete an input<br>
<a href="<?php echo $path; ?>input/delete.json?id=1"><?php echo $path; ?>input/delete.json?id=1</a></p>

<p><b>Input process actions</b></p>

<p>Add an input process:<br><a href="<?php echo $path; ?>input/process/add.json?inputid=1&processid=1&arg=-1&newfeedname=power"><?php echo $path; ?>input/process/add.json?inputid=1&processid=1&arg=-1&newfeedname=power</a></p>

<p>List input process list:<br><a href="<?php echo $path; ?>input/process/list.json?inputid=1"><?php echo $path; ?>input/process/list.json?inputid=1</a></p>

<p>Delete input process:<br><a href="<?php echo $path; ?>input/process/delete.json?inputid=1&processid=1"><?php echo $path; ?>input/process/delete.json?inputid=1&processid=1</a></p>

<p>Move input process:<br><a href="<?php echo $path; ?>input/process/move.json?inputid=1&processid=1&moveby=1"><?php echo $path; ?>input/process/move.json?inputid=1&processid=1&moveby=1</a></p>

<p>Reset input process list:<br><a href="<?php echo $path; ?>input/process/reset.json?inputid=1"><?php echo $path; ?>input/process/reset.json?inputid=1</a></p>

