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

$langProgressCount = [];
foreach ($availableLanguages as $lang) {
    $langProgressCount[$lang] = array(
        'total' => 0,
        'translated' => 0,
        'removed' => 0
    );
}

foreach ($translationPaths as $module=>$modulePath) {

    // Load translation json file e.g fr_FR.json and fr_FR_removed.json
    $localePath = $modulePath . DIRECTORY_SEPARATOR . 'locale';

    foreach ($availableLanguages as $lang) {
        // Try loading the main translation file
        $translationFile = $localePath . DIRECTORY_SEPARATOR . $lang . '.json';
        if (file_exists($translationFile)) {
            $translations = json_decode(file_get_contents($translationFile), true);
        } else {
            $translations = []; 
        }

        $langProgressCount[$lang]['total'] += count($translations);

        $translatedCount = 0;

        foreach ($translations as $key => $value) {
            // if key == value or value is empty, count as not translated
            if ($key === $value || empty($value)) {
                continue;
            }
            $translatedCount++;
        }
        $langProgressCount[$lang]['translated'] += $translatedCount;


        $removedCount = 0;
        // Try loading the removed translation file
        $removedTranslationFile = $localePath . DIRECTORY_SEPARATOR . $lang . '_removed.json';
        if (file_exists($removedTranslationFile)) {
            $removedTranslations = json_decode(file_get_contents($removedTranslationFile), true);
            $removedCount = count($removedTranslations);
            $langProgressCount[$lang]['removed'] += $removedCount;
        }

    }
}

// Order by translated count descending
uasort($langProgressCount, function($a, $b) {
    return $b['translated'] <=> $a['translated'];
});

echo "Translation progress:\n";
foreach ($langProgressCount as $lang => $progress) {
    $total = $progress['total'];
    $translated = $progress['translated'];
    $removed = $progress['removed'];

    $prc_translated = $total > 0 ? round(($translated / $total) * 100, 0) : 0;

    printf("- %-8s %3d%% (%4d/%-4d) Removed: %3d\n", 
        $lang, 
        $prc_translated, 
        $translated, 
        $total, 
        $removed
    );
}


