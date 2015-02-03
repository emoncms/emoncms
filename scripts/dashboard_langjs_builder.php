<?php
echo  "//START";
$dir='../Modules/dashboard';

$filejs=array();

function get_js_file($dir){
    global $filejs;
    $dirs = array_diff( scandir( $dir ), Array( ".", ".." ) );
    foreach( $dirs as $d ){
        if( is_dir($dir."/".$d)  ) get_js_file( $dir."/".$d);
        else if (pathinfo($d, PATHINFO_EXTENSION)=='js') $filejs[]=$dir."/".$d;
    }
    //return $dir_array;
}

function extract_translation($filejs){
  $translation=array();
  foreach ($filejs as $file){
   $lines = explode("\n", file_get_contents($file));
   $tr=array();
   foreach ($lines as $line){ 
     $pos = strpos($line, '_Tr(');
      if ($pos !== false) {
          $r = explode('_Tr("', $line);
          unset($r[0]);
          foreach ($r as $key=>$val){
            $rr= explode('")', $val);
            $tr[]=$rr[0];
          }
      }
   }
   $tr= array_unique($tr);
   if (count($tr)>0) {
    $a=explode('/', $file);
    $name=array_pop($a);
    natcasesort($tr);
    $translation[$name] = $tr;
   }
  }
  return $translation;
}

get_js_file($dir); 
//echo "<pre>".print_r($filejs,true)."</pre>";

 $translation= extract_translation($filejs);

// echo "<pre>".print_r($translation,true)."</pre>";
 foreach ($translation as $file=>$tr){
  echo "\n// ".$file."\n";
   foreach ($tr as $t){
     echo "LANG_JS[\"$t\"] = '<?php echo addslashes(_(\"$t\")); ?>';\n";
   }
 }
?>
//END 