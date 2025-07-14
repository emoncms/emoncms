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
// remove 'en_GB' from the list of available languages
$availableLanguages = array_filter($availableLanguages, function($lang) {
    return $lang !== 'en_GB';
});


foreach ($translationPaths as $module=>$modulePath) {

    // Load translation json file e.g fr_FR.json and fr_FR_removed.json
    $localePath = $modulePath . DIRECTORY_SEPARATOR . 'locale';

    foreach ($availableLanguages as $lang) {
        
        // Try loading the removed translation file
        $removedTranslationFile = $localePath . DIRECTORY_SEPARATOR . $lang . '_removed.json';
        if (file_exists($removedTranslationFile)) {
            $removedTranslations = json_decode(file_get_contents($removedTranslationFile), true);

            // Remove any translations where key == value or value is empty
            foreach ($removedTranslations as $key => $value) {
                if ($key === $value || empty($value)) {
                    unset($removedTranslations[$key]);
                }
            }
            
            // Save the cleaned up removed translations back to the file
            file_put_contents($removedTranslationFile, json_encode($removedTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

    }
}