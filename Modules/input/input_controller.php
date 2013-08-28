<?php 
  /*
   All Emoncms code is released under the GNU Affero General Public License.
   See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
  */

  // no direct access
  defined('EMONCMS_EXEC') or die('Restricted access');

function input_controller()
{
  global $mysqli, $user, $session, $route;

  // There are no actions in the input module that can be performed with less than write privileges
  if (!$session['write']) return array('content'=>false);

  global $feed, $timestore_adminkey;
  $result = false;

  include "Modules/feed/feed_model.php";
  $feed = new Feed($mysqli,$timestore_adminkey);

  require "Modules/input/input_model.php"; // 295
  $input = new Input($mysqli,$feed);

  require "Modules/input/process_model.php"; // 886
  $process = new Process($mysqli,$input,$feed);

  if ($route->format == 'html')
  {
      if ($route->action == 'api') $result = view("Modules/input/Views/input_api.php", array());
      if ($route->action == 'node') $result =  view("Modules/input/Views/input_node.php", array());
      if ($route->action == 'process') 
      {
          $result = view("Modules/input/Views/process_list.php", 
          array(
              'inputid'=> intval(get('inputid')), 
              'processlist' => $process->get_process_list(),
              'inputlist' => $input->getlist($session['userid']),
              'feedlist'=> $feed->get_user_feeds($session['userid'],0)
          ));
      }
  }

  if ($route->format == 'json')
  {
      /*
        
        input/bulk.json?data=[[0,16,1137],[2,17,1437,3164],[4,19,1412,3077]] 

        The first number of each node is the time offset, so for the first node it is 0 which means the packet for the first node arrived at 0 seconds. The second node arrived at 2 seconds and 3rd 4 seconds. 

        The second number is the node id, this is the unqiue identifer for the wireless node. 

        All the numbers after the first two are data values. The first node here (node 16) has only once data value: 1137. 

      */

      if ($route->action == 'bulk')
      {
        $data = json_decode(get('data'));

        // We start by loading all user inputs in a single database call
        // The intention here is to minimize database calls as these are what takes time
        // We then construct an input object that is easily searchable against input 
        // that is recieved in the request and that contains the processList

        $userid = $session['userid'];
        $dbinputs = $input->get_inputs($userid);

        // In the next part we go through the recieved request and start by checking if the
        // recieved inputs exist against the input object. If not we create an input.

        $len = count($data);
        if ($len>0)
        {
          if (isset($data[$len-1][0])) 
          {
            $offset = (int) $data[$len-1][0];
            $start_time = time() - $offset;
     
            foreach ($data as $item)
            {
              if (count($item)>1)
              {
                $time = $start_time + (int) $item[0];
                $nodeid = $item[1];

                $tmp = array();
                for ($i=2; $i<count($item); $i++)
                {
                  $name = $i - 1;
                  $value = (float) $item[$i];
                  if (!isset($dbinputs[$nodeid][$name])) {
                    $input->create_input($userid, $nodeid, $name);
                    $dbinputs[$nodeid][$name] = true;
                  } else { 
                    if ($dbinputs[$nodeid][$name]['record']) $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                    if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
                  }
                  $result = 'ok';
                }
                foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
              }
            }
          }
        }
      }

      /*

      input/post.json?node=10&json={power1:100,power2:200,power3:300}
      input/post.json?node=10&csv=100,200,300

      */
			
      if ($route->action == 'post')
      {
        $dbinputs = $input->get_inputs($session['userid']);
        $nodeid = intval(get('node'));
        if ($nodeid>2000000000) $nodeid = 0;
        if (isset($_GET['time'])) $time = (int) $_GET['time']; else $time = time();

        if (isset($_GET['json']))
        {
          $json = preg_replace('/[^\w\s-.:,]/','',get('json'));
          $datapairs = explode(',', $json);

          $tmp = array();
          for ($i=0; $i<count($datapairs); $i++)
          {
            $keyvalue = explode(':', $datapairs[$i]);
            $name = $keyvalue[0];
            if ($name!='' && isset($keyvalue[1]))
            {
              $value = (float) $keyvalue[1];

              if (!isset($dbinputs[$nodeid][$name])) {
                $input->create_input($session['userid'], $nodeid, $name);
                $dbinputs[$nodeid][$name] = true;
              } else { 
                if ($dbinputs[$nodeid][$name]['record']) $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
              }
              $result = 'ok';
            }
          }
          foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
        }

        if (isset($_GET['csv']))
        {
          $csv = preg_replace('/[^0-9,.-]/','',get('csv')); 
          $csv = explode(',',$csv);

          $tmp = array();
          for ($i=0; $i<count($csv); $i++)
          {
            $name = $i+1;
            if ($csv[$i]!='') {
              $value = (float) $csv[$i];

              if (!isset($dbinputs[$nodeid][$name])) {
                $input->create_input($session['userid'], $nodeid, $name);
                $dbinputs[$nodeid][$name] = true;
              } else { 
                if ($dbinputs[$nodeid][$name]['record']) $input->set_timevalue($dbinputs[$nodeid][$name]['id'],$time,$value);
                if ($dbinputs[$nodeid][$name]['processList']) $tmp[] = array('value'=>$value,'processList'=>$dbinputs[$nodeid][$name]['processList']);
              }
              $result = 'ok';
            }
          }
          foreach ($tmp as $i) $process->input($time,$i['value'],$i['processList']);
        }
      }

      if ($route->action == "list") $result = $input->getlist($session['userid']);
      
      if ($route->action == "deletenode") $result = $input->delete_node($session['userid'],get("nodeid"));

      if (isset($_GET['inputid']) && $input->belongs_to_user($session['userid'],get("inputid")))
      {
          if ($route->action == "delete") $result = $input->delete($session['userid'],get("inputid"));

          if ($route->action == 'set') $result = $input->set_fields(get('inputid'),get('fields'));

          if ($route->action == "process")
          { 
              if ($route->subaction == "add") $result = $input->add_process($process,$session['userid'], get('inputid'), get('processid'), get('arg'), get('newfeedname'), get('newfeedinterval'));
              if ($route->subaction == "list") $result = $input->get_processlist_desc($process, get("inputid"));
              if ($route->subaction == "delete") $result = $input->delete_process(get("inputid"),get('processid'));
              if ($route->subaction == "move") $result = $input->move_process(get("inputid"),get('processid'),get('moveby'));
              if ($route->subaction == "reset") $result = $input->reset_process(get("inputid"));
          }
      }
  } 

  return array('content'=>$result);
}

?>
