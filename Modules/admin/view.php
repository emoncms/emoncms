<?php global $path;
$v=1;
?>
<link rel="stylesheet" href="<?php echo $path?>Modules/admin/static/admin_styles.css?v=<?php echo $v ?>">

<div id="serverinfo-container">
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">Services</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate"><span class="badge-danger badge"></span> emonhub</dt>
      <dd class="col-sm-10 col-8 border-box px-1"><strong>Inactive</strong> Dead</dd>
      <dt class="col-sm-2 col-4 text-truncate"><span class="badge-danger badge"></span> emoncms_mqtt</dt>
      <dd class="col-sm-10 col-8 border-box px-1"><strong>Inactive</strong> Dead</dd>
      <dt class="col-sm-2 col-4 text-truncate"><span class="badge-danger badge"></span> feedwriter</dt>
      <dd class="col-sm-10 col-8 border-box px-1"><strong>Inactive</strong> Dead<font color="red">Service is not running</font> <span id="bufferused">loading...</span></dd>
      <dt class="col-sm-2 col-4 text-truncate"><span class="badge-danger badge"></span> service-runner</dt>
      <dd class="col-sm-10 col-8 border-box px-1"><strong>Inactive</strong> Dead</dd>
      <dt class="col-sm-2 col-4 text-truncate"><span class="badge-danger badge"></span> emonPiLCD</dt>
      <dd class="col-sm-10 col-8 border-box px-1"><strong>Inactive</strong> Dead</dd>
      <dt class="col-sm-2 col-4 text-truncate"><span class="badge-success badge"></span> redis-server</dt>
      <dd class="col-sm-10 col-8 border-box px-1"><strong>Active</strong> Running</dd>
      <dt class="col-sm-2 col-4 text-truncate"><span class="badge-danger badge"></span> mosquitto</dt>
      <dd class="col-sm-10 col-8 border-box px-1"><strong>Inactive</strong> Dead</dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">Emoncms</h4>
   
   <dl class="row">
      <dd class="col-2 inline"><b>Name</b></dd>
      <dd class="col-1 inline"><b>Version</b></dd>
      <dd class="col-2 inline"><b>Git Describe</b></dd>
      <dd class="col-4 inline"><b>Git URL</b></dd>
      <dd class="col-3 inline"><b>Git Branch</b></dd>
      
      <dd class="col-2 inline">Emoncms Core</dd>
      <dd class="col-1 inline">v10.2.2</dd>
      <dd class="col-2 inline">10.2.2-36-g0702043</dd>
      <dd class="col-4 inline"><a href="https://github.com/emoncms/emoncms.git">https://github.com/emoncms/emoncms.git</a></dd>
      <dd class="col-3 inline text-truncate">emoncms_component_table</dd>
      
      <dd class="col-2 inline">Emoncms Core</dd>
      <dd class="col-1 inline">v10.2.2</dd>
      <dd class="col-2 inline">10.2.2-36-g0702043</dd>
      <dd class="col-4 inline"><a href="https://github.com/emoncms/emoncms.git">https://github.com/emoncms/emoncms.git</a></dd>
      <dd class="col-3 inline text-truncate">emoncms_component_table</dd>

      <dd class="col-2 inline">Emoncms Core</dd>
      <dd class="col-1 inline">v10.2.2</dd>
      <dd class="col-2 inline">10.2.2-36-g0702043</dd>
      <dd class="col-4 inline"><a href="https://github.com/emoncms/emoncms.git">https://github.com/emoncms/emoncms.git</a></dd>
      <dd class="col-3 inline text-truncate">emoncms_component_table</dd>
      
      <dd class="col-2 inline">Emoncms Core</dd>
      <dd class="col-1 inline">v10.2.2</dd>
      <dd class="col-2 inline">10.2.2-36-g0702043</dd>
      <dd class="col-4 inline"><a href="https://github.com/emoncms/emoncms.git">https://github.com/emoncms/emoncms.git</a></dd>
      <dd class="col-3 inline text-truncate">emoncms_component_table</dd>
   </dl> 
   
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">Server</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">OS</dt>
      <dd class="col-sm-10 col-8 border-box px-1">Linux 4.4.0-177-generic</dd>
      <dt class="col-sm-2 col-4 text-truncate">Host</dt>
      <dd class="col-sm-10 col-8 border-box px-1">trystan-laptop | trystan-laptop | (127.0.0.1)</dd>
      <dt class="col-sm-2 col-4 text-truncate">Date</dt>
      <dd class="col-sm-10 col-8 border-box px-1">2020-05-08 08:51:55 BST</dd>
      <dt class="col-sm-2 col-4 text-truncate">Uptime</dt>
      <dd class="col-sm-10 col-8 border-box px-1"> 08:51:55 up 1 day, 11:20,  1 user,  load average: 1.11, 1.66, 1.49</dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">Memory</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">RAM</dt>
      <dd class="col-sm-10 col-8 border-box px-1">
         <h5 class="m-0">Used: 51.15%</h5>
         <div class="progress progress-info mb-0">
            <div class="bar" style="width: 51.15%"></div>
         </div>
         <dl class="inline">
            <dt class="pl-0">Total</dt>
            <dd>7.37 GB</dd>
            <dt class="pl-0">Used</dt>
            <dd>3.77 GB</dd>
            <dt class="pl-0">Free</dt>
            <dd>3.6 GB</dd>
         </dl>
      </dd>
      <dt class="col-sm-2 col-4 text-truncate">Swap</dt>
      <dd class="col-sm-10 col-8 border-box px-1">
         <h5 class="m-0">Used: 0.32%</h5>
         <div class="progress progress-info mb-0">
            <div class="bar" style="width: 0.32%"></div>
         </div>
         <dl class="inline">
            <dt class="pl-0">Total</dt>
            <dd>7.57 GB</dd>
            <dt class="pl-0">Used</dt>
            <dd>24.86 MB</dd>
            <dt class="pl-0">Free</dt>
            <dd>7.54 GB</dd>
         </dl>
      </dd>
   </dl>
   <div class="input-prepend" style="float:right; padding-top:5px">
      <span class="add-on">Write Load Period</span>
      <button id="resetwriteload" class="btn btn-info">Reset</button>
   </div>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">Disk</h4>
   <br>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">/</dt>
      <dd class="col-sm-10 col-8 border-box px-1">
         <h5 class="m-0">Used: 84.23%</h5>
         <div class="progress progress-info mb-0">
            <div class="bar" style="width: 84.23%"></div>
         </div>
         <dl class="inline">
            <dt class="pl-0">Total</dt>
            <dd>227.15 GB</dd>
            <dt class="pl-0">Used</dt>
            <dd>191.33 GB</dd>
            <dt class="pl-0">Free</dt>
            <dd>24.26 GB</dd>
            <dt class="pl-0">Write Load</dt>
            <dd>n/a</dd>
         </dl>
      </dd>
      <dt class="col-sm-2 col-4 text-truncate">/run/cgmanager/fs</dt>
      <dd class="col-sm-10 col-8 border-box px-1">
         <h5 class="m-0">Used: 0.00%</h5>
         <div class="progress progress-info mb-0">
            <div class="bar" style="width: 0.00%"></div>
         </div>
         <dl class="inline">
            <dt class="pl-0">Total</dt>
            <dd>100 KB</dd>
            <dt class="pl-0">Used</dt>
            <dd>0 B</dd>
            <dt class="pl-0">Free</dt>
            <dd>100 KB</dd>
            <dt class="pl-0">Write Load</dt>
            <dd>n/a</dd>
         </dl>
      </dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">HTTP</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">Server</dt>
      <dd class="col-sm-10 col-8 border-box px-1">Apache/2.4.18 (Ubuntu) HTTP/1.1 CGI/1.1 80</dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">MySQL</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">Version</dt>
      <dd class="col-sm-10 col-8 border-box px-1">5.7.29-0ubuntu0.16.04.1</dd>
      <dt class="col-sm-2 col-4 text-truncate">Host</dt>
      <dd class="col-sm-10 col-8 border-box px-1">localhost:6379 (127.0.0.1)</dd>
      <dt class="col-sm-2 col-4 text-truncate">Date</dt>
      <dd class="col-sm-10 col-8 border-box px-1">2020-05-08 08:51:54 (UTC 01:00‌​)</dd>
      <dt class="col-sm-2 col-4 text-truncate">Stats</dt>
      <dd class="col-sm-10 col-8 border-box px-1">Uptime: 127214  Threads: 2  Questions: 64307  Slow queries: 0  Opens: 250  Flush tables: 1  Open tables: 160  Queries per second avg: 0.505</dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">Redis</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">Version</dt>
      <dd class="col-sm-10 col-8 border-box px-1">
         <dl class="row">
            <dt class="col-sm-2 col-4 text-truncate">Redis Server</dt>
            <dd class="col-sm-10 col-8 border-box px-1">3.0.6</dd>
            <dt class="col-sm-2 col-4 text-truncate">PHP Redis</dt>
            <dd class="col-sm-10 col-8 border-box px-1"> 5.0.2</dd>
         </dl>
      </dd>
      <dt class="col-sm-2 col-4 text-truncate">Host</dt>
      <dd class="col-sm-10 col-8 border-box px-1">localhost:6379</dd>
      <dt class="col-sm-2 col-4 text-truncated-flex"><span class="align-self-center">Size</span></dt>
      <dd class="col-sm-10 col-8 border-box px-1d-flex align-items-center justify-content-between"><span id="redisused">147 keys (555.66K)</span><button id="redisflush" class="btn btn-info btn-small pull-right">Flush</button></dd>
      <dt class="col-sm-2 col-4 text-truncate">Uptime</dt>
      <dd class="col-sm-10 col-8 border-box px-1">1 days</dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">MQTT Server</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">Version</dt>
      <dd class="col-sm-10 col-8 border-box px-1">Mosquitto 1.6.9</dd>
      <dt class="col-sm-2 col-4 text-truncate">Host</dt>
      <dd class="col-sm-10 col-8 border-box px-1">localhost:1883 (127.0.0.1)</dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">PHP</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">Version</dt>
      <dd class="col-sm-10 col-8 border-box px-1">7.0.33-0ubuntu0.16.04.14 (Zend Version 3.0.0)</dd>
      <dt class="col-sm-2 col-4 text-truncate">Modules</dt>
      <dd class="col-sm-10 col-8 border-box px-1overflow-hidden">
         <ul id="php-modules">
            <li>apache2handler</li>
            <li>calendar </li>
            <li>Core </li>
            <li>ctype </li>
            <li>curl </li>
            <li>date </li>
            <li>dom v20031129</li>
            <li>exif </li>
            <li>fileinfo v1.0.5</li>
            <li>filter </li>
            <li>ftp </li>
            <li>gd </li>
            <li>gettext </li>
            <li>hash v1.0</li>
            <li>iconv </li>
            <li>json v1.4.0</li>
            <li>libxml </li>
            <li>mbstring </li>
            <li>mcrypt </li>
            <li>mosquitto v0.4.0</li>
            <li>mysqli </li>
            <li>mysqlnd vmysqlnd 5.0.12-dev - 20150407 - $Id: b5c5906d452ec590732a93b051f3827e02749b83 $</li>
            <li>openssl </li>
            <li>pcre </li>
            <li>PDO </li>
            <li>pdo_mysql </li>
            <li>Phar v2.0.2</li>
            <li>posix </li>
            <li>readline </li>
            <li>redis v5.0.2</li>
            <li>Reflection </li>
            <li>session </li>
            <li>shmop </li>
            <li>SimpleXML </li>
            <li>sockets </li>
            <li>SPL </li>
            <li>standard </li>
            <li>sysvmsg </li>
            <li>sysvsem </li>
            <li>sysvshm </li>
            <li>tokenizer </li>
            <li>wddx </li>
            <li>xml </li>
            <li>xmlreader </li>
            <li>xmlwriter </li>
            <li>xsl </li>
            <li>Zend OPcache </li>
            <li>zlib </li>
         </ul>
      </dd>
   </dl>
</div>
<h3 class="mt-1 mb-0">Client Information</h3>
<div id="clientinfo-container">
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">HTTP</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">Browser</dt>
      <dd class="col-sm-10 col-8 border-box px-1">Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:75.0) Gecko/20100101 Firefox/75.0</dd>
      <dt class="col-sm-2 col-4 text-truncate">Language</dt>
      <dd class="col-sm-10 col-8 border-box px-1">en-GB,en;q=0.5</dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">Window</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">Size</dt>
      <dd class="col-sm-10 col-8 border-box px-1">
         <span id="windowsize">
            <script>document.write($( window ).width() + " x " + $( window ).height())</script>
         </span>
      </dd>
   </dl>
   <h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">Screen</h4>
   <dl class="row">
      <dt class="col-sm-2 col-4 text-truncate">Resolution</dt>
      <dd class="col-sm-10 col-8 border-box px-1">
         <span id="screensize">
            <script>document.write(window.screen.width + " x " + window.screen.height);</script>
         </span>
      </dd>
   </dl>
</div>



<pre>
<?php
echo json_encode($view_data,JSON_PRETTY_PRINT);?>
</pre>
