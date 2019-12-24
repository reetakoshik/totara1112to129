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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir  . '/testing/generator/data_generator.php');

use \tool_sitepolicy\sitepolicy,
    \tool_sitepolicy\policyversion,
    \tool_sitepolicy\localisedpolicy;
/**
 * Site policy generator.
 *
 * Usage:
 *    $sitepolicygenerator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');
 */
class tool_sitepolicy_generator extends component_generator_base {
    /**
     * Create a new draft version, with specfied translations and number of consent options.
     * If no site policy is provided one is created.
     *
     * Options
     *  [
     *      'sitepolicy' => $sitepolicy // An instance of a site policy, if not provided one is created.
     *      'time' => time(), // Time of creation
     *      'authorid' => 2, // User who created
     *      'languages' => 'en', // Languages. First language is primary
     *      'langprefix' => [''], // Prefix to add each language's values to distinguish between languages
     *      'title' => 'Policy title',
     *      'statement' => 'Policy statement',
     *      'statementformat' => FORMAT_HTML
     *      'numoptions' => 1, // Number of consent options per version
     *      'consentstatement' => 'Consent statement',
     *      'providetext' => 'yes',
     *      'withheldtext' => 'no',
     *      'mandatory' => true],
     *      ]
     *  ]
     *
     * @param array $options
     * @return sitepolicy
     */
    public function create_draft_policy(array $options = []) : sitepolicy {
        // Wait one second to prevent duplicates
        if (!isset($options['time'])) {
            sleep(1);
        }

        $definition = $this->parse_options($options);
        $sitepolicy = $this->create_policy($definition);

        return $sitepolicy;
    }

    /**
     * Create new site policy with one published version, with specfied translations and number of consent options
     * If specified, also add user consents
     *
     * Options
     *  [
     *      'time' => time(), // Time of creation
     *      'authorid' => 2, // User who created
     *      'languages' => 'en', // Languages. First language is primary
     *      'langprefix' => [''], // Prefix to add each language's values to distinguish between languages
     *      'title' => 'Policy title',
     *      'statement' => 'Policy statement',
     *      'numoptions' => 1, // Number of consent options per version
     *      'consentstatement' => 'Consent statement',
     *      'providetext' => 'yes',
     *      'withheldtext' => 'no',
     *      'mandatory' => true,
     *      'hasconsented' => true,
     *      'consentuser' => 3,
     *      'consentlanguage' => 'en',
     *      'consenttime' => time()
     *      ]
     *  ]
     *
     * @param array $options
     * @return sitepolicy
     */
    public function create_published_policy(array $options = []) : sitepolicy {
        // Wait one second to prevent duplicates
        if (!isset($options['time'])) {
            sleep(1);
        }

        $definition = $this->parse_options($options);
        $sitepolicy = $this->create_policy($definition);

        $version = policyversion::from_policy_latest($sitepolicy);
        $version->publish($definition['authorid'], time());

        if (isset($options['hasconsented']) && isset($options['consentuser'])) {
            $consenttime = $options['consenttime'] ?? time();
            $this->add_userconsent($sitepolicy, $options['hasconsented'], $options['userid'], null, $consenttime);
        }
        return $sitepolicy;
    }

    /**
     * Generate one policy with different versions, translations and options
     *
     * Options
     *  [
     *      'hasdraft' => true, // Include a draft version?
     *      'numpublished' => 0,  // Number of versions to publish
     *      'allarchived' => false, // Should all non-draft versions be archived?
     *      'numoptions' => 1, // Number of consent options per version
     *      'time' => time(), // Time of creation
     *      'authorid' => 2, // User who created
     *      'languages' => 'en', // Translation languages. First language is primary
     *      'langprefix' => [''], // Prefix to add each language's values to distinguish between languages
     *      'title' => 'Policy title',
     *      'statement' => 'Policy statement',
     *      'consentstatement' => 'Consent statement',
     *      'providetext' => 'Yes',
     *      'withheldtext' => 'No',
     *      'mandatory' => true],
               (valid mandatory values:
                  'all' - all are mandatory
                  'none' - none are mandatory
                  'first' - only first is mandatory)
     *      ]
     *  ]
     *
     * @param array $options
     * @return sitepolicy
     */
    public function create_multiversion_policy($options) : sitepolicy {
        // Wait one second to prevent duplicates
        if (!isset($options['time'])) {
            sleep(1);
        }

        $hasdraft = $options['hasdraft'] ?? true;
        $numpublished = $options['numpublished'] ?? 0;
        $allarchived = $options['allarchived'] ?? false;
        $definition = $this->parse_options($options);

        if (!$hasdraft && $numpublished == 0) {
            throw new \coding_exception("You must specify at least 1 draft or published  version");
        }

        $sitepolicy = $this->create_policy($definition);
        $latestversion = policyversion::from_policy_latest($sitepolicy);
        $publisherid = $definition['authorid'] ?? 2;

        // Already created a draft - topublish is to ensure we have a draft when required
        $topublish = $numpublished;
        if (!$hasdraft && $numpublished > 0) {
            $topublish -= 1;
        }

        for ($i = 1; $i <= $topublish; $i++) {
            $latestversion->publish($publisherid);
            if ($i != $numpublished) {      // Last published should not be archived
                $latestversion->archive(time());
            }

            $draft = tool_sitepolicy\policyversion::new_policy_draft($sitepolicy);
            $draft->save();
            $draft->clone_content($latestversion);
            $latestversion = $draft;
        }
        for ($i = $topublish + 1; $i <= $numpublished; $i++) {
            $latestversion->publish($publisherid);
        }

        // Archive last published if needed
        if ($numpublished > 0 && $allarchived) {
            if ($latestversion = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_PUBLISHED)) {
                $latestversion->archive(time());
            }
        }

        // Add consents for latest published version only
        if ($numpublished > 0 && !$allarchived && isset($options['hasconsented']) && isset($options['consentuser'])) {
            $consenttime = $options['consenttime'] ?? time();
            $this->add_userconsent($sitepolicy, $options['hasconsented'], $options['consentuser'], null, $consenttime);
        }

        return $sitepolicy;
    }

    /**
     * Parse provided options and pack into a definition array to pass to create_policy method
     *
     * @param array $options
     * @return array $definitions
     */
    private function parse_options($options): array {
        $definition = [];
        $definition['sitepolicy'] = $options['sitepolicy'] ?? null;
        $definition['time'] = $options['time'] ?? time();
        $definition['authorid'] = $options['authorid'] ?? 2;
        $definition['languages'] = isset($options['languages']) ? explode(',', $options['languages']) : ['en'];
        $definition['langprefix'] = isset($options['langprefix']) ? explode(',', $options['langprefix']) : [''];
        $definition['title'] = $options['title'] ?? "Policy title";
        $definition['statement'] = $options['statement'] ?? "Policy statement";
        $definition['statementformat'] = $options['statementformat'] ?? FORMAT_HTML;

        $numoptions = $options['numoptions'] ?? 1;
        if ((int)$numoptions < 1) {
            $numoptions = 1;
        }

        $definition['consents'] = [];

        $consentstatement = $options['consentstatement'] ?? 'Consent statement';
        $providetext = $options['providetext'] ?? 'Yes';
        $withheldtext = $options['withheldtext'] ?? 'No';

        // mandatory can be one of
        //   - all : all consentoptions are mandatory
        //   - none : all consentoptions are optional
        //   - first : only the first consentoption is mandatory
        $mandatory = isset($options['mandatory']) ? $options['mandatory'] : 'first';

        for ($i = 1; $i <= $numoptions; $i++) {
            $optionmandatory = true;
            if ($mandatory == 'none' || ($mandatory == 'first' && $i > 1)) {
                $optionmandatory = false;
            }
            $option = ["{$consentstatement} {$i}", $providetext, $withheldtext, $optionmandatory];
            $definition['consents'][] = $option;
        }

        return $definition;
    }

    /**
     * Create new site policy with one draft version and one or more localised policies
     * Definition
     *  [
     *      'time' => time(), // Time of creation
     *      'authorid' => 2, // User who created
     *      'languages' => 'en', // First language is primary
     *      'langprefix' => [''], // Prefix to add each language's values to distinguish between languages
     *      'title' => 'Policy title',
     *      'statement' => 'Policy statement',
     *      'statementformat' => FORMAT_HTML,
     *      'consents' => [
     *          ['Consent statement', 'yes', 'no', true], // Consent text, agreed text, witheld text, mandatory
     *      ]
     *  ]
     *
     * @param array $definition all optional
     * @return sitepolicy
     */
    private function create_policy(array $definition = []) : sitepolicy {
        $definition = array_merge([
            'time' => time(),
            'authorid' => 2,
            'languages' => ['en'],
            'langprefix' => [''],
            'title' => "Policy title",
            'statement' => "Policy statement",
            'statementformat' => FORMAT_HTML,
            'consents' => [["Consent statement", 'yes', 'no', true]]
        ], $definition);

        $consentoptions = [];

        if ($definition['sitepolicy'] instanceof sitepolicy) {
            $sitepolicy = $definition['sitepolicy'];

        } else {

            $sitepolicy = new sitepolicy();
            $sitepolicy->set_timecreated($definition['time']);
            $sitepolicy->save();
        }

        $version = policyversion::new_policy_draft($sitepolicy);
        $version->set_timecreated($definition['time']);
        $version->save();

        $consentids = [];
        for ($i = 0; $i < count($definition['languages']); $i++) {
            $lang = trim($definition['languages'][$i]);
            $langprefix = !empty($definition['langprefix'][$i]) ? trim($definition['langprefix'][$i]) . ' ' : '';
            $policy = localisedpolicy::from_data($version, $lang, $i == 0);
            $policy->set_authorid($definition['authorid']);
            $policy->set_timecreated($definition['time']);
            $policy->set_title($langprefix . $definition['title']);
            $policy->set_policytext($langprefix . $definition['statement'], $definition['statementformat']);

            $consents = [];

            foreach($definition['consents'] as $idx => $statement) {
                if (count($statement) < 3) {
                    throw new \coding_exception("You must specify at least 3 attributes for each consent - Statement, consentoption and nonconsentoption");
                }

                $consent = new stdClass();
                $consent->dataid = ($i == 0) ? 0 : $consentids[$idx];
                $consent->mandatory = $statement[3] ?? true;
                $consent->statement = $langprefix . $statement[0];
                $consent->provided = $langprefix . $statement[1];
                $consent->withheld = $langprefix . $statement[2];

                $consents[] = $consent;
            }

            $policy->set_statements($consents);
            $policy->save();

            // If primary language - get consent_options.id for other languages to prevent options being created again
            if ($i == 0) {
                $localisedconsents = $policy->get_consentoptions();
                foreach ($localisedconsents as $idx => $localisedconsent) {
                    $consentids[$idx] = $localisedconsent->get_option()->get_id();
                }
            }
        }

        return $sitepolicy;
    }

    /**
     * Create userconsent for each option in the primary language
     *
     * @param $sitepolicy sitepolicy
     * @param bool $hasconsented
     * @param int $userid
     */
    public function add_userconsent(sitepolicy $sitepolicy, bool $hasconsented, int $userid, $language = null, $timeconsented = null) {
        global $DB;

        $params = [
            'userid' => $userid,
            'timeconsented' => $timeconsented ?? time(),
            'hasconsented' => (int)$hasconsented,
            'sitepolicyid' => $sitepolicy->get_id(),
        ];

        if (empty($language)) {
            $tspv_where = 'AND tslp.isprimary = :isprimary';
            $params['isprimary'] = localisedpolicy::STATUS_PRIMARY;
        } else {
            $tspv_where = 'AND tslp.language = :language';
            $params['language'] = $language;
        }

        $sql = "
            INSERT INTO {tool_sitepolicy_user_consent}
                        (userid, timeconsented, hasconsented, consentoptionid, language)
                 SELECT :userid,
                        :timeconsented,
                        :hasconsented,
                        tsco.id,
                        tslp.language
                   FROM {tool_sitepolicy_policy_version} tspv
                   JOIN {tool_sitepolicy_consent_options} tsco
                     ON tsco.policyversionid = tspv.id
                   JOIN {tool_sitepolicy_localised_policy} tslp
                     ON tslp.policyversionid = tspv.id
                    $tspv_where
                  WHERE tspv.sitepolicyid = :sitepolicyid
                    AND tspv.timepublished IS NOT NULL
                    AND tspv.timearchived IS NULL
            ";

        $DB->execute($sql, $params);
    }
}