<?php

// CLI Only
if (php_sapi_name() !== 'cli') {
    echo "This script is for CLI use only.\n";
    die;
}

// Change to PWD and then back two directories up
chdir(dirname(__DIR__, 2));

require 'scripts/translation/common.php';
$translationPaths = getTranslationPaths();
$lang = "en_GB";

foreach ($translationPaths as $module=>$modulePath) {

    $localePath = $modulePath . DIRECTORY_SEPARATOR . 'locale';

    $filename = $localePath . DIRECTORY_SEPARATOR . $lang . '.json';
    if (file_exists($filename)) {
        if (unlink($filename)) {
            echo "Deleted: $filename\n";
        }
    }

    $filename = $localePath . DIRECTORY_SEPARATOR . $lang . '_removed.json';
    if (file_exists($filename)) {
        if (unlink($filename)) {
            echo "Deleted: $filename\n";
        }
    }
}