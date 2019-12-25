<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

defined('MOODLE_INTERNAL') || die();

$string['addtile'] = 'Add Tile';
$string['aggregation_all'] = 'Users matching all of the criteria above can view this feature link';
$string['aggregation_any'] = 'Users matching any of the criteria above can view this feature link';
$string['aggregation_label'] = 'Ruleset aggregation';
$string['aggregation_title'] = 'Ruleset aggregation logic';
$string['audience_add'] = 'Add audiences';
$string['audience_aggregation_all'] = 'All of the audiences above';
$string['audience_aggregation_any'] = 'Any of the audiences above';
$string['audience_aggregation_label'] = 'Audience rule aggregation';
$string['audience_hide'] = 'You do not have permission to edit the audience visibility on this tile. Please contact the system administrator if you wish to do so. Number of visible audiences: {$a}';
$string['audience_showing'] = 'Define access by audience rules';
$string['audience_title'] = 'Audience';
$string['background'] = 'Background';
$string['backgroundappearance'] = 'Image appearance';
$string['backgroundcover'] = 'Fill tile';
$string['backgroundcontain'] = 'Fit inside tile';
$string['block_header'] = 'Title for the block';
$string['bottom_heading'] = 'Bottom';
$string['cannot_edit_tile'] = 'You do not have permissions to edit this tile';
$string['cannot_view_image'] = 'You are not able to see this image';
$string['certification_has_been_deleted'] = 'Certification has been deleted';
$string['certification_hidden'] = 'Hidden';
$string['certification_name'] = 'Certification';
$string['certification_name_label'] = 'Certification name';
$string['certification_not_selected'] = 'No certification selected';
$string['certification_not_found'] = 'Please select a certification';
$string['certification_select'] = 'Select certification';
$string['certification_sr-only'] = 'Link to the {$a} certification';
$string['certificationvisibility'] = 'Certification visibility';
$string['clear_color']  = 'Clear Colour Selection';
$string['confirm'] = 'Are you sure you want to delete this tile?';
$string['content_edit'] = 'Edit Tile';
$string['content_form_title'] = 'Edit {$a} tile';
$string['content_menu_title'] = 'Edit';
$string['content_menu_title_sr-only'] = 'Edit {$a} tile';
$string['color_error'] = 'The value entered did not match a hexadecimal value ie \'#11FFaa\'';
$string['course_has_been_deleted'] = 'Course has been deleted';
$string['course_hidden'] = 'Hidden';
$string['course_name'] = 'Course';
$string['course_name_label'] = 'Course name';
$string['course_not_found'] = 'Please select a course';
$string['course_not_selected'] = 'No course selected';
$string['course_select'] = 'Select course';
$string['course_sr-only'] = 'Link to the {$a} course';
$string['course_tile_title'] = 'Tile for the {$a} course';
$string['coursevisibility'] = 'Course visibility';
$string['currently_selected'] = 'Currently Selected';
$string['default_name'] = 'Static';
$string['delete_audience_rule'] = 'Delete audience {$a} from block';
$string['delete_menu_title'] = 'Delete';
$string['delete_menu_title_sr-only'] = 'Delete the {$a} tile';
$string['error_no_rule'] = 'Please select a rule';
$string['heading_location'] = 'Heading location';
$string['hidden_text'] = 'Hidden';
$string['interval'] = 'Interval (seconds)';
$string['interval_error'] = 'The Interval should be a positive number';
$string['interval_help'] = 'The time in seconds between the background image switching. An interval of 0 means no transitions. Values between 0 and 1 will be rounded up to 1.';
$string['is_visible'] = 'Is visible';
$string['less'] = 'less';
$string['link'] = 'Link Location';
$string['link_target_label'] = 'Open link in new tab';
$string['manual_id'] = 'Manual ID';
$string['manual_id_help'] = 'An ID that is applied to the block, this is so it can be used by themes.';
$string['multi_name'] = 'Gallery';
$string['pluginname'] = 'Featured Links';
$string['preset_aggregation_all'] = 'All of the selected preset rules above';
$string['preset_aggregation_any'] = 'Any of the selected preset rules above';
$string['preset_aggregation_label'] = 'Preset rule aggregation';
$string['preset_checkbox_admin'] = 'User is site administrator';
$string['preset_checkbox_guest'] = 'User is logged in as guest';
$string['preset_checkbox_loggedin'] = 'User is logged in';
$string['preset_checkbox_notguest'] = 'User is not logged in as guest';
$string['preset_checkbox_notloggedin'] = 'User is not logged in';
$string['preset_checkboxes_label'] = 'Condition required to view';
$string['preset_showing'] = 'Define access by preset rules';
$string['preset_title'] = 'Presets';
$string['program_has_been_deleted'] = 'Program has been deleted';
$string['program_hidden'] = 'Hidden';
$string['program_name'] = 'Program';
$string['program_name_label'] = 'Program name';
$string['program_not_selected'] = 'No program selected';
$string['program_not_found'] = 'Please select a program';
$string['program_select'] = 'Select program';
$string['program_sr-only'] = 'Link to the {$a} program';
$string['programvisibility'] = 'Program visibility';
$string['requires_alt_text'] = 'This tile is required to have an Alternative text for accessibility reasons';
$string['shape_fullwidth'] = 'Full width';
$string['shape_landscape'] = 'Landscape';
$string['shape_portrait'] = 'Portrait';
$string['shape_square'] = 'Square';
$string['show_progress_bar'] = 'Show progress';
$string['size_large'] = 'Large';
$string['size_medium']  = 'Medium';
$string['size_small'] = 'Small';
$string['text'] = 'Text';
$string['textbody'] = 'Text Body';
$string['tile_alt_text'] = 'Alternate text';
$string['tile_alt_text_help'] = 'Provide alternative information for this image if a user for some reason cannot view it (because of slow connection, an error in the src attribute, or if the user uses a screen reader).';
$string['tile_background'] = 'Image';
$string['tile_background_color'] = 'Background colour';
$string['tile_background_help'] = 'The images displayed will be square so it is recommended that you upload a square image. Some approximate sizes: small 150x150, medium 200x200, large 350x350. Accepted image types are .jpg, .png, .gif and .svg.';
$string['tile_description'] = 'Description';
$string['tile_gallery_background'] = 'Images';
$string['tile_gallery_background_help'] = 'If multiple images are selected, the background image will switch between the available images at random, at an interval specified by the interval setting. The images displayed will be square so it is recommended that you upload a square image. Some approximate sizes: small 150x150, medium 200x200, large 350x350. Accepted image types are .jpg, .png, .gif and .svg.';
$string['tile_position'] = 'Position of the tile';
$string['tile_rules_show'] = 'Define access by tile rules';
$string['tile_shape'] = 'Tile shape';
$string['tile_size'] = 'Tile size';
$string['tile_title'] = 'Title';
$string['tile_types'] = 'Tile type';
$string['tilerules_title'] = 'Custom Rules';
$string['top_heading'] = 'Top';
$string['top_tile_name'] = 'Label Top';
$string['totara_featured_links:addinstance'] = 'Add a new Featured Links block';
$string['totara_featured_links:edit'] = 'Edit the tiles in the block';
$string['totara_featured_links:myaddinstance'] = 'Add a new Featured Links block to the page';
$string['url_title'] = 'URL';
$string['url_title_help'] = 'URLs can be full names (http://www.sitename.com), partial names, (sitename.com) or local (for addresses on this Totara site). Local URLs should start with a slash ( / ) as this ensures the link will continue to work if the site\'s URL changes. When used, a local URL will have the site\'s web address added.';
$string['userdataitemtotara_featured_links_tiles'] = 'Featured links';
$string['visibility_custom'] = 'Apply rules';
$string['visibility_edit'] = 'Edit Visibility';
$string['visibility_form_title'] = 'Edit visibility of {$a} tile';
$string['visibility_hide'] = 'Hidden from all';
$string['visibility_label'] = 'Access';
$string['visibility_menu_title'] = 'Visibility';
$string['visibility_menu_title_sr-only'] = 'Edit visibility for the {$a} tile';
$string['visibility_show'] = 'Visible to all';
