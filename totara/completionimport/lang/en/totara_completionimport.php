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
 * @package    totara
 * @subpackage completionimport
 * @author     Russell England <russell.england@catalyst-eu.net>
 */

$string['blankcompletiondate'] = 'Blank completion date';
$string['blankgrade'] = 'Blank grade';
$string['blankusername'] = 'Blank user name';
$string['cannotcopyfiles'] = 'Cannot copy file from {$a->fromfile} to {$a->tofile}';
$string['cannotcreatetempname'] = 'Cannot create a temporary file name';
$string['cannotcreatetemppath'] = 'Cannot create temporary directory : {$a}';
$string['cannotdeletefile'] = 'Cannot delete file {$a}';
$string['cannotmovefiles'] = 'Cannot move file from {$a->fromfile} to {$a->tofile}';
$string['cannotsaveupload'] = 'Cannot save file to {$a}';
$string['caseinsensitivecourse'] = 'Case insensitive shortnames';
$string['caseinsensitivecourse_help'] = 'When enabled, course short names will be matched case insensitively.

* If there are two or more courses with shortnames that use different case but have matching idnumbers then the name of the existing course will be matched.
* If the inital match fails, the shortname for the duplicate records with matching idnumbers will be used.

This is an advanced setting and will cause performance issues during uploads. We strongly advise any case issues be corrected in the uploaded file.';
$string['caseinsensitivecertification'] = 'Case insensitive shortnames';
$string['caseinsensitivecertification_help'] = 'When enabled, certification short names will be matched case insensitively.

* If there are two or more certifications with shortnames that use different case but have matching idnumbers then the name of the existing certification will be matched.
* If the inital match fails, the shortname for the duplicate records with matching idnumbers will be used.

This is an advanced setting and will cause performance issues during uploads. We strongly advise any case issues be corrected in the uploaded file.';
$string['certification_results'] = 'Certification results';
$string['certificationblankrefs'] = 'Blank certification shortname and certification ID number';
$string['certificationexpired'] = 'Import certification expired, skipping importing';
$string['certificationdueforrecert'] = 'Import certification due for renewal, skipping import';
$string['certificationloglifetime'] = 'Keep certification completion upload logs for';
$string['certificationloglifetime_desc'] = 'This specifies the length of time to keep certification completion upload logs. Logs that are older than this will be automatically deleted.';
$string['choosefile'] = 'Choose file to upload';
$string['choosecoursefile'] = 'Choose course file to upload';
$string['choosecertificationfile'] = 'Choose certification file to upload';
$string['clearembeddedfilters'] = 'Click here to remove embedded filters';
$string['cleancertificationcompletionuploadlogstask'] = 'Cleanup certification completion upload logs';
$string['cleancoursecompletionuploadlogstask'] = 'Cleanup course completion upload logs';
$string['cleancomplete'] = 'Clean completion upload logs for {$a} has completed';
$string['cleanfailed'] = 'Clean completion upload logs for {$a} has failed';
$string['completiondatesame'] = 'Record completion date exists';
$string['completionimport'] = 'Upload Completion Records';
$string['completionimport_certification'] = 'Completion import: Certification status';
$string['completionimport_course'] = 'Completion import: Course status';
$string['completionimport:import'] = 'Completion import';
$string['course_results'] = 'Course results';
$string['courseblankrefs'] = 'Blank course shortname and course ID number';
$string['courseloglifetime'] = 'Keep course completion upload logs for';
$string['courseloglifetime_desc'] = 'This specifies the length of time to keep course completion upload logs. Logs that are older than this will be automatically deleted.';
$string['csvdateformat'] = 'CSV Date format';
$string['csvdelimiter'] = 'CSV Text Delimited with';
$string['csvencoding'] = 'CSV File encoding';
$string['csvimportdone'] = 'CSV import completed';
$string['csvimportfailed'] = 'Failed to import the CSV file';
$string['csvseparator'] = 'CSV Values separated by';
$string['duplicate'] = 'Duplicate';
$string['duplicateidnumber'] = 'Duplicate ID Number';
$string['emptyfile'] = 'File is empty : {$a}';
$string['emptyrow'] = 'Empty row';
$string['error:actionnotdefined'] = 'Import_certification - code error - the selected action hasn\'t been defined: {$a}';
$string['error:invalidfilesource'] = 'Invalid file source code passed as a parameter';
$string['error:wrongimportname'] = 'Import_certification - code error - doing something that isn\'t certifications: {$a}';
$string['erroropeningfile'] = 'Error opening file : {$a}';
$string['errorskippedduplicate'] = 'Import skipped because it is a duplicate';
$string['evidence_certificationidnumber'] = 'Certification ID number : {$a}';
$string['evidence_certificationshortname'] = 'Certification Short name : {$a}';
$string['evidence_completiondate'] = 'Completion date : {$a}';
$string['evidence_completiondateparsed'] = 'Completion date (timestamp) : {$a}';
$string['evidence_courseidnumber'] = 'Course ID number : {$a}';
$string['evidence_courseshortname'] = 'Course Short name : {$a}';
$string['evidence_grade'] = 'Grade : {$a}';
$string['evidence_importid'] = 'Import ID : {$a}';
$string['evidence_shortname_certification'] = 'Completed certification : {$a}';
$string['evidence_shortname_course'] = 'Completed course : {$a}';
$string['evidence_idnumber_certification'] = 'Completed certification ID : {$a}';
$string['evidence_idnumber_course'] = 'Completed course ID : {$a}';
$string['evidencedescriptionfield'] = 'Evidence field for the description';
$string['evidencedescriptionfield_help'] = 'Any courses or certificates that can\'t be found will be added as evidence in the record of learning.

Please choose a text evidence custom field to store a description of the created evidence.

If the CVS file has a column specifying the custom field directly, this value will be used instead.';
$string['evidencedatefield'] = 'Evidence field for completion date';
$string['evidencedatefield_help'] = 'Any courses or certificates that can\'t be found will be added as evidence in the record of learning.

Please choose a date/time evidence custom field to store the completiondate value.

If the CVS file has a column specifying the custom field directly, this value will be used instead.';
$string['evidencetype'] = 'Default evidence type';
$string['evidencetype_help'] = 'Any courses or certificates that can\'t be found will be added as evidence in the record of learning.

Please choose the default evidence type you wish to use.';
$string['fieldcountmismatch'] = 'Field count mismatch';
$string['fileisinuse'] = 'File is currently being used elsewhere : {$a}';
$string['sourcefile'] = 'Source file name';
$string['sourcefile_help'] = 'Please enter the file name and full path name to a file on the server.

eg: /var/sitedata/csvimport/course.csv

This option allows you to upload a file externally via FTP rather than using a form via HTTP.

Please note the original file will be moved and deleted during the import process.';
$string['sourcefilerequired'] = 'Source file name is required';
$string['importactioncertification'] = 'Import action';
$string['importactioncertification_help'] = 'Choose which action should occur with the imported records.

**Save to history**:

* The imported records will be added to history.
* The certification status of users will remain unchanged.

**Certify uncertified users**:

* If a user is already certified, the imported record is added to history.
* If a user is not currently certified, the imported record will be used to mark them certified.
* If appropriate, the certification window may open and/or expire when cron next runs, causing the completion to be moved to history.

**Certify if more recent**:

* If a user is already certified and the import completion date is more recent than the current completion date, then the current completion will be moved to history and the user will be marked certified on the imported completion date.
* If a user is already certified and the import completion date is further in the past than the current completion date, then the imported record will be added to history.
* If a user is not currently certified, the imported record will be used to mark them certified.
* If appropriate, the certification window may open and/or expire when cron next runs, causing the completion to be moved to history.

**Notes**:

* If a record is imported for a user who is not assigned to the certification, an individual user assignment will be created for them, causing them to be assigned. Assignment (or reassignment as the case may be) occurs first, then the imported record is processed, regardless of the chosen action or outcome.
* If a user is marked certified during import and the recertification window opening date is in the past, when cron runs it will open the recertification window and reset current course progress. If this is not the desired outcome then **Save to history** should probably be selected.';
$string['importactioncertificationcertify'] = 'Certify uncertified users';
$string['importactioncertificationhistory'] = 'Save to history';
$string['importactioncertificationnewer'] = 'Certify if more recent';
$string['importcertification'] = '{$a} Records successfully imported as certifications';
$string['importcourse'] = '{$a} Records successfully imported as courses';
$string['importedby'] = 'Imported by';
$string['importerror_certification'] = 'There were errors while importing the certifications';
$string['importerror_course'] = 'There were errors while importing the courses';
$string['importerrors'] = '{$a} Records with data errors - these were ignored';
$string['importevidence'] = '{$a} Records created as evidence';
$string['importing'] = 'Completion history upload - importing {$a}';
$string['importnone'] = 'No records were imported';
$string['importnotready'] = 'Import not ready, please check the errors above';
$string['importresults'] = 'Import results';
$string['importsource'] = 'Import source';
$string['importtotal'] = '{$a} Records in total';
$string['invalidcompletiondate'] = 'Invalid completion date';
$string['invalidfilenames'] = 'These are invalid filenames and will be ignored : {$a}';
$string['invalidfilesource'] = 'Invalid file source setting {$a}';
$string['missingfields'] = 'These fields are missing, please check the source csv files :';
$string['missingrequiredcolumn'] = 'Missing required column \'{$a->columnname}\'';
$string['nomanualenrol'] = 'Course needs to have manual enrol';
$string['nousername'] = 'No user name';
$string['nocourse'] = 'No course';
$string['nothingtoimport'] = 'No pending files to import';
$string['overrideactivecertification'] = 'Override active certifications';
$string['overrideactivecourse'] = 'Override current course completions';
$string['pluginname'] = 'Completion History Import';
$string['report_certification'] = 'Certification import report';
$string['report_course'] = 'Course import report';
$string['resetimport'] = 'Reset report data';
$string['resetcomplete'] = 'Reset report data for {$a} has completed';
$string['resetfailed'] = 'Reset report data for {$a} has failed';
$string['resetconfirm'] = 'Are you sure you want to reset the report data for {$a}?';
$string['resetcourse'] = 'Reset course report data?';
$string['resetcertification'] = 'Reset certification report data?';
$string['resetabove'] = 'Reset selected';
$string['rpl'] = 'Completion history import - imported grade = {$a}';
$string['runimport'] = 'Run the import';
$string['selectanevidencedatefield'] = 'Select an evidence completion date field';
$string['selectanevidencedescriptionfield'] = 'Select an evidence description field';
$string['settings'] = 'Settings';
$string['sourcefile_beginwith'] = 'The source file name must include the full path to the file and begin with {$a}';
$string['sourcefile_noconfig'] = 'Additional configuration settings are required to specify a file location on the server. Please contact your system administrator.';
$string['sourcefile_validation'] = 'Source file name does not begin with the required path';
$string['timeuploaded'] = 'Time uploaded';
$string['unknowncolumn'] = 'Unknown column \'{$a->columnname}\'';
$string['unreadablefile'] = 'File is unreadable : {$a}';
$string['uploadcertification'] = 'Upload certification csv';
$string['uploadcertificationintro'] = 'This will import historical records from a csv file as certifications.
Any certifications that do not exist in the current system will be created as evidence in the record of learning.

The csv file should contain the following columns in the first line of the file

{$a}

Note: The duedate field should indicate what the due date was at the time of completion, not when the completion is due to expire. The column must be provided in the csv file, but can be left empty. When recertification is set to "Use certification expiry date" or "Use fixed expiry date", then the expiry date will be calculated using the uploaded duedate field. If it is empty or if the recertification is set to "Use certification completion date", then only the completion date will be used to calculate the expiry date
';
$string['uploadcourse'] = 'Upload course csv';
$string['uploadcourseintro'] = 'This will import historical completion records from a csv file and enrol users onto the specified courses.
Any courses that do not exist in the current system will be created as evidence in the record of learning.

The csv file should contain the following columns in the first line of the file

{$a}
';
$string['uploadcoursecustomfieldsintro'] = '
Additional columns below can also be included in the CSV to allow custom field data to be uploaded for evidence

{$a}
';
$string['uploadfilerequired'] = 'Please select a file to upload';
$string['uploadsuccess'] = 'Uploaded files successfully';
$string['uploadvia_directory'] = 'Alternatively upload csv files via a directory on the server';
$string['uploadvia_form'] = 'Alternatively upload csv files via a form';
$string['usernamenotfound'] = 'User name not found';
$string['validfilenames'] = 'Please note, these are the only valid file names, anything else will be ignored :';
$string['viewingwithembeddedfilters'] = 'Currently viewing records with embedded filters';
$string['viewreports'] = 'View import errors';
