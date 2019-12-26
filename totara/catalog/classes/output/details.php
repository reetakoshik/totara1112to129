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

class details extends template {

    /**
     * Gets all of the dataholders which are required to populate the details template.
     *
     * @param provider $provider
     * @return required_dataholder[]
     */
    public static function get_required_dataholders(provider $provider): array {
        $objecttype = $provider->get_object_type();

        $titledataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE);
        $textdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TEXT);
        $iconsdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_ICONS);

        $config = config::instance();

        $requireddataholders = [];

        if ($config->get_value('details_title_enabled')) {
            if (!empty($titledataholders[$provider->get_config('details_title', $objecttype)])) {
                $dataholder = $titledataholders[$provider->get_config('details_title', $objecttype)];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TITLE);
            }
        }

        if ($config->get_value('rich_text_content_enabled')) {
            $richtextdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_RICH_TEXT);
            if (!empty($richtextdataholders[$provider->get_config('rich_text', $objecttype)])) {
                $dataholder = $richtextdataholders[$provider->get_config('rich_text', $objecttype)];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_RICH_TEXT);
            }
        }

        if ($config->get_value('details_description_enabled')) {
            if (!empty($textdataholders[$provider->get_config('details_description', $objecttype)])) {
                $dataholder = $textdataholders[$provider->get_config('details_description', $objecttype)];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_TEXT);
            }
        }

        $additionaltexts = $provider->get_config('details_additional_text', $objecttype);
        $additionaltextcount = $config->get_value('details_additional_text_count');
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

        $additionaliconsenabled = $config->get_value('details_additional_icons_enabled');
        $additionalicons = $provider->get_config('details_additional_icons', $objecttype);
        if ($additionaliconsenabled) {
            foreach ($additionalicons as $additionalicon) {
                if (empty($iconsdataholders[$additionalicon])) {
                    continue;
                }

                $dataholder = $iconsdataholders[$additionalicon];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_PLACEHOLDER_ICONS);
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
     * @param string $request
     * @return details
     */
    public static function create(\stdClass $object, string $request) {
        $provider = provider_handler::instance()->get_provider($object->objecttype);

        $config = config::instance();

        $data = new \stdClass();
        $data->id = $object->id;

        $data->title_enabled = $config->get_value('details_title_enabled');
        if ($data->title_enabled) {
            $titledataholderkey = $provider->get_config('details_title');
            $data->title = $object->data[formatter::TYPE_PLACEHOLDER_TITLE][$titledataholderkey] ?? '';
        }

 $data->image_enabled = (bool)$config->get_value('image_enabled');      
        if ($data->image_enabled) {     
            $imagedataholderkey = $provider->get_data_holder_config('image');       
            $data->image = $object->data[formatter::TYPE_PLACEHOLDER_IMAGE][$imagedataholderkey] ?? '';     
        }       
//echo '<pre>';print_r($object);echo "</pre>";      
//die('test123');       
        $data->manage_link = $provider->get_manage_link($object->objectid);     
        global $DB, $CFG;       
        $ctypeurl = $CFG->wwwroot.'/theme/golearningzone/pix/external-elearning.svg';       
        $ctypeval="E - learning";       
        if($object->objecttype == 'course') {       
            $coursedesc = $DB->get_record_sql("SELECT summary, coursetype FROM {course} WHERE id = '".$object->objectid."'");       
            $data->objectdesc = $coursedesc->summary;       
            $context = $DB->get_record_sql("SELECT id FROM {context} WHERE contextlevel = '".CONTEXT_COURSE."' AND instanceid = '".$object->objectid."'");      
            $files = $DB->get_records_sql("SELECT filename FROM {files} WHERE component = 'course' AND contextid = '".$context->id."' AND itemid = '".$object->objectid."'");       
            $filearr = '#';     
            foreach($files as $file) {      
                $imageFileType = strtolower(pathinfo($file->filename,PATHINFO_EXTENSION));      
                if(!empty($imageFileType) && ($imageFileType == 'jpg' || $imageFileType == 'jpeg' || $imageFileType == 'png' || $imageFileType == 'gif')) {     
                    $filearr = $file->filename;     
                }       
                        
            }       
            $imagepath = $CFG->wwwroot."/pluginfile.php/$context->id/course/images/$object->objectid/$filearr";     
            $data->objctimage = $imagepath;     
            if($coursedesc->coursetype == 2) {      
                $ctypeurl = $CFG->wwwroot.'/theme/golearningzone/pix/in-class-icon.svg';        
                $ctypeval= "Seminar";       
            } elseif($coursedesc->coursetype == 1) {        
                $ctypeurl = $CFG->wwwroot.'/theme/golearningzone/pix/blended.svg';      
                $ctypeval="Blended";        
            }       
            $data->coursetypeimgurl = $ctypeurl;        
            $data->coursetypeval = $ctypeval;
            } 
            elseif($object->objecttype == 'program') {
              $programurl = $CFG->wwwroot.'/totara/program/view.php?id='.$object->objectid;  
            $programlabel = 'Edit program details';
            $pobj = new \stdClass();
            $pobj->url = $programurl;
            $pobj->label = $programlabel;
            $data->manage_link = $pobj;

            $coursedesc = $DB->get_record_sql("SELECT summary FROM {prog} WHERE id = '".$object->objectid."'");
            $data->objectdesc = $coursedesc->summary;
            $context = $DB->get_record_sql("SELECT id FROM {context} WHERE contextlevel = '".CONTEXT_PROGRAM."' AND instanceid = '".$object->objectid."'");
            $files = $DB->get_records_sql("SELECT filename FROM {files} WHERE component = 'totara_program' AND contextid = '".$context->id."' AND itemid = '".$object->objectid."'");
            $filearr = '#';
            foreach($files as $file) {
                $imageFileType = strtolower(pathinfo($file->filename,PATHINFO_EXTENSION));
                if(!empty($imageFileType) && ($imageFileType == 'jpg' || $imageFileType == 'jpeg' || $imageFileType == 'png' || $imageFileType == 'gif')) {
                    $filearr = $file->filename;
                }
                
            }
            $imagepath = $CFG->wwwroot."/pluginfile.php/$context->id/totara_program/images/$object->objectid/$filearr";
            $data->objctimage = $imagepath;
            $data->coursetypeval = $ctypeval;
            $data->coursetypeimgurl = $ctypeurl;
        } elseif($object->objecttype == 'certification') {
            $programurl = $CFG->wwwroot.'/totara/program/view.php?id='.$object->objectid;
            $programlabel = 'Edit certification details';
            $pobj = new \stdClass();
            $pobj->url = $programurl;
            $pobj->label = $programlabel;
            $data->manage_link = $pobj;
            
            $coursedesc = $DB->get_record_sql("SELECT summary FROM {prog} WHERE id = '".$object->objectid."'");
            $data->objectdesc = $coursedesc->summary;
            $context = $DB->get_record_sql("SELECT id FROM {context} WHERE contextlevel = '".CONTEXT_PROGRAM."' AND instanceid = '".$object->objectid."'");
            $files = $DB->get_records_sql("SELECT filename FROM {files} WHERE component = 'totara_program' AND contextid = '".$context->id."' AND itemid = '".$object->objectid."'");
            $filearr = '#';
            foreach($files as $file) {
                $imageFileType = strtolower(pathinfo($file->filename,PATHINFO_EXTENSION));
                if(!empty($imageFileType) && ($imageFileType == 'jpg' || $imageFileType == 'jpeg' || $imageFileType == 'png' || $imageFileType == 'gif')) {
                    $filearr = $file->filename;
                }
                
            }
            $imagepath = $CFG->wwwroot."/pluginfile.php/$context->id/totara_program/images/$object->objectid/$filearr";
            
            $data->objctimage = $imagepath;
            $data->coursetypeval = $ctypeval;
            $data->coursetypeimgurl = $ctypeurl;
        }
        //$data->manage_link = $provider->get_manage_link($object->objectid);
        $data->has_manage_link = !empty($data->manage_link);

        $data->details_link = $provider->get_details_link($object->objectid);
        $data->has_details_link = !empty($data->details_link);

        $data->rich_text_enabled = (bool)$config->get_value('rich_text_content_enabled');
        if ($data->rich_text_enabled) {
            $richtextdataholderkey = $provider->get_config('rich_text');
            $data->rich_text = $object->data[formatter::TYPE_PLACEHOLDER_RICH_TEXT][$richtextdataholderkey] ?? '';
        }

        $data->description_enabled = (bool)$config->get_value('details_description_enabled');
        if ($data->description_enabled) {
            $descriptiondataholderkey = $provider->get_config('details_description');
            $data->description = $object->data[formatter::TYPE_PLACEHOLDER_TEXT][$descriptiondataholderkey] ?? '';
        }

        $additionaltextplaceholdercount = $config->get_value('details_additional_text_count');
        $data->text_placeholders_enabled = $additionaltextplaceholdercount > 0;
        if ($data->text_placeholders_enabled) {
            $data->text_placeholders = [];

            $textdataholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TEXT);
            $additionaltextplaceholders = $provider->get_config('details_additional_text');
            $additionaltextlabels = $provider->get_config('details_additional_text_label');

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

        $data->icon_placeholders_enabled = (bool)$config->get_value('details_additional_icons_enabled');
        if ($data->icon_placeholders_enabled) {
            $data->icon_placeholders = [];

            $additionaliconplaceholders = $provider->get_config('details_additional_icons');

            foreach ($additionaliconplaceholders as $additionaliconplaceholder) {
                $icons = $object->data[formatter::TYPE_PLACEHOLDER_ICONS][$additionaliconplaceholder] ?? [];
                $data->icon_placeholders = array_merge($data->icon_placeholders, $icons);
            }
        }

        $data->request = $request;

        return new static((array)$data);
    }
}