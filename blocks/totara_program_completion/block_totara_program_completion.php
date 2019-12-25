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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package block
 * @subpackage totara_program_completion
 */

require_once($CFG->dirroot.'/totara/program/program.class.php');
require_once($CFG->dirroot.'/blocks/totara_program_completion/locallib.php');
/**
 * Program completion block.
 *
 * Displays program completions for the configured programs.
 */
class block_totara_program_completion extends block_base {

    public function init() {
        $this->title = get_string('programcompletion', 'block_totara_program_completion');

    }

    public function specialization() {
        if (!empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }

        if (!empty($this->config->titlelink)) {
            $link = new moodle_url($this->config->titlelink);
            $this->title = html_writer::link($link, $this->title);
        }
    }

    public function get_content() {
        global $USER, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        if (totara_feature_disabled('programs')) {
            return '';
        }
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->config->programids)) {
            return $this->content;
        }
        if (!$programs = block_totara_program_completion_programs::get_programs($this->instance->id)) {
            return $this->content;
        }

        $args = array('instanceid' => $this->instance->id);
        $this->page->requires->strings_for_js(array('more', 'less'), 'block_totara_program_completion');
        $this->page->requires->js_call_amd('block_totara_program_completion/block', 'init', $args);

        $count = 0;
        $content = '';
        foreach ($programs as $p) {
            try {
                $program = new program($p->id);
            } catch (ProgramException $e) {
                // Just ignore invalid things.
                continue;
            }
            if (!$this->config->shownotassigned && !$program->user_is_assigned($USER->id)) {
                continue;
            }

            $count++;
            $maxreached = !empty($this->config->maxshow) && $count > $this->config->maxshow;
            $rowclass = $maxreached ? "row more more{$this->instance->id}" : "row";
            $programlink = new moodle_url('/totara/program/view.php', array('id' => $p->id));

            $name = html_writer::div(html_writer::link($programlink, format_string($program->fullname)), 'name');
            $value = html_writer::div(prog_display_progress($program->id, $USER->id), 'value');
            $content .= html_writer::div($name . $value, $rowclass);
        }

        if (!empty($this->config->maxshow) && $count > $this->config->maxshow) {
            $this->content->footer = html_writer::link('#', get_string('more', 'block_totara_program_completion'),
                array('class' => 'block-totara-prog-completion-morelink' . $this->instance->id)
            );
        }

        if (empty($content)) {
            return $this->content;
        }

        $this->content->text = html_writer::div($content, 'block-prog-completions-list');
        return $this->content;
    }
}
