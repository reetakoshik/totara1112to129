<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package tool_phpunit
 */

if (isset($_SERVER['REMOTE_ADDR'])) {
    die; // no access from web!
}

// Add some parameters that do not make sense to change.
$first = array_shift($_SERVER['argv']);
array_unshift($_SERVER['argv'], '--runner=WrapperRunner');
$_SERVER['argc']++;
array_unshift($_SERVER['argv'], $first);

require_once(__DIR__.'/../../../../vendor/autoload.php');

$app = new \ParaTest\Console\ParaTestApplication();
$app->run();
