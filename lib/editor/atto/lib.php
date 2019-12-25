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
 * YUI text editor integration.
 *
 * @package    editor_atto
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is the texteditor implementation.
 * @copyright  2013 Damyon Wiese  <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class atto_texteditor extends texteditor {

    /**
     * Is the current browser supported by this editor?
     *
     * Of course!
     * @return bool
     */
    public function supported_by_browser() {
        return true;
    }

    /**
     * Returns array of supported text formats.
     * @return array
     */
    public function get_supported_formats() {
        // FORMAT_MOODLE is not supported here, sorry.
        return array(FORMAT_HTML => FORMAT_HTML);
    }

    /**
     * Returns text format preferred by this editor.
     * @return int
     */
    public function get_preferred_format() {
        return FORMAT_HTML;
    }

    /**
     * Does this editor support picking from repositories?
     * @return bool
     */
    public function supports_repositories() {
        return true;
    }

    /**
     * Use this editor for given element.
     *
     * Available Atto-specific options:
     *   atto:toolbar - set to a string to override the system config editor_atto/toolbar
     *
     * Available general options:
     *   context - set to the current context object
     *   enable_filemanagement - set false to get rid of the managefiles plugin
     *   autosave - true/false to control autosave
     *
     * Options are also passed through to the plugins.
     *
     * @param string $elementid
     * @param array $options
     * @param null $fpoptions
     */
    public function use_editor($elementid, array $options=null, $fpoptions=null) {
        global $PAGE;

        if (array_key_exists('atto:toolbar', $options)) {
            $configstr = $options['atto:toolbar'];
        } else {
            $configstr = get_config('editor_atto', 'toolbar');
        }

        $grouplines = explode("\n", $configstr);

        $groups = array();

        foreach ($grouplines as $groupline) {
            $line = explode('=', $groupline);
            if (count($line) > 1) {
                $group = trim(array_shift($line));
                $plugins = array_map('trim', explode(',', array_shift($line)));
                $groups[$group] = $plugins;
            }
        }

        $modules = array('moodle-editor_atto-editor');
        $options['context'] = empty($options['context']) ? context_system::instance() : $options['context'];

        $jsplugins = array();
        foreach ($groups as $group => $plugins) {
            $groupplugins = array();
            foreach ($plugins as $plugin) {
                // Do not die on missing plugin.
                if (!core_component::get_component_directory('atto_' . $plugin))  {
                    continue;
                }

                // Remove manage files if requested.
                if ($plugin == 'managefiles' && isset($options['enable_filemanagement']) && !$options['enable_filemanagement']) {
                    continue;
                }

                $jsplugin = array();
                $jsplugin['name'] = $plugin;
                $jsplugin['params'] = array();
                $modules[] = 'moodle-atto_' . $plugin . '-button';

                component_callback('atto_' . $plugin, 'strings_for_js');
                $extra = component_callback('atto_' . $plugin, 'params_for_js', array($elementid, $options, $fpoptions));

                if ($extra) {
                    $jsplugin = array_merge($jsplugin, $extra);
                }
                // We always need the plugin name.
                $PAGE->requires->string_for_js('pluginname', 'atto_' . $plugin);
                $groupplugins[] = $jsplugin;
            }
            $jsplugins[] = array('group'=>$group, 'plugins'=>$groupplugins);
        }

        $PAGE->requires->strings_for_js(array(
                'editor_command_keycode',
                'editor_control_keycode',
                'plugin_title_shortcut',
                'textrecovered',
                'textrecoveredwithundo',
                'textrecoveredalert',
                'autosavefailed',
                'autosavesucceeded',
                'errortextrecovery'
            ), 'editor_atto');
        $PAGE->requires->strings_for_js(array(
                'warning',
                'info'
            ), 'moodle');
        $PAGE->requires->yui_module($modules,
                                    'Y.M.editor_atto.Editor.init',
                                    array($this->get_init_params($elementid, $options, $fpoptions, $jsplugins)));

    }

    /**
     * Create a params array to init the editor.
     *
     * @param string $elementid
     * @param array $options
     * @param array $fpoptions
     */
    protected function get_init_params($elementid, array $options = null, array $fpoptions = null, $plugins = null) {
        global $PAGE;

        $directionality = get_string('thisdirection', 'langconfig');
        $strtime        = get_string('strftimetime');
        $strdate        = get_string('strftimedaydate');
        $lang           = current_language();
        $autosave       = true;
        $autosavefrequency = get_config('editor_atto', 'autosavefrequency');
        if (isset($options['autosave'])) {
            $autosave       = $options['autosave'];
        }
        $contentcss     = $PAGE->theme->editor_css_url()->out(false);

        // Autosave disabled for guests.
        if (isguestuser() or !isloggedin()) {
            $autosave = false;
        }
        // Note <> is a safe separator, because it will not appear in the output of s().
        $pagehash = sha1($PAGE->url . '<>' . s($this->get_text()));
        $params = array(
            'elementid' => $elementid,
            'content_css' => $contentcss,
            'contextid' => $options['context']->id,
            'autosaveEnabled' => $autosave,
            'autosaveFrequency' => $autosavefrequency,
            'language' => $lang,
            'directionality' => $directionality,
            'filepickeroptions' => array(),
            'plugins' => $plugins,
            'pageHash' => $pagehash,
        );
        if ($fpoptions) {
            $params['filepickeroptions'] = $fpoptions;
        }
        return $params;
    }

    /**
     * Allow editor to customise template and init itself in Totara forms.
     *
     * @param array $result
     * @param array $editoroptions
     * @param array $fpoptions
     * @param array $fptemplates
     * @return void the $result template data parameter is modified if necessary
     */
    public function totara_form_use_editor(&$result, array $editoroptions, array $fpoptions, array $fptemplates) {
        global $PAGE;

        $attopagehack = new atto_page_hack($PAGE);
        $PAGE = $attopagehack;

        $this->set_text($result['text']);
        $this->use_editor($result['id'], $editoroptions, $fpoptions);

        $PAGE = $attopagehack->oldpage;

        $result['form_item_template'] = 'totara_form/element_editor_atto';
        $result['fptemplates'] = json_encode($fptemplates);
        $result['requiredstrings'] = json_encode($attopagehack->requiredstrings);
        $result['jsmodules'] = json_encode($attopagehack->jsmodules);
        $result['yuimodules'] = json_encode($attopagehack->yuimodules);
        $result['amdmodule'] = 'totara_form/form_element_editor_atto';
    }
}

/**
 * Ugly hack necessary to get all JS init data from Atto editor
 * and all plugins/filters that are initialised.
 */
class atto_page_hack {
    public $oldpage;
    public $context;
    public $requires;
    public $theme;
    public $url;
    public $yuimodules;
    public $jsmodules;
    public $requiredstrings = array();

    public function __construct($oldpage) {
        $this->oldpage = $oldpage;
        $this->requires = $this;
        $this->context = $oldpage->context;
        $this->theme = $oldpage->theme;
        $this->url = $oldpage->url;
    }

    public function strings_for_js($identifiers, $component, $a = null) {
        foreach ($identifiers as $key => $identifier) {
            if (is_array($a) && array_key_exists($key, $a)) {
                $extra = $a[$key];
            } else {
                $extra = $a;
            }
            $this->string_for_js($identifier, $component, $extra);
        }
    }

    public function string_for_js($identifier, $component, $a = null) {
        if ($a !== null) {
            debugging("Do not use \$a parameter when proloading strings for ajax! You need to fix: $identifier, $component", DEBUG_DEVELOPER);
        }
        $this->requiredstrings[] = array('key' => $identifier, 'component' => $component);
    }

    public function yui_module($modules, $function, array $arguments = null, $galleryversion = null, $ondomready = false) {
        $functionstr = js_writer::function_call($function, $arguments);
        $this->yuimodules[] = array('modules' => $modules, 'function' => $function, 'parameters' => $arguments, 'functionstr' => $functionstr);
    }

    public function editor_css_url() {
        return $this->oldpage->theme->editor_css_url();
    }

    public function js_module($module) {
        if (is_array($module) and count($module) === 2 and isset($module['name']) and isset($module['fullpath'])) {
            if ($module['fullpath'] instanceof \moodle_url) {
                $module['fullpath'] = $module['fullpath']->out(false);
            }
            $this->jsmodules[$module['name']] = $module;
        } else {
            debugging('Unexpected $PAGE->requires->js_module() in Atto init: ' . var_export($module, true), DEBUG_DEVELOPER);
        }
    }

    public function __call($name , array $arguments) {
        debugging("Totara forms: Unexpected method call PAGE->xx->'$name'() in Atto init.", DEBUG_DEVELOPER);
    }
}
