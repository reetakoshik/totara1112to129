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
 * Class for changing the tool_sitepolicy_consent_options table
 **/
class consentoption {
    /**
     * @var int consentoptions.id
     */
    private $id = 0;

    /**
     * @var bool mandatory
     */
    private $mandatory = false;

    /**
     * @var string idnumber
     */
    private $idnumber = '';

    /**
     * @var policyversion policyversion
     */
    private $policyversion = null;

    /**
     * consentoption constructor.
     * @param int $id
     */
    public function __construct(int $id = 0) {
        if ($id > 0) {
            $this->id = $id;
            $this->load();
        }
    }

    /**
     * Gets id for localised policy
     * @return int id
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Is field mandatory
     * @return bool
     */
    public function get_mandatory(): bool {
        return $this->mandatory;
    }

    /**
     * Get custom id number
     * @return string
     */
    public function get_idnumber(): string {
        return $this->idnumber;
    }

    /**
     * Get policy version
     * @return policyversion
     */
    public function get_policyversion(): policyversion {
        return $this->policyversion;
    }

    /**
     * Set custom idnumber
     * @param string $idnumber
     */
    public function set_idnumber(string $idnumber) {
        $this->idnumber = $idnumber;
    }

    /**
     * Set if consent option is mandatory
     * @param bool $mandatory
     */
    public function set_mandatory(bool $mandatory) {
        $this->mandatory = $mandatory;
    }


    /**
     * Load instance data from DB
     * @return $this
     */
    private function load() : consentoption {
        global $DB;

        $consentoptions = $DB->get_record('tool_sitepolicy_consent_options', ['id' => $this->id], '*', MUST_EXIST);

        $this->mandatory = (bool)$consentoptions->mandatory;
        $this->idnumber = $consentoptions->idnumber ?? '';
        $this->policyversion = new policyversion($consentoptions->policyversionid);

        return $this;
    }

    /**
     * Create new consent option instance from the provided data
     *
     * @param policyversion $version
     * @param bool $mandatory
     * @param string $idnumber
     * @return consentoption
     */
    public static function from_data(policyversion $version, bool $mandatory, string $idnumber = ''): consentoption {

        if (empty($version->get_id())) {
            throw new \coding_exception('Version must be saved before adding consent options');
        }

        $consentoption = new consentoption();
        $consentoption->policyversion = $version;
        $consentoption->mandatory = $mandatory;
        if (!empty($idnumber)) {
            $consentoption->idnumber = $idnumber;
        }

        return $consentoption;
    }

    /**
     * Save instance to DB
     *
     * @throws \coding_exception
     */
    public function save() {
        global $DB;

        if (empty($this->policyversion) || empty($this->policyversion->get_id())) {
            throw new \coding_exception("Version must be saved before saving the consent option");
        }

        $entry = new \stdClass();
        $entry->mandatory = (int)$this->mandatory;
        $entry->idnumber = $this->idnumber;
        $entry->policyversionid = $this->policyversion->get_id();

        if (empty($this->id)) {
            // Create.
            $this->id = $DB->insert_record('tool_sitepolicy_consent_options', $entry);
            return;
        }

        // Update.
        $entry->id = $this->id;
        $DB->update_record('tool_sitepolicy_consent_options', $entry);
    }

    /**
     * Delete consent option
     * @throws \coding_exception
     */
    public function delete() {
        global $DB;

        if ($DB->record_exists('tool_sitepolicy_localised_consent', ['consentoptionid' => $this->id]) ||
            $DB->record_exists('tool_sitepolicy_user_consent', ['consentoptionid' => $this->id])) {

            throw new \coding_exception("Consent option can't be deleted while localised_consent or user_consent entries exist");
        }

        $DB->delete_records('tool_sitepolicy_consent_options', ['id' => $this->id]);
    }
}

