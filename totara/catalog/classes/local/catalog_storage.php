<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */
namespace totara_catalog\local;

use totara_catalog\dataformatter\formatter;
use totara_catalog\provider;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Create the catalog index data loader.
 */
class catalog_storage {

    /**
     * Gets all of the dataholders which are required to populate the catalog index.
     *
     * @param provider $provider
     * @return required_dataholder[]
     */
    public static function get_required_dataholders(provider $provider): array {
        $ftsdataholders = $provider->get_dataholders(formatter::TYPE_FTS);
        $sorttimedataholders = $provider->get_dataholders(formatter::TYPE_SORT_TIME);
        $sorttextdataholders = $provider->get_dataholders(formatter::TYPE_SORT_TEXT);

        $requireddataholders = [];

        $ftsconfig = $provider->get_data_holder_config('fts');

        $weights = ['high', 'medium', 'low'];
        foreach ($weights as $weight) {
            if (empty($ftsconfig[$weight])) {
                continue;
            }

            $dataholderkeys = $ftsconfig[$weight];
            foreach ($dataholderkeys as $dataholderkey) {
                if (empty($ftsdataholders[$dataholderkey])) {
                    continue;
                }

                $dataholder =  $ftsdataholders[$dataholderkey];
                $requireddataholders[] = new required_dataholder($dataholder, formatter::TYPE_FTS);
            }
        }

        $sortholderconfig = $provider->get_data_holder_config('sort');

        if (!empty($sortholderconfig['text']) && !empty($sorttextdataholders[$sortholderconfig['text']])) {
            $sorttextdataholder = $sorttextdataholders[$sortholderconfig['text']];
            $requireddataholders[] = new required_dataholder($sorttextdataholder, formatter::TYPE_SORT_TEXT);
        }

        if (!empty($sortholderconfig['time']) && !empty($sorttimedataholders[$sortholderconfig['time']])) {
            $sorttimedataholder = $sorttimedataholders[$sortholderconfig['time']];
            $requireddataholders[] = new required_dataholder($sorttimedataholder, formatter::TYPE_SORT_TIME);
        }

        return $requireddataholders;
    }

    /**
     * Get the data needed to populate the catalog index.
     *
     * @param \stdClass $object
     * @return \stdClass
     */
    public static function get_data(\stdClass $object): \stdClass {
        $provider = provider_handler::instance()->get_provider($object->objecttype);

        $ftsconfig = $provider->get_data_holder_config('fts');

        $data = new \stdClass();

        foreach (['high', 'medium', 'low'] as $weight) {
            $data->$weight = '';

            if (empty($ftsconfig[$weight])) {
                continue;
            }

            $searchdata = array();

            $dataholderkeys = $ftsconfig[$weight];
            foreach ($dataholderkeys as $dataholderkey) {
                $text = $object->data[formatter::TYPE_FTS][$dataholderkey] ?? '';
                if (!empty($text)) {
                    $searchdata[] = $text;
                }
            }

            $data->$weight = implode(" ", $searchdata);
        }

        $sortholderconfig = $provider->get_data_holder_config('sort');

        $data->sorttext = $object->data[formatter::TYPE_SORT_TEXT][$sortholderconfig['text']] ?? '';
        $data->sorttime = $object->data[formatter::TYPE_SORT_TIME][$sortholderconfig['time']] ?? '';

        return $data;
    }

    /**
     * @param array $objects
     */
    public static function update_records(array $objects) {
        $objectschunks = array_chunk($objects, BATCH_INSERT_MAX_ROW_COUNT);

        $requireddataholders = [];

        foreach ($objectschunks as $ungroupedobjects) {
            $groupedobjects = [];

            foreach ($ungroupedobjects as $object) {
                $objecttype = $object->objecttype;

                if (empty($groupedobjects[$objecttype])) {
                    $groupedobjects[$objecttype] = [];
                }

                // Only load dataholders for those that are needed, and only once (even across chunks).
                if (empty($requireddataholders[$objecttype])) {
                    $provider = provider_handler::instance()->get_provider($objecttype);
                    $requireddataholders[$objecttype] = static::get_required_dataholders($provider);
                }

                // By indexing by objectid, we prevent the same record being updated more than once (at least in the chunk).
                $groupedobjects[$objecttype][$object->objectid] = $object;
            }

            foreach ($groupedobjects as $objecttype => $objects) {
                // Note that we're passing in required data holders that aren't needed for the object type, but
                // will just be ignored.
                self::update_records_chunk($objects, $requireddataholders);
            }
        }
    }

    /**
     * Create or update some records in the catalog.
     *
     * This function works with the singleton provider handler, so it is fairly efficient to call this multiple
     * times. Can be called a few times from a live webpage, but should be called through a scheduled or adhoc
     * task when working with bulk data.
     *
     * @param \stdClass[] $objects containing 'objecttype', 'objectid' and 'contextid' all of one object
     *                             type, max count BATCH_INSERT_MAX_ROW_COUNT
     * @param required_dataholder[][] $requireddataholders indexed by objecttype
     */
    private static function update_records_chunk(array $objects, array $requireddataholders) {
        global $CFG, $DB;

        if (count($objects) > BATCH_INSERT_MAX_ROW_COUNT) {
            throw new \coding_exception("update_records_chunk called with too many records - max is BATCH_INSERT_MAX_ROW_COUNT");
        }

        $objecttype = null;

        $objectids = [];
        foreach ($objects as $key => $object) {
            if (is_null($objecttype)) {
                $objecttype = $object->objecttype;
            } else if ($objecttype != $object->objecttype) {
                throw new \coding_exception(
                    "update_records_chunk called with a set of objects which aren't all the same object type"
                );
            }

            $objectids[$object->objectid] = $object->objectid;
        }

        list($insql, $params) = $DB->get_in_or_equal($objectids, SQL_PARAMS_NAMED);

        $sql = "SELECT objectid, id, ftshigh, ftsmedium, ftslow, sorttext, sorttime
                  FROM {catalog}
                 WHERE objecttype = :objecttype
                   AND objectid {$insql}";
        $params['objecttype'] = $objecttype;

        $existingrecords = $DB->get_records_sql($sql, $params);

        $objects = provider_handler::instance()->get_data_for_objects($objects, $requireddataholders);

        $insertbatch = [];
        foreach ($objects as $object) {
            $indexdata = static::get_data($object);

            $isinsert = empty($existingrecords[$object->objectid]);
            $existingrecord = null;
            $data = new \stdClass();
            if ($isinsert) {
                $data->objecttype = $objecttype;
                $data->objectid = $object->objectid;
                $data->contextid = $object->contextid;
            } else {
                $existingrecord = $existingrecords[$object->objectid];
                $data->id = $existingrecord->id;
            }

            if (!empty($CFG->catalog_use_and_compatible_buckets)) {
                // If the FTS is using AND between search terms then we need to return results that match words that
                // might have different priorities. We do this by making sure that high and medium priority terms
                // are included in lower priority buckets.
                $data->ftshigh = $indexdata->high;
                $data->ftsmedium = $indexdata->high . ' ' . $indexdata->medium;
                $data->ftslow = $indexdata->high . ' ' . $indexdata->medium . ' ' . $indexdata->low;
            } else {
                // Only works correctly if using OR between search terms. If using AND then records with matching
                // words that are contained in more than one bucket won't be returned at all (score zero).
                $data->ftshigh = $indexdata->high;
                $data->ftsmedium = $indexdata->medium;
                $data->ftslow = $indexdata->low;
            }

            $data->sorttext = $indexdata->sorttext;
            $data->sorttime = $indexdata->sorttime;

            if ($isinsert) {
                $insertbatch[] = $data;
            } else {
                // check data is changed
                if ($existingrecord->sorttime |= $data->sorttime ||
                    $existingrecord->sorttext != $data->sorttext ||
                    $existingrecord->ftshigh != $data->ftshigh ||
                    $existingrecord->ftsmedium != $data->ftsmedium ||
                    $existingrecord->ftslow != $data->ftslow

                ) {
                    $DB->update_record('catalog', $data);
                }
            }
        }

        if (!empty($insertbatch)) {
            $DB->insert_records_via_batch('catalog', $insertbatch);
        }
    }

    /**
     * Delete catalog records. Can be called a few times from a live webpage, but should be called through a
     * scheduled or adhoc task when working with bulk data.
     *
     * @param string $objecttype
     * @param int[] $objectids
     */
    public static function delete_records(string $objecttype, array $objectids) {
        global $DB;

        if (empty($objectids)) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($objectids, SQL_PARAMS_NAMED);

        $where = "objecttype = :objecttype AND objectid {$insql}";
        $params['objecttype'] = $objecttype;

        $DB->delete_records_select('catalog', $where, $params);
    }

    /**
     * Populate the catalog with all data relating to the given provider.
     *
     * This might take a while, so only call this through a scheduled or adhoc task.
     *
     * @param provider $provider
     */
    public static function populate_provider_data(provider $provider) {
        global $DB;

        list($sql, $params) = $provider->get_all_objects_sql();

        // Delete all unwanted objects
        $deletesql = "SELECT catalog.id
                        FROM {catalog} catalog
                        LEFT JOIN ({$sql}) wanted
                          ON catalog.objectid = wanted.objectid
                        WHERE wanted.objectid IS NULL
                          AND catalog.objecttype = :objecttype";
        $deleteparams = array_merge($params, ['objecttype' => $provider->get_object_type()]);

        $deleterecordset = $DB->get_recordset_sql($deletesql, $deleteparams);
        foreach ($deleterecordset as $record) {
            $DB->delete_records('catalog', ['id' => $record->id]);
        }
        $deleterecordset->close();

        // Insert or update records
        $objects = $DB->get_records_sql($sql, $params);

        self::update_records($objects);
    }

    /**
     * Delete all data from the catalog relating to the given provider.
     *
     * This function should be efficient enough to run through the browser, which is a very good idea since
     * we haven't implemented any error checking to deal with catalog items that exist relating to providers
     * that are not enabled, and the only way to deal with this is to immediately delete the data when the
     * provider is disabled.
     *
     * @param string $objecttype
     */
    public static function delete_provider_data(string $objecttype) {
        global $DB;
        $DB->delete_records('catalog', ['objecttype' => $objecttype]);
    }

    /**
     * Has provider data
     *
     * @param string $objecttype
     * @return bool
     */
    public static function has_provider_data(string $objecttype) {
        global $DB;

        return $DB->record_exists('catalog', ['objecttype' => $objecttype]);
    }
}
