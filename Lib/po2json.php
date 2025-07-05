<?php

function po2json($input, $output) 
{
    $lines = file($input);
    $result = array();
    $msgid = null;
    $msgstr = null;
    $state = null;

    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, 'msgid "') === 0) {
            $msgid = stripcslashes(substr($line, 7, -1));
            $state = 'msgid';
        } elseif (strpos($line, 'msgstr "') === 0) {
            $msgstr = stripcslashes(substr($line, 8, -1));
            $state = 'msgstr';
            if ($msgid !== null && $msgid !== '' && $msgstr !== null && $msgstr !== '') {
                if (isset($result[$msgid])) {
                    die ("Error: Duplicate msgid found: $msgid\n");
                }
                $result[$msgid] = $msgstr;
                $msgid = null;
                $msgstr = null;
            }
        }
        // Ignore comments and other lines
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