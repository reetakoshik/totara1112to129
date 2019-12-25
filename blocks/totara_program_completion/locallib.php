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

/**
 * A class, representing programs of a program completion block.
 */
class block_totara_program_completion_programs implements Iterator {
    /**
     * Programs iterator position
     *
     * @var int
     */
    private $position = 0;
    /**
     * Programs to render
     *
     * @var array
     */
    private $items = array();

    /**
     * Block headers
     *
     * @var array
     */
    private $headers = array();

    /**
     * Completion info constructor
     *
     * @param int $blockid
     */
    public function __construct($blockid = 0) {
        $this->headers = array(
            get_string('programname', 'block_totara_program_completion'),
        );

        if ($blockid) {
            $this->set_items(self::get_programs($blockid));
        }
    }

    /**
     * Set programs to render
     *
     * @param array $items
     */
    public function set_items(array $items) {
        $this->rewind();
        foreach ($items as &$item) {
            if (is_number($item)) {
                $item = $this->get_item($item);
            }
        }
        $this->items = $items;
    }

    /**
     * Render a program.
     *
     * @param stdClass $item a program db object
     *
     * @return void
     */
    public function render_item($item, $readonly = false) {
        global $OUTPUT;

        $delete = '';
        if (!$readonly) {
            $delete = html_writer::link('#', $OUTPUT->pix_icon('t/delete', get_string('delete')),
                      array('title' => get_string('delete'), 'class'=>'blockprogramdeletelink'));
        }

        $nameitemhtml = html_writer::tag('li', format_string($item->fullname) . $delete,
                array('data-progid' => $item->id, 'class' => 'item'));

        return $nameitemhtml;
    }

    /**
     * Get a program item.
     *
     * @param int $itemid program id
     *
     * @return stdClass a program db object
     */
    public function get_item($itemid) {
        global $DB;

        return $DB->get_record('prog', array('id' => $itemid), 'id, fullname');
    }

    /**
     * Prints out the actual html.
     *
     * @param bool $return
     * @param string $type Type of the table
     *
     * @return string html
     */
    public function display($return = false) {
        $itemshtml = '';
        foreach ($this->items as $item) {
            $itemshtml .= $this->render_item($item);
        }

        $listhtml = html_writer::tag('ul', $itemshtml, array('id' => 'block-programs-table'));
        $html = html_writer::div($listhtml, '', array('id' => 'block-program-items'));
        if ($return) {
            return $html;
        }
        echo $html;
    }

    /**
     * Get the programs configured for a block.
     *
     * @param int $blockid block instance id
     *
     * @return array of program database objects
     */
    public static function get_programs($blockid) {
        global $DB;

        if (!$blockinstance = $DB->get_record('block_instances', array('id' => $blockid))) {
            return array();
        }
        $block = block_instance('totara_program_completion', $blockinstance);

        if (empty($block->config->programids)) {
            return array();
        }
        $programids = explode(',', $block->config->programids);

        $programs = prog_get_programs();
        $visibleids = array_keys($programs);
        $showids = array_intersect($programids, $visibleids);
        if (empty($showids)) {
            return array();
        }

        list($sqlin, $params) = $DB->get_in_or_equal($showids);

        return $DB->get_records_select('prog', "id {$sqlin}", $params);
    }

    /**
     * Reset iterator
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * Get current program name
     *
     * @return string html
     */
    public function current() {
        return $this->render_item($this->items[$this->position]);
    }

    /**
     * Iterator index
     *
     * @return int
     */
    public function key() {
        return $this->position;
    }

    /**
     * Next program
     *
     * @return int
     */
    public function next() {
        ++$this->position;
    }

    /**
     * Is program on current position
     *
     * @return bool
     */
    public function valid() {
        return isset($this->items[$this->position]);
    }

}
