<?php
/*
 * Copyright (C) 2015 onwards Catalyst IT
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
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\output\flex_icon;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/ojt/lib.php');

class mod_ojt_renderer extends plugin_renderer_base {

    function config_topics($ojt, $config=true) {
        global $DB;

        $out = '';
        $out .= html_writer::start_tag('div', array('id' => 'config-mod-ojt-topics'));

        $topics = $DB->get_records('ojt_topic', array('ojtid' => $ojt->id), 'id');
        if (empty($topics)) {
            return html_writer::tag('p', get_string('notopics', 'ojt'));
        }
        foreach ($topics as $topic) {
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic'));
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-heading'));
            $optionalstr = $topic->completionreq == OJT_OPTIONAL ? ' ('.get_string('optional', 'ojt').')' : '';
            $out .= format_string($topic->name).$optionalstr;
            if ($config) {
                $additemurl = new moodle_url('/mod/ojt/topicitem.php', array('bid' => $ojt->id, 'tid' => $topic->id));
                $out .= $this->output->action_icon($additemurl, new flex_icon('plus', ['alt' => get_string('additem', 'ojt')]));
                $editurl = new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id, 'id' => $topic->id));
                $out .= $this->output->action_icon($editurl, new flex_icon('edit', ['alt' => get_string('edittopic', 'ojt')]));
                $deleteurl = new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id, 'id' => $topic->id, 'delete' => 1));
                $out .= $this->output->action_icon($deleteurl, new flex_icon('delete', ['alt' => get_string('deletetopic', 'ojt')]));
            }
            $out .= html_writer::end_tag('div');

            $out .= $this->config_topic_items($ojt->id, $topic->id, $config);
            $out .= html_writer::end_tag('div');
        }

        $out .= html_writer::end_tag('div');

        return $out;
    }

    function config_topic_items($ojtid, $topicid, $config=true) {
        global $DB;

        $out = '';

        $items = $DB->get_records('ojt_topic_item', array('topicid' => $topicid), 'id');

        $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-items'));
        foreach ($items as $item) {
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-item'));
            $optionalstr = $item->completionreq == OJT_OPTIONAL ? ' ('.get_string('optional', 'ojt').')' : '';
            $out .= format_string($item->name).$optionalstr;
            if ($config) {
                $editurl = new moodle_url('/mod/ojt/topicitem.php',
                    array('bid' => $ojtid, 'tid' => $topicid, 'id' => $item->id));
                $out .= $this->output->action_icon($editurl, new flex_icon('edit', ['alt' => get_string('edititem', 'ojt')]));
                $deleteurl = new moodle_url('/mod/ojt/topicitem.php',
                    array('bid' => $ojtid, 'tid' => $topicid, 'id' => $item->id, 'delete' => 1));
                $out .= $this->output->action_icon($deleteurl, new flex_icon('delete', ['alt' => get_string('deleteitem', 'ojt')]));
            }
            $out .= html_writer::end_tag('div');
        }
        $out .= html_writer::end_tag('div');

        return $out;
    }

    // Build a user's ojt form
    function user_ojt($userojt, $evaluate=false, $signoff=false, $itemwitness=false) {
        global $CFG, $DB, $USER, $PAGE;

        $out = '';
        $out = html_writer::start_tag('div', array('id' => 'mod-ojt-user-ojt', 'class' => 'formOjtWitness'));

        $course = $DB->get_record('course', array('id' => $userojt->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('ojt', $userojt->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        foreach ($userojt->topics as $topic) {
            $out .= html_writer::start_tag('div', array('class' => 'mod-ojt-topic', 'id' => "ojt-topic-{$topic->id}"));
            switch ($topic->status) {
                case OJT_COMPLETE:
                    $completionicon = 'check-success';
                    break;
                case OJT_REQUIREDCOMPLETE:
                    $completionicon = 'check-warning';
                    break;
                default:
                    $completionicon = 'times-danger';
            }
            if (!empty($completionicon)) {
                $completionicon = $this->output->flex_icon($completionicon, ['alt' => get_string('completionstatus'.$topic->status, 'ojt')]);
            }

            $completionicon = html_writer::tag('span', $completionicon,
                array('class' => 'ojt-topic-status'));

            $optionalstr = $topic->completionreq == OJT_OPTIONAL ?
                html_writer::tag('em', ' ('.get_string('optional', 'ojt').')') : '';
            $out .= html_writer::tag('div', format_string($topic->name).$optionalstr.$completionicon,
                array('class' => 'mod-ojt-topic-heading expanded'));

            $table = new html_table();
            $table->attributes['class'] = 'mod-ojt-topic-items generaltable';
            if ($userojt->itemwitness) {
                $table->head = array('', '', get_string('witnessed', 'mod_ojt'));
            }
            $table->data = array();

            foreach ($topic->items as $item) {

                $row = array();
                $optionalstr = $item->completionreq == OJT_OPTIONAL ?
                    html_writer::tag('em', ' ('.get_string('optional', 'ojt').')') : '';
                
                if ($evaluate) {
                    $completionicon = $item->status == OJT_COMPLETE ? 'completion-manual-y' : 'completion-manual-n';
                    $cellcontent = html_writer::start_tag('div', array('class' => 'ojt-eval-actions', 'ojt-item-id' => $item->id));
                    $cellcontent .= $this->output->flex_icon($completionicon, ['classes' => 'ojt-completion-toggle']);
                    $cellcontent2 = $cellcontent;
                    $cellcontent = '';
                    //echo "<pre>";print_r($cellcontent);echo '</pre>';
                    $cellcontent .= html_writer::tag('textarea', $item->comment,
                        array('name' => 'comment-'.$item->id, 'rows' => 3,
                            'class' => 'ojt-completion-comment', 'ojt-item-id' => $item->id));
                    $cellcontent .= html_writer::tag('div', format_text($item->comment, FORMAT_PLAIN),
                        array('class' => 'ojt-completion-comment-print', 'ojt-item-id' => $item->id));
                    $cellcontent .= html_writer::end_tag('div');
                    $row[] = $cellcontent2.format_string($item->name).$optionalstr;

                } else {
                    // Show static stuff.
                    $cellcontent = '';
                    if ($item->status == OJT_COMPLETE) {
                        $cellcontent1 = $this->output->flex_icon('check-success',
                            ['alt' => get_string('completionstatus'.OJT_COMPLETE, 'ojt')]);
                    } else {
                        $cellcontent1 = $this->output->flex_icon('times-danger',
                            ['alt' => get_string('completionstatus'.OJT_INCOMPLETE, 'ojt')]);
                    }

                    $cellcontent .= format_text($item->comment, FORMAT_PLAIN);
                    $row[] = $cellcontent1.format_string($item->name).$optionalstr;

                }

                $userobj = new stdClass();
                $userobj = username_load_fields_from_object($userobj, $item, $prefix = 'modifier');
                
                $cellcontent .= html_writer::tag('div', ojt_get_modifiedstr($item->timemodified, $userobj),
                    array('class' => 'mod-ojt-modifiedstr', 'ojt-item-id' => $item->id));

                if ($item->allowfileuploads || $item->allowselffileuploads) {
                    $cellcontent .= html_writer::tag('div', $this->list_topic_item_files($context->id, $userojt->userid, $item->id),
                        array('class' => 'mod-ojt-topicitem-files'));

                    if (($evaluate && $item->allowfileuploads) || ($userojt->userid == $USER->id && $item->allowselffileuploads)) {
                        $itemfilesurl = new moodle_url('/mod/ojt/uploadfile.php', array('userid' => $userojt->userid, 'tiid' => $item->id));
                        $cellcontent .= $this->output->single_button($itemfilesurl, get_string('updatefiles', 'ojt'), 'get');
                    }
                }

                $row[] = html_writer::tag('p', $cellcontent, array('class' => 'ojt-completion'));

                if ($userojt->itemwitness) {
                    $cellcontent = '';
                    if ($itemwitness) {
                        $witnessicon = $item->witnessedby ? 'completion-manual-y' : 'completion-manual-n';
                        $cellcontent .= html_writer:: start_tag('span', array('class' => 'ojt-witness-item', 'ojt-item-id' => $item->id));
                        $cellcontent .= $this->output->flex_icon($witnessicon, ['classes' => 'ojt-witness-toggle']);
                        $cellcontent .= html_writer::end_tag('div');

                    } else {
                        // Show static witness info
                        if (!empty($item->witnessedby)) {
                            $cellcontent .= $this->output->flex_icon('check-success',
                                ['alt' => get_string('witnessed', 'ojt')]);
                        } else {
                            $cellcontent .= $this->output->flex_icon('times-danger',
                                ['alt' => get_string('notwitnessed', 'ojt')]);
                        }
                    }

                    $userobj = new stdClass();
                    $userobj = username_load_fields_from_object($userobj, $item, $prefix = 'itemwitness');
                    $cellcontent .= html_writer::tag('div', ojt_get_modifiedstr($item->timewitnessed, $userobj),
                        array('class' => 'mod-ojt-witnessedstr', 'ojt-item-id' => $item->id));

                    $row[] = html_writer::tag('p', $cellcontent, array('class' => 'ojt-item-witness'));
                }

                $table->data[] = $row;
            }

            $out .= html_writer::table($table);

            
            // Topic comments
            if ($topic->allowcomments) {
                $out .= $this->output->heading(get_string('topiccomments', 'ojt'), 4);
                require_once($CFG->dirroot.'/comment/lib.php');
                comment::init();
                $options = new stdClass();
                $options->area    = 'ojt_topic_item_'.$topic->id;
                $options->context = $context;
                $options->itemid  = $userojt->userid;
                $options->showcount = true;
                $options->component = 'ojt';
                $options->autostart = true;
                $options->notoggle = true;
                $comment = new comment($options);
                $out .= $comment->output(true);
            }

            // Topic signoff
            if ($userojt->managersignoff) {
                $out .= html_writer::start_tag('div', array('class' => 'mod-ojt-topic-signoff', 'ojt-topic-id' => $topic->id));
                if ($signoff) {
                    $out .= $this->output->flex_icon($topic->signedoff ? 'completion-manual-y' : 'completion-manual-n',
                        ['classes' => 'ojt-topic-signoff-toggle']);
                } else {
                    if ($topic->signedoff) {
                        $out .= $this->output->flex_icon('check-success', ['alt' => get_string('signedoff', 'ojt')]);
                    } else {
                        $out .= $this->output->flex_icon('times-danger', ['alt' => get_string('notsignedoff', 'ojt')]);
                    }
                }
                $out .= get_string('managersignoff', 'ojt');
                
                $userobj = new stdClass();
                $userobj = username_load_fields_from_object($userobj, $topic, $prefix = 'signoffuser');
                $out .= html_writer::tag('div', ojt_get_modifiedstr($topic->signofftimemodified, $userobj),
                    array('class' => 'mod-ojt-topic-modifiedstr'));
                $out .= html_writer::end_tag('div');
            }


            $out .= html_writer::end_tag('div');  // mod-ojt-topic
        }

        $out .= html_writer::end_tag('div');  // mod-ojt-user-ojt

        return $out;
    }

    protected function list_topic_item_files($contextid, $userid, $topicitemid) {
        $out = array();

        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'mod_ojt', 'topicitemfiles'.$topicitemid, $userid, 'itemid, filepath, filename', false);

        foreach ($files as $file) {
            $filename = $file->get_filename();
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            $out[] = html_writer::link($url, $filename);
        }
        $br = html_writer::empty_tag('br');

        return implode($br, $out);
    }

}

