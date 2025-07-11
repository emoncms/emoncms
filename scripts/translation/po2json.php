<?php

/**
 * PO to JSON Translation Converter
 * 
 * This script converts GNU gettext PO (Portable Object) files to JSON format
 * for use in web applications. It searches for PO files in predefined module,
 * theme, and library directories and converts them to JSON files for each locale.
 * 
 * The script handles:
 * - Multi-line msgid and msgstr entries
 * - Escape sequence processing
 * - Duplicate msgid detection
 * - Automatic output file naming based on locale
 */

// CLI Only
if (php_sapi_name() !== 'cli') {
    echo "This script is for CLI use only.\n";
    die;
}

// Change to PWD and then back two directories up
chdir(dirname(__DIR__, 2));

/**
 * Convert a PO file to JSON format
 * 
 * Parses a GNU gettext PO file and converts it to a JSON object with
 * msgid as keys and msgstr as values. Handles multi-line entries and
 * escape sequences properly.
 * 
 * @param string $input  Path to the input PO file
 * @param string $output Path to the output JSON file
 * @throws Exception if duplicate msgids are found
 */
function po2json($input, $output) 
{
    // Read all lines from the PO file, removing newlines
    $lines = file($input, FILE_IGNORE_NEW_LINES);
    $result = array();
    $msgid = '';
    $msgstr = '';
    $state = null; // Tracks current parsing state: 'msgid' or 'msgstr'

    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments (lines starting with #)
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        // Process msgid lines
        if (strpos($line, 'msgid ') === 0) {
            // Save previous entry if we have a complete msgid/msgstr pair
            if ($state === 'msgstr' && $msgid !== '' && $msgstr !== '') {
                if (isset($result[$msgid])) {
                    die ("Error: Duplicate msgid found: $msgid\n");
                }
                $result[$msgid] = $msgstr;
            }
            
            // Extract the msgid content, handling quoted strings
            if (preg_match('/^msgid\s+"(.*)"$/', $line, $matches)) {
                $msgid = stripcslashes($matches[1]); // Process escape sequences
            } else {
                $msgid = ''; // Handle msgid with no quotes (empty string)
            }
            $msgstr = '';
            $state = 'msgid';
            
        } elseif (strpos($line, 'msgstr ') === 0) {
            // Process msgstr lines
            if (preg_match('/^msgstr\s+"(.*)"$/', $line, $matches)) {
                $msgstr = stripcslashes($matches[1]); // Process escape sequences
            } else {
                $msgstr = ''; // Handle msgstr with no quotes (empty string)
            }
            $state = 'msgstr';
            
        } elseif (preg_match('/^"(.*)"$/', $line, $matches)) {
            // Handle continuation lines (multi-line strings)
            $content = stripcslashes($matches[1]);
            if ($state === 'msgid') {
                $msgid .= $content; // Append to current msgid
            } elseif ($state === 'msgstr') {
                $msgstr .= $content; // Append to current msgstr
            }
        }
    }

    // Save the last entry if it's complete
    if ($state === 'msgstr' && $msgid !== '' && $msgstr !== '') {
        if (isset($result[$msgid])) {
            die ("Error: Duplicate msgid found: $msgid\n");
        }
        $result[$msgid] = $msgstr;
    }

    // Convert empty array to empty object for proper JSON formatting
    if (empty($result)) {
        $result = new stdClass();
    }

    // Write JSON file with pretty printing and Unicode support
    file_put_contents($output, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Define search patterns for PO files in different directory structures
$search_paths = [
    'Modules/*/locale/*/LC_MESSAGES/*.po',  // Module translations
    'Theme/locale/*/LC_MESSAGES/*.po',      // Theme translations
    'Lib/locale/*/LC_MESSAGES/*.po'        // Library translations
];

// Process all PO files found in the search paths
foreach ($search_paths as $pattern) {
    foreach (glob($pattern) as $po_file) {
        // Extract locale directory and language code from file path
        if (preg_match('#^(Modules/([^/]+)/locale|Theme/locale|Lib/locale)/([^/]+)/LC_MESSAGES/[^/]+\.po$#', $po_file, $matches)) {
            $locale_dir = $matches[1]; // Base locale directory
            $lang = $matches[3];       // Language code (e.g., 'en', 'fr', 'de')
            $json_file = "$locale_dir/$lang.json"; // Output JSON file path
            
            echo " - $po_file -> $json_file\n";
            po2json($po_file, $json_file);
        } else {
            echo "Could not parse: $po_file\n";
        }
    }
}