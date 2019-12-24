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
 * Class for managing and querying user consent data
 **/
class userconsent {
    /**
     * @var int userconsent.id
     */
    private $id = 0;

    /**
     * @var int userconsent.userid
     */
    private $userid = 0;

    /**
     * @var int timeconsented
     */
    private $timeconsented = null;

    /**
     * @var int hasconsented
     */
    private $hasconsented = 0;

    /**
     * The id of the option the user answered.
     * Not keeping a consentiotion instance here as it will require unnecessary db reads
     * @var int consentoptionid
     */
    private $consentoptionid = 0;

    /**
     * @var string userconsent.language
     */
    private $language = '';

    /**
     * Gets id for userconsent
     * @return int id
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets userid for userconsent
     * @return int userid
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Gets timeconsented for userconsent
     * @return int timeconsented
     */
    public function get_timeconsented(): int {
        return $this->timeconsented;
    }

    /**
     * Gets hasconsented for userconsent
     * @return int hasconsented
     */
    public function get_hasconsented(): int {
        return (int)$this->hasconsented;
    }

    /**
     * Gets consentoptionid
     * @return int consentoptionid
     */
    public function get_consentoptionid(): int {
        return $this->consentoptionid;
    }

    /**
     * Gets language used when user consented
     * @return string language
     */
    public function get_language(): string {
        return $this->language;
    }

    /**
     * Sets hasconsented field based on $answer (from form)
     * @param int $answer
     */
    public function set_hasconsented(int $answer) {
        $this->hasconsented = $answer;
    }

    /**
     * Sets $timeconsented
     * @param int $timeconsented
     */
    public function set_timeconsented(int $time) {
        $this->timeconsented = $time;
    }


    /**
     * Sets userid for userconsent
     * @param int userid
     */
    public function set_userid($userid) {
        $this->userid = $userid;
    }

    /**
     * Sets consentoptionid
     * @param int $consentoptionid
     */
    public function set_consentoptionid(int $consentoptionid) {
        $this->consentoptionid = $consentoptionid;
    }

    /**
     * Sets consent language
     * @param string $language
     */
    public function set_language(string $language) {
        $this->language = $language;
    }

    /**
     * userconsent constructor.
     * @param int $id
     */
    public function __construct(int $id = 0) {
        global $DB;
        if ($id > 0) {
            $userconsent = $DB->get_record('tool_sitepolicy_user_consent', ['id' => $id]);
            $this->id = $id;
            $this->userid = $userconsent->userid;
            $this->timeconsented = $userconsent->timeconsented;
            $this->hasconsented = $userconsent->hasconsented;
            $this->consentoptionid = $userconsent->consentoptionid;
            $this->language = $userconsent->language;
        }
    }

    /**
     * Save a new instance to the DB
     * We don't update existing consent records.
     * Older is kept for historic purposes only.
     *
     * @throws \coding_exception
     */
    public function save() {
        global $DB, $USER;

        // Make sure required values were set
        if (empty($this->consentoptionid) || empty($this->language)) {
            throw new \coding_exception('Expected consentoptionid and language not set');
        }

        if (empty($this->userid)) {
            $this->userid = $USER->id;
        }
        if (empty($this->timeconsented)) {
            $this->timeconsented = time();
        }

        $entry = new \stdClass();
        $entry->userid = $this->userid;
        $entry->timeconsented = $this->timeconsented;
        $entry->hasconsented = $this->hasconsented;
        $entry->consentoptionid = $this->consentoptionid;
        $entry->language = $this->language;
        $this->id = $DB->insert_record('tool_sitepolicy_user_consent', $entry);
    }

    /**
     * Gets all policyversions that have options to been answered by the user (in any language)
     * Ignore answers provided by guest users
     *
     * @param int userid
     * @return array
     **/
    public static function get_unansweredpolicies(int $userid): array {
        global $DB, $USER, $CFG;

        // We need to ensure that rows are always returned in the same order
        $sql = "
            SELECT DISTINCT tspv.id AS policyversionid,
                   tspv.versionnumber
              FROM {tool_sitepolicy_consent_options} tsco
              JOIN {tool_sitepolicy_policy_version} tspv
                ON tspv.id = tsco.policyversionid
               AND tspv.timepublished IS NOT NULL
               AND tspv.timearchived IS NULL
         LEFT JOIN (SELECT consentoptionid, MAX(id) as id
                      FROM {tool_sitepolicy_user_consent}
                     WHERE userid <> :guestid
                       AND userid = :userid
                  GROUP BY consentoptionid) tsuc_latest
                ON tsuc_latest.consentoptionid = tsco.id
         LEFT JOIN {tool_sitepolicy_user_consent} tsuc
                ON tsuc.id = tsuc_latest.id
             WHERE tsuc.id IS NULL
                OR (tsuc.hasconsented = 0 AND tsco.mandatory = 1)
          ORDER BY tspv.id
        ";

        $params = ['userid' => empty($userid) ? $USER->id : $userid, 'guestid' => $CFG->siteguest];
        $consentpolicies = $DB->get_records_sql($sql, $params);

        return $consentpolicies;
    }

    /**
     * Determine whether the user has consented to this option
     *
     * @param int $consentoptionid
     * @return bool
     **/
    public static function has_user_consented($consentoptionid, int $userid = null): bool {
        global $DB, $USER;

        $userid = !empty($userid) ? $userid : $USER->id;

        $sql = "
              SELECT tsuc.id,
                     tsuc.hasconsented
                FROM {tool_sitepolicy_user_consent} tsuc
               WHERE consentoptionid = :consentoptionid
                 AND userid = :userid
            ORDER BY timeconsented DESC
        ";

        $params = ['consentoptionid' => $consentoptionid,
                   'userid' => $userid];
        $rows = $DB->get_records_sql($sql, $params);

        if (empty($rows)) {
            return false;
        } else {
            return reset($rows)->hasconsented;
        }
    }

    /**
     * Determine whether the user has answered this option
     *
     * @param int $consentoptionid
     * @param int $userid
     * @return bool
     **/
    public static function has_user_answered(int $consentoptionid, int $userid = null): bool {
        global $DB, $USER;

        if (isguestuser()) {
            return false;
        }

        $params = [
            'consentoptionid' => $consentoptionid,
            'userid' => $userid ?? $USER->id
        ];
        return (bool)$DB->count_records('tool_sitepolicy_user_consent', $params);
    }

    /**
     * Gets the users preferred language based on a fallback of: user lang, site lang, lang of primary version
     *
     * @param int $policyversionid
     * @param int $userid
     * @param bool $mustexist
     * @return string
     **/
    public static function get_user_consent_language(int $policyversionid, int $userid, bool $mustexist = false): string {
        global $DB;

        $languages = get_string_manager()->get_list_of_translations();

        $userchoosensql = "
            SELECT currentuser.id, currentuser.lang AS language
              FROM {user} currentuser
              JOIN {tool_sitepolicy_localised_policy} tslp
                ON tslp.language = currentuser.lang
               AND tslp.policyversionid = :policyversionid
             WHERE currentuser.id = :userid
            ";

        $sitelanguagesql = "
            SELECT tslp.id, tslp.language
              FROM {tool_sitepolicy_localised_policy} tslp
             WHERE tslp.language = :sitelang
               AND tslp.policyversionid = :policyversionid
        ";

        $primarysql = "
            SELECT tslp.id, tslp.language
              FROM {tool_sitepolicy_localised_policy} tslp
             WHERE tslp.policyversionid = :policyversionid
               AND tslp.isprimary = :isprimary
        ";

        if ($userid != 0) {
            $userchoosen = $DB->get_record_sql($userchoosensql,
                ['userid' => $userid, 'policyversionid' => $policyversionid]);
            if (!empty($userchoosen)) {
                // Ignore whether this is an available translation or not!
                // It is what the user has viewed and consented to.
                return $userchoosen->language;
            }
        }

        $sitelanguage = $DB->get_record_sql($sitelanguagesql,
            ['sitelang' => current_language(), 'policyversionid' => $policyversionid]);
        if (!empty($sitelanguage) && isset($languages[$sitelanguage->language])) {
            return $sitelanguage->language;
        }

        $primary = $DB->get_record_sql($primarysql,
            ['policyversionid' => $policyversionid, 'isprimary' => localisedpolicy::STATUS_PRIMARY]);
        if (isset($languages[$primary->language])) {
            return $primary->language;
        }

        // Primary language no longer available
        // Find any other available language, else fallback to en or primary
        $notprimary = $DB->get_records_sql($primarysql,
            ['policyversionid' => $policyversionid, 'isprimary' => localisedpolicy::STATUS_NOTPRIMARY]);
        if ($notprimary) {
            foreach ($notprimary as $row) {
                if (isset($languages[$row->language])) {
                    return $row->language;
                }
            }
        }

        return $mustexist ? $primary->language : 'en';

    }

    /**
     * Gets the consent from the lastest version of the policy users have consented to
     *
     * @param int $userid
     * @return array
     **/
    public static function get_userconsenttable(int $userid): array {
        global $DB;

        $userconsentsql = "
            SELECT tsuc.id AS consentid,
                   tspv.id AS policyversionid,
                   tslp.title,
                   tspv.versionnumber,
                   tsuc.timeconsented,
                   tsuc.hasconsented,
                   tsuc.consentoptionid,
                   tsuc.language,
                   tslc.statement,
                   CASE WHEN tsuc.hasconsented =  1 THEN tslc.consentoption ELSE tslc.nonconsentoption END AS response
              FROM {tool_sitepolicy_user_consent} tsuc

              JOIN (
                       SELECT tspv.sitepolicyid,
                              MAX(tsuc2.timeconsented) AS latest
                         FROM {tool_sitepolicy_user_consent} tsuc2
                         JOIN {tool_sitepolicy_consent_options} tsco
                           ON tsco.id = tsuc2.consentoptionid
                         JOIN {tool_sitepolicy_policy_version} tspv
                           ON tspv.id = tsco.policyversionid
                        WHERE tsuc2.userid = :userid
                     GROUP BY tspv.sitepolicyid
                   ) as tsuc_latest
                ON tsuc.timeconsented = tsuc_latest.latest

              JOIN {tool_sitepolicy_consent_options} tsco
                ON tsuc.consentoptionid = tsco.id

              JOIN {tool_sitepolicy_policy_version} tspv
                ON tsco.policyversionid = tspv.id

              JOIN {tool_sitepolicy_localised_policy} tslp
                ON tslp.policyversionid = tspv.id
               AND tslp.language = tsuc.language

              JOIN {tool_sitepolicy_localised_consent} tslc
                ON tslc.localisedpolicyid = tslp.id
               AND tslc.consentoptionid = tsuc.consentoptionid
          ORDER BY tspv.id, tsuc.id
        ";

        $userconsent = $DB->get_records_sql($userconsentsql, ['userid' => $userid]);
        return $userconsent;
    }

    /**
     * Check and return if user need to provide consent.
     * @param int $userid
     * @return bool
     */
    public static function is_consent_needed(int $userid): bool {
        global $CFG, $SESSION;

        // Check if feature is enabled.
        if (empty($CFG->enablesitepolicies)) {
            return false;
        }

        if (isguestuser()) {
            return empty($SESSION->tool_sitepolicy_consented);
        }

        // Check if user has policies to consent.
        if (!empty(self::get_unansweredpolicies($userid))) {
            return true;
        }

        $SESSION->tool_sitepolicy_consented = true;

        return false;
    }

    /**
     * Check whether user consented to previous version
     * @param policyversion $version
     * @param int $userid
     * @return bool
     */
    public static function has_consented_previous_version(policyversion $version, $userid = null): bool {
        global $DB, $USER;

        if (isguestuser()) {
            return false;
        }

        $sql = "
            SELECT tsco.id
              FROM {tool_sitepolicy_consent_options} tsco
              JOIN {tool_sitepolicy_policy_version} tspv
                ON tsco.policyversionid = tspv.id
             WHERE tspv.sitepolicyid = :sitepolicyid
               AND versionnumber = :versionnumber
               AND EXISTS (
                   SELECT tsuc.id
                     FROM {tool_sitepolicy_user_consent} tsuc
                    WHERE tsuc.consentoptionid = tsco.id
                      AND tsuc.userid = :userid
                      AND (tsuc.hasconsented = 1
                       OR (tsuc.hasconsented = 0 AND tsco.mandatory = 0))
                   )
          ";

        $params = ['sitepolicyid' => $version->get_sitepolicy()->get_id(),
                   'versionnumber' => $version->get_versionnumber() - 1,
                   'userid' => $userid ?? $USER->id];
        return $DB->record_exists_sql($sql, $params);
    }
}
