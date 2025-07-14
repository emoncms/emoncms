<?php

/**
 * Translation Key Generator for JSON Internationalization
 * 
 * This script scans PHP modules for translation function calls (tr() and ctx_tr()) 
 * and generates JSON language files containing all discovered translation keys.
 * It preserves existing translations while adding new keys and maintaining proper ordering.
 * 
 * Usage: php gen_locale.php [language_code]
 * Example: php gen_locale.php fr_FR
 * 
 * The script will:
 * - Scan all modules in the Modules/ directory
 * - Extract translation keys from tr() and ctx_tr() function calls
 * - Generate/update JSON language files in each module's locale/ directory
 * - Preserve existing translations and add new keys with default values
 */

// CLI Only
if (php_sapi_name() !== 'cli') {
    echo "This script is for CLI use only.\n";
    die;
}

// Change to PWD and then back two directories up
chdir(dirname(__DIR__, 2));

require 'scripts/translation/common.php';

/**
 * Extracts translation keys from PHP files in a directory
 * 
 * Scans all PHP files recursively and uses regex patterns to find:
 * - tr("text") function calls for simple translations
 * - ctx_tr("context", "text") function calls for contextual translations
 * 
 * @param string $directory The directory path to scan for PHP files
 * @return array Associative array with 'tr_keys' and 'ctx_keys' containing extracted translation strings
 */
function extractTranslationKeys($directory) {
    $keys = [];
    $ctx_keys = [];

    // Recursively iterate through all files in the directory
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            
            // Regex explanation for tr() calls:
            // \btr\s* - word boundary + 'tr' + optional whitespace
            // \(\s* - opening parenthesis + optional whitespace
            // ([\'\"]) - capture group 1: opening quote (single or double)
            // ((?:[^\\\\]|\\\\.)*?) - capture group 2: non-greedy match of text, handling escaped characters
            // \1 - backreference to the same quote type as opening
            // \s*\) - optional whitespace + closing parenthesis
            preg_match_all('/\btr\s*\(\s*([\'"])((?:[^\\\\]|\\\\.)*?)\1\s*\)/', $content, $trMatches);
            
            // Regex explanation for ctx_tr() calls:
            // Similar to above but matches two string parameters separated by comma
            // First parameter is context, second is the translatable text
            preg_match_all('/\bctx_tr\s*\(\s*([\'"])((?:[^\\\\]|\\\\.)*?)\1\s*,\s*([\'"])((?:[^\\\\]|\\\\.)*?)\3\s*\)/', $content, $ctxMatches);
            
            // Add tr() matches - index 2 contains the captured text content
            foreach ($trMatches[2] as $key) {
                $keys[] = stripcslashes($key); // Remove escape characters
            }
            
            // Add ctx_tr() matches
            // Index 2 = context, Index 4 = translatable text
            for ($i = 0; $i < count($ctxMatches[2]); $i++) {
                $context = stripcslashes($ctxMatches[2][$i]);
                $text = stripcslashes($ctxMatches[4][$i]);

                // Group contextual translations by their context
                if (!isset($ctx_keys[$context])) {
                    $ctx_keys[$context] = [];
                }
                $ctx_keys[$context][] = $text;
            }
        }
    }

    return array(
        'tr_keys' => array_unique($keys), // Remove duplicates from simple translations
        'ctx_keys' => array_map('array_unique', $ctx_keys) // Remove duplicates from each context group
    );
}

/**
 * Generates or updates a JSON language file with translation keys
 * 
 * This function preserves existing translations while adding new keys.
 * The order of keys is maintained: existing keys first (in their original order),
 * then new keys are appended.
 * 
 * @param array $keys Array of translation keys to include in the language file
 * @param string $outputFile Path to the JSON file to create or update
 */
function generateLanguageFile($keys, $outputFile) {
    // Load existing translations if the file exists
    $existing = [];
    if (file_exists($outputFile)) {
        $json = file_get_contents($outputFile);
        $existing = json_decode($json, true);
        if (!is_array($existing)) $existing = []; // Handle malformed JSON
    }

    $langArray = [];
    $deletedKeys = []; // Track keys that are no longer in source code

    // Strategy 1: Preserve order of existing translations
    // First, add existing keys in their original order (only if they're still needed)
    foreach ($existing as $key => $value) {
        if (in_array($key, $keys)) {
            $langArray[$key] = $value; // Keep existing translation
        } else {
            // Key exists in translation file but not in source code anymore
            $deletedKeys[$key] = $value;
        }
    }
    
    // Then, add new keys that weren't in the existing file
    foreach ($keys as $key) {
        if (!isset($langArray[$key])) {
            $langArray[$key] = $key; // Default value is the key itself
        }
    }

    /*
    // Alternative Strategy 2: Order keys as they appear in source files
    // This approach would order keys by their appearance in the scanned files
    foreach ($keys as $key) {
        if (isset($existing[$key]) && $existing[$key] !== '') {
            $langArray[$key] = $existing[$key]; // Preserve existing translation
        } else {
            $langArray[$key] = $key; // Default to key as value
        }
    }*/

    // Convert empty array to object for consistent JSON structure (matches po2json behavior)
    if (empty($langArray)) {
        $langArray = new stdClass();
    }

    // Use same JSON encoding flags as po2json for consistency
    // JSON_PRETTY_PRINT: Human-readable formatting
    // JSON_UNESCAPED_UNICODE: Keep Unicode characters unescaped
    $content = json_encode($langArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($outputFile, $content);

    // Write deleted keys to a separate file if any exist
    if (!empty($deletedKeys)) {
        $deletedFile = str_replace('.json', '_removed.json', $outputFile);
        $deletedContent = json_encode($deletedKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($deletedFile, $deletedContent);
    }
}

// Parse command line arguments
// Get language code from first argument, default to 'en_GB'
$lang = isset($argv[1]) ? $argv[1] : 'en_GB';
if ($lang == 'en') $lang = 'en_GB'; // Normalize 'en' to 'en_GB'

// Skip generation for default language (en_GB is the source language)
if ($lang == 'en_GB') {
    echo "No need to generate en_GB language file, it is the default.\n";
    exit(0);
}


$translationPaths = getTranslationPaths();


if ($lang == "all") {
    $availableLanguages = getAvailableLanguages($translationPaths);
} else {
    $availableLanguages = [$lang]; // Only generate for specified language
}

foreach ($availableLanguages as $lang) {
    foreach ($translationPaths as $module=>$modulePath) {

        echo "Processing: $modulePath\n";

        $result = extractTranslationKeys($modulePath);

        $keys = $result['tr_keys'];
        $ctx_keys = $result['ctx_keys'];

        // Handle contextual translations
        // Convention: context should be "{module_name}_messages" for module-specific messages
        $messages_context = $module . "_messages";
        if (isset($ctx_keys[$messages_context])) {
            // Merge contextual translations into main keys array
            foreach ($ctx_keys[$messages_context] as $msgKey) {
                if (!in_array($msgKey, $keys)) {
                    $keys[] = $msgKey;
                }
            }
        }
        
        // Ensure all keys are unique after merging
        $keys = array_unique($keys);

        // Validation: Check for unexpected contexts
        // All contexts should either match the module name or follow the "{module}_messages" pattern
        foreach ($ctx_keys as $context => $texts) {
            if ($context !== $messages_context && $context !== $module) {
                echo "Warning: Found context '$context' in module '$module' that does not match expected patterns.\n";
                echo "Expected: '$module' or '$messages_context'\n";
                // Note: This is a warning, not a fatal error, in case there are legitimate edge cases
            }
        }
        
        echo "Found " . count($keys) . " translation keys in $module\n";
        
        // Debug: Uncomment to see all extracted keys
        // foreach ($keys as $key) {
        //     echo "- $key\n";
        // }

        // Generate the language file for this module
        $outputFile = $modulePath . DIRECTORY_SEPARATOR . "locale" . DIRECTORY_SEPARATOR . $lang . '.json';
        echo "Generating language file: $outputFile\n";

        generateLanguageFile($keys, $outputFile);
    }
}