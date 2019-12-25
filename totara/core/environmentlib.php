<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package totara_code
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check that database user has enough permission for database upgrade
 * @param environment_results $result
 * @return environment_results
 */
function totara_core_mysql_environment_check(environment_results $result) {
    global $DB;
    $result->info = 'mysql_configuration';

    if ($DB->get_dbfamily() === 'mysql') {
        // No matter what anybody says InnoDB and XtraDB are the only supported and tested engines.
        $engine = $DB->get_dbengine();
        if (!in_array($engine, array('InnoDB', 'XtraDB'))) {
            $result->setRestrictStr(array('mysqlneedsinnodb', 'totara_core', $engine));
            $result->setStatus(false);
            return $result;
        }
        // Do not show this entry unless we have a problem.
    }

    // Do not show anything for other databases.
    return null;
}

/**
 * Check that the Totara build date always goes up.
 * @param environment_results $result
 * @return environment_results
 */
function totara_core_linear_upgrade_check(environment_results $result) {
    global $CFG;
    if (empty($CFG->totara_build)) {
        // This is a new install or upgrade from Moodle.
        return null;
    }

    $result->info = 'linear_upgrade';

    $TOTARA = new stdClass();
    $TOTARA->build = 0;
    require("$CFG->dirroot/version.php");

    if ($TOTARA->build < $CFG->totara_build) {
        $result->setRestrictStr(array('upgradenonlinear', 'totara_core', $CFG->totara_build));
        $result->setStatus(false);
        return $result;
    }

    // Everything is fine, no need for any info.
    return null;
}

/**
 * Used to recursively check a DOMDocument for a given string.
 *
 * @param DOMDocument|DOMElement $dom
 * @param string $text
 * @return bool true if string found, false if not.
 */
function totara_core_xml_external_entities_check_searchdom($dom, $text) {
    $found = false;
    /** @var DOMElement $childNode */
    foreach($dom->childNodes as $childNode) {
        if (strpos($childNode->nodeValue, $text) !== false) {
            $found = true;
            break;
        }
        if ($childNode->hasChildNodes()) {
            if ($found = totara_core_xml_external_entities_check_searchdom($childNode, $text)) {
                break;
            }
        }
    }

    return $found;
}

/**
 * Checks whether xml loaded with one of the libraries that uses libxml, we've chosen DOMDocument here,
 * are loading external entities by default. If they are, this means parts of the site could be
 * vulnerable to local file inclusion. Recent versions of PHP and libxml should not have this vulnerability.
 *
 * @param environment_results $result
 * @return environment_results|null - null is returned if check finds nothing wrong.
 */
function totara_core_xml_external_entities_check(environment_results $result) {
    global $CFG;

    if (!class_exists('DOMDocument')) {
        // They should have libxml installed to have loaded the environment.xml, but perhaps this particular class
        // is not enabled somehow. It's unlikely and this is the class referenced in security discussions
        // so is the best to test against.
        $result->setInfo(get_string('domdocumentnotfound', 'admin'));
        $result->setStatus(false);
        return $result;
    }

    $dom = new DOMDocument();
    $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");

    if (totara_core_xml_external_entities_check_searchdom($dom, 'filetext')) {
        $result->setInfo(get_string('xmllibraryentitycheckerror', 'admin'));
        $result->setStatus(false);
        return $result;
    }

    // The test passed, no text from the external file was found.
    return null;
}
