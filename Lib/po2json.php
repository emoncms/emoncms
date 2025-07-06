<?php

function po2json($input, $output) 
{
    $lines = file($input, FILE_IGNORE_NEW_LINES);
    $result = array();
    $msgid = '';
    $msgstr = '';
    $state = null;

    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        if (strpos($line, 'msgid ') === 0) {
            // Save previous entry if complete
            if ($state === 'msgstr' && $msgid !== '' && $msgstr !== '') {
                if (isset($result[$msgid])) {
                    die ("Error: Duplicate msgid found: $msgid\n");
                }
                $result[$msgid] = $msgstr;
            }
            
            // Start new msgid
            if (preg_match('/^msgid\s+"(.*)"$/', $line, $matches)) {
                $msgid = stripcslashes($matches[1]);
            } else {
                $msgid = '';
            }
            $msgstr = '';
            $state = 'msgid';
            
        } elseif (strpos($line, 'msgstr ') === 0) {
            // Start msgstr
            if (preg_match('/^msgstr\s+"(.*)"$/', $line, $matches)) {
                $msgstr = stripcslashes($matches[1]);
            } else {
                $msgstr = '';
            }
            $state = 'msgstr';
            
        } elseif (preg_match('/^"(.*)"$/', $line, $matches)) {
            // Continuation line
            $content = stripcslashes($matches[1]);
            if ($state === 'msgid') {
                $msgid .= $content;
            } elseif ($state === 'msgstr') {
                $msgstr .= $content;
            }
        }
    }

    // Save the last entry
    if ($state === 'msgstr' && $msgid !== '' && $msgstr !== '') {
        if (isset($result[$msgid])) {
            die ("Error: Duplicate msgid found: $msgid\n");
        }
        $result[$msgid] = $msgstr;
    }

    // convert empty array to associative array
    if (empty($result)) {
        $result = new stdClass();
    }

    file_put_contents($output, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$search_paths = [
    'Modules/*/locale/*/LC_MESSAGES/*.po',
    'Theme/locale/*/LC_MESSAGES/*.po',
    'Lib/locale/*/LC_MESSAGES/*.po'
];

foreach ($search_paths as $pattern) {
    foreach (glob($pattern) as $po_file) {
        if (preg_match('#^(Modules/([^/]+)/locale|Theme/locale|Lib/locale)/([^/]+)/LC_MESSAGES/[^/]+\.po$#', $po_file, $matches)) {
            $locale_dir = $matches[1];
            $lang = $matches[3];
            $json_file = "$locale_dir/$lang.json";
            echo " - $po_file -> $json_file\n";
            po2json($po_file, $json_file);
        } else {
            echo "Could not parse: $po_file\n";
        }
    }
}