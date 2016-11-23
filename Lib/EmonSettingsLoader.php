<?php

Class EmonSettingsLoader
{
    const DEFAULT_SETTINGS_FILE = dirname(__FILE__)."/settings.php";

    public function __construct($settings_file)
    {
        $this->settings_file = (isset($settings_file) ? $settings_file : self::DEFAULT_SETTINGS_FILE);
        $this->log = new EmonLogger(__FILE__);
    }


    private function e_load() {

        $prev_defined_vars = get_defined_vars();
        require_once $this->settings_file;
        $now_defined_vars = get_defined_vars();

        $new_vars = array_diff($now_defined_vars, $prev_defined_vars);

        for($i = 0; $i < count($new_vars); $i++){
            global $$newvars[$i];
        }
    }
}