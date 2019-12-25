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
 * Class for changing the tool_sitepolicy_site_policy table
 **/
class sitepolicy {

    /**
     * @var int sitepolicy.id
     */
    private $id = 0;

    /**
     * @var int sitepolicy.timecreated
     */
    private $timecreated = 0;


    /**
     * Gets id for policy
     * @return int id
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets timecreated for policy
     * @return int timecreated
     */
    public function get_timecreated(): int {
        return $this->timecreated;
    }

    /**
     * Sets timecreated for policy
     * @param int $time Unix timestamp
     * @return int timecreated
     */
    public function set_timecreated(int $time) {
        $this->timecreated = $time;
    }

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
     * Save instance to DB
     */
    public function save() {
        global $DB;

        $entry = new \stdClass();
        if (empty($this->timecreated)) {
            $this->timecreated = time();
        }

        $entry->timecreated = $this->timecreated;
        if (empty($this->id)) {
            // Create.
            $this->id = $DB->insert_record('tool_sitepolicy_site_policy', $entry);
        } else {
            // Update.
            $entry->id = $this->id;
            $DB->update_record('tool_sitepolicy_site_policy', $entry);
        }
    }

    /**
     * Deletes sitepolicy
     */
    public function delete() {
        global $DB;
        $DB->delete_records('tool_sitepolicy_site_policy', ['id' => $this->id]);
    }

    /**
     * loads sitepolicy
     * @return $this
     */
    public function load() : sitepolicy {
        global $DB;

        $policyverison = $DB->get_record('tool_sitepolicy_site_policy', ['id' => $this->id], '*', MUST_EXIST);
        $this->timecreated = $policyverison->timecreated;

        return $this;
    }

    /**
     * Change current active policy version to new.
     * @param policyversion $newpolicyversion
     * @param $time
     * @param int $publisherid

     */
    public function switchversion(policyversion $newpolicyversion, int $time = 0, int $publisherid = 0) {
        if ($newpolicyversion->get_sitepolicy()->get_id() != $this->id) {
            throw new \coding_exception("Cannot change to new policy version as it does not belong to this site policy");
        }

        if ($newpolicyversion->get_status() != policyversion::STATUS_DRAFT) {
            throw new \coding_exception("Cannot publish a non-draft policy version");
        }

        // Get current active version and archive it.
        if (policyversion::has_active($this)) {
            policyversion::from_policy_active($this)->archive();
        }

        // Publish new version.
        $newpolicyversion->publish($publisherid, $time);
    }

    /**
     * Get all sitepolicies and related data for sitepolicy table
     * @return array of policies
     **/
    public static function get_sitepolicylist() : array {
        global $DB;

        $policylistsql = "
            SELECT a.id,
                   tslp.id AS localisedpolicyid,
                   tslp.title AS title,
                   a.numdraft,
                   a.numpublished,
                   a.numarchived,
                   CASE
                      WHEN a.numdraft > 0 THEN :statusdraft
                      WHEN (a.numpublished > 0 AND a.numpublished > a.numarchived) THEN :statuspublished
                      WHEN (a.numpublished > 0 AND a.numpublished = a.numarchived AND a.numdraft = 0) THEN :statusarchived
                      ELSE ''
                   END AS status
              FROM (
                    SELECT tssp.id,
                       COALESCE(SUM(CASE WHEN tspv.timepublished IS NOT NULL THEN 1 END), 0) AS numpublished,
                       COALESCE(SUM(CASE WHEN tspv.timearchived IS NOT NULL THEN 1 END), 0) AS numarchived,
                       COALESCE(SUM(CASE WHEN tspv.timepublished IS NULL AND tspv.timearchived IS NULL THEN 1 END), 0) AS numdraft,
                       MAX(versionnumber) as versionnumber
                      FROM {tool_sitepolicy_site_policy} tssp
                 LEFT JOIN {tool_sitepolicy_policy_version} tspv
                        ON (tspv.sitepolicyid = tssp.id)
                  GROUP BY tssp.id) a

             LEFT JOIN {tool_sitepolicy_policy_version} tspv_latest
                    ON (tspv_latest.sitepolicyid = a.id AND tspv_latest.versionnumber = a.versionnumber)

             LEFT JOIN {tool_sitepolicy_localised_policy} tslp
                    ON (tslp.policyversionid = tspv_latest.id AND tslp.isprimary = :isprimary)
            ";

        $params = ['isprimary' => localisedpolicy::STATUS_PRIMARY,
                   'statusdraft' => policyversion::STATUS_DRAFT,
                   'statuspublished' => policyversion::STATUS_PUBLISHED,
                   'statusarchived' => policyversion::STATUS_ARCHIVED
                  ];
        return $DB->get_records_sql($policylistsql, $params);
    }

    /**
     * Creates a new site policy
     *
     * @param string $title
     * @param string $policytext
     * @param statement[] $statements
     * @param string $language
     * @param int|null $authorid
     * @param int $policytextformat- Note that the FORMAT_* definitions are strings.
     *                               You need to convert it if passing it to this function.
     * @return sitepolicy
     * @throws \coding_exception
     * @throws \dml_transaction_exception
     */
    public static function create_new_policy(string $title, string $policytext, array $statements, string $language, int $authorid = null, int $policytextformat = null): sitepolicy {
        global $DB, $USER;

        $authorid = $authorid ?? $USER->id;
        $policytextformat = $policytextformat ?? (int)FORMAT_HTML;
        $time = time();

        $trans = $DB->start_delegated_transaction();

        $sitepolicy = new sitepolicy();
        $sitepolicy->set_timecreated($time);
        $sitepolicy->save();

        $version = policyversion::new_policy_draft($sitepolicy, $time);
        $version->save();

        $primarypolicy = localisedpolicy::from_data($version, $language, true);
        $primarypolicy->set_authorid($authorid);
        $primarypolicy->set_timecreated($time);
        $primarypolicy->set_title($title);
        $primarypolicy->set_policytext($policytext, $policytextformat);
        $primarypolicy->set_statements($statements);

        $primarypolicy->save();
        $trans->allow_commit();

        return $sitepolicy;
    }

    /**
     * Creates a new draft version of this site policy.
     *
     * @return policyversion
     */
    public function create_new_draft_version() {
        global $DB;
        $latestversion = policyversion::from_policy_latest($this);

        if ($latestversion->get_status() == policyversion::STATUS_DRAFT) {
            return $latestversion;
        }

        $trans = $DB->start_delegated_transaction();
        $draft = policyversion::new_policy_draft($this);
        $draft->save();
        $draft->clone_content($latestversion);
        $trans->allow_commit();

        return $draft;
    }
}
