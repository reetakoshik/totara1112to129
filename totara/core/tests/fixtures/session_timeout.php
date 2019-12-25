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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

/**
 * Simulate session timeout, but keep the persistent cookie.
 */

define('PERSISTENT_LOGIN_SKIP', true);

require_once('../../../../config.php');

if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    throw new coding_exception('Invalid access detected.');
}

$sid = session_id();

\core\session\manager::write_close();
$DB->set_field('sessions', 'timemodified', time() - 60 * 60 * 24 * 10, array('sid' => $sid));
\core\session\manager::gc();
$sessionname = 'TotaraSession' . $CFG->sessioncookie;
setcookie($sessionname, '', time() - HOURSECS, $CFG->sessioncookiepath, $CFG->sessioncookiedomain);

?><html>
<head>
</head>
<body>
<div id="message">Session was timed out</div>
</body>
</html>