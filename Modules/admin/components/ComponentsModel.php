<?php
/*
    All Emoncms code is released under the GNU Affero General Public License.
    See COPYRIGHT.txt and LICENSE.txt.

    ---------------------------------------------------------------------
    Emoncms - open source energy visualisation
    Part of the OpenEnergyMonitor project:
    http://openenergymonitor.org
*/

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class ComponentsModel
{
    private $settings;
    private $redis;
    private $log;
    private $update_logfile;

    public function __construct($settings, $redis)
    {
        $this->settings = $settings;
        $this->redis = $redis;
        $this->log = new EmonLogger(__FILE__);
        $this->update_logfile = $settings['log']['location'] . "/update.log";
    }

    public function update_logfile()
    {
        return $this->update_logfile;
    }

    // -------------------------------------------------------------------------
    // Component listing
    // -------------------------------------------------------------------------

    public function components_available()
    {
        $localfile = $this->settings['openenergymonitor_dir'] . "/EmonScripts/components_available.json";
        if (file_exists($localfile)) {
            return json_decode(file_get_contents($localfile));
        } elseif ($response = @file_get_contents("https://raw.githubusercontent.com/openenergymonitor/EmonScripts/stable/components_available.json")) {
            return json_decode($response);
        } else {
            return array('success' => false, 'message' => "Can't get components available file");
        }
    }

    public function component_list($git_info = true)
    {
        $emoncms_path = substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], '/'));

        $components = array();

        // Emoncms core
        if (file_exists($emoncms_path . "/version.json")) {
            $json = json_decode(file_get_contents($emoncms_path . "/version.json"));
            if (isset($json->version) && $json->version != "") {
                $name = "emoncms";
                $components[$name] = array(
                    "name" => ucfirst(isset($json->name) ? $json->name : $name),
                    "version" => $json->version,
                    "path" => $emoncms_path,
                    "target_location" => isset($json->location) ? $json->location : $emoncms_path,
                    "branches_available" => isset($json->branches_available) ? $json->branches_available : array(),
                    "requires" => isset($json->requires) ? $json->requires : array()
                );
            }
        }

        foreach (array("$emoncms_path/Modules", $this->settings['emoncms_dir'] . "/modules", $this->settings['openenergymonitor_dir']) as $path) {
            $directories = glob("$path/*", GLOB_ONLYDIR);

            foreach ($directories as $module_fullpath) {
                if (!is_link($module_fullpath)) {
                    $fullpath_parts = explode("/", $module_fullpath);
                    $name = $fullpath_parts[count($fullpath_parts) - 1];

                    if (file_exists($module_fullpath . "/module.json")) {
                        $json = json_decode(file_get_contents($module_fullpath . "/module.json"));

                        if (isset($json->version) && $json->version != "") {
                            $components[$name] = array(
                                "name" => ucfirst(isset($json->name) ? $json->name : $name),
                                "version" => $json->version,
                                "path" => $module_fullpath,
                                "target_location" => isset($json->location) ? $json->location : $path,
                                "branches_available" => isset($json->branches_available) ? $json->branches_available : array(),
                                "requires" => isset($json->requires) ? $json->requires : array()
                            );
                        }
                    }
                }
            }
        }

        if ($git_info) {
            foreach ($components as $name => $component) {
                $path = $component["path"];
                $components[$name]["describe"] = $this->git_describe($path);
                $components[$name]["branch"] = str_replace("* ", "", $this->git_abbrev_ref($path));
                $components[$name]["local_changes"] = $this->git_local_changes($path);
                $components[$name]["url"] = $this->git_remote_url($path);

                if ($components[$name]["branch"] !== '' && !in_array($components[$name]["branch"], $components[$name]["branches_available"])) {
                    $components[$name]["branches_available"][] = $components[$name]["branch"];
                }
            }
        }

        return $components;
    }

    public function get_current_git_branch($path)
    {
        return $this->git_abbrev_ref($path);
    }

    // -------------------------------------------------------------------------
    // Component update actions
    // -------------------------------------------------------------------------

    public function update_component($module, $branch)
    {
        $components = $this->component_list(false);

        if (!isset($components[$module])) {
            return array('success' => false, 'message' => "Invalid module");
        }

        $module_path = $components[$module]["path"];

        // If branch is not in available branches, verify it matches the current branch
        if (!in_array($branch, $components[$module]["branches_available"])) {
            $current_branch = $this->get_current_git_branch($module_path);
            if ($branch !== $current_branch) {
                return array('success' => false, 'message' => "Invalid branch");
            }
        }

        $script = $this->settings['openenergymonitor_dir'] . "/EmonScripts/update/update_component.sh";
        return $this->runService($script, escapeshellarg($module_path) . " " . escapeshellarg($branch) . ">" . escapeshellarg($this->update_logfile));
    }

    public function update_all_components($branch)
    {
        $available_branches = array();
        foreach ($this->component_list(false) as $c) {
            foreach ($c["branches_available"] as $b) {
                if (!in_array($b, $available_branches)) {
                    $available_branches[] = $b;
                }
            }
        }

        if (!in_array($branch, $available_branches)) {
            return array('success' => false, 'message' => "Invalid branch");
        }

        $script = $this->settings['openenergymonitor_dir'] . "/EmonScripts/update/update_all_components.sh";
        return $this->runService($script, escapeshellarg($branch) . ">" . escapeshellarg($this->update_logfile));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function runService($script, $attributes)
    {
        if (!file_exists($script)) {
            $this->log->error("ComponentsModel::runService() Script not found '$script' attributes=$attributes");
            return array('success' => false, 'message' => "File not found '$script'");
        }
        if ($this->redis) {
            $this->redis->rpush("service-runner", "$script $attributes");
            $this->log->info("ComponentsModel::runService() service-runner trigger sent for '$script $attributes'");
            return array('success' => true, 'message' => "service-runner trigger sent for '$script $attributes'");
        } else {
            $this->log->error("ComponentsModel::runService() Redis not enabled. Cannot execute '$script $attributes' safely.");
            return array('success' => false, 'message' => "Redis is required to run service commands");
        }
    }

    private function exec($cmd)
    {
        $output = false;
        if (function_exists("exec")) {
            $output = @exec($cmd);
        }
        return $output;
    }

    private function exec_git($path, $git_cmd)
    {
        if (!function_exists("exec")) {
            $this->log->warn("ComponentsModel::exec_git() PHP exec() is disabled");
            return '';
        }

        if (!is_dir($path)) {
            $this->log->warn("ComponentsModel::exec_git() invalid path '$path'");
            return '';
        }

        $cmd = "git -c " . escapeshellarg("safe.directory=$path") . " -C " . escapeshellarg($path) . " " . $git_cmd . " 2>&1";
        $output = array();
        $exit_code = 0;
        @exec($cmd, $output, $exit_code);

        $result = trim(implode("\n", $output));
        if ($exit_code !== 0) {
            $this->log->warn("ComponentsModel::exec_git() command failed exit_code=$exit_code path='$path' cmd='$git_cmd' output='" . $result . "'");
            return '';
        }

        return $result;
    }

    private function git_describe($path)
    {
        return $this->exec_git($path, "describe");
    }

    private function git_abbrev_ref($path)
    {
        return $this->exec_git($path, "rev-parse --abbrev-ref HEAD");
    }

    private function git_local_changes($path)
    {
        return $this->exec_git($path, "diff-index -G. HEAD --");
    }

    private function git_remote_url($path)
    {
        return $this->exec_git($path, "ls-remote --get-url origin");
    }
}
