<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage totara_sync
 */

require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/classes/hierarchy.element.class.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');

class totara_sync_element_comp extends totara_sync_hierarchy
{
    /**
     * Add Org fields.
     *
     * @param MoodleQuickForm $mform
     */
    public function config_form(&$mform)
    {
        parent::config_form($mform);
        // Disable the field when nothing is selected, and when database is selected.
        $mform->disabledIf('csvsaveemptyfields', 'source_comp', 'eq', '');
        $mform->disabledIf('csvsaveemptyfields', 'source_comp', 'eq', 'totara_sync_source_comp_database');
    }

    function get_hierarchy()
    {
        return new competency();
    }

    function get_source($sourceclass=null)
    {
    	global $CFG;

        $source = $sourceclass 
            ? $sourceclass 
            : get_config('totara_sync', 'source_'.$this->get_name());

    	if ($source === 'totara_sync_source_comp_csv') {
    		$sourcefilename = str_replace('totara_sync_' ,'', $source);
    		$sourcefile = $CFG->dirroot.'/local/compsync/lib/sources/'.$sourcefilename.'.php';
    		require_once($sourcefile);
       		return new $source;
    	}

        return parent::get_source($sourceclass);
    }

    function get_sources() {
    	$sources = parent::get_sources();
    	$compsync_sources = $this->get_compsync_sources();
    	return $sources + $compsync_sources; 
    }

    private function get_compsync_sources()
    {
    	global $CFG;

        $elname = $this->get_name();

        $sdir = $CFG->dirroot.'/local/compsync/lib/sources/';
        $pattern = '/^source_' . $elname . '_(.*?)\.php$/';
        $sfiles = preg_grep($pattern, scandir($sdir));
        $sources = [];
        foreach ($sfiles as $f) {
            require_once($sdir.$f);

            $basename = basename($f, '.php');
            $sname = str_replace("source_{$elname}_", '', $basename);

            $sclass = "totara_sync_{$basename}";
            if (!class_exists($sclass)) {
                continue;
            }

            $sources[$sname] = new $sclass;
        }

        return $sources;
    }

    
}
