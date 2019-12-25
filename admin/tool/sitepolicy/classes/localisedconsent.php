<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for changing the tool_sitepolicy_localised_consent table
 **/
class localisedconsent {

    /**
     * @var int localisedconsent.id
     */
    private $id = 0;

    /**
     * @var string statement
     */
    private $statement = '';

    /**
     * @var string consentoption
     */
    private $consentoption = '';

    /**
     * @var string nonconsentoption
     */
    private $nonconsentoption = '';

    /**
     * @var localisedpolicy localisedpolicyid
     */
    private $localisedpolicy = null;

    /**
     * @var consentoption option
     */
    private $option = null;

    /**
     * @var bool removed
     */
    private $removed = false;


    /**
     * localisedconsent constructor.
     * Use from_data to create a new instance
     */
    private function __construct() {
    }

    /**
     * Gets id for localised consent
     * @return int id
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets statement for localised consent
     * @return string statement
     */
    public function get_statement(): string {
        return $this->statement;
    }

    /**
     * Gets consentoption for localised consent
     * @return string consentoption
     */
    public function get_consentoption(): string {
        return $this->consentoption;
    }

    /**
     * Gets nonconsentoption for localised consent
     * @return string nonconsentoption
     */
    public function get_nonconsentoption(): string {
        return $this->nonconsentoption;
    }

    /**
     * Gets localisedpolicy of this localisedconsent
     * @return localisedpolicy localisedpolicy
     */
    public function get_localisedpolicy(): localisedpolicy {
        return $this->localisedpolicy;
    }

    /**
     * Gets consentoption instance of this localisedconsent
     * @return consentoption
     */
    public function get_option(): consentoption {
        return $this->option;
    }

    /**
     * Gets removed flag of this localisedconsent
     * @return bool
     */
    public function is_removed(): bool {
        return $this->removed;
    }

    /**
     * Set statement text
     * @param string $statement
     */
    public function set_statement(string $statement) {
        $this->statement = $statement;
    }

    /**
     * Set consent option text
     * @param string $consentoption
     */
    public function set_consentoption(string $consentoption) {
        $this->consentoption = $consentoption;
    }

    /**
     * Set non-consent option text
     * @param string $nonconsentoption
     */
    public function set_nonconsentoption(string $nonconsentoption) {
        $this->nonconsentoption = $nonconsentoption;
    }

    /**
     * Sets removed flag of this localisedconsent
     * @param bool $removed
     */
    public function set_removed(bool $removed) {
        $this->removed = $removed;
    }

    /**
     * Create instance from provided data
     *
     * @param localisedpolicy $localisedpolicy
     * @param consentoption $option
     * @param string $statement
     * @param string $consentoption
     * @param string $nonconsentoption
     * @return localisedconsent
     */
    public static function from_data(localisedpolicy $localisedpolicy, consentoption $option,
                                     string $statement, string $consentoption, string $nonconsentoption): localisedconsent {

        global $DB;

        $localisedpolicyid = $localisedpolicy->get_id();
        $consentoptionid = $option->get_id();

        if ($localisedpolicyid > 0 && $consentoptionid > 0) {
            $params = ['localisedpolicyid' => $localisedpolicy->get_id(),
                       'consentoptionid' => $option->get_id()];
            $row = $DB->get_record('tool_sitepolicy_localised_consent', $params);
        } else {
            $row = false;
        }

        $localisedconsent = new self();
        if ($row) {
            $localisedconsent->id = $row->id;
        }

        $localisedconsent->localisedpolicy = $localisedpolicy;
        $localisedconsent->option = $option;
        $localisedconsent->statement = $statement;
        $localisedconsent->consentoption = $consentoption;
        $localisedconsent->nonconsentoption = $nonconsentoption;

        return $localisedconsent;
    }

    /**
     * Get localised consent options of the policy
     *
     * @param localisedpolicy $localisedpolicy
     * @param int $consentoptionid
     * @return array $consentoptions
     */
    public static function get_policy_options(localisedpolicy $localisedpolicy, int $consentoptionid = 0): array {
        global $DB;

        $where = '';
        $params = ['localisedpolicyid' => $localisedpolicy->get_id(),
                   'policyversionid' => $localisedpolicy->get_policyversion()->get_id()];

        if (!empty($consentoptionid)) {
            $where = 'AND tsco.id = :consentoptionid';
            $params['consentoptionid'] = $consentoptionid;
        }

        $sql = "
               SELECT tsco.id AS consentoptionid,
                      tslc.id AS localisedconsentid,
                      tslc.statement,
                      tslc.consentoption,
                      tslc.nonconsentoption
                 FROM {tool_sitepolicy_consent_options} tsco

            LEFT JOIN {tool_sitepolicy_localised_consent} tslc
                   ON (tslc.consentoptionid = tsco.id
                  AND tslc.localisedpolicyid = :localisedpolicyid)

                WHERE tsco.policyversionid = :policyversionid $where
             ORDER BY consentoptionid ASC
        ";

        $consentrecs = $DB->get_records_sql($sql, $params);

        $consentoptions = [];
        foreach ($consentrecs as $consentrec) {
            $consentoption = new localisedconsent();
            if (!is_null($consentrec->localisedconsentid)) {
                $consentoption->id = $consentrec->localisedconsentid;
                $consentoption->statement = $consentrec->statement;
                $consentoption->consentoption = $consentrec->consentoption;
                $consentoption->nonconsentoption = $consentrec->nonconsentoption;
            }
            $consentoption->option = new consentoption($consentrec->consentoptionid);
            $consentoption->localisedpolicy = $localisedpolicy;
            $consentoptions[$consentrec->consentoptionid] = $consentoption;
        }

        return $consentoptions;
    }

    /**
     * Save object to database
     */
    public function save() {
        global $DB;
        $entry = new \stdClass();
        $entry->statement = $this->statement;
        $entry->consentoption = $this->consentoption;
        $entry->nonconsentoption = $this->nonconsentoption;
        $entry->consentoptionid = $this->option->get_id();
        $entry->localisedpolicyid = $this->localisedpolicy->get_id();

        if (empty($entry->localisedpolicyid)) {
            throw new \coding_exception("Localised policy must be saved before saving localised consent option");
        }

        if (empty($entry->consentoptionid)) {
            throw new \coding_exception("Consent option must be saved before saving localised consent option");
        }

        if (empty($this->id)) {
            // Create.
            $this->id = $DB->insert_record('tool_sitepolicy_localised_consent', $entry);
            return;
        }

        // Update.
        $entry->id = $this->id;
        $DB->update_record('tool_sitepolicy_localised_consent', $entry);
    }

    /**
     * Delete localised consent
     */
    public function delete() {
        global $DB;

        $DB->delete_records('tool_sitepolicy_localised_consent', ['id' => $this->id]);
    }

    /**
     * Delete all localised consent for a specifiec consent_option
     * @param int $consentoptionid
     */
    public static function delete_all($consentoptionid) {
        global $DB;

        $DB->delete_records('tool_sitepolicy_localised_consent', ['consentoptionid' => $consentoptionid]);
    }
}