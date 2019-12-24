<?php
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Totara core font admin setting.
 *
 * This is a copy of the admin setting from mod/certificate.
 * We can't use that one here because it would create an underlying dependency, but we can very easily create
 * a copy that is now usable by all Totara components.
 *
 * @package   Totara core
 * @copyright 2015 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Sam Hemelryk <sam.hemelryk@totaralms.com>
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/adminlib.php');

/**
 * Totara core font admin setting
 *
 * @since 2.9
 * @copyright 2015 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Sam Hemelryk <sam.hemelryk@totaralms.com>
 */
class totara_core_admin_setting_font extends admin_setting_configselect {

    /**
     * If set to true an appropriate default option will be added before all other options with a value of ''
     * When enabled the code using this setting should select an appropriate font for the given language at the time of execution.
     * @var bool
     */
    protected $useappropriatedefault = true;

    /**
     * Constructor
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param string|int $defaultsetting
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $useappropriatedefault = true) {
        $this->useappropriatedefault = $useappropriatedefault;
        parent::__construct($name, $visiblename, $description, $defaultsetting, null);
    }

    /**
     * Lazy load the font options.
     *
     * @return bool true if loaded, false if error
     */
    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }

        $this->choices = array();
        if ($this->useappropriatedefault) {
            $this->choices[''] = get_string('fontdefault', 'totara_core');
        }

        // Originally the code here initialised a PDF object in order to read the available fonts.
        // This however was incredibly inefficient as the PDF object initialised not just font's but everything else
        // that could be needed to produce a PDF.
        // As this is an admin setting we don't need a whole PDF object, and the cost of initialising was high enough that
        // we choose to a little code in order to separate the font list initialisation into this method.
        // Check git history for the original code.

        $fontfamilies = $this->get_available_fonts();
        if (!empty($fontfamilies)) {
            foreach ($fontfamilies as $family => $fonts) {
                $this->choices[$family] = $family;
            }
        } else {
            // Hmm no font's use a couple of safe defaults.
            $this->choices['freeserif'] = 'freeserif';
            $this->choices['freesans'] = 'freesans';
        }
        return true;
    }

    /**
     * Returns an array of font families and the fonts within those families (think bold, italic, etc).
     *
     * Copied from pdf::get_font_families
     *
     * @return array
     */
    private function get_available_fonts() {
        $fontlist = $this->get_fonts_list();
        $families = array();
        foreach ($fontlist as $font) {
            if (strpos($font, 'uni2cid') === 0) {
                // This is not an font file.
                continue;
            }
            if (strpos($font, 'cid0') === 0) {
                // These do not seem to work with utf-8, better ignore them for now.
                continue;
            }
            if (substr($font, -2) === 'bi') {
                $family = substr($font, 0, -2);
                if (in_array($family, $fontlist)) {
                    $families[$family]['BI'] = 'BI';
                    continue;
                }
            }
            if (substr($font, -1) === 'i') {
                $family = substr($font, 0, -1);
                if (in_array($family, $fontlist)) {
                    $families[$family]['I'] = 'I';
                    continue;
                }
            }
            if (substr($font, -1) === 'b') {
                $family = substr($font, 0, -1);
                if (in_array($family, $fontlist)) {
                    $families[$family]['B'] = 'B';
                    continue;
                }
            }
            // This must be a Family or incomplete set of fonts present.
            $families[$font]['R'] = 'R';
        }

        // Sort everything consistently.
        ksort($families);
        foreach ($families as $k => $v) {
            krsort($families[$k]);
        }

        return $families;
    }

    /**
     * Return the font directory path.
     *
     * @see tcpdf_init_k_font_path()
     *
     * @return string
     */
    private function get_font_path() {
        global $CFG;

        // Now this is a horrible little hack, when you include pdflib.php it creates a function called tcpdf_init_k_font_path
        // and then immediately calls it.
        // This defines the magic K_PATH_FONTS.
        require_once("$CFG->libdir/pdflib.php");

        // It should always be defined at this point, but just in case it is not check!
        return defined('K_PATH_FONTS') ? K_PATH_FONTS : '';
    }

    /**
     * Fill the list of available fonts.
     *
     * Copied from TCPDF::getFontsList
     *
     * @return array
     */
    private function get_fonts_list() {
        $fontlist = array();
        $fontpath = $this->get_font_path();
        if ($fontpath === '') {
            // No font path? the system hasn't been configured with one, nothing we can do now.
            return $fontlist;
        }
        $fontsdir = opendir($fontpath);
        if ($fontsdir === false) {
            // Failed to open the directory, likely unable to read or incorrect location.
            return $fontlist;
        }

        while (($file = readdir($fontsdir)) !== false) {
            if (substr($file, -4) == '.php') {
                $fontlist[] = strtolower(basename($file, '.php'));
            }
        }
        closedir($fontsdir);

        return $fontlist;
    }
}