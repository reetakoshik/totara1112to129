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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

namespace totara_form;

use \stored_file;

/**
 * Class representing current file area that is edited via form.
 *
 * There is not access control in this class,
 * devs need to make sure user is allowed to change the files
 * before creating instance of this class!
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class file_area {
    /** @var \context $context */
    protected $context;

    /** @var string $component */
    protected $component;

    /** @var string $filearea */
    protected $filearea;

    /** @var int $itemid */
    protected $itemid;

    /** @var int $draftitemid */
    protected $draftitemid;

    /**
     * Current file area constructor.
     *
     * @throws \coding_exception If the component is empty, you must provide a valid component.
     * @throws \coding_exception If the file area is empty, you must provide a valid filearea.
     * @param \context|null $context null means not known yet (new area)
     * @param string $component
     * @param string $filearea
     * @param int $itemid null means not known yet (new area)
     */
    public function __construct(\context $context = null, $component, $filearea, $itemid = null) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');

        if ($component === null) {
            throw new \coding_exception('$component argument must be known!');
        }
        if ($filearea === null) {
            throw new \coding_exception('$filearea argument must be known!');
        }
        $this->context = $context;
        $this->component = $component;
        $this->filearea = $filearea;
        $this->itemid = $itemid;
    }

    /**
     * Create new draft area.
     *
     * @return int
     */
    public function create_draft_area() {
        if ($this->draftitemid !== null) {
            // We do this once on each request only, it may have timing issues
            // but we cannot create new drafts over and over when form is frozen.
            return $this->draftitemid;
        }

        if ($this->context === null or $this->itemid === null) {
            // We are not editing existing area, nothing to do...
            return file_get_unused_draft_itemid();
        }

        if ($this->context === null or $this->itemid === null) {
            $itemid = null;
            $contextid = null;
        } else {
            $itemid = $this->itemid;
            $contextid = $this->context->id;
        }
        $options = array('subdirs' => true); // Keep whatever structure was there before!

        $draftitemid = null;
        file_prepare_draft_area($draftitemid, $contextid, $this->component, $this->filearea, $itemid, $options);
        $this->draftitemid = $draftitemid;

        return $this->draftitemid;
    }

    /**
     * Store the modified in new or existing field area.
     *
     * @throws \coding_exception If the context or itemid are empty, they must be provided.
     * @param stored_file[] $files submitted files
     * @param array $options
     * @param \context|null $context used if not know in constructor, null means keep previous
     * @param int $itemid used if not know in constructor, null means keep previous
     * @return bool success
     */
    public function update_file_area(array $files, array $options, \context $context = null, $itemid = null) {
        if ($context !== null) {
            $this->context = $context;
        }
        if ($itemid !== null) {
            $this->itemid = $itemid;
        }
        if ($this->itemid === null or $this->context === null) {
            throw new \coding_exception('Context and itemid must be known before saving area files!');
        }

        file_save_draft_area_files($files, $this->context->id, $this->component, $this->filearea, $this->itemid, $options);
        return true;
    }

    /**
     * Replace text @@PLUGINFILE@@ placeholders with draft files placeholders.
     *
     * @param string $text
     * @param int $draftitemid
     * @return string
     */
    public static function rewrite_links_to_draftarea($text, $draftitemid) {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');

        // Get rid of previously broken links.
        $text = str_replace("\"$CFG->wwwroot/draftfile.php", "\"$CFG->wwwroot/brokenfile.php#", $text);

        // Replace placeholders with links to the newly created draft area.
        $usercontext = \context_user::instance($USER->id);
        return file_rewrite_pluginfile_urls($text, 'draftfile.php', $usercontext->id, 'user', 'draft', $draftitemid);
    }

    /**
     * Normalise accept attribute for file related elements.
     *
     * @param string|array|null $value
     * @param bool $debugwarning show debug warnings when types are invalid
     * @return string comma separated list of mime types or wildcards, null means all accepted
     */
    public static function normalise_accept_attribute($value, $debugwarning = false) {
        if ($value === null or $value === '') {
            return null;
        }

        if (!is_array($value)) {
            $value = explode(',', $value);
        }
        $value = array_map('trim', $value);

        if (in_array('*', $value) or in_array('', $value)) {
            return null;
        }

        if ($debugwarning) {
            // TODO TL-9415: find out if the values are actually valid.
            // see http://www.w3schools.com/tags/att_input_accept.asp.
            // Note that Moodle uses supports mimetype 'groups' too,
            // we have to verify if mimetypes actually work there.
        }

        return implode(',', $value);
    }

    /**
     * Repository code is buggy as hell, try to give it accepted_types
     * in some form it understands...
     *
     * @param string|null $value
     * @return array|string array accepted_types or '*' expected by repository subsystem
     */
    public static function accept_attribute_to_accepted_types($value) {
        $types = explode(',', $value);

        $types = array_map('trim', $types);
        if (in_array('*', $types) or in_array('', $types)) {
            return '*';
        }

        $accepted_types = array();
        foreach ($types as $type) {
            if (substr($type, -2) === '/*') {
                // Use groups instead of xxx/*.
                $type = substr($type, 0, strlen($type) - 2);
            }

            //  TL-9416: Verify repository code accepts mime types and groups.

            $accepted_types[] = $type;
        }

        if (count($accepted_types) === 1) {
            // Repository code is known to be full of problems,
            // better not use arrays here unless it is necessary.
            return reset($accepted_types);
        }

        return $accepted_types;
    }

    /**
     * Is this file mimetype valid according to the accept attribute?
     *
     * NOTE: this works for repository accepted_types and html5 accept attribute values.
     *
     * @param string|array $acceptattribute
     * @param string $filename
     * @param string $filemimetype
     * @return bool
     */
    public static function is_accepted_file($acceptattribute, $filename, $filemimetype) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');

        if ($acceptattribute === null or $acceptattribute === '*') {
            return true;
        }

        if (!is_array($acceptattribute)) {
            $acceptattribute = explode(',', $acceptattribute);
        }

        // TODO TL-9416: test and fix the sloppy /repository/* code.

        foreach ($acceptattribute as $type) {
            if (substr($type, -2) === '/*') {
                // Use groups instead of xxx/* to match the behaviour of accept_attribute_to_accepted_types().
                $type = substr($type, 0, strlen($type) - 2);
            }
            if ($type === '*') {
                return true;

            } else if ($type === $filemimetype) {
                return true;

            } else if (strpos($type, '.') === 0) {
                if (substr(strtolower($filename), -strlen($type)) === strtolower($type)) {
                    // We have exact file extension match.
                    return true;
                }
                // Let's see if at least the mimetype matches,
                // this is the way upstream repository code works...
                $mime = mimeinfo('type', $type);
                if ($mime === $filemimetype) {
                    return true;
                }

            } else {
                // Groups are the last option.
                $groups = mimeinfo('groups', $filename);
                if ($groups and in_array($type, $groups)) {
                    return true;
                }
            }
        }

        return false;
    }
}
