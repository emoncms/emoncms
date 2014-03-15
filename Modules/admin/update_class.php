<?php

/**
 * Class contains a list of updates that are created as schema changes
 *
 * These updates are loaded and ran from the admin_controller() method
 */
class Update
{
    private $mysqli;

/**
 * Map updates and descriptions
 *
 * Having these at the top of the class makes it much easier to see what is going on.
 */
    protected $_updateInfo = array(
        'u0001' => array(
            'title' => 'Changed input naming convention',
            'description'=>'The input naming convention has been changed from the <b>node10_1</b> convention to <b>1</b> with the nodeid in the nodeid field. The following list, lists all the input names in your database that the script can update automatically:',
        ),
        'u0002' => array(
            'title' => 'Inputs are only recorded if used',
            'description'=>'To improve performance inputs are only recorded if used as part of / x + - by input processes.',
        ),
        'u0003' => array(
            'title' => 'Username format change',
            'description' => 'All . characters have been removed from usernames as the . character conflicts with the new routing implementation where emoncms thinks that the part after the . is the format the page should be in.',
        ),
        'u0004' => array(
            'title' => 'Field name change',
            'description' => 'Changed to more generic field name called engine rather than timestore specific',
        ),
    );

/**
 * Constructor
 *
 * @param Object $mysql the db object
 */
    public function __construct($mysqli)
    {
            $this->mysqli = $mysqli;
    }

/**
 * Get a list of methods that can be run to update the system
 *
 * This is all methods in the format uXXXX where XXXX is a integer from 0000 -> 9999
 * 
 * @return array
 */
    public function methodsToRun() 
    {
        $return = array();
        foreach (get_class_methods($this) as $method) 
        {
            if (preg_match('/^u[0-9]{4}$/', $method)) 
            {
                $return[] = $method;
            }
        }

        return $return;
    }

/**
 * Update 1
 *
 * @param boolean $apply true to apply the change, false to do a dry run and see what will change
 *
 * @return array
 */
    function u0001($apply)
    {
        $operations = array();
        $result = $this->mysqli->query('SELECT userid, id, name, nodeid, time, processList FROM input');
        while ($result && $row = $result->fetch_object()) 
        {
            preg_match('/^node/', $row->name, $node_matches);
            if ($node_matches) 
            {
                $out = preg_replace('/^node/', '', $row->name);
                $out = explode('_', $out);

                if (is_numeric($out[0])) 
                {
                    $nodeid = (int)$out[0];
                    $name = is_numeric($out[1]) ? (int)$out[1] : $out[1];

                    $inputexists = $this->mysqli->query(sprintf('SELECT id FROM input WHERE `userid` = "%s" AND `nodeid` = "%s" AND `name` = "%s"', $row->userid, $nodeid, $name));
                    if (!$inputexists->num_rows) 
                    {
                        $sql = sprintf('UPDATE input SET `name` = "%s", `nodeid` = "%s" WHERE `id` = "%s"', $name, $nodeid, $row->id);
                        $operations[] = $sql;

                        if ($apply) 
                        {
                            $this->mysqli->query($sql);    
                        }
                    }
                }
            }

            preg_match('/^csv/', $row->name, $csv_matches);
            if (!$csv_matches || $row->nodeid != 0) 
            {
                continue;
            }

            $name = preg_replace('/^csv/', '', $row->name);
            $nodeid = 0;

            $inputexists = $this->mysqli->query(sprintf('SELECT id FROM input WHERE `userid` = "%s" AND `nodeid` = "%s" AND `name` = "%s"', $row->userid, $nodeid, $name));
            if ($inputexists->num_rows) 
            {
                continue;
            }
            
            $sql = sprintf('UPDATE input SET `name` = "%s",`nodeid` = "%s" WHERE `id` = "%s"', $name, $nodeid, $row->id);
            $operations[] = $sql;
            if ($apply) 
            {
                $this->mysqli->query($sql);
            }
        }

        return $this->_updateInfo[__FUNCTION__] + array('operations' => $operations);
    }

/**
 * Update 2
 *
 * @param boolean $apply true to apply the change, false to do a dry run and see what will change
 *
 * @return array
 */
    function u0002($apply)
    {
        require 'Modules/input/process_model.php';
        $process = new Process(null, null, null);
        $process_list = $process->get_process_list();

        $operations = array();
        $result = $this->mysqli->query('SELECT userid, id, processList, time, record FROM input');
        while ($result && $row = $result->fetch_object()) 
        {
            if (!$row->processList) 
            {
                continue;
            }

            foreach (explode(',', $row->processList) as $pair) 
            {
                $inputprocess = explode(':', $pair);
                if (!isset($inputprocess[1]) || $process_list[$inputprocess[0]][1] != 1) 
                {
                    continue;
                }

                $inputexists = $this->mysqli->query(sprintf('SELECT record FROM input WHERE `id` = "%s"', $inputprocess[1]));
                if ($inputexists->fetch_object()->record) 
                {
                    continue;
                }

                $sql = sprintf('UPDATE input SET `record` = "%s" WHERE `id` = "%s"', 1, $inputprocess[1]);
                $operations[] = $sql;
                if ($apply) 
                {
                    $this->mysqli->query($sql);    
                }
            }
        }

        return $this->_updateInfo[__FUNCTION__] + array('operations' => $operations);
    }

/**
 * Update 3
 *
 * @param boolean $apply true to apply the change, false to do a dry run and see what will change
 *
 * @return array
 */
    function u0003($apply)
    {
        $operations = $data = $data2 = array();
        $result = $this->mysqli->query('SELECT id, username FROM users');

        while ($result && $row = $result->fetch_object()) 
        {
            $usernameout = preg_replace('/[^\w\s-]/', '', $row->username);
            if ($usernameout != $row->username) 
            {
                $userexists = $this->mysqli->query(sprintf('SELECT id FROM users WHERE `username` = "%s"', $usernameout));
                if (!$userexists->num_rows) 
                {
                    $operations[] = sprintf('Change username from "%s" to "%s"', $row->username, $usernameout);
                    if ($apply) 
                    {
                        $this->mysqli->query(sprintf('UPDATE users SET `username`="%s" WHERE `id`="%s"', $usernameout, $row->id));
                    }
                } else 
                {
                    $operations[] = sprintf('Cannot change username from "%s" to "%s" as it already exists, please fix manually.', $row->username, $usernameout);
                }

            }
        }
        return $this->_updateInfo[__FUNCTION__] + array('operations' => $operations);
    }

/**
 * Update 4
 *
 * @param boolean $apply true to apply the change, false to do a dry run and see what will change
 *
 * @return array
 */
    function u0004($apply)
    {
        $operations = array();
        $result = $this->mysqli->query('Show columns from feeds like "timestore"');
        $row = $result->fetch_array();

        if ($row) 
        {
            $result = $this->mysqli->query('SELECT id, timestore, engine FROM feeds');
            while ($result && $row = $result->fetch_object()) 
            {
                $id = $row->id;
                $timestore = $row->timestore;

                if ($timestore == 1) 
                {
                    if ($row->engine == 0) 
                    {
                        $operations[] = sprintf('Set feed engine for feed %s to timestore', $id);
                    }
                    if ($apply) {
                        $this->mysqli->query(sprintf('UPDATE feeds SET `engine` = "%s" WHERE `id` = "%s"', 1, $id));
                    }
                }
            }
        }
        return $this->_updateInfo[__FUNCTION__] + array('operations' => $operations);
    }
}
