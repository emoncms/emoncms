<?php global $path, $session; ?>

<h2>Input API</h2>

<h3>Apikey authentication</h3>
<p>If you want to call any of the following action's when your not logged in, add an apikey to the URL of your request: &apikey=APIKEY.</p>
<p><b>Read only:</b><br>
<input type="text" style="width:230px" readonly="readonly" value="<?php echo get_apikey_read($session['userid']); ?>" />
</p>

<p><b>Read & Write:</b><br>
<input type="text" style="width:230px" readonly="readonly" value="<?php echo get_apikey_write($session['userid']); ?>" />
</p>

<h3>Post data</h3>
<p>JSON:<br><a href="<?php echo $path; ?>input/post?json={power:200}"><?php echo $path; ?>input/post?json={power:200}</a></p>

<p>CSV:<br><a href="<?php echo $path; ?>input/post?csv=100,200,300"><?php echo $path; ?>input/post?csv=100,200,300</a></p>

<p>Assign inputs to a node group:<br><a href="<?php echo $path; ?>input/post?node=1&csv=100,200,300"><?php echo $path; ?>input/post?<b>node=1</b>&csv=100,200,300</a></p>

<p>Set the input entry time manually:<br>
<a href="<?php echo $path; ?>input/post?time=<?php echo time(); ?>&node=1&csv=100,200,300"><?php echo $path; ?>input/post?<b>time=<?php echo time(); ?></b>&node=1&csv=100,200,300</a></p>
<p><b>APIKEY</b><br>
To post data from a remote device you will need to include in the request url your write apikey. This give your device write access to your emoncms account, allowing it to post data. For example using the first json type request above just add the apikey to the end like this:</p>
<p><a href="<?php echo $path; ?>input/post?json={power:200}&apikey=<?php echo get_apikey_write($session['userid']); ?>"><?php echo $path; ?>input/post?json={power:200}<b>&apikey=<?php echo get_apikey_write($session['userid']); ?></b></a></p>

<h3>List</h3>
<p><a href="<?php echo $path; ?>input/list"><?php echo $path; ?>input/list</a></p>

<p><a href="<?php echo $path; ?>input/node"><?php echo $path; ?>input/node</a></p>

<h3>Delete</h3>
<p><a href="<?php echo $path; ?>input/delete?id=1"><?php echo $path; ?>input/delete?id=1</a></p>



<h3>Process</h3>

<p>Query input process:<br><a href="<?php echo $path; ?>input/process/query?type=1"><?php echo $path; ?>input/process/query?type=1</a></p>

<p>List input process list:<br><a href="<?php echo $path; ?>input/process/list?inputid=1"><?php echo $path; ?>input/process/list?inputid=1</a></p>

<p>Add an input process:<br><a href="<?php echo $path; ?>input/process/add?inputid=1&type=1&arg=-1&arg2=power"><?php echo $path; ?>input/process/add?inputid=1&type=1&arg=-1&arg2=power</a></p>

<p>Delete input process:<br><a href="<?php echo $path; ?>input/process/delete?inputid=1&processid=1"><?php echo $path; ?>input/process/delete?inputid=1&processid=1</a></p>

<p>Move input process:<br><a href="<?php echo $path; ?>input/process/move?inputid=1&processid=1&moveby=1"><?php echo $path; ?>input/process/move?inputid=1&processid=1&moveby=1</a></p>

<p>Reset input process list:<br><a href="<?php echo $path; ?>input/process/reset?inputid=1"><?php echo $path; ?>input/process/reset?inputid=1</a></p>

<p>Autoconfigure inputs (works with inputs called power, temp & temperature):<br><a href="<?php echo $path; ?>input/autoconfigure"><?php echo $path; ?>input/autoconfigure</a></p>
