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

namespace totara_core\progressinfo;

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
     * Name of the class that implements progressinfo_aggregation to aggregate the progress
     * @var string agg_class
     */
    private $agg_class = '';

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
            'agg_class' => $this->get_agg_class(),
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
            $data['customdata'],
            $data['agg_class']
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
     * @param string $agg_class
     * @return progressinfo
     */
    public static function from_data($agg_method = self::AGGREGATE_ALL, $weight = 0, $score = 0.0, $customdata = null, $agg_class = '') {

        // For now expect customdata to always be an array
        if (!is_null($customdata) && !is_array($customdata)) {
            $customdata = json_decode(json_encode($customdata), true);
        }

        return new self($agg_method, $weight, $score, $customdata, $agg_class);
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
     * @param string $agg_class
     */
    private function __construct($agg_method = self::AGGREGATE_ALL, $weight = 0, $score = 0.0, $customdata = null, $agg_class = '') {
        $this->set_agg_method($agg_method);
        $this->set_weight($weight);
        $this->set_score($score);
        $this->set_customdata($customdata);
        $this->set_agg_class($agg_class);
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
     * @param string $agg_class Name of the aggregation class to use
     * @return progressinfo Newly added progress criteria
     * @throws \coding_exception if the given key already exists.
     */
    public function add_criteria($key, $agg_method = self::AGGREGATE_ALL, $weight = 0, $score = 00.0, $customdata = null, $agg_class = '') {
        if ($this->criteria_exist($key)) {
            throw new \coding_exception('Progress info criteria already exists', $key);
        }
        $criteria = self::from_data($agg_method, $weight, $score, $customdata, $agg_class);
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
     * Get the aggregation class name
     *
     * @return string agg_class value
     */
    public function get_agg_class() {
        return $this->agg_class;
    }


    /**
     * Set the agg_class value
     *
     * @param string $agg_class The name of the aggregation class to use
     * @throws \coding_exception if the given function is not the name of a class that implements the progressinfo_aggregation interface
     */
    public function set_agg_class($agg_class) {
        if (empty($agg_class)) {
            $this->agg_class = '';
            return;
        }

        try {
            $cls = new \ReflectionClass($agg_class);
            if ($cls->implementsInterface('\totara_core\progressinfo\progressinfo_aggregation')) {
                $this->agg_class = $agg_class;
            } else {
                throw new \coding_exception('The agg_class is expected to be an implementation of the progressinfo_aggregation interface.', $agg_class);
            }
        } catch (\ReflectionException $e) {
            throw new \coding_exception('The given agg_class is not a valid class.', $agg_class);
        }
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
     * Aggregate this node's score and weight by calling the aggregate() method of the agg_class
     *
     * @throws \coding_exception if the given aggregation implementation doesn't return a weight and score
     */
    public function aggregate_score_weight() {

        if (empty ($this->criteria)) {
            // Nothing to aggregate - are on lowest level
            return;
        }

        foreach ($this->criteria as $criteria) {
            $criteria->aggregate_score_weight();
        }

        // Use the default aggregation classes if none were provided by the user
        $agg_class = !empty($this->agg_class) ? $this->agg_class : $this->get_default_agg_class($this->agg_method);

        // Aggregated results are expected to be an array or object providing the weight and score
        $agg_result = $agg_class::aggregate($this);
        if (is_object($agg_result)) {
            $agg_result = (array)$agg_result;
        }

        if (!is_array($agg_result)) {
            throw new \coding_exception('The provided aggregation implementation is expected to return and array containing the aggregated weight and score.', $this->agg_class);
        }

        if (!isset($agg_result['weight']) || !isset($agg_result['score'])) {
            throw new \coding_exception('The provided aggregation implementation is expected to return the aggregated weight and score.', $this->agg_class);
        }

        $this->weight = $agg_result['weight'];
        $this->score = $agg_result['score'];
    }


    /**
     * Return the name of the default progress_aggretion implementation to use with the specified aggregation method
     *
     * @param int @agg_method Aggregation method
     * @return string Name of the default progress_aggregation class to use for this aggregation method
     */
    private function get_default_agg_class($agg_method) {
        switch ($agg_method) {
            case self::AGGREGATE_NONE:
                return 'totara_core\progressinfo\progressinfo_aggregate_none';

            case self::AGGREGATE_ANY:
                return 'totara_core\progressinfo\progressinfo_aggregate_any';

            case self::AGGREGATE_ALL:
            default:
                return 'totara_core\progressinfo\progressinfo_aggregate_all';
        }
    }
}
