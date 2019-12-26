<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

namespace tool_totara_sync\internal\hierarchy;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait customfield_processor_trait
 *
 * To be used by source classes that process custom fields
 * (using instances of \tool_totara_sync\internal\hierarchy\customfield).
 */
trait customfield_processor_trait {

    /**
     * The full array of customfield instances that will refer to all possible custom fields for this hierarchy.
     *
     * This will include those that are being imported and those that are not.
     *
     * @var customfield[]
     */
    protected $hierarchy_customfields;

    /**
     * Returns an array containing field names of all custom fields (after mapping, so the field mappings will have
     *  been applied, otherwise the default field name will be there).
     *
     * This will only include custom fields that have been set to be imported.
     *
     * @return string[] of form ['key' => 'mapped_field_name']
     */
    protected function get_mapped_customfields() {
        $mappedfields = [];

        foreach ($this->hierarchy_customfields as $customfield) {
            if (empty($this->config->{$customfield->get_import_setting_name()})) {
                continue;
            }
            $mappedfields[$customfield->get_key()] = $this->get_mapped_field($customfield);
        }

        return $mappedfields;
    }

    /**
     * Returns the array of mapped field names as given by get_mapped_customfields, but made unique.
     *
     * Keys are stripped and replaced with integers because if there were duplicate field name values,
     * the keys are not meaningful.
     *
     * @return string[] of form ['mapped_field_name'] - keys are arbitrary integers.
     */
    protected function get_unique_mapped_customfields() {
        return array_values(array_unique($this->get_mapped_customfields()));
    }

    /**
     * Gets the fieldname that applies for a particular custom field, taking into account custom field mappings.
     *
     * @param customfield $customfield
     * @return string with name of mapped field
     */
    private function get_mapped_field($customfield) {
        if (!empty($this->config->{$customfield->get_fieldmapping_setting_name()})) {
            return $this->config->{$customfield->get_fieldmapping_setting_name()};
        } else {
            return $customfield->get_default_fieldname();
        }
    }

    /**
     * Takes a row that has come from the source and formats custom field data if it is being imported.
     *
     * Returns the json encoding of this data in a single string.
     *
     * @param string[] $sourcerow Data from the source with the field name for the key and data in the value.
     * @return string containing json encoded custom field data.
     */
    protected function get_customfield_json($sourcerow, $saveemptyfields = true) {
        $cfield_data = [];

        $invalidtypes = [];

        foreach ($this->hierarchy_customfields as $customfield) {
            $columnname = $this->get_mapped_field($customfield);

            if (isset($sourcerow[$columnname])) {
                $value = trim($sourcerow[$columnname]);
                if ($value === '' && !$saveemptyfields) {
                    $value = null;
                }
            } else {
                $value = null;
            }

            if (!isset($sourcerow['typeidnumber']) || $sourcerow['typeidnumber'] === '') {
                // We need to check empty and not isset here. Otherwise if we're saving empty fields on csv, any custom field
                // that is not valid for a given type would throw an error.
                if (!empty($value)) {
                    $this->addlog(get_string('customfieldsnotype', 'tool_totara_sync', $sourcerow['idnumber']), 'error', 'customfieldprocessing');
                }
                continue;
            }

            if ($customfield->get_typeidnumber() !== $sourcerow['typeidnumber']) {
                if (!empty($value)) {
                    // We'll need to check later whether there was a valid type and column name. If there was only this
                    // invalid one, then we should log this.
                    $a = new \stdClass();
                    $a->columnname = $columnname;
                    $a->typeidnumber = $sourcerow['typeidnumber'];
                    $invalidtypes[] = $a;
                }
                continue;
            }
            if ($this->is_importing_customfield($customfield)) {
                if (isset($value)) {
                    switch ($customfield->get_datatype()) {
                        case 'datetime':
                            // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                            $parsed_date = totara_date_parse_from_format(
                                $this->get_csv_date_format(),
                                $value,
                                true
                            );
                            if ($parsed_date) {
                                $value = $parsed_date;
                            }
                            break;
                        case 'date':
                            // Try to parse the contents - if parse fails assume a unix timestamp and leave unchanged.
                            $parsed_date = totara_date_parse_from_format(
                                $this->get_csv_date_format(),
                                $value,
                                true,
                                'UTC'
                            );
                            if ($parsed_date) {
                                $value = $parsed_date;
                            }
                            break;
                        default:
                            break;
                    }
                }
                $cfield_data[$customfield->get_default_fieldname()] = $value;
            }
        }

        // If there were any invalid type to custom field column combinations found, we need to check
        // if it was *only* invalid combinations present.
        // It could be that there was both a valid and invalid combination because two custom fields used the
        // same column name, which is fine.
        foreach($invalidtypes as $invalidtype) {
            foreach($this->hierarchy_customfields as $customfield) {
                $columnname = $this->get_mapped_field($customfield);

                if (($customfield->get_typeidnumber() === $invalidtype->typeidnumber) && ($columnname === $invalidtype->columnname)) {
                    // There was a valid type idnumber/column name combination. No need to create a warning for this one.
                    continue 2;
                }
            }
            $invalidtype->idnumber = $sourcerow['idnumber'];
            // By this point, we found no valid combination of type id number and column name.
            $this->addlog(get_string('customfieldinvalidmaptype', 'tool_totara_sync', $invalidtype), 'error', 'customfieldprocessing');
        }

        return json_encode($cfield_data);
    }

    /**
     * Checks for whether we are importing a custom field.
     *
     * A more object-oriented way of making this check vs using the key directly.
     *
     * @param customfield $customfield
     * @return bool True if custom field is to be imported.
     */
    protected function is_importing_customfield($customfield) {
        return $this->is_importing_field($customfield->get_key());
    }
}
