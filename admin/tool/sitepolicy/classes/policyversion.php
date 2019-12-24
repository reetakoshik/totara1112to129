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
 * Class for changing the tool_sitepolicy_policy_version table
 **/
class policyversion {
    /*
     * Version status
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    /**
     * @var int id
     */
    private $id = 0;

    /**
     * @var int versionnumber
     */
    private $versionnumber = 0;

    /**
     * @var int timecreated
     */
    private $timecreated = 0;

    /**
     * @var int timepublished
     */
    private $timepublished = null;

    /**
     * @var int timearchived
     */
    private $timearchived = null;

    /**
     * @var sitepolicy sitepolicy
     */
    private $sitepolicy = null;

    /**
     * @var int publisherid
     */
    private $publisherid = null;

    /**
     * An array of summary information about localised versions for this policy version.
     * @var \stdClass[]
     */
    private $summaryinformation = null;

    /**
     * The primary localised version.
     * @var localisedpolicy
     */
    private $primarylocalisedpolicy = null;

    /**
     * policyversion constructor.
     * @param int $id
     */
    public function __construct(int $id = 0) {
        if ($id > 0) {
            $this->id = $id;
            $this->load();
        }
    }

    /**
     * Gets id for policy version
     * @return int id
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets versionnumber for policy version
     * @return ?int versionnumber
     */
    public function get_versionnumber(): ?int {
        return $this->versionnumber;
    }

    /**
     * Gets timecreated for policy version
     * @return ?int timecreated
     */
    public function get_timecreated(): ?int {
        return $this->timecreated;
    }

    /**
     * Gets timepublished for policy version
     * @return ?int timepublished
     */
    public function get_timepublished(): ?int {
        return $this->timepublished;
    }

    /**
     * Gets timearchived for policy version
     * @return ?int timearchived
     */
    public function get_timearchived(): ?int {
        return $this->timearchived;
    }

    /**
     * Gets sitepolicyid for policy version
     * Sitepolicy must be set
     *
     * @return sitepolicy sitepolicy
     */
    public function get_sitepolicy(): sitepolicy {
        return $this->sitepolicy;
    }

    /**
     * Gets publisherid for policy version
     * @return ?int publisherid
     */
    public function get_publisherid(): ?int {
        return $this->publisherid;
    }

    /**
     * Get version status
     * @return string One of the STATUS consts
     */
    public function get_status(): string {
        if (is_null($this->timepublished)) {
            return self::STATUS_DRAFT;
        }

        if (is_null($this->timearchived)) {
            return self::STATUS_PUBLISHED;
        }

        return self::STATUS_ARCHIVED;
    }

    /**
     * Sets time created for policy version
     */
    public function set_timecreated(int $time) {
        $this->timecreated = $time;
    }

    /**
     * Sets time published for policy version
     */
    public function set_timepublished(int $time) {
        $this->timepublished = $time;
    }

    /**
     * Magic setter. Ignore names not in the object
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value) {
        $properties = get_object_vars($this);
        if (array_key_exists($name, $properties)) {
            $this->{$name} = $value;
        }
    }

    /**
     * Load data from object
     * @return $this
     */
    private function load(): policyversion {
        global $DB;

        $policyversion = $DB->get_record('tool_sitepolicy_policy_version', ['id' => $this->id], '*', MUST_EXIST);
        $this->versionnumber = $policyversion->versionnumber;
        $this->timecreated = $policyversion->timecreated;
        $this->timepublished = $policyversion->timepublished;
        $this->timearchived = $policyversion->timearchived;
        $this->sitepolicy = new sitepolicy($policyversion->sitepolicyid);
        $this->publisherid = $policyversion->publisherid;

        return $this;
    }

    /**
     * Instantiate a new instance with the provided data
     *
     * @param sitepolicy $sitepolicy Site policy to which this version belongs.
     * @param Object $dbrow Optional object providing instance attribute values
     *                      Can't use type hinting as it is only available from PHP7.2
     *
     * @return policyversion
     */
    public static function from_data (sitepolicy $sitepolicy, $dbrow = null): policyversion {

        $policyversion = new self();
        $policyversion->sitepolicy = $sitepolicy;
        if (!is_null($dbrow)) {
            foreach (get_object_vars($dbrow) as $field => $value) {
                $policyversion->__set($field, $value);
            }
        }

        return $policyversion;
    }


    /**
     * Generate new empty draft policy instance.
     * Note - new instance is not persisted. To persist call save() method
     *
     * @param sitepolicy $sitepolicy
     * @param int timecreated
     * @return policyversion
     * @throws \coding_exception if draft already exists
     */
    public static function new_policy_draft(sitepolicy $sitepolicy, int $timecreated = null): policyversion {
        global $DB;

        if (empty($sitepolicy->get_id())) {
            throw new \coding_exception("Site policy must be saved before adding policy versions");
        }

        $sql = "SELECT id
                  FROM {tool_sitepolicy_policy_version}
                 WHERE timepublished IS NULL
                   AND sitepolicyid = :sitepolicyid";

        $params = ['sitepolicyid' => $sitepolicy->get_id()];
        $versionrec = $DB->get_record_sql($sql, $params);
        if (!empty($versionrec)) {
            throw new \coding_exception('Cannot create draft as it already exists');
        }

        $version = new policyversion();
        $version->sitepolicy = $sitepolicy;
        if (!empty($timecreated)) {
            $version->timecreated = $timecreated;
        }

        return $version;
    }

    /**
     * Save instance to DB
     *
     * @throws \coding_exception
     */
    public function save() {
        global $DB;

        if (empty($this->sitepolicy) || empty($this->sitepolicy->get_id())) {
            throw new \coding_exception("Site policy must be saved before saving the policy version");
        }

        $this->timecreated = $this->timecreated ?? time();

        $entry = new \stdClass();
        $entry->versionnumber = $this->versionnumber;
        $entry->timecreated = $this->timecreated;
        $entry->timepublished = $this->timepublished;
        $entry->timearchived = $this->timearchived;
        $entry->sitepolicyid = $this->sitepolicy->get_id();
        $entry->publisherid = $this->publisherid;

        if (empty($this->id)) {
            // Create.
            // Set versionnumber only on version create.
            $sql = "
              SELECT MAX(versionnumber) as latestnumber
                FROM {tool_sitepolicy_policy_version}
               WHERE sitepolicyid = :sitepolicyid
            ";

            $latestversionnum = $DB->get_field_sql($sql, ['sitepolicyid' => $entry->sitepolicyid]);
            $this->versionnumber = $entry->versionnumber = (int)$latestversionnum + 1;

            $this->id = $DB->insert_record('tool_sitepolicy_policy_version', $entry);
            return;
        }

        // Update.
        $entry->id = $this->id;
        $DB->update_record('tool_sitepolicy_policy_version', $entry);
    }

    /**
     * Delete current version
     *
     * @param bool $force Delete version even if it is already published
     * @throws \coding_exception
     */
    public function delete(bool $force = false) {
        global $DB;

        if (!$force) {
            if (!empty($this->timepublished)) {
                throw new \coding_exception('This version was published, so it cannot be deleted');
            }
        }

        $trans = $DB->start_delegated_transaction();

        // Get all translations and delete
        $localisedpolicyrecs = $DB->get_records('tool_sitepolicy_localised_policy', ['policyversionid' => $this->id]);
        foreach ($localisedpolicyrecs as $localisedpolicyrec) {
            $localisedpolicy = new localisedpolicy($localisedpolicyrec->id);
            $localisedpolicy->delete();
        }

        // Get all consent options and remove.
        $consentoptionrecs = $DB->get_records('tool_sitepolicy_consent_options', ['policyversionid' => $this->id]);
        foreach ($consentoptionrecs as $consentoptionrec) {
            $consentoption = new consentoption($consentoptionrec->id);
            $consentoption->delete();
        }

        $DB->delete_records('tool_sitepolicy_policy_version', ['id' => $this->id]);

        $trans->allow_commit();
    }

    /**
     * Get latest policy version
     *
     * @param sitepolicy $sitepolicy
     * @param string $status Search for latest policy version in this state
     * @return policyversion
     * @throws \coding_exception
     */
    public static function from_policy_latest(sitepolicy $sitepolicy, $status = null): policyversion {
        global $DB;

        if (empty($sitepolicy->get_id())) {
            throw new \coding_exception("Site policy must be saved before retrieving a version");
        }

        $where = '';
        if (!is_null($status)) {
            switch ($status) {
                case self::STATUS_DRAFT:
                    $where = 'AND timepublished IS NULL';
                    break;

                case self::STATUS_PUBLISHED:
                    $where = 'AND timepublished IS NOT NULL AND timearchived IS NULL';
                    break;

                case self::STATUS_ARCHIVED:
                    $where = 'AND timepublished IS NOT NULL AND timearchived IS NOT NULL';
                    break;

                default:
                    throw new \coding_exception("Invalid status passed", $status);
                    break;
            }
        }

        $sql = "SELECT *
                  FROM {tool_sitepolicy_policy_version}
                 WHERE sitepolicyid = :sitepolicyid";
        $orderby = "ORDER BY versionnumber DESC";
        $params = ['sitepolicyid' => $sitepolicy->get_id()];

        // As a sitepolicy may have more than one version, the sql statement may return multiple rows.
        // This function's purpose is to retrieve the latest version
        // As LIMIT is not part of the sql standards, we are using IGNORE_MULTIPLE here to mimic it
        $versionrec = $DB->get_record_sql("$sql $where $orderby", $params, IGNORE_MULTIPLE);
        if (empty($versionrec)) {
            throw new \coding_exception("Policy don't have any versions, remove policy and create new");
        }

        return self::from_data($sitepolicy, $versionrec);
    }

    /**
     * Get current active version for policy
     *
     * @return policyversion
     */
    public static function from_policy_active(sitepolicy $sitepolicy): policyversion {
        return self::from_policy_latest($sitepolicy, policyversion::STATUS_PUBLISHED);
    }

    /**
     * Get all versions of a sitepolicy and related data for policyversion table
     *
     * @param int $sitepolicyid
     * @return array
     **/
    public static function get_versionlist(int $sitepolicyid): array {
        global $DB;

        $versionlistsql = "
             SELECT tspv.id,
                    tspv.versionnumber,
                    tspv.timepublished,
                    tspv.timearchived,
                    CASE
                        WHEN tspv.timepublished IS NULL THEN :statusdraft
                        WHEN tspv.timearchived IS NOT NULL THEN :statusarchived
                        ELSE :statuspublished
                    END AS status,
                    tsoptions.cnt_options,
                    tsoptions.cnt_translations,
                    tsoptions.cnt_translatedoptions,
                    tslp.id AS primarylocalisedid
               FROM {tool_sitepolicy_policy_version} tspv

               JOIN {tool_sitepolicy_localised_policy} tslp
                 ON tspv.id = tslp.policyversionid
                AND tslp.isprimary = :isprimary

               JOIN (
                     SELECT tsco.policyversionid,
                            COUNT(tsco.id) AS cnt_options,
                            COUNT(DISTINCT tslp.id) AS cnt_translations,
                            SUM(CASE WHEN tslc.id IS NOT NULL THEN 1 ELSE 0 END) AS cnt_translatedoptions
                       FROM {tool_sitepolicy_consent_options} tsco
                       JOIN {tool_sitepolicy_localised_policy} tslp
                         ON tslp.policyversionid = tsco.policyversionid
                  LEFT JOIN {tool_sitepolicy_localised_consent} tslc
                         ON tslc.localisedpolicyid = tslp.id
                        AND tslc.consentoptionid = tsco.id
                   GROUP BY tsco.policyversionid
                     ) tsoptions
                 ON tsoptions.policyversionid = tspv.id

              WHERE tspv.sitepolicyid = :sitepolicyid
           ORDER BY tspv.versionnumber DESC
          ";

        $params = ['sitepolicyid' => $sitepolicyid,
                   'statusdraft' => self::STATUS_DRAFT,
                   'statuspublished' => self::STATUS_PUBLISHED,
                   'statusarchived' => self::STATUS_ARCHIVED,
                   'isprimary' => localisedpolicy::STATUS_PRIMARY];
        return $DB->get_records_sql($versionlistsql, $params);
    }

    /**
     * Get summary data
     *
     * @param bool $reset If set to true summary information will be reloaded.
     * @return \stdClass[]
     */
    public function get_summary($reset = false): array {
        if ($reset) {
            $this->summaryinformation = null;
        }
        $this->ensure_summary_loaded();
        return $this->summaryinformation;
    }

    /**
     * Ensure that summary information has been loaded.
     */
    public function ensure_summary_loaded(): void {
        global $DB;
        if ($this->summaryinformation !== null) {
            return;
        }

        $localisedpolicysql = "
               SELECT tslp.id,
                      tspv.timepublished,
                      tslp.language,
                      tslp.isprimary,
                      tslp2.id AS primarylocalisedpolicyid,
                      tslp2.language AS primarylanguage,
                      COUNT(tslc2.id) AS cnt_statements,
                      COUNT(tslc2.id) - COUNT(tslc.id) AS incomplete

                 FROM {tool_sitepolicy_policy_version} tspv
                 JOIN {tool_sitepolicy_localised_policy} tslp
                   ON (tspv.id = tslp.policyversionid)

                 JOIN {tool_sitepolicy_localised_policy} tslp2
                   ON (tspv.id = tslp2.policyversionid AND tslp2.isprimary = 1)

            LEFT JOIN {tool_sitepolicy_localised_consent} tslc2
                   ON (tslp2.id = tslc2.localisedpolicyid)
            LEFT JOIN {tool_sitepolicy_localised_consent} tslc
                   ON (tslp.id = tslc.localisedpolicyid
                  AND tslc2.consentoptionid = tslc.consentoptionid)

                WHERE tspv.id = :policyversionid
             GROUP BY tslp.id, tspv.timepublished, tslp.language, tslp.isprimary, tslp2.id, tslp2.language
             ORDER BY tslp.id";
        $params = [
            'policyversionid' => $this->id,
            'policyversionid2' => $this->id
        ];

        $this->summaryinformation = $DB->get_records_sql($localisedpolicysql, $params);
    }

    /**
     * Check if this version is draft
     * @return bool
     */
    public function is_draft(): bool {
        return $this->get_status() == self::STATUS_DRAFT;
    }

    /**
     * Check if this version is archived
     * @return bool
     */
    public function is_archived(): bool {
        return $this->get_status() == self::STATUS_ARCHIVED;
    }

    /**
     * Check if all translations has all consent options completed
     * @return bool
     */
    public function is_complete(): bool {
        global $DB;

        $sql = "
               SELECT 1
                 FROM {tool_sitepolicy_localised_policy} tslp
                 JOIN {tool_sitepolicy_consent_options} tsco
                   ON (tsco.policyversionid = tslp.policyversionid)
            LEFT JOIN {tool_sitepolicy_localised_consent} tslc
                   ON (tslc.localisedpolicyid = tslp.id AND tslc.consentoptionid = tsco.id)
                WHERE tslp.policyversionid = :policyversionid
                  AND (tslc.id IS NULL
                   OR tslc.statement = ''
                   OR tslc.consentoption = ''
                   OR tslc.nonconsentoption = '')
        ";

        $incomplete = $DB->record_exists_sql($sql, ['policyversionid' => $this->id]);
        return !$incomplete;
    }

    /**
     * Archive published version
     * @param int $time
     * @throws \coding_exception
     */
    public function archive(int $time = 0) {
        if (empty($time)) {
            $time = time();
        }

        if (empty($this->timepublished)) {
            throw new \coding_exception("Cannot archive unpublished version");
        }

        if (!empty($this->timearchived)) {
            // Already archived - nothing to do
            return;
        }

        $this->timearchived = $time;
        $this->save();
    }


    /**
     * Publish unpublished version
     * @param int $publisherid
     * @param int $time
     */
    public function publish(int $publisherid = 0, int $time = 0) {
        global $USER;

        if (empty($time)) {
            $time = time();
        }

        if (empty($publisherid)) {
            $publisherid = $USER->id;
        }

        if (!empty($this->timepublished)) {
            throw new \coding_exception("Cannot publish version that is already published");
        }

        if (!$this->is_complete()) {
            throw new \coding_exception("Cannot publish incomplete version");
        }

        $this->timepublished = $time;
        $this->publisherid = $publisherid;
        $this->save();
    }

    /**
     * Clones policy primary version and constent options
     * @param policyversion $from Policy version to take content from
     */
    public function clone_content(policyversion $from) {

        $fromprimarypolicy = localisedpolicy::from_version($from, ['isprimary' => localisedpolicy::STATUS_PRIMARY]);

        $tolocalisedpolicy = localisedpolicy::from_data($this, $fromprimarypolicy->get_language(false), 1);
        $tolocalisedpolicy->clone_content($fromprimarypolicy);
    }

    /**
     * Check if current active policy has active version.
     * @return bool
     */
    public static function has_active(sitepolicy $sitepolicy): bool {
        global $DB;

        $sql = "
            SELECT 1
              FROM {tool_sitepolicy_policy_version}
             WHERE sitepolicyid = :sitepolicyid
               AND timepublished IS NOT NULL
               AND timearchived IS NULL";
        $params = ['sitepolicyid' => $sitepolicy->get_id()];

        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Get all localisations languages for current version
     *
     * @param bool $formatted If true the language list will be translated and formatted for output.
     * @return array of languages. First element is the primary language
     */
    public function get_languages($formatted = false): array {
        global $DB;

        // Sorting by id as the primary is always created first
        $languages = $DB->get_records('tool_sitepolicy_localised_policy', ['policyversionid' => $this->id], 'id', 'id, language, isprimary');
        $returnlanguages = [];

        if ($formatted) {
            $primarylang = reset($languages)->language;
            $installedlanguagepacks = get_string_manager()->get_list_of_translations(false);
            $alllanguagepacks = get_string_manager()->get_list_of_translations(false);
            $alllanguages = get_string_manager()->get_list_of_languages($primarylang);
            foreach ($languages as $language) {
                if (isset($installedlanguagepacks[$language->language])) {
                    // Expected.
                    $language->language = $installedlanguagepacks[$language->language];
                } else if (isset($alllanguagepacks[$language->language])) {
                    // Its a translation for a language pack that is no longer installed.
                    $language->language = $alllanguagepacks[$language->language];
                } else if (isset($alllanguages[$language->language])) {
                    // No dice, its not a known language pack, maybe its a language.
                    $language->language = $alllanguages[$language->language];
                } else {
                    // Woah, what is this?!
                    debugging('Unknown localised site policy language "'.$language->language.'"', DEBUG_DEVELOPER);
                }
            }
        }

        foreach ($languages as $language) {
            if (isset($returnlanguages[$language->language])) {
                debugging('Policy version found with two translations in the same language "'.$language->language.'".', DEBUG_DEVELOPER);
            }
            $returnlanguages[$language->language] = $language;
        }

        return $returnlanguages;
    }

    /**
     * Returns true if there are any incomplete language translations.
     *
     * @return bool
     */
    public function has_incomplete_language_translations(): bool {
        if ($this->get_status() != policyversion::STATUS_DRAFT) {
            return false;
        }
        $versionsummary = $this->get_summary();
        foreach ($versionsummary as $entries => $entry) {
            if ($entry->incomplete) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns an array of incomplete language translations.
     *
     * @return string[]
     */
    public function get_incomplete_language_translations(): array {
        if ($this->get_status() != policyversion::STATUS_DRAFT) {
            return array();
        }
        $versionsummary = $this->get_summary();
        $incompletelanguages = [];
        $translations = get_string_manager()->get_list_of_translations(true);
        $languages = get_string_manager()->get_list_of_languages();
        foreach ($versionsummary as $entries => $entry) {
            if ($entry->incomplete) {
                if (isset($translations[$entry->language])) {
                    $incompletelanguages[] = $translations[$entry->language];
                } else if (isset($languages[$entry->language])) {
                    $incompletelanguages[] = $languages[$entry->language];
                } else {
                    $incompletelanguages[] = $entry->language;
                }
            }
        }
        return $incompletelanguages;
    }

    /**
     * Ensure the primary localised version has been loaded.
     */
    private function ensure_primary_localisedversion_loaded() {
        if (!$this->primarylocalisedpolicy) {
            $this->primarylocalisedpolicy = localisedpolicy::from_version($this, ['isprimary' => localisedpolicy::STATUS_PRIMARY]);
        }
    }

    /**
     * Returns the primary localised version.
     * @return localisedpolicy
     */
    public function get_primary_localisedpolicy(): localisedpolicy {
        $this->ensure_primary_localisedversion_loaded();
        return $this->primarylocalisedpolicy;
    }

    /**
     * Return the title of the primary localised version.
     *
     * @param bool $formatted
     * @return string
     */
    public function get_primary_title(bool $formatted = false): string {
        return $this->get_primary_localisedpolicy()->get_title($formatted);
    }
}

