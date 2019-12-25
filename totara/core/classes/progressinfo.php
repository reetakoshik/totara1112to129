<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_core
 */

namespace totara_core;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara progressinfo class
 */
final class progressinfo implements \cacheable_object {

    /** Progress aggregation type constants */
    const AGGREGATE_NONE = 0;
    const AGGREGATE_ALL = 1;
    const AGGREGATE_ANY = 2;

    /**
     * @var int Aggregation method
     */
    private $agg_method;

    /**
     * @var int weight
     */
    private $weight;

    /**
     * @var float score
     */
    private $score;

    /**
     * Custom data
     * @var mixed customdata
     */
    private $customdata;

    /**
     * Completion criteria {@link progressinfo::get_criteria()}
     * $criteria can contain multiple progress nodes representing sets of criteria that
     *  must be aggregated in order to determine this node's weight and score.
     * @var array criteria
     */
    private $criteria;

    /**
     * Prepares an array for the cache that will be restored by wake_from_cache()
     *
     * @return array
     */
    public function prepare_to_cache() {
        $data = array(
            'agg_method' => $this->get_agg_method(),
            'weight' => $this->get_weight(),
            'score' => $this->get_score(),
            'customdata' => $this->get_customdata(),
            'criteria' => []
        );
        foreach ($this->criteria as $key => $criterion) {
            $data['criteria'][$key] = $criterion->prepare_to_cache();
        }
        return $data;
    }

    /**
     * Takes cached data and restores it as a progressinfo object.
     *
     * @param array $data
     * @return progressinfo
     */
    public static function wake_from_cache($data) {
        $progressinfo = new self(
            $data['agg_method'],
            $data['weight'],
            $data['score'],
            $data['customdata']
        );
        $progressinfo->criteria = [];
        foreach ($data['criteria'] as $key => $criterion) {
            $progressinfo->criteria[$key] = self::wake_from_cache($criterion);
        }
        return $progressinfo;
    }

    /**
     * Creates a new progressinfo object given some specific data.
     *
     * @param int $agg_method
     * @param int $weight
     * @param float $score
     * @param null $customdata
     * @return progressinfo
     */
    public static function from_data($agg_method = self::AGGREGATE_ALL, $weight = 0, $score = 0.0, $customdata = null) {

        // For now expect customdata to always be an array
        if (!is_null($customdata) && !is_array($customdata)) {
            $customdata = json_decode(json_encode($customdata), true);
        }

        return new self($agg_method, $weight, $score, $customdata);
    }

    /**
     * Constructs with specified detail.
     *
     * Only I can create me.
     *
     * @param int $agg_method Aggregation method, one of self::AGGREGATE_*
     * @param int $weight
     * @param float $score
     * @param array $customdata
     */
    private function __construct($agg_method = self::AGGREGATE_ALL, $weight = 0, $score = 0.0, $customdata = null) {
        $this->set_agg_method($agg_method);
        $this->set_weight($weight);
        $this->set_score($score);
        $this->set_customdata($customdata);
        $this->criteria = array();
    }

    /**
     * Get progress criteria
     *
     * @param mixed $key Criteria key to get
     * @return progressinfo|false if key exists, else false
     */
    public function get_criteria($key) {
        if ($this->criteria_exist($key)) {
            return $this->criteria[$key];
        }
        return false;
    }


    /**
     * Get all criteria
     *
     * @return progressinfo[] All criteria
     */
    public function get_all_criteria() {
        return $this->criteria;
    }


    /**
     * Check whether criteria with the specified key exists
     *
     * @param mixed $key Criteria key to use
     * @return boolean
     */
    public function criteria_exist($key) {
        return isset($this->criteria[$key]);
    }


    /**
     * Get criteria count
     *
     * @return int Criteria count
     */
    public function count_criteria() {
        return count($this->criteria);
    }


    /**
     * Find the specified criteria key on any level
     *
     * @param mixed $key Criteria key to find
     * @return array progressinfo All instances with the specified key
     */
    public function search_criteria($key) {
        $nodes = array();

        // Each key can only appear once on each level
        if ($this->criteria_exist($key)) {
            $nodes[] = $this->get_criteria($key);
        }

        if (!empty($this->criteria)) {
            foreach ($this->criteria as $criteria) {
                $nodes = array_merge($nodes, $criteria->search_criteria($key));
            }
        }

        return $nodes;
    }


    /**
     * Add progress criteria for this key if it doesn't yet exist
     *
     * @param mixed $key Criteria key - used as criteria array index
     * @param int $agg_method Criteria aggregation method
     * @param int $weight Criteria weight
     * @param float $score Criteria score
     * @param array $customdata Custom data
     * @return progressinfo Newly added progress criteria
     * @throws \coding_exception if the given key already exists.
     */
    public function add_criteria($key, $agg_method = self::AGGREGATE_ALL, $weight = 0, $score = 00.0, $customdata = null) {
        if ($this->criteria_exist($key)) {
            throw new \coding_exception('Progress info criteria already exists', $key);
        }
        $criteria = self::from_data($agg_method, $weight, $score, $customdata);
        return $this->attach_criteria($key, $criteria);
    }


    /**
     * Attach initialized progress criteria with this key if the key doesn't exist
     *
     * @param progressinfo $info Initialized info to add
     * @param mixed $key Criteria key - used as criteria array index
     * @return progressinfo|false indication whether criteria was added or not
     */
    public function attach_criteria($key, progressinfo $info) {
        if (isset($this->criteria[$key])) {
            return false;
        }

        $this->criteria[$key] = $info;
        return $info;
    }


    /**
     * Replace all the criteria with the specified key with the provided info
     *
     * @param mixed $key Criteria key to replace
     * @param progressinfo $info Progressinfo to replace with
     * @return bool indication whether criteria was replaced or not
     */
    public function replace_criteria($key, progressinfo $info) {
        $replaced = false;

        if (isset($this->criteria[$key])) {
            $this->criteria[$key] = $info;
            $replaced = true;
        }

        foreach ($this->criteria as $criteria) {
            $replaced = $replaced || $criteria->replace_criteria($key, $info);
        }

        return $replaced;
    }


    /**
     * Checks whether completion is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        return ($this->weight > 0 && !empty($this->criteria));
    }

    /**
     * Get the score
     *
     * @return float score
     */
    public function get_score() {
        return $this->score;
    }


    /**
     * Set the score
     *
     * @param float $score
     */
    public function set_score($score) {
        $this->score = (float)$score;
    }


    /**
     * Get the weight
     *
     * @return int weight
     */
    public function get_weight() {
        return $this->weight;
    }


    /**
     * Set the weight
     *
     * @param int $weight
     */
    public function set_weight($weight) {
        $this->weight = $weight;
    }


    /**
     * Get the aggregation method
     *
     * @return int Criteria aggregation method
     */
    public function get_agg_method() {
        return $this->agg_method;
    }


    /**
     * Set the aggregation method
     *
     * @throws \coding_exception If an invalid aggregation method is provided.
     * @param int $agg_method Criteria aggregation method
     */
    public function set_agg_method($agg_method) {
        switch ($agg_method) {
            case self::AGGREGATE_NONE:
            case self::AGGREGATE_ALL:
            case self::AGGREGATE_ANY:
                $this->agg_method = $agg_method;
                break;

            default:
                throw new \coding_exception('Invalid aggregation method provided', $agg_method);
        }
    }


    /**
     * Get the customdata value
     *
     * @return mixed customdata value
     */
    public function get_customdata() {
        return $this->customdata;
    }


    /**
     * Set the customdata value
     *
     * @param array $customdata
     */
    public function set_customdata($customdata) {
        if (empty($customdata)) {
            return;
        }

        // For now always expect and store array
        if (!is_array($customdata)) {
            $customdata = json_decode(json_encode($customdata), true);
        }
        $this->customdata = $customdata;
    }


    /**
     * Returns percentage complete for this level of aggregation
     * If percentage is near (but not at) 0%, return 1%
     * If percentage is near (but not quite) 100%, return 99%
     *
     * @return int|false Percentage complete if enabled, else false
     */
    public function get_percentagecomplete() {
        if ($this->weight > 0) {
            $val = 0;
            if ($this->score != 0) {
                $val = floor(max(1, ($this->score / $this->weight * 100)));
            }
            return (int)$val;
        }

        return false;
    }


    /**
     * Recursive depth first function to aggregate this node's score and weight
     *
     * Score and weight are aggregated up as follows:
     *     If ALL must be completed - aggregated weight = sum (individual weights)
     *                              - aggregated score = sum (individual scores)
     *     If ANY must be completed - aggregated weight = max (weight) of items with max(score / weight)
     *                              - aggregated score = score of item with max weight with max(score / weight)
     * (TODO - we may need to re-check these if actual weights are provided by the user)
     */
    public function aggregate_score_weight() {

        if (empty ($this->criteria)) {
            // Nothing to aggregate - are on lowest level
            return;
        }

        foreach ($this->criteria as $criteria) {
            $criteria->aggregate_score_weight();
        }

        $weight = 0;
        $score = 0.0;

        switch ($this->agg_method) {

            case self::AGGREGATE_ALL:
                // score = sum (individual scores)
                // weight = sum (individual weights)
                foreach ($this->criteria as $critall) {
                    // Simply add the weights
                    $weight += $critall->get_weight();
                    $score += $critall->get_score();
                }
                break;

            case self::AGGREGATE_ANY:
                // weight = max (weight) of items with max(score / weight)
                // score = score of item with max weight with max(score / weight)
                $weightedscore = 0;

                foreach ($this->criteria as $critany) {
                    if ($critany->get_weight() != 0) {
                        $critscore = ($critany->get_score() / $critany->get_weight());
                    } else {
                        $critscore = $critany->get_score();
                    }

                    if ($critscore > $weightedscore) {
                        $score = $critany->get_score();
                        $weight = $critany->get_weight();
                        $weightedscore = $critscore;
                    } else if ($critscore == $weightedscore &&
                               $critany->get_weight() > $weight) {
                        $score = $critany->get_score();
                        $weight = $critany->weight;
                    }
                }
                break;

            case self::AGGREGATE_NONE:
                $weight = 0;
                $score = 0.0;
                break;

            default:
                debugging('Unexpected aggregation method provided '.(string)$this->agg_method, DEBUG_DEVELOPER);
                break;
        }

        $this->weight = $weight;
        $this->score = $score;
    }

}
