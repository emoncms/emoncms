<?php

// Limit to CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

// Change to the directory where the script is located
$scriptDir = dirname(__FILE__);
if (!chdir($scriptDir)) {
    die("Failed to change directory to script location: $scriptDir");
}

// Run this to combine into single js

$files = array(
  "jquery.flot.min.js",
  "jquery.flot.selection.min.js",
  "jquery.flot.touch.min.js",
  "jquery.flot.time.min.js",
  "date.format.min.js",
  "jquery.flot.canvas.min.js",
  "plugin/saveAsImage/lib/base64.js",
  "plugin/saveAsImage/lib/canvas2image.js",
  "plugin/saveAsImage/jquery.flot.saveAsImage.js"
);

$merged = "";

foreach ($files as $file) {
    print $file."\n";
    $merged .= file_get_contents($file)."\n";
}

$fh = fopen("jquery.flot.merged.js","w");
fwrite($fh,trim($merged));
fclose($fh);
