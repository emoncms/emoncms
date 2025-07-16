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

$langProgressCount = [];
foreach ($availableLanguages as $lang) {
    $langProgressCount[$lang] = array(
        'total' => 0,
        'translated' => 0,
        'removed' => 0,
        'modules' => []
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


        // Store module information
        $langProgressCount[$lang]['modules'][$module] = array(
            'total' => count($translations),
            'translated' => $translatedCount,
            'removed' => $removedCount
        );
    }
}


$show_detailed = false;
$selected_language = null;

// Parse command line arguments
for ($i = 1; $i < count($argv); $i++) {
    if ($argv[$i] === '--detailed') {
        $show_detailed = true;
    } elseif (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $argv[$i])) {
        $selected_language = $argv[$i];
    }
}

// Order by translated count descending
uasort($langProgressCount, function($a, $b) {
    return $b['translated'] <=> $a['translated'];
});

echo "Translation progress:\n";
foreach ($langProgressCount as $lang => $progress) {

    // If a specific language is selected, skip others
    if ($selected_language && $lang !== $selected_language) {
        continue;
    }

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

    if ($show_detailed && !empty($progress['modules'])) {

        // Sort modules by translated count descending
        uasort($progress['modules'], function($a, $b) {
            $percentageA = $a['total'] > 0 ? ($a['translated'] / $a['total']) * 100 : 0;
            $percentageB = $b['total'] > 0 ? ($b['translated'] / $b['total']) * 100 : 0;
            return $percentageB <=> $percentageA;
        });

        foreach ($progress['modules'] as $module => $moduleProgress) {
            printf("    - %-20s %3d%% (%4d/%-4d) Removed: %3d\n", 
                $module, 
                $moduleProgress['total'] > 0 ? round(($moduleProgress['translated'] / $moduleProgress['total']) * 100, 0) : 0,
                $moduleProgress['translated'], 
                $moduleProgress['total'], 
                $moduleProgress['removed']
            );
        }
    }
}

// Create simple JSON output file

// Remove module information for the JSON output
foreach ($langProgressCount as $lang => $progress) {
    unset($langProgressCount[$lang]['modules']);
}

$outputFile = 'Lib/translation_status.json';
file_put_contents($outputFile, json_encode($langProgressCount, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
// Output the path to the JSON file
echo "Translation progress saved to: $outputFile\n";
