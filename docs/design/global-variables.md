# Global variables in EmonCMS
Global variables can be declared in new modules, for example:
```
global $mysqli, $session;
```
There are many globals available, here we describe the more relevant ones.

## $mysqli
Object that connects to EmonCMS database. It is an instance of the php class mysqli. You can see how to use it the php documentation: [http://php.net/manual/en/class.mysqli.php](http://php.net/manual/en/class.mysqli.php)
## $redis
Redis is an in-memory database (MySQL is in-disk). `$redis` is used to reduce the write load to the database. 
__Anybody how can do this section??__
## $route
Object that is an instance of the class Route. This class is defined in `route.php`
Once the URL is decoded in *index.php* the *$route* object properties are set. For the following URL:
```
http://server/controller/action.subaction.format?attribute=blabla
```
The *$route* properties are:
```
$route->controller = controller
$route->action = action 
$route-> subaction = subaction
$route-> format = format
```
## $session
Associative array that stores info about permissions after a user or a node has authenticated.
$session['userid']: the session has been started by this user or a node that belongs to him/her
- **$session['read']** session with read privileges (1 for true, 0 for false)
- **$session['write']**: session with write privileges (1 for true, 0 for false)
- **$session['userid']**: id of the user that has started the session (it can be logged in with the web browser or with the API key in the query string from a node request)
- **$session['username']**: the name of the user that has started the session 
- **$session['admin']**: session with admin privileges (1 for true, 0 for false)
- **$session['editmode']**: I don't know what it is for and i haven't been able to find any usage
- **$session['lang']**: language to be used, useful for the html output;

## $user
Object that is an instance of the class User. This class is defined in `Modules/user/user_model.php` 
This global variable is useful if you need to deal with: user login, user authentication, set/get user info like username, id, apikeys, email, language or timezone.

To know everything you can do using its methods, have look at the model.
