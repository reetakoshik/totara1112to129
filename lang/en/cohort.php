<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'cohort', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    core_cohort
 * @subpackage cohort
 * @copyright  2010 Petr Skoda (info@skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addcohort'] = 'Add new audience';
$string['allcohorts'] = 'All audiences';
$string['anycohort'] = 'Any';
$string['assign'] = 'Assign';
$string['assigncohorts'] = 'Assign audiences to members';
$string['assignto'] = 'Audience \'{$a}\' members';
$string['backtocohorts'] = 'Back to audiences';
$string['bulkadd'] = 'Add to audience';
$string['bulknocohort'] = 'No available audiences found';
$string['categorynotfound'] = 'Category <b>{$a}</b> not found or you don\'t have permission to create an audience there. The default context will be used.';
$string['cohort'] = 'Audience';
$string['cohorts'] = 'Audiences';
$string['cohortsin'] = '{$a}: available audiences';
$string['cohort:assign'] = 'Assign audience members';
$string['cohort:manage'] = 'Manage audiences';
$string['cohort:view'] = 'Use audiences and view members';
$string['component'] = 'Source';
$string['contextnotfound'] = 'Context <b>{$a}</b> not found or you don\'t have permission to create an audience there. The default context will be used.';
$string['csvcontainserrors'] = 'Errors were found in CSV data. See details below.';
$string['csvcontainswarnings'] = 'Warnings were found in CSV data. See details below.';
$string['csvextracolumns'] = 'Column(s) <b>{$a}</b> will be ignored.';
$string['currentusers'] = 'Current users';
$string['currentusersmatching'] = 'Current users matching';
$string['defaultcontext'] = 'Default context';
$string['delcohort'] = 'Delete audience';
$string['delconfirm'] = 'Do you really want to delete audience \'{$a}\'?';
$string['description'] = 'Description';
$string['displayedrows'] = '{$a->displayed} rows displayed out of {$a->total}.';
$string['duplicateidnumber'] = 'Audience with the same ID number already exists';
$string['editcohort'] = 'Edit audience';
$string['eventcohortcreated'] = 'Audience created';
$string['eventcohortdeleted'] = 'Audience deleted';
$string['eventcohortmemberadded'] = 'User added to a audience';
$string['eventcohortmemberremoved'] = 'User removed from a audience';
$string['eventcohortupdated'] = 'Audience updated';
$string['external'] = 'External audience';
$string['idnumber'] = 'Audience ID';
$string['memberscount'] = 'Audience size';
$string['name'] = 'Name';
$string['namecolumnmissing'] = 'There is something wrong with the format of the CSV file. Please check that it includes column names.';
$string['namefieldempty'] = 'Field name can not be empty';
$string['nocomponent'] = 'Created manually';
$string['potusers'] = 'Potential users';
$string['potusersmatching'] = 'Potential matching users';
$string['preview'] = 'Preview';
$string['removeuserwarning'] = 'Removing users from a audience may result in unenrolling of users from multiple courses which includes deleting of user settings, grades, group membership and other user information from affected courses.';
$string['selectfromcohort'] = 'Select members from audience';
$string['systemcohorts'] = 'System audiences';
$string['unknowncohort'] = 'Unknown audience ({$a})!';
$string['uploadcohorts'] = 'Upload audiences';
$string['uploadedcohorts'] = 'Uploaded {$a} audiences';
$string['useradded'] = 'User added to audience "{$a}"';
$string['search'] = 'Search';
$string['searchcohort'] = 'Search audience';

$string['error:cohortdoesnotexist'] = 'Audience with id {$a} does not exist';
$string['error:staticcannotsetcriteria'] = 'Cannot set criteria for static audiences';
$string['error:dynamiccritalreadyapplied'] = 'Dynamic audience \'{$a}\' already has criteria applied';
$string['error:doesnotexist'] = 'Audiences with this id does not exist';
$string['searchcohort'] = 'Search audience';
$string['uploadcohorts_help'] = 'Audiences may be uploaded via text file. The format of the file should be as follows:

* Each line of the file contains one record.
* Each record is a series of data separated by commas (or other delimiters).
* The first record contains a list of field names defining the format of the rest of the file.
* Required field name is name.
* Optional field names are idnumber, description, descriptionformat, context, category, category_id, category_idnumber, category_path.';
$string['visible'] = 'Visible';
$string['visible_help'] = 'Any audience can be viewed by users who have the **moodle/cohort:view** capability in the audience context.Visible audiences can also be viewed by users in the underlying courses.';