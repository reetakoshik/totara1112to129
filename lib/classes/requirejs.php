<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * RequireJS helper functions.
 *
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Collection of requirejs related methods.
 *
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_requirejs {

    /**
     * Check a single module exists and return the full path to it.
     *
     * The expected location for amd modules is:
     *  <componentdir>/amd/src/modulename.js
     *
     * @param string $component The component determines the folder the js file should be in.
     * @param string $jsfilename The filename for the module (with the js extension).
     * @param boolean $debug If true, returns the paths to the original (unminified) source files.
     * @return array $files An array of mappings from module names to file paths.
     *                      Empty array if the file does not exist.
     */
    public static function find_one_amd_module($component, $jsfilename, $debug = false) {
        $jsfileroot = core_component::get_component_directory($component);
        if (!$jsfileroot) {
            return array();
        }

        $module = str_replace('.js', '', $jsfilename);

        $srcdir = $jsfileroot . '/amd/build';
        $minpart = '.min';
        if ($debug) {
            $srcdir = $jsfileroot . '/amd/src';
            $minpart = '';
        }

        $filename = $srcdir . '/' . $module . $minpart . '.js';
        if (!file_exists($filename)) {
            return array();
        }

        $fullmodulename = $component . '/' . $module;
        return array($fullmodulename => $filename);
    }

    /**
     * Scan the source for AMD modules and return them all.
     *
     * The expected location for amd modules is:
     *  <componentdir>/amd/src/modulename.js
     *
     * @param boolean $debug If true, returns the paths to the original (unminified) source files.
     * @return array $files An array of mappings from module names to file paths.
     */
    public static function find_all_amd_modules($debug = false) {
        global $CFG;

        $jsdirs = array();
        $jsfiles = array();

        $dir = $CFG->libdir . '/amd';
        if (!empty($dir) && is_dir($dir)) {
            $jsdirs['core'] = $dir;
        }
        $subsystems = core_component::get_core_subsystems();
        foreach ($subsystems as $subsystem => $dir) {
            if (!empty($dir) && is_dir($dir . '/amd')) {
                $jsdirs['core_' . $subsystem] = $dir . '/amd';
            }
        }
        $plugintypes = core_component::get_plugin_types();
        foreach ($plugintypes as $type => $dir) {
            $plugins = core_component::get_plugin_list_with_file($type, 'amd', false);
            foreach ($plugins as $plugin => $dir) {
                if (!empty($dir) && is_dir($dir)) {
                    $jsdirs[$type . '_' . $plugin] = $dir;
                }
            }
        }

        foreach ($jsdirs as $component => $dir) {
            // Totara: make sure devs know about grunt, without build dirs the JS fails.
            if (!file_exists($dir . '/src')) {
                continue;
            }
            if (!file_exists($dir . '/build')) {
                error_log("Missing build directory {$dir}/build - run grunt!");
                continue;
            }

            $srcdir = $dir . '/build';
            if ($debug) {
                $srcdir = $dir . '/src';
            }
            if (!is_dir($srcdir) || !is_readable($srcdir)) {
                // This is probably an empty amd directory without src or build.
                // Skip it - RecursiveDirectoryIterator fatals if the directory is not readable as an iterator.
                continue;
            }
            $items = new RecursiveDirectoryIterator($srcdir);
            foreach ($items as $item) {
                $extension = $item->getExtension();
                if ($extension === 'js') {
                    $filename = str_replace('.min', '', $item->getBaseName('.js'));
                    // We skip lazy loaded modules.
                    if (strpos($filename, '-lazy') === false) {
                        $modulename = $component . '/' . $filename;
                        $jsfiles[$modulename] = $item->getRealPath();
                    }
                }
                unset($item);
            }
            unset($items);
        }

        return $jsfiles;
    }

    /**
     * Returns configuration data for requirejs,
     * this method replaces previous /lib/requirejs/moodle-config.js template.
     *
     * @since Totara 12
     *
     * @param int $jsrev js caching revision
     * @return array JS object with requirejs configuration
     */
    public static function get_config_data($jsrev) {
        global $CFG;
        // NOTE: slasharguments are now always enabled in Totara.

        // Get jquery versions.
        $plugins = [];
        require("{$CFG->dirroot}/lib/jquery/plugins.php");
        $jquery = str_replace('.min.js', '.min', $plugins['jquery']['files'][0]);
        $jqueryui = str_replace('.min.js', '.min', $plugins['ui']['files'][0]);
        unset($plugins);

        $config = [];
        $config['baseUrl'] = "{$CFG->wwwroot}/lib/requirejs.php/{$jsrev}/";

        // We only support AMD modules with an explicit define() statement.
        $config['enforceDefine'] = true;
        $config['skipDataMain'] = true;
        $config['waitSeconds'] = 0;

        // Path exceptions.
        $config['paths'] = [];
        $config['paths']['jquery'] = "{$CFG->wwwroot}/lib/javascript.php/{$jsrev}/lib/jquery/{$jquery}";
        $config['paths']['jqueryui'] = "{$CFG->wwwroot}/lib/javascript.php/{$jsrev}/lib/jquery/{$jqueryui}";
        $config['paths']['jqueryprivate'] = "{$CFG->wwwroot}/lib/javascript.php/{$jsrev}/lib/requirejs/jquery-private";

        // Custom jquery config map.
        $config['map'] = [];
        // '*' means all modules will get 'jqueryprivate' for their 'jquery' dependency.
        $config['map']['*'] = ['jquery' => 'jqueryprivate'];
        // 'jquery-private' wants the real jQuery module
        // though. If this line was not here, there would
        // be an unresolvable cyclic dependency.
        $config['map']['jqueryprivate'] = ['jquery' => 'jquery'];

        // Add bundle for all Totara AMD modules, this replaces old fragile 'core/first' workaround.
        $config['bundles'] = [];
        $config['bundles']['core/bundle'] = [];
        foreach (core_requirejs::find_all_amd_modules() as $modulename => $unused) {
            $config['bundles']['core/bundle'][] = $modulename;
        }

        return $config;
    }

    /**
     * Returns configuration file content for requirejs in Totara,
     * previously the configuration was included in page markup.
     *
     * @since Totara 12
     *
     * @param int $jsrev js caching revision
     * @return string content of JS file to be served as configuration.
     */
    public static function get_config_file_content($jsrev) {
        $config = self::get_config_data($jsrev);

        // Encode the result as js file setting require global as config for requirejs.
        if ($jsrev > 0) {
            $config = json_encode($config, JSON_UNESCAPED_SLASHES);
        } else {
            $config = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return "var require = {$config};";
    }
}
