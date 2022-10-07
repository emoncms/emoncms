## Developing a new module
There is not a rule about how to make a new module but several things should be taken into account. The aim of this section is to provide information that can guide the process of developing a new module.

***

#### Model-View-Controller (MVC) paradigm
EmonCMS uses the **Model-View-Controller (MVC)** paradigm where the **Controller** deals with the user/node request using the functionality defined in the **Model** (that works as a kind of library).

When the format specified in the request is *json*, the raw output of the **Controller** is sent back to the client (normally a node).

When the format specified in the request is *html*, the **Controller** normally passes the output to the **View** to be rendered before returning it. EmonCMS (index.php) also wraps what the **Controller** returned into a **Theme**. The **Theme** adds the menus but also is responsible for the look and feel of the page.
When developing a new module for EmonCMS the only file that is required is the **Controller**.

If you think that any of the functionality of your new module can be reused by other modules it makes sense to put this functionality in the **Model**. All **Models** in EmonCMS are classes with properties and methods.

#### URL syntaxis
The syntaxis is:
```
http://server/controller/action.subaction.format?attribute=blabla`
```
Where:
- `controller` is the module we want to use
- `action` tells the controller what to do.
- `subaction` is like action (optional)
- `format` tells the controller the format of the output (html or json)
- `attribute` is data to be used by the controller.

Only subaction is optional, so a typical URL would be:

`http://server/controller/action.format?attribute=blabla`

Like in:

`http://emoncms.org/input/post.json?json={power:200}&node=5`

This is telling EmonCMS to load the *input* module which will run the code for the action *post*. EmonCMS will send back a json string.

#### The Modules directory and naming conventions
EmonCMS is modular and there is a strong emphasis in making it easy to add new modules. You only need to paste a directory with your files in the *Modules* directory. It is simple but very basic rules need to be followed. A request like:

`http://server/mymodule/action.subaction.format?attribute=blabla`

will trigger *mymodule*. To do this EmonCMS will include a file called `mymodule_controller.php` which is in the directory `Modules/mymodule`, captions are important. Apart of this you can organize your files however you want.

#### How to avoid direct access
The execution of our module doesn't happen straight away. Everything starts with a call to *index.php* which after a while calls our module.

For example a typical request is:

`http://server/mymodule/action.subaction.format?attribute=blabla`

As said before, this will first call *index.php* (you cannot see it in the request but *.htaccess* is in charge of this) and then it will load our module.

Another way to call our module could be what is known as **direct access**. Look at this request

 `http://server/Modules/mymodule/mymodule_controller.php`

The result of this is unpredictable and can give the client info about the system. So this is a security hole. In order to avoid this, **all the php files in a module must begin with**:
```
defined('EMONCMS_EXEC') or die('Restricted access');
```
This line of code will kill the process if the constant  **EMONCMS_EXEC** is not found. This constant is defined in *index.php*, therefore when trying direct access the constant will not be found and the code will not be executed.

#### Sanitation
A request to EmonCMS defines the controller to use, the action and format but also in many cases has some data to be used by our module. In the example below:

`http://emoncms.org/input/post.json?json={power:200}&node=5`

what comes after the `?` (the query string) is data to be used by the input module. This can be a door for an attacker to run malicious code. This is why **always before using any data from the query string we must sanitize it**.

A simple way of sanitazing the node in the example above would be:
```
$nodeid = preg_replace('/[^\w\s-.]/','',get('node'));
```
#### Using the database.
EmonCMS works with two databases:

- **MySQL**: used to store persistent data, which means *data we want to keep in case we switch off the server.*
- **REDIS**: it is an in memory database.  It is very fast to access and can improve performance: reading/writing to disk (what we do when using the MySQL database) takes longer that doing it to memory (REDIS). Also it can increase the life span of the SD card in the RaspberryPi (limited number of writes). When the server switches off the data in REDIS will be lost unless it has been dumped into disk.

Using one or the other database depends on what is the aim of the data to store. As said before if we want to keep the data, it should go to the MySQL. But if what we want is to cache some data to use it later, REDIS is the one to use. It can also be the case that we store the data in both databases: in the MySQL to ensure the data is kept in cas of the server being switched off but in REDIS to speed up the process.

A good example can be found in `Modules/feed/feed_model`:
```
 public function create($userid,$name,$datatype,$engine,$options_in)
    {
	    //code removed for the example
        $result = $this->mysqli->query("INSERT INTO feeds (userid,name,datatype,public,engine) VALUES ('$userid','$name','$datatype',false,'$engine')");
        $feedid = $this->mysqli->insert_id;

        if ($feedid>0)
        {
            // Add the feed to redis
            if ($this->redis) {
                $this->redis->sAdd("user:feeds:$userid", $feedid);
                $this->redis->hMSet("feed:$feedid",array(
                    'id'=>$feedid,
                    'userid'=>$userid,
                    'name'=>$name,
                    'datatype'=>$datatype,
                    'tag'=>'',
                    'public'=>false,
                    'size'=>0,
                    'engine'=>$engine
                ));
            }
            /code removed for the example
		}
	}		            
```
In the example the feed is first added in the MySQL database but then in the REDIS one too.
In the next example, we see that when fetching feeds we first try to get them from REDIS and if we are not using REDIS then we fetch them from the MySQL one.
```
    public function get_user_feeds($userid)
    {
        $userid = (int) $userid;

        if ($this->redis) {
            $feeds = $this->redis_get_user_feeds($userid);
        } else {
            $feeds = $this->mysql_get_user_feeds($userid);
        }    

        return $feeds;
    }
```

#### Working with the MySQL database

The **MySQL** database can be accessed with the global variable `$mysqli`. It is an instance of the **php class mysqli**. You can see how to use it in the php documentation: http://php.net/manual/en/class.mysqli.php

### Working with the REDIS database
In order to use this database, REDIS must be installed inn the server. For Linux users go to your terminal:
```
sudo apt-get install redis-server
sudo pecl install channel://pecl.php.net/dio-0.0.6 redis swift/swift
sudo sh -c 'echo "extension=redis.so" > /etc/php5/apache2/conf.d/20-redis.ini'
sudo sh -c 'echo "extension=redis.so" > /etc/php5/cli/conf.d/20-redis.ini'
```
EmonCMS will connect to the REDIS database if it is running. You can have access to it with the global variable `$redis` which will be `false` if the EmonCMS couldn't connect to REDIS.

Add list of typical methods we use with $redis

#### The output of your module
When **index.php** calls the controller, **it expects an associative array (with one element that has 'content' as key) to be returned**.

Normally if the format in the request  is *html*, then *'content'* will be the result of calling a **View**

If the format is *json*, then *'content'* will be another associative array that will be sent back to the client in *json* format. See example below taken from `Modules/input/input_controller`:
```
if ($route->format == 'html'){
	if ($route->action == 'api') $result = view("Modules/input/Views/input_api.php", array());
	if ($route->action == 'view') $result =  view("Modules/input/Views/input_view.php", array());
}
if ($route->format == 'json'){
/*code removed for the example*/
	if ($route->subaction == "list") $result = $input->get_processlist(get("inputid"));
	if ($route->subaction == "add") $result = $input->add_process(/*code removed*/);
}
return array('content'=>$result);
```
where `$input->add_process()`  returns: `array('success'=>true, 'message'=>'Process added');`

#### Making a View
A **View** is a php file that makes a web page. Therefore in a **View** we normally find *php, html* and *javascript.*

A **View** is called in the **Controller** as follows:
```
$result = view("Modules/mymodule/Views/myview_view.php", ['variable_1' => $variable_1, 'variable_2' => $variable_2]);
```
where the elements in `array()` can be accesed in the **View** with $args: `$args['variable_1]` and `$args['variable_2']`.

For generating a webpage with language support have a look at the *Locale (translations)* section.

#### Logging information
EmonCMS comes with a logger. In order to use the logger just make an object from the class **EmonLogger**:
```
$this->log = new EmonLogger(__FILE__);  // if you declare it in the constructor of a class
```
You can log warnings and information calling to the appropiate methods:
```
$this->log->warn("Feed model: failed to create feed model feedid=$feedid");
```
or
```
$this->log->info("PHPTimestore:post id=$feedid timestamp=$timestamp value=$value");
```
By default the log file is stored in `/var/log/emoncms.log`.

#### Database autosetup
Probably your new module will need to use one table or more from the database. Normally when developing you will create the table manually but for production you want it to be created automatically. **A schema for the table must be defined.**

A good example of how to define the schema in a *php* file that EmonCMS can use to create the table/s in the database is `Modules/user/user_schema.php`:
```
<?php
$schema['users'] = array(
    'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'username' => array('type' => 'varchar(30)'),
    'email' => array('type' => 'varchar(30)'),
    'password' => array('type' => 'varchar(64)'),
    'salt' => array('type' => 'varchar(3)'),
    'apikey_write' => array('type' => 'varchar(64)'),
    'apikey_read' => array('type' => 'varchar(64)'),
    'lastlogin' => array('type' => 'datetime'),
    'admin' => array('type' => 'int(11)', 'Null'=>'NO')
    //code removed for the example
);
$schema['rememberme'] = array(
    'userid' => array('type' => 'int(11)'),
    'token' => array('type' => 'varchar(40)'),
    'persistentToken' => array('type' => 'varchar(40)'),
    'expire' => array('type' => 'datetime')
);
```
If you know some SQL the code above doesn't need any explanation to understand how the schema is defined. **Just notice that there isn't any `?>` clause to close the php script.**

When installing EmonCMS (with your module) from scratch your table/s will be created the first time that EmonCMS is run. But if you want to add a new module to an existing EmonCMS installation you can just paste it in the Modules directory and in order to create the table/s you need update the database. Logged in as *Administrator* click on *Admin* on the menu and find the button to update the database.

#### Adding a module to the menu
EmonCMS has its own way of building up menus. The menu is loaded in *index.php* and rendered in the page by the Theme. The menu items are fetched from the modules directories.

In order to add a new module to the menu, a file called `mymodule_menu.php` must be added to your module's directory.

The best way to understand how this file should look like is to check out the following menu file

`/Modules/user/user_menu.php`

#### Locale (translations)
EmonCMS comes with language support. Translations files are located in a directory called `locale` in a module's directory. In a translation file two strings are defined:
```
msgid "Input API Help"
msgstr "Ver ayuda para el uso de la API"
```
Where **msgid** is the string used in the **View** and **msgstr** is the string with the translation. See example below from `Modules/input/input_view.php`:
```
<div id="apihelphead"><div style="float:right;"><a href="api"><?php echo _('Input API Help'); ?></a></div></div>
```
which will become:
```
<div id="apihelphead"><div style="float:right;"><a href="api">Ver ayuda para el uso de la API</a></div></div>
```
#### Files that every EmonCMS developer should have a look at
Obviously one of the best ways to learn how to make a module is having a look at other modules files but that is not enough.

Deep understanding on how EmonCMS works starts looking at
- **index.php**: this is the front controller and is the one who makes everything happen and in a specific order: it loads the settings and core scripts, connects to the database, checks authentication, sets the language, loads the controller specified in the request and finally prints the output.
- **settings.php**: nothing to say, just have a look ;-)

There are other files that are worth to look at but you can live without them:
- **core.php**: library that provides essential functionality.
- Modules/user/**user_model.php**
- Modules/user/**feed_model.php**
- Modules/user/**input_model.php**
