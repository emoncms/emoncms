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
            
            // Match tr("text") and tr('text')
            preg_match_all('/\btr\s*\(\s*[\'"]([^\'"]*)[\'"]/', $content, $trMatches);
            
            // Match ctx_tr("context", "text") and ctx_tr('context', 'text')
            preg_match_all('/\bctx_tr\s*\(\s*[\'"]([^\'"]*)[\'"]s*,\s*[\'"]([^\'"]*)[\'"]/', $content, $ctxMatches);
            // Add tr() matches
            foreach ($trMatches[1] as $key) {
                $keys[] = trim($key);
            }
            
            // Add ctx_tr() matches (second parameter)
            for ($i = 0; $i < count($ctxMatches[1]); $i++) {
                $context = trim($ctxMatches[1][$i]);
                $text = trim($ctxMatches[2][$i]);

                if (!isset($ctx_keys[$context])) {
                    $ctx_keys[$context] = [];
                }
                $ctx_keys[$context][] = $text;
            }
            
            echo "Processed: " . $file->getPathname() . "\n";
        }
    }
    
    // return array_unique($keys);

    return array(
        'tr_keys' => array_unique($keys),
        'ctx_keys' => array_map('array_unique', $ctx_keys)
    );
}

function generateLanguageFile($keys, $outputFile) {
    $langArray = [];
    foreach ($keys as $key) {
        $langArray[$key] = $key;
    }
    $content = json_encode($langArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    file_put_contents($outputFile, $content);
}

// Usage
$viewsDirectory = '/var/www/emoncms/Theme'; // Change this to your views directory
$result = extractTranslationKeys($viewsDirectory);

$keys = $result['tr_keys'];
$ctx_keys = $result['ctx_keys'];

echo "Found " . count($keys) . " translation keys:\n";
foreach ($keys as $key) {
    echo "- $key\n";
}

echo "Found " . count($ctx_keys) . " context translation keys:\n";
foreach ($ctx_keys as $context => $texts) {
    echo "- Context '$context':\n";
    foreach ($texts as $text) {
        echo "  - $text\n";
    }
}

die;

// Generate language files
generateLanguageFile($keys, 'lang/en.json');

echo "\nLanguage files generated!\n";