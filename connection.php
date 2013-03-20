<?php
/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
*/

global $database;

class emoncmsdbconnection extends mysqli
{
    protected static $instance;

    public function __construct() {
      
        global $database,$username,$password,$server,$port;
        
        // Check if database connection configuration is set (password can be blank)
        // If database name is blank, connection does not throw errors :(
        if ($database == "" || $username == "") {         
          die('Please, configure database connection settings in settings.php file');        
        }
       
        // connect to database (check if previous settings.php file sets $port variable )
        @parent::__construct($server,$username,$password,$database, ($port == NULL) ? 3306 : $port );        
    }

    public static function getInstance() {
        global $database, $dbtest;
        
        if( !self::$instance ) {
            self::$instance = new self(); 
        }
        
        // check if a connection established
        if( mysqli_connect_errno() ) {
            die("Error connecting database. Please, check settings.php");
        } else {          
            if ($dbtest==true) {              
              require_once "Lib/dbschemasetup.php";
              if (!db_check(self::$instance,$database)) db_schema_setup(self::$instance,load_db_schema());
            }
        }
        
        return self::$instance;
    }

/*    public function query($query) {
        if( !$this->real_query($query) ) {
            throw new exception( $this->error, $this->errno );
        }

        $result = new mysqli_result($this);
        return $result;
    }

    public function prepare($query) {
        $stmt = new mysqli_stmt($this, $query);
        return $stmt;
    }*/    
}
