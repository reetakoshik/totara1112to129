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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\hook;

/**
 * Calendar upcoming event hook.
 *
 * @package totara_core\hook
 */
class calendar_upcoming_event extends \totara_core\hook\base {

    /**
     * @var \stdClass
     */
    public $event;

    /**
     * @var string
     */
    public $content;

    /**
     * The calendar_upcoming_event constructor.
     *
     * @param \stdClass $event
     * @param string $content
     */
    public function __construct(\stdClass $event, &$content) {
        $this->event   = $event;
        $this->content =& $content;
    }
}