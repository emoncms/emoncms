<?php

// Run this to combine into single js
die;

$files = array(
  "jquery.flot.min.js",
  "jquery.flot.selection.min.js",
  "jquery.flot.touch.min.js",
  "jquery.flot.time.min.js",
  "date.format.min.js",
  "jquery.flot.canvas.js",
  "plugin/saveAsImage/lib/base64.js",
  "plugin/saveAsImage/lib/canvas2image.js",
  "plugin/saveAsImage/jquery.flot.saveAsImage.js"
);

$merged = "";

foreach ($files as $file) {
    print $file."\n";
    $merged .= file_get_contents($file);
}

$fh = fopen("jquery.flot.merged.js","w");
fwrite($fh,$merged);
fclose($fh);
