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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\local;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataholder;

/**
 * A simple container holding a dataholder which is required in order to load data and format the results
 */
class required_dataholder {

    /** @var dataholder */
    public $dataholder;

    /** @var int from totara_catalog\dataformatter\formatter::TYPE_XXX */
    public $formattertype;

    /** @var formatter */
    public $formatter;

    /**
     * @param dataholder $dataholder
     * @param int $formattertype
     */
    public function __construct(dataholder $dataholder, int $formattertype) {
        $this->dataholder = $dataholder;
        $this->formattertype = $formattertype;
        $this->formatter = $dataholder->formatters[$formattertype];
    }
}
