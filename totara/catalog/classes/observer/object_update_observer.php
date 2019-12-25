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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog\observer
 */

namespace totara_catalog\observer;

defined('MOODLE_INTERNAL') || die();

use core\event\base as event_base;
use totara_catalog\local\catalog_storage;
use totara_catalog\provider_handler;

abstract class object_update_observer {

    /** @var string */
    private $objecttype = null;

    /** @var event_base|null */
    protected $event = null;

    /** @var \stdClass[] */
    private $updateobjects = [];

    /** @var int[] */
    private $deleteobjectids = [];

    /**
     * @param string $objecttype
     * @param event_base $event
     */
    public function __construct(string $objecttype, event_base $event) {
        $this->event = $event;
        $this->objecttype = $objecttype;
    }

    /**
     * Get a list of events which should trigger the subclass
     *
     * @return array
     */
    abstract public function get_observer_events(): array;

    /**
     * Initialize change objects
     *
     * This function should calculate changes that need to occur in the catalog when the event occurs. The event
     * may result in zero, one or more changes. Adding and updating catalog records should be flagged by calling
     * $this->register_for_update, while removing catalog records is done by calling $this->register_for_delete.
     */
    abstract protected function init_change_objects(): void;

    /**
     * Flag an object as needing to be added or updated in the catalog
     *
     * @param \stdClass $object
     */
    protected function register_for_update(\stdClass $object): void {
        if (empty($object->objecttype)) {
            $object->objecttype = $this->objecttype;
        } else if ($object->objecttype != $this->objecttype) {
            throw new \coding_exception("Incorrect object type specified in object to add or update: " . $object->objecttype);
        }
        $this->updateobjects[] = $object;
    }

    /**
     * Flag an object as needing to be removed from the catalog
     *
     * @param int $id
     */
    protected function register_for_delete(int $id): void {
        $this->deleteobjectids[] = $id;
    }

    /**
     * Process event observer
     */
    public function process(): void {
        if (!provider_handler::instance()->is_active($this->objecttype)) {
            return;
        }

        $this->init_change_objects();

        if (!empty($this->updateobjects)) {
            catalog_storage::update_records($this->updateobjects);
        }

        if (!empty($this->deleteobjectids)) {
            catalog_storage::delete_records($this->objecttype, $this->deleteobjectids);
        }
    }

    /**
     * Child classes may need to know what object type they are representing.
     *
     * @return string $objecttype
     */
    protected function get_objecttype() {
        return $this->objecttype;
    }
}
