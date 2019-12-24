<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

namespace totara_core;

/**
 * Class tabexport_writer class is a base for all tabexport plugins.
 *
 * Export plugins iterate over source data and either send file
 * to output or store it in file.
 *
 * Plugins may optionally include information page.
 *
 * @package totara_core
 */
abstract class tabexport_writer {
    /** @var tabexport_source */
    protected $source;

    /** @var string Frankenstyle component name */
    protected $component;

    /**
     * Constructor.
     *
     * @param tabexport_source $source
     */
    public function __construct(tabexport_source $source) {
        $format = $source->get_format();
        if (empty($format)) {
            throw new \coding_exception('tabexport_source does not have format');
        }
        $this->source = $source;
        $classname = get_class($this);
        $parts = explode('\\', $classname);
        $this->component = $parts[0];
    }

    /**
     * Get plugin setting.
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public function get_config($name, $default = null) {
        $value = get_config($this->component, $name);
        if ($value === false) {
            return $default;
        }
        return $value;
    }

    /**
     * Set plugin setting.
     *
     * @param string $name name of setting
     * @param string $value null means delete
     */
    public function set_config($name, $value) {
        set_config($name, $value, $this->component);
    }

    /**
     * Send the file to browser including http headers
     * and stop execution at the end.
     *
     * It is recommended to start streaming of the data
     * as soon as possible.
     *
     * @param string $filename without extension
     * @return void serves the file and terminates process.
     */
    public abstract function send_file($filename);

    /**
     * Save to file.
     *
     * @param string $file full file path
     * @return bool success
     */
    public abstract function save_file($file);

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public static function get_file_extension() {
        throw new \coding_exception('static method get_file_extension must be overriden');
    }

    /**
     * Get the name of this export option.
     *
     * Defaults to get_string('optionname', 'tabexport_xxx').
     *
     * @param string $lang requested language
     * @return \lang_string
     */
    public static function get_export_option_name($lang = null) {
        $called = get_called_class();
        $parts = explode('\\', $called);
        return new \lang_string('optionname', $parts[0], null, $lang);
    }

    /**
     * Returns list of available export options
     *
     * @return \lang_string[] $type => $option_name
     */
    public static function get_export_options() {
        $result = array();
        foreach (self::get_export_classes() as $type => $class) {
            if (!$class::is_ready()) {
                continue;
            }
            $result[$type] = $class::get_export_option_name();
        }

        \core_collator::asort($result);
        return $result;
    }

    /**
     * Returns list of available export classes.
     *
     * Note: hidden flag is not verified.
     *
     * @return string[] $type => $writer_classname
     */
    public static function get_export_classes() {
        $plugins = \core_component::get_plugin_list('tabexport');
        $result = array();
        foreach ($plugins as $pluginname => $fulldir) {
            $result[$pluginname] = "tabexport_{$pluginname}\\writer";
        }
        return $result;
    }

    /**
     * Normalise the format name.
     *
     * Legacy numbers are converted to new plugin names.
     *
     * @param string|int $format
     * @return string format plugin name
     */
    public static function normalise_format($format) {
        $format = (string)$format;
        $legacy = array(
            '1' => 'excel',         // REPORT_BUILDER_EXPORT_EXCEL
            '2' => 'csv',           // REPORT_BUILDER_EXPORT_CSV
            '4' => 'ods',           // REPORT_BUILDER_EXPORT_ODS
            '8' => 'fusion',        // REPORT_BUILDER_EXPORT_FUSION
            '16' => 'pdfportrait',  // REPORT_BUILDER_EXPORT_PDF_PORTRAIT
            '32' => 'pdflandscape', // REPORT_BUILDER_EXPORT_PDF_LANDSCAPE
        );
        if (isset($legacy[$format])) {
            return $legacy[$format];
        }
        if (is_numeric($format)) {
            return '';
        }
        $format = clean_param($format, PARAM_ALPHANUM);
        return strtolower($format);
    }

    /**
     * Is this plugin fully configured and ready to use?
     * @return bool
     */
    public static function is_ready() {
        return true;
    }
}
