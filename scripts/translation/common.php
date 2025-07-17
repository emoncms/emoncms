<?php

// Function to fetch translation paths: Modules/module/locale, Theme/locale, Lib/locale
function getTranslationPaths() {
    $paths = [];
    
    // Modules: Modules/module/locale
    $modulesDir = 'Modules';
    if (is_dir($modulesDir)) {
        $modules = scandir($modulesDir);
        foreach ($modules as $module) {
            if ($module !== '.' && $module !== '..') {
                $modulePath = $modulesDir . DIRECTORY_SEPARATOR . $module;
                $modulePath = realpath($modulePath); // Resolve symlinks
                
                if ($modulePath !== false) { // Check if realpath succeeded
                    $localePath = $modulePath . DIRECTORY_SEPARATOR . 'locale';
                    if (is_dir($localePath)) {
                        $paths[$module] = $modulePath;
                    }
                }
            }
        }
    }

    $paths["theme"] = realpath('Theme');
    $paths["lib"] = realpath('Lib');

    return $paths;
}

// Get available languages
function getAvailableLanguages($translationPaths) {
    $languages = [];
    
    foreach ($translationPaths as $pathName => $path) {
        if ($path === false) continue; // Skip if realpath failed
        
        $localePath = $path . DIRECTORY_SEPARATOR . 'locale';
        if (!is_dir($localePath)) continue;
        
        $files = scandir($localePath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            // Match locale files like fr_FR.json or fr_FR_removed.json
            if (preg_match('/^([a-z]{2}_[A-Z]{2})(?:_removed)?\.json$/', $file, $matches)) {
                $localeCode = $matches[1];
                if (!in_array($localeCode, $languages)) {
                    $languages[] = $localeCode;
                }
            }
        }
    }
    
    // Sort the languages alphabetically
    sort($languages);
    
    return $languages;
}

