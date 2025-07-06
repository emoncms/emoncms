<?php

function extractTranslationKeys($directory) {
    $keys = [];
    $ctx_keys = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            
            // Match tr("text") and tr('text') - improved regex to handle quotes better
            preg_match_all('/\btr\s*\(\s*([\'"])((?:[^\\\\]|\\\\.)*?)\1\s*\)/', $content, $trMatches);
            
            // Match ctx_tr("context", "text") and ctx_tr('context', 'text') - fixed typo and improved regex
            preg_match_all('/\bctx_tr\s*\(\s*([\'"])((?:[^\\\\]|\\\\.)*?)\1\s*,\s*([\'"])((?:[^\\\\]|\\\\.)*?)\3\s*\)/', $content, $ctxMatches);
            
            // Add tr() matches
            foreach ($trMatches[2] as $key) {
                $keys[] = stripcslashes($key);
            }
            
            // Add ctx_tr() matches (fourth parameter is the text)
            for ($i = 0; $i < count($ctxMatches[2]); $i++) {
                $context = stripcslashes($ctxMatches[2][$i]);
                $text = stripcslashes($ctxMatches[4][$i]);

                if (!isset($ctx_keys[$context])) {
                    $ctx_keys[$context] = [];
                }
                $ctx_keys[$context][] = $text;
            }
        }
    }

    return array(
        'tr_keys' => array_unique($keys),
        'ctx_keys' => array_map('array_unique', $ctx_keys)
    );
}

function generateLanguageFile($keys, $outputFile) {
    // Load existing translations if the file exists
    $existing = [];
    if (file_exists($outputFile)) {
        $json = file_get_contents($outputFile);
        $existing = json_decode($json, true);
        if (!is_array($existing)) $existing = [];
    }

    $langArray = [];
    foreach ($keys as $key) {
        if (isset($existing[$key]) && $existing[$key] !== '') {
            $langArray[$key] = $existing[$key];
        } else {
            $langArray[$key] = $key;
        }
    }

    // Convert empty array to associative array (consistent with po2json)
    if (empty($langArray)) {
        $langArray = new stdClass();
    }

    // Use same JSON encoding flags as po2json
    $content = json_encode($langArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($outputFile, $content);
}

// get lang from first arg, default to 'en_GB'
$lang = isset($argv[1]) ? $argv[1] : 'en_GB';
if ($lang == 'en') $lang = 'en_GB'; // Default to en_GB if 'en' is provided
if ($lang == 'en_GB') {
    echo "No need to generate en_GB language file, it is the default.\n";
    exit(0);
}

$modulesDir = 'Modules';
$modules = scandir($modulesDir);

foreach ($modules as $module) {
    if ($module === '.' || $module === '..') continue;
    $modulePath = $modulesDir . DIRECTORY_SEPARATOR . $module;
    $localePath = $modulePath . DIRECTORY_SEPARATOR . 'locale';
    $viewsPath = $modulePath; // Adjust if you want a subdirectory like 'views'

    if (is_dir($modulePath) && is_dir($localePath)) {
        echo "Processing module: $module\n";
        $result = extractTranslationKeys($viewsPath);

        $keys = $result['tr_keys'];
        $ctx_keys = $result['ctx_keys'];

        $messages_context = $module . "_messages";
        if (isset($ctx_keys[$messages_context])) {
            foreach ($ctx_keys[$messages_context] as $msgKey) {
                if (!in_array($msgKey, $keys)) {
                    $keys[] = $msgKey;
                }
            }
        }
        // Make sure the keys are unique
        $keys = array_unique($keys);

        // If we find another context that does not match the module name, exit with error
        // Not sure if this will ever happen?
        foreach ($ctx_keys as $context => $texts) {
            if ($context !== $messages_context && $context !== $module) {
                echo "Error: Found context '$context' in module '$module' that does not match the module name or messages context.\n";
                // die;
            }
        }

        
        echo "Found " . count($keys) . " translation keys in $module:\n";
        foreach ($keys as $key) {
            //echo "- $key\n";
        }

        // Create target file names
        $outputFile = $localePath . DIRECTORY_SEPARATOR . $lang . '.json';
        echo "Generating language file: $outputFile\n";

        generateLanguageFile($keys, $outputFile);


        /*

        foreach ($ctx_keys as $context => $texts) {
            echo "- Context '$context' has " . count($texts) . " texts:\n";
            foreach ($texts as $text) {
                //echo "  - $text\n";
            }
        }*/

        // Generate language files in the module's locale directory
        //generateLanguageFile($keys, $localePath . '/en.json');
        //echo "Language file generated at $localePath/en.json\n\n";
    }
}