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
 * Class for changing the tool_sitepolicy_localised_policy table
 **/
class localisedpolicy {
    /*
     * primary status
     */
    const STATUS_PRIMARY = 1;
    const STATUS_NOTPRIMARY = 0;


    /**
     * @var int id
     */
    private $id = 0;

    /**
     * @var string language
     */
    private $language = '';

    /**
     * @var string title
     */
    private $title = '';

    /**
     * @var string policytext
     */
    private $policytext = '';

    /**
     * @var int policytextformat
     */
    private $policytextformat = FORMAT_HTML;

    /**
     * @var string whatsnew
     */
    private $whatsnew = '';

    /**
     * @var int whatsnewformat
     */
    private $whatsnewformat = FORMAT_HTML;

    /**
     * @var int timecreated
     */
    private $timecreated = 0;

    /**
     * @var int isprimary
     */
    private $isprimary = self::STATUS_NOTPRIMARY;

    /**
     * @var int authorid
     */
    private $authorid = 0;

    /**
     * @var policyversion policyversion
     */
    private $policyversion = null;

    /**
     * Localised consent options associated with this policy
     * @var localisedconsent[]
     */
    private $consentoptions = [];

    /**
     * localisedpolicy constructor.
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
     * Gets language for localised policy
     * @param bool $formatted If true the language is translated and formatted for output.
     * @return string language
     */
    public function get_language($formatted = false): string {
        if ($formatted) {
            $translations = get_string_manager()->get_list_of_translations(true);
            if (isset($translations[$this->language])) {
                return $translations[$this->language];
            }
            // This is just a guess, it may or may not match up, if it does then great!
            $languages = get_string_manager()->get_list_of_languages();
            if (isset($languages[$this->language])) {
                return $languages[$this->language];
            }
            // No luck.
            return $this->language;
        }
        return $this->language;
    }

    /**
     * Gets title for localised policy
     *
     * @param bool $formatted If true the title will be formatted for output before being returned.
     * @return string title
     */
    public function get_title($formatted = false): string {
        if ($formatted) {
            return format_string($this->title, true, ['context' => \context_system::instance()]);
        }
        return $this->title;
    }

    /**
     * Gets policytext for localised policy
     *
     * @param bool $formatted If set to true the text will be formatted for output before being returned.
     * @return string policytext
     */
    public function get_policytext($formatted = true): string {
        if ($formatted) {
            return format_text($this->policytext, $this->policytextformat);
        }
        return $this->policytext;
    }

    /**
     * Gets policytextformat for localised policy
     *
     * @return int policytextformat
     */
    public function get_policytextformat(): int {
        return $this->policytextformat;
    }

    /**
     * Gets whatsnew for localised polic
     * @param bool $formatted If set to true the text will be formatted for output before being returned.
     * @return string whatsnew
     */
    public function get_whatsnew($formatted = true): string {
        if ($formatted) {
            return format_text($this->whatsnew, $this->whatsnewformat);
        }
        return $this->whatsnew;
    }

    /**
     * Gets whatsnewformat for localised policy
     *
     * @return int whatsnewformat
     */
    public function get_whatsnewformat(): int {
        return $this->whatsnewformat;
    }

    /**
     * Gets timecreated for localised policy
     * @return int timecreated
     */
    public function get_timecreated(): ?int {
        return $this->timecreated;
    }

    /**
     * Gets isprimary for localised policy
     * @return int isprimary
     */
    public function is_primary(): int {
        return $this->isprimary;
    }

    /**
     * Gets authorid for localised policy
     * @return int authorid
     */
    public function get_authorid(): int {
        return $this->authorid;
    }

    /**
     * Gets policyversionid for localised policy
     * @return policyversion policyversion
     */
    public function get_policyversion(): policyversion {
        return $this->policyversion;
    }

    /**
     * Get consentoptions
     * @return localisedconsent[]
     */
    public function get_consentoptions(): array {
        return $this->consentoptions;
    }

    /**
     * Sets language
     * @param string $language
     */
    public function set_language(string $language) {
        $this->language = $language;
    }

    /**
     * Sets title
     * @param string $title
     */
    public function set_title(string $title) {
        $this->title = $title;
    }

    /**
     * Set policy text
     * @param string $policytext
     * @param int $policytextformat - Note that the FORMAT_* definitions are strings.
     *                                You need to convert it if passing it to this function.
     */
    public function set_policytext(string $policytext, int $policytextformat = null) {
        $this->policytext = $policytext;
        if (!is_null($policytextformat)) {
            $this->policytextformat = $policytextformat;
        }
    }

    /**
     * Set policy text format
     * @param int $policytextformat - Note that the FORMAT_* definitions are strings.
     *                                You need to convert it if passing it to this function.
     */
    public function set_policytextformat(int $policytextformat) {
        $this->policytextformat = $policytextformat;
    }

    /**
     * Set what's new text
     * @param string $whatsnew
     * @param int $whatsnewformat - Note that the FORMAT_* definitions are strings.
     *                              You need to convert it if passing it to this function.
     */
    public function set_whatsnew(string $whatsnew, int $whatsnewformat = null) {
        $this->whatsnew = $whatsnew;
        if (!is_null($whatsnewformat)) {
            $this->whatsnewformat = $whatsnewformat;
        }
    }

    /**
     * Set whatsnew format
     * @param int $whatsnewformat - Note that the FORMAT_* definitions are strings.
     *                              You need to convert it if passing it to this function.
     */
    public function set_whatsnewformat(int $whatsnewformat) {
        $this->whatsnewformat = $whatsnewformat;
    }

    /**
     * Sets timecreated
     * @param int $time
     */
    public function set_timecreated(int $time) {
        $this->timecreated = $time;
    }

    /**
     * Sets is_primary
     * @param int $isprimary
     */
    public function set_isprimary(int $isprimary) {
        $this->isprimary = $isprimary;
    }

    /**
     * Sets authorid for localised policy
     * @param int $userid author id
     */
    public function set_authorid(int $userid) {
        $this->authorid = $userid;
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
     * Load instance data from DB
     * @return $this
     */
    private function load(): localisedpolicy {
        global $DB;

        $localisedpolicy = $DB->get_record('tool_sitepolicy_localised_policy', ['id' => $this->id], '*', MUST_EXIST);

        $this->language = $localisedpolicy->language;
        $this->title = $localisedpolicy->title;
        $this->policytext = $localisedpolicy->policytext;
        $this->policytextformat = $localisedpolicy->policytextformat;
        $this->whatsnew = $localisedpolicy->whatsnew;
        $this->whatsnewformat = $localisedpolicy->whatsnewformat;
        $this->timecreated = $localisedpolicy->timecreated;
        $this->isprimary = $localisedpolicy->isprimary;
        $this->authorid = $localisedpolicy->authorid;
        $this->policyversion = new policyversion($localisedpolicy->policyversionid);

        $this->consentoptions = localisedconsent::get_policy_options($this);

        return $this;
    }

    /**
     * Create a new localised policy instance from the provided data
     *
     * @param policyversion $version
     * @param string $language
     * @param bool $isprimary if null it will be automatically determined
     * @param Object $dbrow Optional object containing data of the instance
     *                      Can't use type hinting as it is only available from PHP7.2
     * @return localisedpolicy
     */
    public static function from_data(policyversion $version, string $language, int $isprimary = null, $dbrow = null): localisedpolicy {

        $localisedpolicy = new self();
        $localisedpolicy->policyversion = $version;
        $localisedpolicy->language = $language;

        if (is_null($isprimary)) {
            $isprimary = self::STATUS_PRIMARY;
            if (self::exists($version, ['isprimary' => $isprimary])) {
                // Primary exist, create non primary
                $isprimary = self::STATUS_NOTPRIMARY;
            }
        }

        $localisedpolicy->isprimary = $isprimary;

        if (!is_null($dbrow)) {
            foreach (get_object_vars($dbrow) as $field => $value) {
                $localisedpolicy->__set($field, $value);
            }
        }

        return $localisedpolicy;
    }

    /**
     * Instantiate localised policy instance for given policyversion and optional params
     * Only the first localised policy that satisfies the criteria is returned
     *
     * @param int $version
     * @return localisedpolicy
     */
    public static function from_version(policyversion $version, array $params): localisedpolicy {
        global $DB;

        $params = array_merge(
            ['policyversionid' => $version->get_id()],
            $params);

        $row = $DB->get_record('tool_sitepolicy_localised_policy', $params, '*', MUST_EXIST);
        $localisedpolicy = self::from_data($version, $row->language, $row->isprimary, $row);
        $localisedpolicy->consentoptions = localisedconsent::get_policy_options($localisedpolicy);

        return $localisedpolicy;
    }

    /**
     * Save localised policy and localised_consent to database
     * @throws \coding_exception
     */
    public function save() {
        global $DB;

        if ($this->isprimary && $this->other_primary_exists()) {
            throw new \coding_exception("Cannot save localised policy. Another primary localised policy already exists.");
        }

        if ($this->other_localised_exists()) {
            throw new \coding_exception("Cannot save localised policy. Another policy with this language and version already exists.");
        }

        foreach ($this->consentoptions as $key => $localisedconsent) {
            $localisedpolicyid = $localisedconsent->get_localisedpolicy()->get_id();
            if ($localisedpolicyid != 0 && $localisedpolicyid != $this->id) {
                throw new \coding_exception("Localised policy belongs to a different policy version.");
            }
        }

        $entry = new \stdClass();
        $entry->language = $this->language;
        $entry->title = $this->title;
        $entry->policytext = $this->policytext;
        $entry->policytextformat = $this->policytextformat;
        $entry->whatsnew = $this->whatsnew;
        $entry->whatsnewformat = $this->whatsnewformat;
        $entry->timecreated = $this->timecreated;
        $entry->isprimary = $this->isprimary;
        $entry->authorid = $this->authorid;
        $entry->policyversionid = $this->policyversion->get_id();

        if (empty($entry->timecreated)) {
            // Hmm, its empty default to now.
            $entry->timecreated = time();
        }

        // Save localised policy and its options together
        $trans = $DB->start_delegated_transaction();

        if (empty($this->id)) {
            // Create.
            $this->id = $DB->insert_record('tool_sitepolicy_localised_policy', $entry);
        } else {
            // Update.
            $entry->id = $this->id;
            $DB->update_record('tool_sitepolicy_localised_policy', $entry);
        }

        // Now we also need to save the associated consentoptions and localised_consent
        $this->save_consentoptions();

        $trans->allow_commit();
    }

    /**
     * Add, update or delete all localised consent options of this localised policy
     */
    private function save_consentoptions() {
        foreach ($this->consentoptions as $key => $localisedconsent) {
            $option = $localisedconsent->get_option();
            if (!$localisedconsent->is_removed()) {
                if ($this->isprimary) {
                    $option->save();
                }
                $localisedconsent->save();

            } else {
                if ($this->isprimary) {
                    localisedconsent::delete_all($option->get_id());
                    $option->delete();
                } else {
                    $localisedconsent->delete();
                }

                unset($this->consentoptions[$key]);
            }
        }
    }

    /**
     * Set all statements provided
     * @param statement[] $statements
     */
    public function set_statements(array $statements) {

        $this->consentoptions = [];

        foreach ($statements as $statement) {
            $consentoptionid = isset($statement->dataid) ? $statement->dataid : 0;
            $removed = isset($statement->removedstatement) ? $statement->removedstatement : '';

            if (empty($consentoptionid)) {
                if (empty($removed)) {
                    // New option
                    $option = consentoption::from_data($this->get_policyversion(), (bool)$statement->mandatory);
                } else {
                    continue;
                }
            } else {
                $option = new consentoption($consentoptionid);
                if ($this->isprimary) {
                    $option->set_mandatory((bool)$statement->mandatory);
                }
            }

            $curoption = $this->consentoptions[] = localisedconsent::from_data($this, $option,
                    $statement->statement, $statement->provided, $statement->withheld);

            if (!empty($removed)) {
                $curoption->set_removed(true);
            }
        }
    }

    /**
     * Get consentoption statements
     * As the dataid of all non-persisted entries are 0, we index
     * the resulting array with (-1) * dataid for persisted entries
     * and a positive sequence number (>= 0) for non-persisted entries
     *
     * @param bool $clearids Clear the dataids to force creation?
     * @return array of statements
     */
    public function get_statements(bool $clearids = false): array {
        $statements = [];

        foreach ($this->consentoptions as $localisedconsent) {
            $statement = new statement();
            $statement->statement = $localisedconsent->get_statement();
            $statement->mandatory = $localisedconsent->get_option()->get_mandatory();
            $statement->dataid = $clearids ? 0 : $localisedconsent->get_option()->get_id();
            $statement->provided = $localisedconsent->get_consentoption();
            $statement->withheld = $localisedconsent->get_nonconsentoption();

            if ($statement->dataid == 0) {
                $statements[] = $statement;
            } else {
                $statements[-1 * $statement->dataid] = $statement;
            }
        }

        return $statements;
    }

    /**
     * Delete current translation as well as all its options
     * @throws \coding_exception
     */
    public function delete() {
        global $DB;

        $sql = "SELECT 1
                  FROM {tool_sitepolicy_user_consent} tsuc
                  JOIN {tool_sitepolicy_consent_options} tsco
                    ON tsco.id = tsuc.consentoptionid
                 WHERE tsuc.language = :language
                   AND tsco.policyversionid = :policyversionid";
        $params = ['language' => $this->language,
                   'policyversionid' => $this->policyversion->get_id()];

        if ($DB->record_exists_sql($sql, $params)) {
            throw new \coding_exception("Localised policy can't be deleted while user_consent entries exist");
        }

        // Also delete all localisedconsents belonging to this localisedpolicy
        $trans = $DB->start_delegated_transaction();

        $localisedconsents = localisedconsent::get_policy_options($this);
        foreach ($localisedconsents as $localisedconsent) {
            $localisedconsent->delete();
        }

        $DB->delete_records('tool_sitepolicy_localised_policy', ['id' => $this->id]);

        $trans->allow_commit();
    }

    /**
     * Make clone content of primary localised policy
     * Note: If required non-primary version clone, it will require different behaviour (main difference is how
     *       policy treats options). In this case create primarylocalisedpolicy class which extend localised policy
     *       and implement only non-primary behaviour in this class, and override affected methods (set_statements,
     *       delete, and clone_content) in that class. Use proper factory methods to get correct versions.
     */
    public function clone_content(localisedpolicy $from) {

        if (!$this->is_primary() || !$from->is_primary()) {
            throw new \coding_exception("Cannot clone non primary localised policy");
        }

        $this->title = $from->get_title(false);
        $this->policytext = $from->get_policytext(false);
        $this->policytextformat = $from->get_policytextformat();

        // Can't just copy consentoptions - we need to ensure that new objects are created
        $statements = $from->get_statements(true);
        $this->set_statements($statements);
        $this->save();
    }

    /**
     * Get title of the primary version of this policyversion
     *
     * @param bool $formatted If true the title will be formatted for output.
     * @return string
     */
    public function get_primary_title($formatted = false): string {
        global $DB;

        if ($this->is_primary()) {
            return $this->get_title($formatted);
        }

        $params = ['policyversionid' => $this->policyversion->get_id(),
                   'isprimary' => self::STATUS_PRIMARY];
        $title = $DB->get_field('tool_sitepolicy_localised_policy', 'title', $params);
        if ($formatted) {
            return format_string($title, true, ['context' => \context_system::instance()]);
        }
        return $title;
    }

    /**
     * Get translated title in the specified language
     * @return string
     */
    public function get_translated_title($language): string {
        global $DB;

        if ($this->language == $language) {
            return $this->title;
        }

        $params = ['policyversionid' => $this->policyversion->get_id(),
                   'language' => $language];
        return $DB->get_field('tool_sitepolicy_localised_policy', 'title', $params);
    }

    /**
     * Check whether a localised policy with the provided attributes exist
     *
     * @param policyversion $version Policy_version to check against
     * @param array $params Attributes to search for
     * @return bool true if a matching record exists, else false.
     */
    public static function exists(policyversion $version, array $params = []): bool {
        global $DB;

        $params = array_merge(['policyversionid' => $version->get_id()], $params);
        return $DB->record_exists('tool_sitepolicy_localised_policy', $params);
    }

    /**
     * Check whether another primary version already exists
     */
    private function other_primary_exists(): bool {
        global $DB;

        $sql = "
            SELECT id
              FROM {tool_sitepolicy_localised_policy}
             WHERE policyversionid= :policyversionid
               AND isprimary = :isprimary
               AND id <> :id
        ";

        $params = [
            'policyversionid' => $this->policyversion->get_id(),
            'isprimary' => localisedpolicy::STATUS_PRIMARY,
            'id' => $this->id];
        return $DB->record_exists_sql($sql, $params);

    }

    /**
     * Check whether another localised policy with the same language already exists
     */
    private function other_localised_exists(): bool {
        global $DB;

        $sql = "
          SELECT id
            FROM {tool_sitepolicy_localised_policy}
           WHERE policyversionid = :policyversionid
             AND language = :language
             AND id <> :id
        ";

        $params = [
            'policyversionid' => $this->policyversion->get_id(),
            'language' => $this->language,
            'id' => $this->id];
        return $DB->record_exists_sql($sql, $params);
    }

}
