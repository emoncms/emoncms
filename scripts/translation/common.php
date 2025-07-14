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

    $paths["theme"] = 'Theme';
    $paths["lib"] = 'Lib';

    return $paths;
}