<?php
/*
  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org
*/

class emoncmsdbconnection extends mysqli
{
    protected static $instance;

    public function __construct($db_server,$db_username,$db_password,$db_database,$db_port) {
      
        // Check if database connection configuration is set (password can be blank)
        // If database name is blank, connection does not throw errors :(
        if ($db_database == "" || $db_username == "") {
          die('Please, configure database connection settings in settings.php file');        
        }
       
        // turn of error reporting
        mysqli_report(MYSQLI_REPORT_OFF);

        // connect to database
        @parent::__construct($db_server,$db_username,$db_password,$db_database,$db_port );

        // check if a connection established
        if( mysqli_connect_errno() ) {
            //throw new exception(mysqli_connect_error(), mysqli_connect_errno()); 
            die("Error connecting database. Please, check settings.php");
        } else {
          //if (!$mysqli->connect_error && $dbtest==true) {
            require "Lib/dbschemasetup.php";
            die("creating tables");
            if (!db_check($mysqli,$database)) db_schema_setup($mysqli,load_db_schema());
         // }
        }
    }

    public static function getInstance() {
        if( !self::$instance ) {
            self::$instance = new self(); 
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
