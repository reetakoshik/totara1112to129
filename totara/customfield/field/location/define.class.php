<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Learning Pool
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

define('GMAP_SIZE_SMALL', 'small');
define('GMAP_SIZE_MEDIUM', 'medium');
define('GMAP_SIZE_LARGE', 'large');

define('GMAP_VIEW_MAP', 'map');
define('GMAP_VIEW_SATELLITE', 'satellite');
define('GMAP_VIEW_HYBRID', 'hybrid');

define('GMAP_DISPLAY_MAP_AND_ADDRESS', 'both');
define('GMAP_DISPLAY_MAP_ONLY', 'map');
define('GMAP_DISPLAY_ADDRESS_ONLY', 'address');

require_once($CFG->dirroot . '/totara/customfield/definelib.php');
require_once($CFG->dirroot . '/totara/customfield/field/location/field.class.php');

class customfield_define_location extends customfield_define_base {

    public static function set_location_field_form_element_defaults(&$form, $defaultlocationdata, $storeddata) {
        global $CFG;

        $datatouse = $storeddata;

        if (empty($storeddata)) {
            $datatouse = $defaultlocationdata;

            if (empty($defaultlocationdata)) {
                return;
            }
        }

        $defaults = self::convert_location_json_to_object($datatouse);

        if (empty($defaults)) {
            return;
        }

        $form->setDefault($form->_customlocationfieldname . 'address', $defaults->address);
        $form->setDefault($form->_customlocationfieldname . 'latitude', $defaults->latitude);
        $form->setDefault($form->_customlocationfieldname . 'longitude', $defaults->longitude);
        $form->setDefault($form->_customlocationfieldname . 'size', $defaults->size);
        $form->setDefault($form->_customlocationfieldname . 'view', $defaults->view);
        $form->setDefault($form->_customlocationfieldname . 'display', $defaults->display);

        $defaults->zoom = isset($defaults->zoom) ? $defaults->zoom : $CFG->gmapsdefaultzoomlevel;
        $form->setDefault($form->_customlocationfieldname . 'zoom', $defaults->zoom);
    }

    public function define_form_specific(&$form) {
        self::define_add_js();
        self::add_location_field_form_elements($form);
    }

    public static function add_radio_group($groupfieldname, $grouparray, $options, $form) {
        if (empty($options)) {
            return false;
        }

        // This will not be set when creating the custom field.
        $fieldprefix = isset($form->_customlocationfieldname) ? $form->_customlocationfieldname : '';
        foreach ($options as $size => $name) {
            $grouparray[] =& $form->createElement(
                'radio',
                $fieldprefix . $groupfieldname,
                '',
                get_string('customfieldtypelocation_' . $groupfieldname . $name, 'totara_customfield'),
                $name, // Value.
                array(
                    'class' => 'radio_' . $groupfieldname
                )
            );
        }

        if (!empty($grouparray)) {
            return $grouparray;
        }
    }

    /**
     * @param MoodleQuickForm $form
     * @param $fielddefinition bool This flag is used to determine if this function is being used for field or instance creation
     * @throws coding_exception
     */
    public static function add_location_field_form_elements($form, $fieldname = 'Location', $fielddefinition = true) {
        global $OUTPUT, $CFG;

        // These fields shouldn't be required during field definition, just in field instantiation.
        $stringsuffix = $fielddefinition ? 'default' : '';

        $formprefix = (!$fielddefinition) ? $form->_customlocationfieldname : '';

        // Give html element a name here, so it won't cause moodle form bug
        $h3element = $form->createElement('html', html_writer::tag('h3', $fieldname));
        $h3element->setName(uniqid("customfieldtitle_"));
        $form->addElement($h3element);

        // Address element.
        $form->addElement(
            'textarea',
            $formprefix . 'address',
            get_string('customfieldtypelocation_address'.$stringsuffix, 'totara_customfield'),
            array(
                "cols" => "50",
                "rows" => "10"
            )
        );
        $form->setType($formprefix . 'address', PARAM_TEXT);

        // Size elements.
        $radiogroupmapsize = array();
        $sizeoptions = [GMAP_SIZE_SMALL, GMAP_SIZE_MEDIUM, GMAP_SIZE_LARGE];
        $mapsizeoptions = self::add_radio_group('size', $radiogroupmapsize, $sizeoptions, $form);
        $form->addGroup(
            $mapsizeoptions,
            $formprefix . 'size',
            get_string('customfieldtypelocation_mapsize'.$stringsuffix, 'totara_customfield'),
            '<br />',
            false
        );
        // Give it an appropriate default.
        $form->setDefault($formprefix . 'size', GMAP_SIZE_MEDIUM);

        // View elements.
        $radiogroupmapview = array();
        $viewoptions = [GMAP_VIEW_MAP, GMAP_VIEW_SATELLITE, GMAP_VIEW_HYBRID];
        $mapviewoptions = self::add_radio_group('view', $radiogroupmapview, $viewoptions, $form);
        $form->addGroup(
            $mapviewoptions,
            $formprefix . 'view',
            get_string('customfieldtypelocation_mapview'.$stringsuffix, 'totara_customfield'),
            '<br />',
            false
        );
        $form->setDefault($formprefix . 'view', GMAP_VIEW_MAP);

        // Display elements.
        $radiogroupdisplay = array();
        $displayoptions = [GMAP_DISPLAY_MAP_AND_ADDRESS, GMAP_DISPLAY_MAP_ONLY, GMAP_DISPLAY_ADDRESS_ONLY];
        $radiogroupdisplayoptions = self::add_radio_group('display', $radiogroupdisplay, $displayoptions, $form);
        $form->addGroup(
            $radiogroupdisplayoptions,
            $formprefix . 'display',
            get_string('customfieldtypelocation_display'.$stringsuffix, 'totara_customfield'),
            '<br />',
            false
        );
        if (self::has_google_maps_client_id()) {
            // If they have a client ID then they have a Premium plan and they have agreed to the ToS.
            $form->setDefault($formprefix . 'display', GMAP_DISPLAY_MAP_AND_ADDRESS);
        } else if (self::has_google_maps_api_key()) {
            $form->setDefault($formprefix . 'display', GMAP_DISPLAY_MAP_AND_ADDRESS);
            $form->addElement('static', 'googlemapapitoscheck', null, $OUTPUT->notification(get_string('gmaptosnotice_apikey', 'totara_customfield'), 'notifymessage'));
        } else {
            $form->setDefault($formprefix . 'display', GMAP_DISPLAY_ADDRESS_ONLY);
            $form->addElement('static', 'googlemapapitoscheck', null, $OUTPUT->notification(get_string('gmaptosnotice_nokey', 'totara_customfield'), 'notifymessage'));
        }

        // Give element a name here, so it won't cause moodle form bug
        $mapaddresslookupdiv = $form->createElement('html', html_writer::start_div('mapaddresslookup'));
        $mapaddresslookupdiv->setName(uniqid('mapaddresslookup_'));
        $form->addElement($mapaddresslookupdiv);

        $mapelements = array();
        $mapelements[] = $form->createElement(
            'button',
            $formprefix . 'useaddress_btn',
            get_string('customfieldtypelocation_useaddress', 'totara_customfield'),
            array(
                'class' => 'btn_useaddress'
            )
        );

        $mapelements[] = $form->createElement('static', 'selectpersonalheader', null,
            html_writer::tag('b', get_string('customfieldtypelocation_or', 'totara_customfield')));

        $mapelements[] = $form->createElement(
            'text',
            $formprefix . 'addresslookup',
            get_string('customfieldtypelocation_addresslookup'.$stringsuffix, 'totara_customfield')
        );
        $form->setType($formprefix . 'addresslookup', PARAM_TEXT);
        $form->setDefault($formprefix . 'addresslookup', '');

        $mapelements[] = $form->createElement(
            'button',
            $formprefix . 'searchaddress_btn',
            get_string('customfieldtypelocation_searchbutton', 'totara_customfield'),
            array(
                'class' => 'btn_search'
            )
        );
        // Google Map element
        $mapelements[] = $form->createElement(
            'static',
            $formprefix . 'googlemap',
            null,
            html_writer::tag('div', null, array('id' => "{$formprefix}location_map", 'class' => 'location_map'))
        );

        $form->addGroup($mapelements, $formprefix . 'mapelements', get_string('customfieldtypelocation_setmap', 'totara_customfield'), ' ', false);
        $form->addHelpButton($formprefix . 'mapelements', 'customfieldtypelocation_setmap', 'totara_customfield');

        $usertz = core_date::get_user_timezone();
        $tz = new DateTimeZone($usertz);
        $tzlocation = $tz->getLocation();

        // Latitude element.
        $form->addElement(
            'hidden',
            $formprefix . 'latitude',
            "",
            array(
                'id' => $formprefix . 'latitude'
            )
        );
        if (isset($tzlocation['latitude'])) {
            $form->setDefault($formprefix . 'latitude', $tzlocation['latitude']);
        }
        $form->setType($formprefix . 'latitude', PARAM_FLOAT);

        // Longitude element.
        $form->addElement(
            'hidden',
            $formprefix . 'longitude',
            "",
            array(
                'id' => $formprefix . 'longitude'
            )
        );
        if (isset($tzlocation['longitude'])) {
            $form->setDefault($formprefix . 'longitude', $tzlocation['longitude']);
        }
        $form->setType($formprefix . 'longitude', PARAM_FLOAT);

        // Zoom element.
        $form->addElement(
            'hidden',
            $formprefix . 'zoom',
            "",
            array(
                'id' => $formprefix . 'zoom'
            )
        );
        $form->setDefault($formprefix . 'zoom', $CFG->gmapsdefaultzoomlevel);
        $form->setType($formprefix . 'zoom', PARAM_INT);

        // Give it a name here, so it won't cause a moodle form debugging
        $mapaddresslookupenddiv = $form->createElement('html', html_writer::end_div());
        $mapaddresslookupenddiv->setName(uniqid('mapaddresslookupenddiv_'));
        $form->addElement($mapaddresslookupenddiv);
    }

    public static function define_add_js($args = null) {
        global $PAGE, $CFG;

        $mapparams = 'define=1';

        if (isset($CFG->gmapsforcemaplanguage)) {
            $mapparams .= "&language=" . $CFG->gmapsforcemaplanguage;
        }

        if (is_null($args)) {
            $args = new stdClass();
            // If not defined this MUST be false. DO NOT CHANGE!
            // The reason for this is that if you change the default you need to review all uses of the custom field in all
            // situations, false gives the correct behaviour behaviour most of the time.
            // This logic is reflected in the AMD module.
            $args->fordisplay = false;
        }

        if (isset($args->formprefix)) {
            debugging('Location custom fields need to be initalised with fieldprefix now, not formprefix.');
            $args->fieldprefix = $args->formprefix;
        }

        $args->regionbias = $CFG->gmapsregionbias;
        $args->defaultzoomlevel = $CFG->gmapsdefaultzoomlevel;
        $args->mapparams = $mapparams;

        // Check if they have set a key.
        // If so then we want to send it along.
        if (self::has_google_maps_api_key()) {
            if (strpos($CFG->googlemapkey3, 'client-id: ') === 0) {
                // Its 100% a client id.
                $args->clientid = substr($CFG->googlemapkey3, strlen('client-id: '));
                $args->mapparams = '&client='.$args->clientid;
            } else if (strpos($CFG->googlemapkey3, 'apikey: ') === 0) {
                // Its 100% an api key.
                $args->apikey = substr($CFG->googlemapkey3, strlen('apikey: '));
                $args->mapparams = '&key='.$args->apikey;
            } else if (strpos($CFG->googlemapkey3, 'gme-') === 0) {
                // It appears this is a client ID not an API key, just a guess but we'll go with it.
                // Good for them, they have purchased a premium plan.
                $args->clientid = $CFG->googlemapkey3;
                $args->mapparams = '&client='.$args->clientid;
            } else {
                // Its an API key... most likely.
                $args->apikey = $CFG->googlemapkey3;
                $args->mapparams = '&key='.$args->apikey;
            }
        }

        $PAGE->requires->js_call_amd('totara_customfield/field_location', 'init', array($args));
    }

    /**
     * Returns true if a Google Maps API key has been provided.
     * @return bool
     */
    public static function has_google_maps_api_key() {
        global $CFG;
        return (isset($CFG->googlemapkey3) && !empty($CFG->googlemapkey3));
    }

    /**
     * Returns true if a Google Maps API client ID has been provided.
     * @return bool
     */
    public static function has_google_maps_client_id() {
        global $CFG;
        if (!self::has_google_maps_api_key()) {
            return false;
        }
        if (strpos($CFG->googlemapkey3, 'client-id: ') === 0) {
            return true;
        }
        if (strpos($CFG->googlemapkey3, 'apikey: ') === 0) {
            return false;
        }
        if (strpos($CFG->googlemapkey3, 'gme-') === 0) {
            return true;
        }
        return false;
    }

    public function define_save_preprocess($data, $old = null) {
        $data->param2 = self::prepare_location_data($data);

        return $data;
    }

    public function define_load_preprocess($data) {
        if (isset($data->param2) && !empty($data->param2)) {
            $locationdata = self::convert_location_json_to_object($data->param2);
            foreach ($locationdata as $index => $value) {
                $data->$index = $value;
            }
        }
        return $data;
    }

    public static function prepare_location_data($data, $fieldname = '') {
        global $CFG;

        $locationdata = new stdClass();

        $latitude = $fieldname . 'latitude';
        $longitude = $fieldname . 'longitude';
        $address = $fieldname . 'address';
        $size = $fieldname . 'size';
        $view = $fieldname . 'view';
        $display = $fieldname . 'display';
        $zoom = $fieldname . 'zoom';

        $locationdata->latitude = (!empty($data->$latitude)) ? $data->$latitude : "0";
        $locationdata->longitude = (!empty($data->$longitude)) ? $data->$longitude : "0";

        $options = new stdClass();
        $options->para = false;

        $newdata = new stdClass();
        $newdata->address = (!empty($data->$address)) ? format_text($data->$address, FORMAT_HTML, $options) : "";
        $newdata->size = (!empty($data->$size)) ? $data->$size : "";
        $newdata->view = (!empty($data->$view)) ? $data->$view : "";
        $newdata->display = (!empty($data->$display)) ? $data->$display : "";
        $gmapsdefaultzoomlevel = isset($CFG->gmapsdefaultzoomlevel) ? $CFG->gmapsdefaultzoomlevel : 12; // Not set during upgrade!
        $newdata->zoom = (!empty($data->$zoom)) ? $data->$zoom : $gmapsdefaultzoomlevel;
        $newdata->location = $locationdata;

        return json_encode($newdata);
    }

    public static function prepare_form_location_data_for_db(&$data, $fieldname) {
        $data->{$fieldname} = self::prepare_location_data($data, $fieldname);

        unset($data->latitude);
        unset($data->longitude);
        unset($data->address);
        unset($data->size);
        unset($data->view);
        unset($data->display);
        unset($data->zoom);
    }

    public static function prepare_db_location_data_for_form($data) {
        global $CFG;

        if (empty($data)) {
            return null;
        }

        if (is_string($data)) {
            $data = json_decode($data);
        }

        $options = new stdClass();
        $options->para = false;

        $newdata = new stdClass();
        $newdata->address = (isset($data->address) && !empty($data->address)) ? format_text($data->address, FORMAT_MOODLE, $options) : "";
        $newdata->size = (isset($data->size) && !empty($data->size)) ? $data->size : "";
        $newdata->view = (isset($data->view) && !empty($data->view)) ? $data->view : "";
        $newdata->display = (isset($data->display) && !empty($data->display)) ? $data->display : GMAP_DISPLAY_ADDRESS_ONLY;
        $newdata->zoom = (isset($data->zoom) && !empty($data->zoom)) ? $data->zoom : $CFG->gmapsdefaultzoomlevel;

        $newdata->location = new stdClass();
        $newdata->location->latitude = (isset($data->location->latitude) && !empty($data->location->latitude)) ? $data->location->latitude : "0";
        $newdata->location->longitude = (isset($data->location->longitude) && !empty($data->location->longitude)) ? $data->location->longitude : "0";

        return $newdata;
    }

    public static function convert_location_json_to_object($json) {
        if (!empty($json)) {
            $newdata = new stdClass();

            $locationdata = json_decode($json);

            foreach ($locationdata as $index => $value) {
                if ($index == 'location') {
                    if (empty($value->latitude) && empty($value->longitude)) {
                        $newdata->latitude = null;
                        $newdata->longitude = null;
                    } else {
                        $newdata->latitude = $value->latitude;
                        $newdata->longitude = $value->longitude;
                    }
                } else {
                    $newdata->$index = $value;
                }
            }

            return $newdata;
        } else {
            return null;
        }

    }

    public static function render($fielddata, $extradata = array()) {
        global $CFG;

        static $instancecount = 0;
        $instancecount++;

        if (empty($fielddata)) {
            return '';
        }
        if (is_string($fielddata)) {
            // Data is json_endoded prior to storage. Lets decode it.
            $fielddata = json_decode($fielddata);
        }

        // Export address only
        if (!empty($extradata['isexport'])) {
            return $fielddata->address;
        }

        // Ensure zoom level is set.
        if (!isset($fielddata->zoom)) {
            $fielddata->zoom = $CFG->gmapsdefaultzoomlevel;
        }

        $extended = isset($extradata['extended']) && !empty($extradata['extended']);

        // Enable extended display (Maps etc.).
        // Disabled by default, since custom fields are typically rendered in tables or within <dd> elements.
        if (!$extended) {
            $options = new stdClass();
            $options->para = false;
            return format_text($fielddata->address, FORMAT_MOODLE, $options);
        }

        switch ($fielddata->view) {
            case GMAP_VIEW_MAP:
                $view = GMAP_VIEW_MAP;
                break;
            case GMAP_VIEW_SATELLITE:
                $view = GMAP_VIEW_SATELLITE;
                break;
            default:
                $view = GMAP_VIEW_HYBRID;
                break;
        }

        switch ($fielddata->display) {
            case GMAP_DISPLAY_MAP_ONLY:
                $displaytype = GMAP_DISPLAY_MAP_ONLY;
                break;
            case GMAP_DISPLAY_ADDRESS_ONLY:
                $displaytype = GMAP_DISPLAY_ADDRESS_ONLY;
                break;
            default:
                $displaytype = GMAP_DISPLAY_MAP_AND_ADDRESS;
                break;
        }

        $output = array();
        if ($displaytype === GMAP_DISPLAY_ADDRESS_ONLY || $displaytype === GMAP_DISPLAY_MAP_AND_ADDRESS) {
            $output[] = html_writer::tag('span', $fielddata->address);
            if ($displaytype === GMAP_DISPLAY_ADDRESS_ONLY) {
                return implode("", $output);
            }
        }

        if (isset($extradata['itemid'])) {
            $formprefix = 'item_'.$extradata['itemid'];
        } else {
            $formprefix = 'inst_'.$instancecount;
        }
        if ($displaytype === GMAP_DISPLAY_MAP_ONLY || $displaytype === GMAP_DISPLAY_MAP_AND_ADDRESS) {
            $output[] = html_writer::div(
                '',
                '',
                array(
                    'id' => $formprefix . 'location_map',
                    'class' => 'map_' . $fielddata->size
                )
            );
        }

        $output[] = html_writer::empty_tag('input',
            array('type' => 'hidden', 'id' => $formprefix . 'address', 'value' => $fielddata->address)
        );
        $output[] = html_writer::empty_tag('input',
            array('type' => 'hidden', 'id' => $formprefix . 'latitude', 'value' => $fielddata->location->latitude)
        );
        $output[] = html_writer::empty_tag('input',
            array('type' => 'hidden', 'id' => $formprefix . 'longitude', 'value' => $fielddata->location->longitude)
        );
        $output[] = html_writer::empty_tag('input',
            array('type' => 'hidden', 'id' => $formprefix . 'zoom', 'value' => $fielddata->zoom)
        );
        $output[] = html_writer::empty_tag('input',
            array('type' => 'hidden', 'id' => $formprefix . 'room-location-view', 'value' => $view)
        );

        $output = html_writer::div(implode("", $output), 'mapaddresslookup');

        $args = new stdClass;
        $args->fieldprefix = $formprefix;
        $args->fordisplay = true;
        customfield_define_location::define_add_js($args);

        return $output;
    }
}
