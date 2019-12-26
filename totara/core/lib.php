<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @package totara
 * @subpackage totara_core
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/totara/core/totara.php');
require_once($CFG->dirroot . '/totara/core/deprecatedlib.php');

/**
 *  * Resize an image to fit within the given rectange, maintaing aspect ratio
 *
 * @param string Path to image
 * @param string Destination file - without file extention
 * @param int Width to resize to
 * @param int Height to resize to
 * @param string Force image to this format
 *
 * NOTE: this function was called resize_image() until Totara 10
 *
 * @global $CFG
 * @return string Path to new file else false
 */
function totara_resize_image($originalfile, $destination, $newwidth, $newheight, $forcetype = false) {
    global $CFG;

    require_once($CFG->libdir.'/gdlib.php');

    if(!(is_file($originalfile))) {
        return false;
    }

    $imageinfo = GetImageSize($originalfile);
    if (empty($imageinfo)) {
        return false;
    }

    $image = new stdClass;

    $image->width  = $imageinfo[0];
    $image->height = $imageinfo[1];
    $image->type   = $imageinfo[2];

    $ratiosrc = $image->width / $image->height;

    if ($newwidth/$newheight > $ratiosrc) {
        $newwidth = $newheight * $ratiosrc;
    } else {
        $newheight = $newwidth / $ratiosrc;
    }

    switch ($image->type) {
    case IMAGETYPE_GIF:
        if (function_exists('ImageCreateFromGIF')) {
            $im = ImageCreateFromGIF($originalfile);
            $outputformat = 'png';
        } else {
            notice('GIF not supported on this server');
            return false;
        }
        break;
    case IMAGETYPE_JPEG:
        if (function_exists('ImageCreateFromJPEG')) {
            $im = ImageCreateFromJPEG($originalfile);
            $outputformat = 'jpeg';
        } else {
            notice('JPEG not supported on this server');
            return false;
        }
        break;
    case IMAGETYPE_PNG:
        if (function_exists('ImageCreateFromPNG')) {
            $im = ImageCreateFromPNG($originalfile);
            $outputformat = 'png';
        } else {
            notice('PNG not supported on this server');
            return false;
        }
        break;
    default:
        return false;
    }

    if ($forcetype) {
        $outputformat = $forcetype;
    }

    $destname = $destination.'.'.$outputformat;

    if (function_exists('ImageCreateTrueColor') and $CFG->gdversion >= 2) {
        $im1 = ImageCreateTrueColor($newwidth,$newheight);
    } else {
        $im1 = ImageCreate($newwidth, $newheight);
    }
    ImageCopyBicubic($im1, $im, 0, 0, 0, 0, $newwidth, $newheight, $image->width, $image->height);

    switch($outputformat) {
    case 'jpeg':
        imagejpeg($im1, $destname, 90);
        break;
    case 'png':
        imagepng($im1, $destname, 9);
        break;
    default:
        return false;
    }
    return $destname;
}


/**
 * hook to add extra sticky-able page types.
 */
function local_get_sticky_pagetypes() {
    return array(
        // not using a constant here because we're doing funky overrides to PAGE_COURSE_VIEW in the learning path format
        // and it clobbers the page mapping having them both defined at the same time
        'Totara' => array(
            'id' => 'Totara',
            'lib' => '/totara/core/lib.php',
            'name' => 'Totara'
        ),
    );
}

/**
 * Require login for ajax supported scripts
 *
 * @see require_login()
 */
function ajax_require_login($courseorid = null, $autologinguest = true, $cm = null, $setwantsurltome = true,
        $preventredirect = false) {
    if (is_ajax_request($_SERVER)) {
        try {
            require_login($courseorid, $autologinguest, $cm, $setwantsurltome, true);
        } catch (require_login_exception $e) {
            ajax_result(false, $e->getMessage());
            exit();
        }
    } else {
        require_login($courseorid, $autologinguest, $cm, $setwantsurltome, $preventredirect);
    }
}

/**
 * Return response to AJAX request
 * @param bool $success
 * @param string $message
 */
function ajax_result($success = true, $message = '') {
    if ($success) {
        echo 'success';
    } else {
        header('HTTP/1.0 500 Server Error');
        echo $message;
    }
}

/**
 * Drop table if exists
 *
 * @param string $table
 * @return bool
 */
function sql_drop_table_if_exists($table) {
    global $DB;
    $tablename = trim($table, '{}');
    $table = $DB->get_prefix() . $tablename;
    switch ($DB->get_dbfamily()) {
        case 'mssql':
            $sql = "IF OBJECT_ID('dbo.{$table}','U') IS NOT NULL DROP TABLE dbo.{$table}";
            break;
        case 'mysql':
            $sql = "DROP TABLE IF EXISTS \"{$table}\"";
            break;
        case 'postgres':
        default:
            $sql = "DROP TABLE IF EXISTS \"{$table}\"";
            break;
    }
    $DB->change_database_structure($sql, array($tablename));
    return true;
}

/**
 * Reorder elements based on order field
 *
 * @param int $id Element ID
 * @param int $pos It's new relative position, or -1 to make it last
 * @param string $table Table name
 * @param string $parentfield Field name
 * @param string $orderfield Order field name
 */
function db_reorder($id, $pos, $table, $parentfield = '', $orderfield = 'sortorder') {
    global $DB;
    $transaction = $DB->start_delegated_transaction();
    if ($parentfield != '') {
        $sql = 'SELECT tosort.id
                FROM {' . $table . '} tosort
                LEFT JOIN {' . $table . '} element
                    ON (element.' . $parentfield . ' = tosort.' . $parentfield . ')
                WHERE element.id = ?
                    AND tosort.id <> ?
                ORDER BY tosort.' . $orderfield;
        $records = $DB->get_records_sql($sql, array($id, $id));
    } else {
        $sql = 'SELECT tosort.id
                FROM {' . $table . '} tosort
                WHERE tosort.id <> ?
                ORDER BY tosort.' . $orderfield;
        $records = $DB->get_records_sql($sql, array($id));
    }
    $newpos = 0;
    $todb = new stdClass();
    $todb->id = $id;

    // Handle placing last.
    if ($pos == -1) {
        if ($parentfield != '') {
            $parentid = $DB->get_field($table, $parentfield, array('id' => $id));
            $sql = 'SELECT COUNT(*) FROM {' . $table . '} WHERE ' . $parentfield . ' = ?';
            $count = $DB->count_records_sql($sql, array($parentid));
        } else {
            $count = $DB->count_records($table);
        }

        if ($count > 0) {
            $pos = $count - 1;
        } else {
            $pos = 0;
        }
    }

    $todb->$orderfield = $pos;
    foreach ($records as $record) {
        if ($newpos == $pos) {
            ++$newpos;
        }
        $record->$orderfield = $newpos;
        $DB->update_record($table, $record);
        ++$newpos;
    }
    $DB->update_record($table, $todb);
    $transaction->allow_commit();
}

/**
 * Include code to pull in site version check code to notify the admin if
 * their site is not on the most current release.
 *
 * This function should only be included on the admin notification page.
 */
function totara_site_version_tracking() {
    global $CFG, $PAGE, $TOTARA;

    //Params for JS
    $totara_version = $TOTARA->version;
    preg_match('/^\d+/', $TOTARA->version, $matches);
    $major_version = $matches[0];
    $siteurl = parse_url($CFG->wwwroot);
    if (!empty($siteurl['scheme'])) {
        $protocol = $siteurl['scheme'];
    } else if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $protocol = 'https';
    } else {
        $protocol = 'http';
    }

    $PAGE->requires->strings_for_js(array('unsupported_branch_text', 'supported_branch_text', 'supported_branch_old_release_text'), 'totara_core', $major_version);
    $PAGE->requires->strings_for_js(array('old_release_text_singular', 'old_release_text_plural', 'old_release_security_text_singular', 'old_release_security_text_plural', 'totarareleaselink'), 'totara_core');

    $args = array('args' => '{"totara_version":"'.$totara_version.'", "major_version":"'.$major_version.'", "protocol":"'.$protocol.'"}');

    $jsmodule = array(
        'name' => 'totara_version_tracking',
        'fullpath' => '/totara/core/js/version_tracking.js',
        'requires' => array('json'));
    $PAGE->requires->js_init_call('M.totara_version_tracking.init', $args, false, $jsmodule);

}

/**
 * To download the file we upload in totara_core filearea
 *
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @return void Download the file
 */
function totara_core_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array()) {
    $component = 'totara_core';
    $itemid = $args[0];
    $filename = $args[1];
    $fs = get_file_storage();

    $file = $fs->get_file($context->id, $component, $filearea, $itemid, '/', $filename);

    if (empty($file)) {
        send_file_not_found();
    }

    send_stored_file($file, 60*60*24, 0, false, $options); // Enable long cache and disable forcedownload.
}

/**
 * Resize all images found in a filearea.
 *
 * @param int $contextid Context id where image(s) are
 * @param string $component Component where image(s) are
 * @param string $filearea Filearea where image(s) are
 * @param int $itemid Itemid where image(s) are
 * @param int $width Width that the image(s) should have
 * @param int $height Height that the image(s) should have
 * @param bool $replace If true, replace the file for the resized one
 * @return array $resizedimages Array of resized images
 */
function totara_resize_images_filearea($contextid, $component, $filearea, $itemid, $width, $height, $replace=false) {
    global $CFG, $USER;
    require_once($CFG->dirroot .'/lib/gdlib.php');

    $resizedimages = array();
    $fs = get_file_storage();
    $files = $fs->get_area_files($contextid, $component, $filearea, $itemid, 'id');

    foreach ($files as $file) {
        if (!$file->is_valid_image()) {
            continue;
        }
        $tmproot = make_temp_directory('thumbnails');
        $tmpfilepath = $tmproot . '/' . $file->get_contenthash();
        $file->copy_content_to($tmpfilepath);
        $imageinfo = getimagesize($tmpfilepath);
        if (empty($imageinfo) || ($imageinfo[0] <= $width && $imageinfo[1] <= $height)) {
            continue;
        }
        // Generate thumbnail.
        $data = generate_image_thumbnail($tmpfilepath, $width, $height);
        $resizedimages[] = $data;
        unlink($tmpfilepath);

        if ($replace) {
            $record = array(
                'contextid' => $file->get_contextid(),
                'component' => $file->get_component(),
                'filearea'  => $file->get_filearea(),
                'itemid'    => $file->get_itemid(),
                'filepath'  => '/',
                'filename'  => $file->get_filename(),
                'status'    => $file->get_status(),
                'source'    => $file->get_source(),
                'author'    => $file->get_author(),
                'license'   => $file->get_license(),
                'mimetype'  => $file->get_mimetype(),
                'userid'    => $USER->id,
            );
            $file->delete();
            $fs->create_file_from_string($record, $data);
        }
    }
    return $resizedimages ;
}

/**
 * Create recursively totara menu table
 *
 * @param html_table $table to add data to.
 * @param stdClass $item item record to render
 * @param int $depth of the category.
 * @param bool $up true if this category can be moved up.
 * @param bool $down true if this category can be moved down.
 * @param bool $dimmed true if this item and descendants should be shown as dimmed (due to dimmed ascendant).
 */
function totara_menu_table_load(html_table &$table, $item = null, $depth = 0, $up = false, $down = false, $dimmed = false) {
    global $OUTPUT, $DB;

    $str = new stdClass;
    $str->edit = new lang_string('edit');
    $str->delete = new lang_string('delete');
    $str->moveup = new lang_string('moveup');
    $str->movedown = new lang_string('movedown');
    $str->hide = new lang_string('hide');
    $str->show = new lang_string('show');
    $str->spacer = $OUTPUT->spacer(array('width' => 11, 'height' => 11));

    // Nasty hack: let's store the list of processed items in the $table for now.
    if (!isset($table->processedmenuitems)) {
        $table->processedmenuitems = array();
    }

    if ($item) {
        $parentid = $item->id;
    } else {
        $parentid = 0;
    }
    $children = $DB->get_records('totara_navigation', array('parentid' => $parentid), 'sortorder ASC, id ASC');

    if ($item) {
        if ($depth > 20) {
            // Break out of infinite recursion.
            return;
        }
        if ($item->classname === '\totara_core\totara\menu\unused') {
            // Skip this special section and all it's children for now.
            return;
        }
        $node = \totara_core\totara\menu\item::create_instance($item);
        if (!$node) {
            // Bad node, will be included in 'Unused' section.
            return;
        }

        $table->processedmenuitems[$item->id] = $item->id;

        $iscontainer = $node->is_container();
        $istoodeep = ((!$iscontainer and $depth > \totara_core\totara\menu\item::MAX_DEPTH)
            || ($iscontainer and $depth > \totara_core\totara\menu\item::MAX_DEPTH - 1));

        $dimmed = $dimmed || !$item->visibility || $node->is_disabled() || $istoodeep;
        $dimmedclass = $dimmed ? ' dimmed' : '';

        $url = '/totara/core/menu/index.php';

        $itemtitle = $node->get_title();

        if ($iscontainer) {
            $itemurl = '';
            $itemtype = get_string('menuitem:typeparent', 'totara_core');
        } else {
            $itemtype = get_string('menuitem:typeurl', 'totara_core');
            $itemurl = new moodle_url($node->get_url(true));
            $itemurl = html_writer::link($itemurl, s($node->get_url(false)), array('class' => $dimmedclass));
        }

        $attributes = array();
        $attributes['title'] = $str->edit;
        $attributes['class'] = 'totara_item_depth'.$depth.$dimmedclass;
        $itemtitle = html_writer::link(new moodle_url('/totara/core/menu/edit.php',
                array('id' => $item->id)), $itemtitle, $attributes);
        if ($help = $node->get_default_admin_help()) {
            $itemtitle .= $OUTPUT->help_icon($help[0], $help[1], null);
        }

        $icons = array();
        // Edit category.
        $icons[] = $OUTPUT->action_icon(
                        new moodle_url('/totara/core/menu/edit.php', array('id' => $item->id)),
                        new pix_icon('t/edit', $str->edit, 'moodle', array('class' => 'iconsmall')),
                        null, array('title' => $str->edit)
        );
        // Change visibility.
        if (!$node->is_disabled() and $depth <= \totara_core\totara\menu\item::MAX_DEPTH) {
            if ($item->visibility != \totara_core\totara\menu\item::VISIBILITY_HIDE) {
                $icons[] = $OUTPUT->action_icon(
                    new moodle_url($url, array('hideid' => $item->id, 'sesskey' => sesskey())),
                    new pix_icon('t/hide', $str->hide, 'moodle', array('class' => 'iconsmall')),
                    null, array('title' => $str->hide)
                );
            } else {
                $icons[] = $OUTPUT->action_icon(
                    new moodle_url($url, array('showid' => $item->id, 'sesskey' => sesskey())),
                    new pix_icon('t/show', $str->show, 'moodle', array('class' => 'iconsmall')),
                    null, array('title' => $str->show)
                );
            }
        } else {
            $icons[] = $str->spacer;
        }
        // Move up/down.
        if ($up) {
            $icons[] = $OUTPUT->action_icon(
                            new moodle_url($url, array('moveup' => $item->id, 'sesskey' => sesskey())),
                            new pix_icon('t/up', $str->moveup, 'moodle', array('class' => 'iconsmall')),
                            null, array('title' => $str->moveup)
            );
        } else {
            $icons[] = $str->spacer;
        }
        if ($down) {
            $icons[] = $OUTPUT->action_icon(
                            new moodle_url($url, array('movedown' => $item->id, 'sesskey' => sesskey())),
                            new pix_icon('t/down', $str->movedown, 'moodle', array('class' => 'iconsmall')),
                            null, array('title' => $str->movedown)
            );
        } else {
            $icons[] = $str->spacer;
        }
        // Delete item.
        if (\totara_core\totara\menu\helper::is_item_deletable($item->id)) {
            $icons[] = $OUTPUT->action_icon(
                            new moodle_url('/totara/core/menu/delete.php', array('id' => $item->id)),
                            new pix_icon('t/delete', $str->delete, 'moodle', array('class' => 'iconsmall')),
                            null, array('title' => $str->delete)
            );
        } else {
            $icons[] = $str->spacer;
        }

        if ($node->is_disabled()) {
            $itemvisibility = get_string('menuitem:typedisabled', 'totara_core');
        } else {
            if ($istoodeep) {
                $itemvisibility = get_string('menuitem:hiddentoodeep', 'totara_core');
                $itemvisibility .= $OUTPUT->help_icon('menuitem:hiddentoodeep', 'totara_core', null);
            } else {
                $itemvisibility = $node->get_visibility_description();
            }
        }

        if ($dimmed) {
            $itemtype = '<span class="dimmed_text">' . $itemtype . '</span>';
            $itemvisibility = '<span class="dimmed_text">' . $itemvisibility . '</span>';
        }

        $row = new html_table_row(array(
             new html_table_cell($itemtitle),
             new html_table_cell($itemtype),
             new html_table_cell($itemurl),
             new html_table_cell($itemvisibility),
             new html_table_cell(join(' ', $icons)),
        ));

        $row->id = \totara_core\totara\menu\helper::get_admin_edit_rowid($item->id);
        $table->data[] = $row;

        if (!$iscontainer) {
            // Ignore invalid children, they will be included in Unused section..
            $children = array();
        }
    }

    if ($children) {

        // Print all the children recursively.
        $countchildren = count($children);
        $count = 0;
        $first = true;
        $last  = false;
        foreach ($children as $node) {

            $count++;
            if ($count == $countchildren) {
                $last = true;
            }
            $up    = $first ? false : true;
            $down  = $last  ? false : true;
            $first = false;

            totara_menu_table_load($table, $node, $depth+1, $up, $down, $dimmed);
        }
    }

    if (!$item) {
        // We have just processed all valid used items, let's add 'Unused' container with the rest.
        $unusedcontainerid = \totara_core\totara\menu\helper::get_unused_container_id();

        list($select, $params) = $DB->get_in_or_equal($table->processedmenuitems, SQL_PARAMS_NAMED, 'mi', false, -1);
        unset($table->processedmenuitems); // End of nasty hack.

        $select = "id $select AND id <> :unusedid";
        $params['unusedid'] = $unusedcontainerid;

        $unuseditems = $DB->get_records_select('totara_navigation', $select, $params);
        foreach ($unuseditems as $unuseditem) {
            $unusednode = \totara_core\totara\menu\item::create_instance($unuseditem);
            if ($unusednode) {
                $unuseditem->node = $unusednode;
                $unuseditem->currenttitle = $unusednode->get_title();
            } else {
                $unuseditem->node = null;
                $unuseditem->currenttitle = $unuseditem->classname;
            }
        }

        if ($unuseditems) {
            $row = new html_table_row(array(
                new html_table_cell(html_writer::span(get_string('unused', 'totara_core'), 'totara_item_depth1 dimmed_text')),
                new html_table_cell(''),
                new html_table_cell(''),
                new html_table_cell(''),
                new html_table_cell(''),
            ));

            $row->id = \totara_core\totara\menu\helper::get_admin_edit_rowid($unusedcontainerid);
            $table->data[] = $row;

            core_collator::asort_objects_by_property($unuseditems, 'currenttitle');

            foreach ($unuseditems as $unuseditem) {
                /** @var \totara_core\totara\menu\item $node */
                $node = $unuseditem->node;

                if (!$node) {
                    $itemurl = '';
                    $itemtype = get_string('error');
                } else if ($node->is_container()) {
                    $itemurl = '';
                    $itemtype = get_string('menuitem:typeparent', 'totara_core');
                } else {
                    $itemtype = get_string('menuitem:typeurl', 'totara_core');
                    $itemurl = new moodle_url($node->get_url(true));
                    $itemurl = html_writer::link($itemurl, s($node->get_url(false)), array('class' => 'dimmed'));
                }
                $itemtype = '<span class="dimmed_text">' . $itemtype . '</span>';

                if (!$node) {
                    $attributes = array();
                    $attributes['class'] = 'totara_item_depth2 dimmed_text';
                    $itemtitle = html_writer::span($unuseditem->currenttitle, '', $attributes);
                } else {
                    $attributes = array();
                    $attributes['title'] = $str->edit;
                    $attributes['class'] = 'totara_item_depth2 dimmed';
                    $itemtitle = html_writer::link(new moodle_url('/totara/core/menu/edit.php',
                        array('id' => $unuseditem->id)), $unuseditem->currenttitle, $attributes);
                }

                $itemvisibility = '<span class="dimmed_text">' . get_string('unused', 'totara_core') . '</span>';

                $icons = array();

                // Edit category.
                if (!$node) {
                    $icons[] = $str->spacer;
                } else {
                    $icons[] = $OUTPUT->action_icon(
                        new moodle_url('/totara/core/menu/edit.php', array('id' => $unuseditem->id)),
                        new pix_icon('t/edit', $str->edit, 'moodle', array('class' => 'iconsmall')),
                        null, array('title' => $str->edit)
                    );
                }

                $icons[] = $str->spacer;
                $icons[] = $str->spacer;
                $icons[] = $str->spacer;

                // Delete item if no children present and it is either custom or broken item.
                if (\totara_core\totara\menu\helper::is_item_deletable($unuseditem->id)) {
                    $icons[] = $OUTPUT->action_icon(
                        new moodle_url('/totara/core/menu/delete.php', array('id' => $unuseditem->id)),
                        new pix_icon('t/delete', $str->delete, 'moodle', array('class' => 'iconsmall')),
                        null, array('title' => $str->delete)
                    );
                } else {
                    $icons[] = $str->spacer;
                }

                $row = new html_table_row(array(
                    new html_table_cell($itemtitle),
                    new html_table_cell($itemtype),
                    new html_table_cell($itemurl),
                    new html_table_cell($itemvisibility),
                    new html_table_cell(join(' ', $icons)),
                ));

                $row->id = \totara_core\totara\menu\helper::get_admin_edit_rowid($unuseditem->id);
                $table->data[] = $row;
            }
        }
    }
}

/**
 * Get userfrom based on settings.
 *
 * @param mixed $fromuser
 * @return mixed $userfrom The user to be set in emails
 */
function totara_get_user_from($fromuser = null) {
    global $CFG, $USER;

    $userfrom = clone(
        empty($fromuser) ? $USER : $fromuser
    );

    $userfrom->email = core_user::get_noreply_user()->email;
    return $userfrom;
}

/**
 * Get user that sent the message.
 *
 * @param $useridfrom
 * @return stdClass $userfrom User object.
 */
function totara_get_sender_from_user_by_id($useridfrom) {
    global $DB;

    // Get the user that sent the message.
    switch ($useridfrom) {
        case 0:
        case core_user::SUPPORT_USER:
            $from = core_user::get_support_user();
            break;
        case core_user::NOREPLY_USER:
            $from = core_user::get_noreply_user();
            break;
        case \mod_facetoface\facetoface_user::FACETOFACE_USER:
            $from = \mod_facetoface\facetoface_user::get_facetoface_user();
            break;
        default:
            $from = $DB->get_record('user', array('id' => $useridfrom));
            break;
    }

    return $from;
}

/**
 * This checks if the user has a given capability within the course category context
 * and returns the first category ID it finds where this is the case.
 *
 * @param string $capability The capability we are checking.
 * @return string Category id or bool False.
 */

function totara_get_categoryid_with_capability($capability) {
    global $DB;

    $recordid = false;

    $fields = context_helper::get_preload_record_columns_sql('ctx');
    $sql = "SELECT cc.id, cc.sortorder, cc.depth, cc.visible, $fields
                  FROM {course_categories} cc
                  JOIN {context} ctx ON cc.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
              ORDER BY depth ASC, sortorder ASC";
    $recordset = $DB->get_recordset_sql($sql, array('contextlevel' => CONTEXT_COURSECAT));
    foreach ($recordset as $record) {
        context_helper::preload_from_record($record);
        $context = context_coursecat::instance($record->id);
        if (!$record->visible && !has_capability('moodle/category:viewhiddencategories', $context)) {
            continue;
        }
        if (has_capability($capability, $context)) {
            $recordid = $record->id;
            break;
        }
    }
    $recordset->close();

    return $recordid;
}

/**
 * Run any required changes to completion data when updating activity settings.
 *
 * Ideally, this function would occur in a completion/lib.php file if it existed. But for now, exists here to
 * avoid potential Moodle merge conflicts.
 *
 * @param object|cm_info $cm - an object representing a course module. Could be object returned by get_coursemodule_from_id()
 * @param object $moduleinfo - e.g. data from form in course/modedit.php
 * @param object $course
 * @param completion_info $completion
 */
function totara_core_update_module_completion_data($cm, $moduleinfo, $course, $completion) {
    global $DB, $USER;

    if ($completion->is_enabled()) {
        if (!empty($moduleinfo->completionunlocked) && empty($moduleinfo->completionunlockednoreset)) {
            // This will wipe all user completion data.
            // It will be recalculated when completion_cron_completions() is next run.
            totara_core_uncomplete_course_modules_completion($cm, $completion);

            // Bulk start users (creates missing course_completion records for all active participants).
            completion_start_user_bulk($cm->course);

            // Trigger module_completion_reset event here.
            \totara_core\event\module_completion_reset::create_from_module($moduleinfo)->trigger();
        }

        $transaction = $DB->start_delegated_transaction();

        // TL-6981 Fix reaggregation of course completion after activity completion unlock.
        // Mark all users for reaggregation (regardless of what happens just above, in case something was missed).
        $now = time();
        $sql = "UPDATE {course_completions}
                   SET reaggregate = :now
                 WHERE course = :courseid
                   AND status < :statuscomplete";
        $params = array('now' => $now, 'courseid' => $course->id, 'statuscomplete' => COMPLETION_STATUS_COMPLETE);
        $DB->execute($sql, $params);

        $nowstring = \core_completion\helper::format_log_date($now);
        $logdescription = $DB->sql_concat(
            "'Updated current completion in totara_core_update_module_completion_data<br><ul>'",
            "'<li>Reaggregate: {$nowstring}</li>'",
            "'</ul>'"
        );
        $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
                SELECT course, userid, :changeuserid, {$logdescription}, :timemodified
                  FROM {course_completions}
                 WHERE course = :courseid AND status < :statuscomplete";
        $params = array(
            'changeuserid' => $USER->id,
            'timemodified' => $now,
            'cmid' => $cm->id,
            'courseid' => $cm->course,
            'statuscomplete' => COMPLETION_STATUS_COMPLETE
        );
        $DB->execute($sql, $params);

        $transaction->allow_commit();

        // Trigger module_completion_criteria_updated event here.
        \totara_core\event\module_completion_criteria_updated::create_from_module($moduleinfo)->trigger();
    }
}

/**
 * Used in the completion_regular_task scheduled task. This reaggregates any activity completion records
 * in the course_modules_completion table that have a reaggregate flag set (as long as that flag is not a timestamp in
 * the future).  It then sets the reaggregate flag to zero for all of those records.
 *
 * Ideally, this function would occur in a completion/lib.php file if it existed. But for now, exists here to
 * avoid potential Moodle merge conflicts.
 */
function totara_core_reaggregate_course_modules_completion() {
    global $DB, $USER;

    $now = time();

    // Get records in course_modules_completion that require aggregation, as long as they are for
    // course modules that have completion enabled.
    $completionsql = '
        SELECT cmc.*, cm.course as courseid
          FROM {course_modules_completion} cmc
          JOIN {course_modules} cm
            ON cmc.coursemoduleid = cm.id
         WHERE cm.completion <> 0
           AND cmc.reaggregate > 0
           AND cmc.reaggregate < :now';
    $completionparams = array('now' => $now);

    $completions = $DB->get_records_sql($completionsql, $completionparams);

    if (empty($completions)) {
        // Nothing to reaggregate. No need to continue.
        return;
    }

    if (debugging() && !PHPUNIT_TEST && !defined('BEHAT_TEST')) {
        mtrace('Aggregating activity completions in course_modules_completions table.');
    }

    $cms = array();

    foreach($completions as $completion) {
        if (!isset($cms[$completion->coursemoduleid])) {
            $cms[$completion->coursemoduleid] = $DB->get_record('course_modules', array('id' => $completion->coursemoduleid));
        }

        $course = new stdClass();
        $course->id = $completion->courseid;
        $completioninfo = new completion_info($course);
        $completioninfo->update_state($cms[$completion->coursemoduleid], COMPLETION_UNKNOWN, $completion->userid);
    }

    // Reset all reaggregate flags that would have been covered above to zero.
    // Note that this will additionally reset any reaggregate flags between 0 and time() where
    // activity completion is not enabled.
    // IMPORTANT: This does not update the timemodified field of the course_modules_completion record
    // on purpose. This is because timemodified is currently used to get the completion time in various
    // cases in Totara and Moodle code.

    // Note that this transaction doesn't need to include update_state above - the log records the resetting of the reaggregate flag only.
    $transaction = $DB->start_delegated_transaction();

    $logdescription = $DB->sql_concat(
        "'Updated module completion in totara_core_reaggregate_course_modules_completion<br><ul>'",
        "'<li>CMCID: '",
        $DB->sql_cast_2char("cmc.id"),
        "'</li>'",
        "'<li>Reaggregate: Not set (0)</li>'",
        "'</ul>'"
    );
    $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
                SELECT cm.course, cmc.userid, :changeuserid, {$logdescription}, :timemodified
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                 WHERE cmc.reaggregate > 0
                   AND cmc.reaggregate < :now";
    $params = array(
        'changeuserid' => $USER->id,
        'timemodified' => $now,
        'now' => $now
    );
    $DB->execute($sql, $params);

    $resetsql = '
        UPDATE {course_modules_completion}
           SET reaggregate = 0
         WHERE reaggregate > 0
           AND reaggregate < :now';
    $resetparams = array('now' => $now);
    $DB->execute($resetsql, $resetparams);

    $transaction->allow_commit();

    if (debugging() && !PHPUNIT_TEST && !defined('BEHAT_TEST')) {
        mtrace('Finished aggregating activity completions.');
    }

    return;
}

/**
 * Sets all activity completion records in the course_modules_completion to be incomplete. It also sets timecompleted to null
 * and flags the records for reaggregation when the totara_core_reaggregate_course_modules_completion function is next run in cron.
 *
 * This will also delete course completions where the given activity is a criteria for that course completion.
 *
 * Ideally, this function would occur in a completion/lib.php file if it existed. But for now, exists here to
 * avoid potential Moodle merge conflicts.
 *
 * @param stdClass|cm_info $cm - an object representing a course module. Could be object returned by get_coursemodule_from_id(),
 *  or even just a record from the course_modules table.
 * @param completion_info $completion
 * @param null|int $now - a timestamp. Leave as null to set reaggregate and timemodified to now. Intended to only be set to
 *  anything else when unit testing.
 */
function totara_core_uncomplete_course_modules_completion($cm, $completion, $now = null) {
    global $DB, $USER;

    if (!isset($now)) {
        $now = time();
    }

    $transaction = $DB->start_delegated_transaction();

    // The completion state is set to incomplete. Timecompleted is also set to null at this point.
    // The timemodified field is also updated. This is consistent with other places where the state changes from complete to incomplete.
    // Ideally we would not update timemodified when the state was already incomplete, as we are not updating timemodified when
    // the reaggregate flag is the only thing changing.
    // But timemodified is not an issue when the record is incomplete and it's better not to complicate the code.
    $modulecompletionsql = "UPDATE {course_modules_completion}
                               SET reaggregate = :reaggregate, completionstate = :incomplete, timemodified = :timemodified, timecompleted = NULL
                             WHERE coursemoduleid = :cmid";
    $modulecompletionparams = array('reaggregate' => $now, 'incomplete' => COMPLETION_INCOMPLETE, 'timemodified' => $now, 'cmid' => $cm->id);
    $DB->execute($modulecompletionsql, $modulecompletionparams);

    // Log the changes.
    $nowstring = \core_completion\helper::format_log_date($now);
    $logdescription = $DB->sql_concat(
        "'Updated module completion in totara_core_uncomplete_course_modules_completion<br><ul>'",
        "'<li>CMCID: '",
        $DB->sql_cast_2char("cmc.id"),
        "'</li>'",
        "'<li>Completion state: Not complete (" . COMPLETION_INCOMPLETE . ")</li>'",
        "'<li>Viewed: '",
        "COALESCE(" . $DB->sql_cast_2char("cmc.viewed") . ", '')",
        "'</li>'",
        "'<li>Time modified: {$nowstring}</li>'",
        "'<li>Time completed: Not set (null)</li>'",
        "'<li>Reaggregate: {$nowstring}</li>'",
        "'</ul>'"
    );
    $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
            SELECT :courseid, cmc.userid, :changeuserid, {$logdescription}, :timemodified
              FROM {course_modules_completion} cmc
             WHERE cmc.coursemoduleid = :cmid";
    $params = array(
        'courseid' => $cm->course,
        'changeuserid' => $USER->id,
        'timemodified' => $now,
        'cmid' => $cm->id
    );
    $DB->execute($sql, $params);

    // The rest of this function is copied from delete_all_state in lib/completionlib.php.

    // Check if there is an associated course completion criteria
    $criteria = $completion->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
    $acriteria = false;
    foreach ($criteria as $criterion) {
        if ($criterion->moduleinstance == $cm->id) {
            $acriteria = $criterion;
            break;
        }
    }

    if ($acriteria) {
        // Log and delete all criteria completions relating to this activity, but skip any RPL records.
        $logdescription = $DB->sql_concat(
            "'Deleted crit compl in totara_core_uncomplete_course_modules_completion<br><ul><li>CCCCID: '",
            $DB->sql_cast_2char("id"),
            "'</li></ul>'"
        );
        $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
                SELECT course, userid, :changeuserid, {$logdescription}, :timemodified
                  FROM {course_completion_crit_compl}
                 WHERE course = :courseid AND criteriaid = :criteriaid AND (rpl = '' OR rpl IS NULL)";
        $params = array(
            'changeuserid' => $USER->id,
            'timemodified' => $now,
            'cmid' => $cm->id,
            'courseid' => $cm->course,
            'criteriaid' => $acriteria->id
        );
        $DB->execute($sql, $params);

        $where = "course = ? AND criteriaid = ? AND (rpl = '' OR rpl IS NULL)";
        $DB->delete_records_select('course_completion_crit_compl', $where, array($cm->course, $acriteria->id));

        // Log and delete all course completions relating to this activity, but skip any RPL records.
        $sql = "INSERT INTO {course_completion_log} (courseid, userid, changeuserid, description, timemodified)
                SELECT course, userid, :changeuserid, :logdescription, :timemodified
                  FROM {course_completions}
                 WHERE course = :courseid AND (rpl = '' OR rpl IS NULL)";
        $params = array(
            'changeuserid' => $USER->id,
            'logdescription' => 'Deleted current completion in totara_core_uncomplete_course_modules_completion',
            'timemodified' => $now,
            'cmid' => $cm->id,
            'courseid' => $cm->course
        );
        $DB->execute($sql, $params);

        $DB->delete_records_select('course_completions', "course = ? AND (rpl = '' OR rpl IS NULL)", array($cm->course));
    }

    $transaction->allow_commit();

    // Purge the course completion cache.
    $cache = cache::make('core', 'completion');
    $cache->purge();
}

/**
 * Helper function to update task schedule
 *
 * @param $task String  the classname of the task
 * @param $oldschedule  Array   the current task schedule
 * @param $newschedule  Array   the new task schedule
 * @return true
 */
function totara_upgrade_default_schedule($task, $oldschedule, $newschedule) {
    global $DB;

    $params = array(
        'classname' => $task,
        'minute' => $oldschedule['minute'],
        'hour' => $oldschedule['hour'],
        'day' => $oldschedule['day'],
        'month' => $oldschedule['month'],
        'dayofweek' => $oldschedule['dayofweek']
    );

    $task = $DB->get_record('task_scheduled',$params);

    if (!empty($task)) {
        $task->minute = $newschedule['minute'];
        $task->hour = $newschedule['hour'];
        $task->day = $newschedule['day'];
        $task->month = $newschedule['month'];
        $task->dayofweek = $newschedule['dayofweek'];
        $DB->update_record('task_scheduled', $task);
    }

    return true;
}