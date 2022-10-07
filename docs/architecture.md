### Architecture

The emoncms architecture is a combination of a front controller on the server, a model-view-controller design pattern and a directory structure that makes adding features in a self contained modular way easy. 

As with most web applications emoncms has both a server side component and a client side component. The server side is written in PHP and client side is a mixture of html, css & javascript. This client side component is typically called a 'view' within emoncms. The server side 'controller' typically defines the HTTP API as well as providing the route for loading a view. Models typically implement a class that defines and implements interaction with the underlying database.

Although not adheared to strictly, emoncms does try to avoid siginificant use of PHP templating. It instead renders dynamic content and interacts with the server side API using Javascript, jQuery and VueJS. Data is usually passed back and forth in JSON format.

The server side API is also used directly by energy monitoring equipment posting data to emoncms.

**Directory Structure**

    / (your Web root)
    .htaccess
    index.php
    Modules/
        user/
            user_controller.php
            user_model.php
            user_view.php
        feed/
            feed_controller.php
            feed_model.php
            feed_view.php
    Lib/
        php/
        js/
            jquery
            flot

**index.php: The Front Controller**

The first key point is that all site traffic is directed through index.php. This is a common design pattern called the front controller which allows us to load components used by the whole application such as user sessions and database connection in one place.

We use mod_rewrite to make the URL look clean, converting:

    emoncms/user/login

to:

    emoncms/index.php?q=user/login

index.php then fetches the property **q** with $_GET['q']. using q as a command to tell the application what to do.

#### Build it

Start by creating a folder in your LAMP server */var/www* directory lets call it *framework*

Create a new file called .htaccess and open it in your favourite code editor, copy and paste the following code into the .htaccess file:

.htaccess:

    #
    # Apache/PHP/Emoncms settings:
    #

    # Don't show directory listings for URLs which map to a directory.
    Options -Indexes

    # Set the default handler.
    DirectoryIndex index.php

    # Various rewrite rules.
    <IfModule mod_rewrite.c>
      RewriteEngine on
      # Rewrite URLs of the form 'x' to the form 'index.php?q=x'.
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteCond %{REQUEST_URI} !=/favicon.ico
      RewriteRule ^(.*)$ index.php?q=$1 [L,QSA]
    </IfModule>


This script tells Apache that whenever a HTTP request arrives and if no physical file (!-f) or path (!-d) or symbolic link (!-l) can be found, it should transfer control to index.php, which is the front controller.

Next create a new file called index.php, to illustrate what the .htaccess file does add the following two lines to index.php.

    <?php

    echo "q=".$_GET['q'];

Next: navigate to [http://localhost/framework/feed/list.json](http://localhost/framework/feed/list.json) in your browser and you should see the following:

    q=feed/list.json

As you can see the feed/list.json part has been passed to index.php as a string rather than navigating to an actual folder and file location.

#### Decoding the route

Next we want to decode the "feed/list.json" so that we can use it in our application, in this example:

- **feed** is the controller (the module we want to use)
- **list** is the action
- **json** is the format

Copy and paste the following into index.php:

    <?php

    require "route.php";
    $route = new Route($_GET['q']);

    echo "The requested controller is: ".$route->controller."<br>";
    echo "The requested action is: ".$route->action."<br>";
    echo "The requested format is: ".$route->format."<br>";

and create a file called route.php with the following:

    <?php

    class Route
    {
        public $controller = '';
        public $action = '';
        public $subaction = '';
        public $format = "html";

        public function __construct($q)
        {
            $this->decode($q);
        }

        public function decode($q)
        {
            // filter out all except a-z and / .
            $q = preg_replace('/[^.\/A-Za-z0-9]/', '', $q);
     
            // Split by /
            $args = preg_split('/[\/]/', $q);

            // get format (part of last argument after . i.e view.json)
            $lastarg = sizeof($args) - 1;
            $lastarg_split = preg_split('/[.]/', $args[$lastarg]);
            if (count($lastarg_split) > 1) { $this->format = $lastarg_split[1]; }
            $args[$lastarg] = $lastarg_split[0];

            if (count($args) > 0) { $this->controller = $args[0]; }
            if (count($args) > 1) { $this->action = $args[1]; }
            if (count($args) > 2) { $this->subaction = $args[2]; }
        }
    }

The route class decodes the string into the following properties that can be accessed from within the application:

    $route->controller
    $route->action
    $route->subaction
    $route->format

Navigate again to [http://localhost/framework/feed/list.json](http://localhost/framework/feed/list.json) in your browser and you should see the following:

    The requested controller is: feed
    The requested action is: list
    The requested format is: json

Now that we have our routing specification we can use the first part of the route: $route->controller to load the controller of the module named by $route->controller. In the case of the /feed/list.json query we want to load the feed module which has a feed_controller.php inside, the next section introduces the concept of the module in full:

#### Modules

An emoncms module is simply a directory with all the files that belong to a certain distinct feature inside. The use of the single module directory was [introduced in October 2012](http://openenergymonitor.blogspot.co.uk/2012/10/emoncms-development-update-modules.html) with the main reason being to make it really easy to add features to emoncms just by dropping a new module in the modules folder. A module can then be developed in its own github repository, making development easier. The core emoncms modules are: user, input, feed, vis and dashboard.

A module usually includes the following files, but does not have to include all of them:

- **The module controller:**
The module controller is the second part of responding to the HTTP request. In the first part the front controller index.php loads the module controller using the $route->controller property, now that we are in the module controller the properties $route->action, subaction and format are used to either select html, css, js pages to be sent to the client or to call module model methods.

- **The module model:**, a class with properties and methods that defines and implements a data model, in most cases a model methods include an element of input sanitation, data validation, processing, storage and error reporting. A good way to think of the model is as a software library that can be included in an application, you could even use an emoncms model in another application.

- **Module Views:** Each module usually comes with client side application scripts to generate the user interface and handle data on the client (i.e HTML, css, js pages - the word page is used in a blurred sense here). Client side code is getting more complex as the user interface's becomes more and more javascript driven (providing a nicer more dynamic experience for the user).

- DB Schema definition

- Module menu settings

#### Build it

Lets now extend our index.php to load the requested module controller and create a bare-bones module controller to test it:

Copy and paste the following code into index.php:

    <?php

    require "core.php";
    require "route.php";

    $route = new Route(get('q'));
    $output = controller($route->controller);

    print $output;

Create a file called core.php and add the controller loading function to it:

    <?php
    
    function controller($controller_name)
    {
        $output = array('content'=>'');

        if ($controller_name)
        {
            $controller = $controller_name."_controller";
            $controllerScript = "Modules/".$controller_name."/".$controller.".php";
            if (is_file($controllerScript))
            {
                require $controllerScript;
                $output = $controller();
            }
        }
        return $output;
    }
    
Add also to core.php the following helper function for checking if $_GET has been set.

    function get($index)
    {
      $val = null;
      if (isset($_GET[$index])) $val = $_GET[$index];
      return $val;
    }

Create a folder called Modules and a sub-folder called feed. Inside the feed folder create a file called feed_controller.php and copy and paste the following there:

    <?php
  
    function feed_controller()
    {
        global $route;
    
        // JSON API
        if ($route->format == 'json')
        {
            if ($route->action == 'list') $output = "There will be a feed list here soon";
        }

        return $output;
    }

Navigate again to [http://localhost/framework/feed/list.json](http://localhost/framework/feed/list.json) in your browser and you should see the following:

    There will be a feed list here soon

That completes the implementation and use of routing in the emoncms framework. The next sections will detail how to build data models and views to be used by our module.

#### The Module Model

As of [February 2013](http://openenergymonitor.blogspot.co.uk/2013/02/ideas-for-improving-emoncms-framework.html) the new recommended model construction is a php class that contains the properties and methods that define the functionality of a module rather than straight functions.

Lets create a simple model for our feed module that allows us to create, get a list of and delete feeds. The model below shows a complete example of input sanitation and returned success or error reporting for each method:

    <?php

    class Feed
    {
        private $mysqli;

        public function __construct($mysqli)
        {
            $this->mysqli = $mysqli;
        }

        public function create($userid, $name)
        {
            // Sanitise input
            $userid = intval($userid);
            $name = preg_replace('/[^\w\s-]/','',$name);

            // Insert entry in feeds table
            $result = $this->mysqli->query("INSERT INTO `feeds` (`userid`, `name`) VALUES ('$userid', '$name')");

            $feedid = $this->mysqli->insert_id;

            if ($feedid==0){
                return array('success'=>false);
            } else {
                return array('success'=>true, 'feedid'=>$feedid);
            }
        }

        public function select($userid)
        {
            // Sanitise input
            $userid = intval($userid);

            $result = $this->mysqli->query("SELECT id, name FROM feeds WHERE userid = '$userid'");
            $feeds = array();
            while ($row = $result->fetch_object()) $feeds[] = $row;
            return $feeds;
        }

        public function delete($userid, $feedid)
        {
            // Sanitise input
            $userid = intval($userid);
            $feedid = intval($feedid);

            $result = $this->mysqli->query("DELETE FROM feeds WHERE id = '$feedid' AND userid = '$userid'");

            if ($this->mysqli->affected_rows>0){
                return array('success'=>true, 'message'=>'feed deleted');
            } else {
                return array('success'=>false, 'message'=>'feed does not exist');
            }
        }
    }

We will need a database and feeds table for the above to connect and query, create a table with the following sql:

    CREATE TABLE  `framework`.`feeds` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `userid` INT NOT NULL ,
    `name` TEXT NOT NULL ,
    PRIMARY KEY (  `id` )
    ) ENGINE = MYISAM ;

Next we need to update the feed model controller to route actions to the feed model methods: create, select and delete. We also need to include and initialize the feed model and pass the $mysqli instance through to the feed_model as it is a dependency:

    <?php

    function feed_controller()
    {
        global $route, $mysqli;

        // Fixed userid for now, the userid would usually be set by user session control.
        $userid = 1;

        include "Modules/feed/feed_model.php";
        $feed = new Feed($mysqli);

        // JSON API
        if ($route->format == 'json')
        {
            if ($route->action == 'create') $output = $feed->create($userid,get('name'));
            if ($route->action == 'list') $output = $feed->select($userid);
            if ($route->action == 'delete') $output = $feed->delete($userid,get('id'));
        }

        return $output;
    }

We also need to update index.php to include the connection to the database, placing the connection here means we can use $mysqli in all modules as we add more modules. We also want to add here the line to format the output as json if the request format is json:

    <?php
 
    require "core.php";
    require "route.php";

    $mysqli = new mysqli("localhost","username","password","framework");

    $route = new Route(get('q'));
    $output = controller($route->controller);

    if ($route->format == 'json') echo json_encode($output);

**Try it out**

[http://localhost/framework/feed/create.json?name=power](http://localhost/framework/feed/create.json?name=power)

    {"success":true,"feedid":1}

[http://localhost/framework/feed/list.json](http://localhost/framework/feed/list.json)

    [{"id":"1","name":"power"},{"id":"2","name":"temperature"}]

[http://localhost/framework/feed/delete.json?id=1](http://localhost/framework/feed/delete.json?id=1)

    {"success":true,"message":"feed deleted"} or {"success":false,"message":"feed does not exist"}

Thats the JSON API done! next we will build a nice client side user interface using javascript:

#### Module Views

Views are html, css, javascript application scripts to be loaded from the server to the browser on the client. Once on the client the javascript on the client side will usually continue to request, receive and send data to and from the server JSON API.

This view uses angular js [http://angularjs.org/](http://angularjs.org/)

Create a file in the feed directory called feed_view.html:

    <div ng-app>

        <script src="http://ajax.googleapis.com/ajax/libs/angularjs/1.0.4/angular.min.js"></script>

        <div class="container" ng-controller="FeedsListCtrl">
            <h1>Feeds</h1>

            <table class="table table-bordered">
                <tr>
                    <th><a ng-click="sort='id'">Id</a></th>
                    <th><a ng-click="sort='name'">Name</a></th>
                </tr>

                <tr ng-repeat="feed in feeds | orderBy: sort">
                    <td>{{feed.id}}</td>
                    <td>{{feed.name}}</td>
                </tr>

            </table>
        </div>

        <script>
            function FeedsListCtrl($scope, $http) {
               $http.get('/framework/feed/list.json').success(function (data) {
                   $scope.feeds = data;
               });
            }
        </script>

    </div>
    
Tell the feed controller to load the the feed\_view.html, add the following lines before return $output in feed_controller.php

    if ($route->format == 'html')
    {
        $output['content'] = view("Modules/feed/feed_view.html",array());
    }

Add the view function to core.php:

    function view($filepath, array $args)
    {
      extract($args);
      ob_start();
      include "$filepath";
      $content = ob_get_clean();
      return $content;
    }

The view above is returned to index.php, at the moment we have an option to print the output if its json format but no option to print the output when in html format, so we will need to add that. But first lets wrap the module view in a common site wide twitter bootstrap based theme:

#### Theme

Create a folder called Theme and create a file called theme.php with the following:

    <!doctype html>
    <html>

        <head>
            <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/css/bootstrap-combined.min.css" rel="stylesheet">
        </head>

        <body style="padding-top:42px;" >

            <div class="navbar navbar-inverse navbar-fixed-top">
                <div class="navbar-inner"></div>
            </div>

            <?php echo $content; ?>
        </body>

    </html>

To complete we tell index.php to wrap $output in the theme if the format is html and print the result, add the following line below the if ($route->format=='json') line.

    if ($route->format == 'html') print view("Theme/theme.php", $output);

#### Try it out

[http://localhost/framework/feed/](http://localhost/framework/feed/)

You should now see a simple list as follows, you may need to create some feeds first:

[http://localhost/framework/feed/create.json?name=power](http://localhost/framework/feed/create.json?name=power)

![text](files/final.png)

## table.js dynamic editable table view's

Lets now upgrade our table view above to a fully dynamic editable table ui created using a library called table.js developed as part of this project.

1) Replace the content of feed_view.html with the following:

    <?php
      global $path;
      $path = "http://localhost/framework/";
    ?>

    <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Modules/feed/feed.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/table.js"></script>
    <script type="text/javascript" src="<?php echo $path; ?>Lib/tablejs/custom-table-fields.js"></script>
    <style>
    input[type="text"] {
         width: 88%;
    }
    </style>

    <div class="container">
        <h2>Feeds</h2>
        <div id="table"></div>
    </div>

    <script>

      var path = "<?php echo $path; ?>";

      // Extemd table library field types
      for (z in customtablefields) table.fieldtypes[z] = customtablefields[z];

      table.element = "#table";

      table.fields = {
        'id':{'type':"fixed"},
        'name':{'type':"text"},

        // Actions
        'edit-action':{'title':'', 'type':"edit"},
        'delete-action':{'title':'', 'type':"delete"},
        'view-action':{'title':'', 'type':"iconlink", 'link':path+"vis/auto?feedid="}

      }

      update();

      function update()
      {
        table.data = feed.select();
        table.draw();
      }

      var updater = setInterval(update, 5000);

      $("#table").bind("onEdit", function(e){
        clearInterval(updater);
      });

      $("#table").bind("onSave", function(e,id,fields_to_update){
        feed.update(id,fields_to_update);
        updater = setInterval(update, 5000);
      });

      $("#table").bind("onDelete", function(e,id){
        feed.delete(id);
      });

    </script>
    
2) Create a new script called feed.js in the Modules/feed folder with the following in it:


    var feed = {

      'create':function()
      {
        var result = {};
        $.ajax({ url: path+"feed/create.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
      },

      'select':function()
      {
        var result = {};
        $.ajax({ url: path+"feed/list.json", dataType: 'json', async: false, success: function(data) {result = data;} });
        return result;
      },

      'update':function(id, fields)
      {
        var result = {};
        $.ajax({ url: path+"feed/update.json", data: "id="+id+"&fields="+JSON.stringify(fields), async: false, success: function(data){} });
        return result;
      },

      'delete':function(id)
      {
        $.ajax({ url: path+"feed/delete.json", data: "id="+id, async: false, success: function(data){} });
      }

    }

3) Create a folder called Lib in the framework directory and download and place the tablejs library in that folder:

    https://github.com/emoncms/tablejs
    
4) Next we need to add an update method to the feed model and controller, in the feed model add:

    public function update($userid,$id,$fields)
    {
        $id = intval($id);
        $userid = intval($userid);

        $fields = json_decode($fields);

        $array = array();

        // Repeat this line changing the field name to add fields that can be updated:
        if (isset($fields->name)) $array[] = "`name` = '".preg_replace('/[^\w\s-]/','',$fields->name)."'";

        // Convert to a comma seperated string for the mysql query
        $fieldstr = implode(",",$array);

        $this->mysqli->query("UPDATE feeds SET ".$fieldstr." WHERE `id` = '$id' AND userid = '$userid'");

        if ($this->mysqli->affected_rows>0){
            return array('success'=>true, 'message'=>'Field updated');
        } else {
            return array('success'=>false, 'message'=>'Field could not be updated');
        }
    }

and in the controller add (in with the other API method calls)

    if ($route->action == 'update') $output = $feed->update($userid,get('id'),get('fields'));

#### Try It Out

[http://localhost/framework/feed/](http://localhost/framework/feed/)

You should now see a simple list as follows, you may need to create some feeds first:

[http://localhost/framework/feed/create.json?name=power](http://localhost/framework/feed/create.json?name=power)

![text](files/tablejs.png)


#### Resources

- [Models in MVC](http://blog.astrumfutura.com/2008/12/the-m-in-mvc-why-models-are-misunderstood-and-unappreciated/)
- [Twitter bootstrap](http://twitter.github.com/bootstrap/)
- [jQuery](http://jquery.com)
- [angularjs.org](http://angularjs.org)

