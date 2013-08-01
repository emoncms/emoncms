<?php

/*
   Timestore php wrapper is released under the GNU Affero General Public License.
   See LICENSE for full text
   Author: Trystan Lea https://github.com/TrystanLea
   timestore: Mike Stirling http://www.livesense.co.uk/timestore
*/

class Timestore
{

  private $adminkey = false;
  private $host = '127.0.0.1:8080';

  public function __construct($adminkey)
  {
    $this->adminkey = $adminkey;
  }

  public function create_node($node_id,$interval)
  {
    $array = array(
      'interval'=>$interval,
      'decimation'=>array(20, 6, 6, 4, 7),
      'metrics'=>array(array('pad_mode'=>0,'downsample_mode'=>0))
    );
    return $this->do_request('PUT',"/nodes/$node_id",$array,false,$this->adminkey);
  }

  public function set_key($node_id,$key_name,$keyval)
  {
    $array = array('key'=>base64_encode($keyval));
    return $this->do_request('PUT',"/nodes/$node_id/keys/$key_name",$array,false,$this->adminkey);
  }

  public function get_key($node_id,$key_name)
  { 
    return $this->do_request('GET',"/nodes/$node_id/keys/$key_name",false,false,$this->adminkey);
  }

  public function post_values($node_id,$timestamp,$values,$key)
  {
    $array = array("timestamp"=>$timestamp, "values"=>$values);
    return $this->do_request('POST',"/nodes/$node_id/values",$array,false,$key);
  }

  public function get_series($node_id,$series,$npoints,$start,$end,$key)
  { 
    $args = array(
      "npoints"=>$npoints,
      "start"=>$start,
      "end"=>$end
    );
    return $this->do_request('GET',"/nodes/$node_id/series/$series",false,$args,$key);
  }

  public function get_nodes($key)
  { 
    return $this->do_request('GET',"/nodes",false,false,$key);
  }

  private function do_request($method,$path,$req,$args,$key)
  {
    $reqbody = '';
    if ($req) $reqbody = json_encode($req);

    $argstr = '';
    $urlstr = '';
    if ($args) {
      $urlstr = http_build_query($args);
      foreach ($args as $k => $v)
        $argstr .= "$k=$v\n";
    }

    $msg = $method."\n".$path."\n".$argstr.$reqbody;

    if ($args) $path .= "?".$urlstr;

    if ($key) $signature = base64_encode(hash_hmac('sha256',$msg,$key,true));

    $curl = curl_init($this->host.$path);
    if ($key) curl_setopt($curl, CURLOPT_HTTPHEADER,array('Signature:'.$signature));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    if ($req) curl_setopt($curl, CURLOPT_POSTFIELDS,$reqbody);
    $curl_response = curl_exec($curl);
    curl_close($curl); 

    return $curl_response;
  }

}

