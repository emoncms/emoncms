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

$availableLanguages = getAvailableLanguages($translationPaths);
echo "Available languages:\n";
foreach ($availableLanguages as $lang) {
    echo "- $lang\n";
}



foreach ($translationPaths as $module=>$modulePath) {

    echo "Processing: $modulePath\n";


}

