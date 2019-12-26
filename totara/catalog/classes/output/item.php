<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\output;

use core\output\template;
use totara_catalog\dataformatter\formatter;
use totara_catalog\local\config;
use totara_catalog\local\required_dataholder;
use totara_catalog\provider;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Extend this class to implement a tile output.
 */
abstract class item extends template {

    /**
     * Gets all of the dataholders which are required to populate the item template.
     *
     * @param provider $provider
     * @return required_dataholder[]
     */
    public static function get_required_dataholders(provider $provider): array {
        $titledataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE);
        $textdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TEXT);
        $icondataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_ICON);
        $iconsdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_ICONS);

        $config = config::instance();

        $requireddataholders = [];

        $titledataholderkey = $provider->get_config('item_title');
        if (empty($titledataholders[$titledataholderkey])) {
            // Default to first title dataholder.
            $firsttitledataholder = reset($titledataholders);
            $titledataholderkey = $firsttitledataholder->key;
        }
        $dataholder = $titledataholders[$titledataholderkey];
        $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TITLE);

        if ($config->get_value('hero_data_type') == 'text') {
            if (!empty($textdataholders[$provider->get_config('hero_data_text')])) {
                $dataholder = $textdataholders[$provider->get_config('hero_data_text')];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TEXT);
            }
        } else if ($config->get_value('hero_data_type') == 'icon') {
            if (!empty($icondataholders[$provider->get_config('hero_data_icon')])) {
                $dataholder = $icondataholders[$provider->get_config('hero_data_icon')];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_ICON);
            }
        }

        if ($config->get_value('item_description_enabled')) {
            if (!empty($textdataholders[$provider->get_config('item_description')])) {
                $dataholder = $textdataholders[$provider->get_config('item_description')];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TEXT);
            }
        }

        $additionaltexts = $provider->get_config('item_additional_text');
        $additionaltextcount = $config->get_value('item_additional_text_count');
        $i = 0;
        foreach ($additionaltexts as $additionaltext) {
            if (!empty($textdataholders[$additionaltext])) {
                $dataholder = $textdataholders[$additionaltext];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TEXT);
            }

            $i++;
            if ($i == $additionaltextcount) {
                break;
            }
        }

        $additionaliconsenabled = $config->get_value('item_additional_icons_enabled');
        $additionalicons = $provider->get_config('item_additional_icons');
        if ($additionaliconsenabled) {
            foreach ($additionalicons as $additionalicon) {
                if (empty($iconsdataholders[$additionalicon])) {
                    continue;
                }

                $dataholder = $iconsdataholders[$additionalicon];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_ICONS);
            }
        }

        $imagedataholderkey = $provider->get_data_holder_config('image');
        if (!empty($imagedataholderkey) && $config->get_value('image_enabled')) {
            $imagedataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_IMAGE);
            if (!empty($imagedataholders[$imagedataholderkey])) {
                $dataholder = $imagedataholders[$imagedataholderkey];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_IMAGE);
            }
        }

        $progressdataholderkey = $provider->get_data_holder_config('progressbar');
        if (!empty($progressdataholderkey) && $config->get_value('progress_bar_enabled')) {
            $progressdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_PROGRESS);
            if (!empty($progressdataholders[$progressdataholderkey])) {
                $dataholder = $progressdataholders[$progressdataholderkey];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_PROGRESS);
            }
        }

        return $requireddataholders;
    }

    /**
     * $object contains:
     * - int id (from catalog table)
     * - int objectid
     * - string objecttype
     * - int contextid
     * - bool featured (optional, depending on configuration)
     * - mixed[$dataholder->type][$dataholder->key] data (which has already been formatted)
     *
     * @param \stdClass $object
     * @return item
     */
    public static function create(\stdClass $object) {
        $provider = provider_handler::instance()->get_provider($object->objecttype);

        $config = config::instance();

        $data = new \stdClass();

        $data->itemid = $object->id;
        $data->featured = !empty($object->featured);

        $titledataholderkey = $provider->get_config('item_title');
        $titledataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE);
        if (empty($titledataholders[$titledataholderkey])) {
            // Default to first title dataholder.
            $titledataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE);
            $firsttitledataholder = reset($titledataholders);
            $titledataholderkey = $firsttitledataholder->key;
        }
        $data->title = $object->data[formatter::TYPE_PLACEHOLDER_TITLE][$titledataholderkey] ?? '';

          $data->titlealt = $data->title;       
        $string = strip_tags($data->title);     
        if (strlen($string) > 45) {     
         // truncate string     
         $stringCut = substr($string, 0, 45);       
        $data->title = $stringCut.' ...';       
        }
        $data->image_enabled = (bool)$config->get_value('image_enabled');
        if ($data->image_enabled) {
            $imagedataholderkey = $provider->get_data_holder_config('image');
            $data->image = $object->data[formatter::TYPE_PLACEHOLDER_IMAGE][$imagedataholderkey] ?? '';
        }

        $data->hero_data_text_enabled = false;
        $data->hero_data_icon_enabled = false;
        $data->hero_data_type = $config->get_value('hero_data_type');
        if ($data->hero_data_type == 'text') {
            $herotextdataholderkey = $provider->get_config('hero_data_text');
            $data->hero_data_text = $object->data[formatter::TYPE_PLACEHOLDER_TEXT][$herotextdataholderkey] ?? '';
            $data->hero_data_text_enabled = true;
        } else if ($data->hero_data_type == 'icon') {
            $heroicondataholderkey = $provider->get_config('hero_data_icon');
            $data->hero_data_icon = $object->data[formatter::TYPE_PLACEHOLDER_ICON][$heroicondataholderkey] ?? '';
            $data->hero_data_icon_enabled = true;
        }

        $data->description_enabled = (bool)$config->get_value('item_description_enabled');
        if ($data->description_enabled) {
            $descriptiondataholderkey = $provider->get_config('item_description');
            $data->description = $object->data[formatter::TYPE_PLACEHOLDER_TEXT][$descriptiondataholderkey] ?? '';
        }
               
        global $DB, $CFG;       
        $ctypeurl = $CFG->wwwroot.'/theme/golearningzone/pix/external-elearning.svg';       
        $ctypeval="E - learning";       
        if($object->objecttype == 'course') {       
            $coursedesc = $DB->get_record_sql("SELECT summary, coursetype FROM {course} WHERE id = '".$object->objectid."'");       
            $data->objectdesc = $coursedesc->summary;       
            if($coursedesc->coursetype == 2) {      
                $ctypeurl = $CFG->wwwroot.'/theme/golearningzone/pix/in-class-icon.svg';        
                $ctypeval= "Seminar";       
            } elseif($coursedesc->coursetype == 1) {        
                $ctypeurl = $CFG->wwwroot.'/theme/golearningzone/pix/blended.svg';      
                $ctypeval="Blended";        
            }       
            $data->coursetypeimgurl = $ctypeurl;        
            $data->coursetypeval = $ctypeval;       
        } elseif($object->objecttype == 'program') {        
            $coursedesc = $DB->get_record_sql("SELECT summary FROM {prog} WHERE id = '".$object->objectid."'");     
            $data->objectdesc = $coursedesc->summary;       
            $data->coursetypeimgurl = $ctypeurl;        
            $data->coursetypeval = $ctypeval;       
        } elseif($object->objecttype == 'certification') {      
            $coursedesc = $DB->get_record_sql("SELECT summary FROM {prog} WHERE id = '".$object->objectid."'");     
            $data->objectdesc = $coursedesc->summary;       
            $data->coursetypeimgurl = $ctypeurl;        
            $data->coursetypeval = $ctypeval;       
        }
        $data->progress_bar_enabled = (bool)$config->get_value('progress_bar_enabled');
        if ($data->progress_bar_enabled) {
            $progressdataholderkey = $provider->get_data_holder_config('progressbar');
            if (!empty($object->data[formatter::TYPE_PLACEHOLDER_PROGRESS][$progressdataholderkey])) {
                $data->progress_bar = $object->data[formatter::TYPE_PLACEHOLDER_PROGRESS][$progressdataholderkey];
            }
        }

        $additionaltextplaceholdercount = $config->get_value('item_additional_text_count');
        $data->text_placeholders_enabled = $additionaltextplaceholdercount > 0;
        if ($data->text_placeholders_enabled) {
            $data->text_placeholders = [];

            $textdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TEXT);
            $additionaltextplaceholders = $provider->get_config('item_additional_text');
            $additionaltextlabels = $provider->get_config('item_additional_text_label');

            for ($i = 0; $i < $additionaltextplaceholdercount; $i++) {
                $key = $additionaltextplaceholders[$i] ?? null;
                $value = $object->data[formatter::TYPE_PLACEHOLDER_TEXT][$key] ?? '';

                $placeholder = new \stdClass();
                $placeholder->data_exists = !empty($value);
                $placeholder->data = $value;

                if (!empty($additionaltextlabels[$i]) && !empty($textdataholders[$key])) {
                    $placeholder->show_label = true;
                    $placeholder->label = $textdataholders[$key]->name;
                }

                $data->text_placeholders[] = $placeholder;
            }
        }

        $data->icon_placeholders_enabled = (bool)$config->get_value('item_additional_icons_enabled');
        if ($data->icon_placeholders_enabled) {
            $data->icon_placeholders = [];

            $additionaliconplaceholders = $provider->get_config('item_additional_icons');

            foreach ($additionaliconplaceholders as $additionaliconplaceholder) {
                $icons = $object->data[formatter::TYPE_PLACEHOLDER_ICONS][$additionaliconplaceholder] ?? [];
                $data->icon_placeholders = array_merge($data->icon_placeholders, $icons);
            }
        }

        return new static((array)$data);
    }
}