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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

$string['abstractmethodcalled'] = 'Abstract method {$a} called - must be implemented';
$string['access'] = 'Access';
$string['accessbyrole'] = 'Restrict access by role';
$string['accesscontrols'] = 'Access Controls';
$string['accessiblereportsonly'] = 'Only reports accessible to the report viewer';
$string['activateglobalrestriction'] = 'Activate';
$string['activeglobalrestriction'] = 'Active';
$string['activeglobalrestriction_help'] = 'This option determines if this restriction is active or not. Inactive restrictions are ignored.';
$string['activeuser'] = 'Active';
$string['activities'] = 'Activities';
$string['actions'] = 'Actions';
$string['activitygroupdesc'] = 'Activity groups let you define sets of activites for the purpose of site-wide reporting.';
$string['activitygroupingx'] = 'Activity grouping \'{$a}\'';
$string['activitygroupnotfound'] = 'The activity group could not be found';
$string['activitygroups'] = 'Activity groups';
$string['add'] = 'Add';
$string['addanothercolumn'] = 'Add another column...';
$string['addanotherfilter'] = 'Add another filter...';
$string['addanothersearchcolumn'] = 'Add another search column...';
$string['addbadges'] = 'Add badges';
$string['addcohorts'] = 'Add audiences';
$string['addedscheduledreport'] = 'Added new scheduled report';
$string['addexternalemail'] = 'Add email';
$string['addanewscheduledreport'] = 'Add a new scheduled report to the list: ';
$string['addscheduledreport'] = 'Add scheduled report';
$string['addsystemusers'] = 'Add system user(s)';
$string['addnewscheduled'] = 'Add scheduled';
$string['advanced'] = 'Advanced?';
$string['advancedcolumnheading'] = 'Aggregation or grouping';
$string['advancedgroupaggregate'] = "Aggregations";
$string['advancedgrouptimedate'] = "Time and date (DB server time zone)";
$string['aggregatetypeavg_heading'] = 'Average of {$a}';
$string['aggregatetypeavg_name'] = 'Average';
$string['aggregatetypecountany_heading'] = 'Count of {$a}';
$string['aggregatetypecountany_name'] = 'Count';
$string['aggregatetypecountdistinct_heading'] = 'Count unique values of {$a}';
$string['aggregatetypecountdistinct_name'] = 'Count unique';
$string['aggregatetypegroupconcat_heading'] = '{$a}';
$string['aggregatetypegroupconcat_name'] = 'Comma separated values';
$string['aggregatetypegroupconcatdistinct_heading'] = '{$a}';
$string['aggregatetypegroupconcatdistinct_name'] = 'Comma separated values without duplicates';
$string['aggregatetypemaximum_heading'] = 'Maximum value from {$a}';
$string['aggregatetypemaximum_name'] = 'Maximum';
$string['aggregatetypeminimum_heading'] = 'Minimum value from {$a}';
$string['aggregatetypeminimum_name'] = 'Minimum';
$string['aggregatetypepercent_heading'] = 'Percentage of {$a}';
$string['aggregatetypepercent_name'] = 'Percentage';
$string['aggregatetypestddev_heading'] = 'Standard deviation of {$a}';
$string['aggregatetypestddev_name'] = 'Standard deviation';
$string['aggregatetypesum_name'] = 'Sum';
$string['aggregatetypesum_heading'] = 'Sum of {$a}';
$string['alldata'] = 'All data';
$string['allofthefollowing'] = 'All of the following';
$string['allowtotalcount'] = 'Allow reports to show total count';
$string['allowtotalcount_desc'] = 'When enabled Report Builder reports can be configured to show a total count of records, before filters have been applied. Please be aware that getting this count can be an expensive operation, and for performance reasons we recommend you leave this setting off.';
$string['allembeddedreports'] = 'All embedded reports';
$string['alluserreports'] = 'All user reports';
$string['allrestrictions'] = '&laquo; All Restrictions';
$string['allscheduledreports'] = 'All scheduled reports';
$string['and'] = ' and ';
$string['anycontext'] = 'Users may have role in any context';
$string['anyofthefollowing'] = 'Any of the following';
$string['anyrole'] = 'Any role';
$string['ascending'] = 'Ascending (A to Z, 1 to 9)';
$string['assigned'] = 'Assigned';
$string['assignedactivities'] = 'Assigned activities';
$string['assignedanyrole'] = 'Assigned any role';
$string['assignedgroups'] = 'Assigned groups';
$string['assignedusers'] = 'Assigned users';
$string['assignedrole'] = 'Assigned role \'{$a->role}\'';
$string['assigngroup'] = 'Assign a group to restriction';
$string['assigngrouprecord'] = 'Assign restriction records';
$string['assigngroupuser'] = 'Assign restricted users';
$string['at'] = 'at';
$string['audiences'] = 'Audiences';
$string['audiencevisibility'] = 'Audience Visibility';
$string['audiencevisibilitydisabled'] = 'Audience Visibility (not applicable)';
$string['backtoallgroups'] = 'Back to all groups';
$string['badcolumns'] = 'Invalid columns';
$string['badcolumnsdesc'] = 'The following columns have been included in this report, but do not exist in the report\'s source. This can occur if the source changes on disk after reports have been generated. To fix, either restore the previous source file, or delete the columns from this report.';
$string['baseactivity'] = 'Base activity';
$string['basedon'] = 'Group based on';
$string['baseitem'] = 'Base item';
$string['baseitemdesc'] = 'The aggregated data available to this group is based on the questions in the activity \'<a href="{$a->url}">{$a->activity}</a>\'.';
$string['both'] = 'Both';
$string['bydateenable'] = 'Show records based on the record date';
$string['bytrainerenable'] = 'Show records by trainer';
$string['byuserenable'] = 'Show records by user';
$string['cache'] = 'Enable Report Caching';
$string['cachedef_rb_ignored_embedded'] = 'Report builder ignored embedded reports cache';
$string['cachedef_rb_ignored_sources'] = 'Report builder ignored report sources cache';
$string['cachedef_rb_source_directories'] = 'Report builder source directory path cache';
$string['cachegenfail'] = 'The last attempt to generate cache failed. Please try again later.';
$string['cachegenstarted'] = 'Cache generation started at {$a}. This process can take several minutes.';
$string['cachenow'] = 'Generate Now';
$string['cachenow_help'] = 'If **Generate now** is checked, then report cache will be generated immediately after form submit.';
$string['cachenow_title'] = 'Report cache';
$string['cachepending'] = '{$a} There are changes to this report\'s configuration that have not yet been applied. The report will be updated next time the report is generated.';
$string['cachereport'] = 'Generate report cache';
$string['cannotviewembedded'] = 'Embedded reports can only be accessed through their embedded url';
$string['category'] = 'Category';
$string['changeglobalrestriction'] = 'change';
$string['chooseapp'] = 'Choose Appraiser...';
$string['chooseappplural'] = 'Choose Appraisers';
$string['choosecatplural'] = 'Choose Categories';
$string['choosecomp'] = 'Choose Competency...';
$string['choosecompplural'] = 'Choose Competencies';
$string['chooseman'] = 'Choose Manager...';
$string['choosemanplural'] = 'Choose Managers';
$string['chooseorg'] = 'Choose Organisation...';
$string['chooseorgplural'] = 'Choose Organisations';
$string['choosepos'] = 'Choose Position...';
$string['chooseposplural'] = 'Choose Positions';
$string['chooserestrictiondesc'] = 'You have access to records belonging to multiple groups of users. Select which groups of records you want to show when viewing the report:';
$string['chooserestrictiontitle'] = 'Viewing records for:';
$string['chooserole'] = 'Choose role...';
$string['clearform'] = 'Clear';
$string['clone'] = 'Clone';
$string['clonecompleted'] = 'Report cloned successfully';
$string['clonedescrhtml'] = 'Report "{$a->origname}" will be cloned as "{$a->clonename}" including the following properties: {$a->properties}';
$string['clonereportaccesswarning'] = 'Warning: Report content and access controls may change when copying an embedded report as content or access controls that are applied by the embedded page will be lost.';
$string['clonereportaccessreset'] = 'Access properties will be reset to system default for clone of embedded report';
$string['clonereportfilters'] = 'Report filters';
$string['clonereportcolumns'] = 'Report columns';
$string['clonereportsearchcolumns'] = 'Report text search columns';
$string['clonereportsettings'] = 'Report settings';
$string['clonereportgraph'] = 'Report graph and aggregation settings';
$string['clonenamepattern'] = 'Clone of {$a}';
$string['clonefailed'] = 'Could not make copy of report';
$string['clonereport'] = 'Clone report';
$string['column'] = 'Column';
$string['column_deleted'] = 'Column deleted';
$string['column_moved'] = 'Column moved';
$string['column_vis_updated'] = 'Column visibility updated';
$string['columns'] = 'Columns';
$string['columns_updated'] = 'Columns updated';
$string['competency_evidence'] = 'Competency Evidence';
$string['completedorgenable'] = 'Show records completed in the user\'s organisation';
$string['configenablereportcaching'] = 'This will allow administrators to configure report caching';
$string['confirmdeleterestrictionheader'] = 'Confirm deletion of "{$a}" restriction';
$string['confirmdeleterestriction'] = 'Are you sure you want to delete this restriction? All restriction data will be lost.';
$string['confirmcoldelete'] = 'Are you sure you want to delete this column?';
$string['confirmcolumndelete'] = 'Are you sure you want to delete this column?';
$string['confirmdeletereport'] = 'Confirm Deletion';
$string['confirmfilterdelete'] = 'Are you sure you want to delete this filter?';
$string['confirmfilterdelete_rid_enabled'] = 'Are you sure? Removing all filters means this report will display automatically on page load{$a}.';
$string['confirmfilterdelete_grid_enabled'] = ' (the enabled \'Restrict initial display in all report builder reports\' setting will no longer apply)';
$string['confirmrecord'] = 'Confirm {$a}';
$string['confirmreloadreport'] = 'Confirm Reset';
$string['confirmsearchcolumndelete'] = 'Are you sure you want to delete this search column?';
$string['content'] = 'Content';
$string['contentclassnotexist'] = 'Content class {$a} does not exist';
$string['contentcontrols'] = 'Content Controls';
$string['contentdesc_userown'] = 'The {$a->field} is "{$a->user}"';
$string['contentdesc_userdirect'] = 'The {$a->field} reports directly to "{$a->user}" in one of their job assignments';
$string['contentdesc_userindirect'] = 'The {$a->field} reports indirectly to "{$a->user}" in one of their job assignments';
$string['contentdesc_usertemp'] = 'The {$a->field} temporarly reports to "{$a->user}" in one of their job assignments';
$string['contentdesc_posbelow'] = 'The user\'s current position is below "{$a}" in one of their job assignments';
$string['contentdesc_posboth'] = 'The user\'s current position is equal to or below "{$a}" in one of their job assignments';
$string['contentdesc_posequal'] = 'The user\'s current position is equal to "{$a}" in one of their job assignments';
$string['contentdesc_orgbelow'] = 'The user\'s current organisation is below "{$a}" in one of their job assignments';
$string['contentdesc_orgboth'] = 'The user\'s current organisation is equal to or below "{$a}" in one of their job assignments';
$string['contentdesc_orgequal'] = 'The user\'s current organisation is equal to "{$a}" in one of their job assignments';
$string['contentdesc_delim'] = '" or "';
$string['context'] = 'Context';
$string['couldnotsortjoinlist'] = 'Could not sort join list. Source either contains circular dependencies or references a non-existent join';
$string['course_completion'] = 'Course Completion';
$string['courseenddate'] = 'End date';
$string['courseenrolavailable'] = 'Open enrolment';
$string['courseenroltype'] = 'Enrolment type';
$string['courseenroltypes'] = 'Enrolment Types';
$string['courseexpandlink'] = 'Course Name (expanding details)';
$string['coursecategory'] = 'Course Category';
$string['coursecategoryid'] = 'Course Category ID';
$string['coursecategorylinked'] = 'Course Category (linked to category)';
$string['coursecategorylinkedicon'] = 'Course Category (linked to category with icon)';
$string['coursecategorymultichoice'] = 'Course Category (multichoice)';
$string['coursecategoryidnumber'] = 'Course Category ID Number';
$string['coursecompletedon'] = 'Course completed on {$a}';
$string['coursedatecreated'] = 'Course Date Created';
$string['courseenrolledincohort'] = 'Course is enrolled in by audience';
$string['courseicon'] = 'Course Icon';
$string['courseid'] = 'Course ID';
$string['courseidnumber'] = 'Course ID Number';
$string['courselanguage'] = 'Course language';
$string['coursemultiitem'] = 'Course (multi-item)';
$string['coursemultiitemchoose'] = 'Choose Courses';
$string['coursename'] = 'Course Name';
$string['coursenameandsummary'] = 'Course Name and Summary';
$string['coursenamelinked'] = 'Course Name (linked to course page)';
$string['coursenamelinkedicon'] = 'Course Name (linked to course page with icon)';
$string['coursenotset'] = 'Course Not Set';
$string['courseprogress'] = 'Progress';
$string['courseshortname'] = 'Course Shortname';
$string['coursestartdate'] = 'Course Start Date';
$string['coursestatuscomplete'] = 'You have completed this course';
$string['coursestatusenrolled'] = 'You are currently enrolled in this course';
$string['coursestatusnotenrolled'] = 'You are not currently enrolled in this course';
$string['coursesummary'] = 'Course Summary';
$string['coursetypeicon'] = 'Type';
$string['coursetype'] = 'Course Type';
$string['coursevisible'] = 'Course Visible';
$string['coursevisibledisabled'] = 'Course Visible (not applicable)';
$string['createasavedsearch'] = 'Create a saved search';
$string['createreport'] = 'Create report';
$string['csvformat'] = 'CSV format';
$string['currentfinancial'] = 'The current financial year';
$string['currentorg'] = 'The user\'s current organisation';
$string['currentpos'] = 'The user\'s current position';
$string['currentorgenable'] = 'Show records from staff in the user\'s organisation';
$string['currentposenable'] = 'Show records from staff in the user\'s position';
$string['currentsearchparams'] = 'Settings to be saved';
$string['customiseheading'] = 'Customise heading';
$string['customisename'] = 'Customise Field Name';
$string['daily'] = 'Daily';
$string['data'] = 'Data';
$string['dateafter'] = 'After {$a}';
$string['datebefore'] = 'Before {$a}';
$string['datebetween'] = '{$a->from} to {$a->to}';
$string['dateisbetween'] = 'is between start of today and ';
$string['datelabelisafter'] = '{$a->label} is after {$a->after}';
$string['datelabelisafterandnotset'] = '{$a->label} is after {$a->after} and includes dates that are blank';
$string['datelabelisbefore'] = '{$a->label} is before {$a->before}';
$string['datelabelisbeforeandnotset'] = '{$a->label} is before {$a->before} and includes dates that are blank';
$string['datelabelisbetween'] = '{$a->label} is between {$a->after} and {$a->before}';
$string['datelabelisbetweenandnotset'] = '{$a->label} is between {$a->after} and {$a->before} and includes dates that are blank';
$string['datelabelisdaysafter'] = '{$a->label} is after today\'s date and before {$a->daysafter}';
$string['datelabelisdaysafterandnotset'] = '{$a->label} is after today\'s date and before {$a->daysafter} including dates that are not set';
$string['datelabelisdaysbefore'] = '{$a->label} is before today\'s date and after {$a->daysbefore}.';
$string['datelabelisdaysbeforeandnotset'] = '{$a->label} is before today\'s date and after {$a->daysbefore} including dates that are not set';
$string['datelabelisdaysbetween'] = '{$a->label} is after {$a->daysbefore} and before {$a->daysafter}';
$string['datelabelisdaysbetweenandnotset'] = '{$a->label} is after {$a->daysbefore} and before {$a->daysafter} including dates that are not set';
$string['datelabelnotset'] = 'Blank date records';
$string['datenotset'] = 'show blank date records';
$string['deactivateglobalrestriction'] = 'Deactivate';
$string['defaultsortcolumn'] = 'Default column';
$string['defaultsortorder'] = 'Default order';
$string['delete'] = 'Delete';
$string['deleterecord'] = 'Delete {$a}';
$string['deletecheckschedulereport'] = 'Are you sure you would like to delete the \'{$a}\' scheduled report?';
$string['deletedescrhtml'] = 'Report "{$a}" will be completely deleted.';
$string['deletedscheduledreport'] = 'Successfully deleted Scheduled Report \'{$a}\'';
$string['deleteduser'] = 'Deleted';
$string['deletereport'] = 'Report Deleted';
$string['deletescheduledreport'] = 'Delete scheduled report?';
$string['descending'] = 'Descending (Z to A, 9 to 1)';
$string['disabled'] = 'Disabled?';
$string['duration_hours_minutes'] = '{$a->hours}h {$a->minutes}m';
$string['edit'] = 'Edit';
$string['editingsavedsearch'] = 'Editing saved search';
$string['editreport'] = 'Edit Report \'{$a}\'';
$string['editrestriction'] = 'Edit restriction \'{$a}\'';
$string['editscheduledreport'] = 'Edit Scheduled Report';
$string['editrecord'] = 'Edit {$a}';
$string['editthisreport'] = 'Edit this report';
$string['emailexternaluserisonthelist'] = 'This email is already on the external users email list';
$string['emailexternalusers'] = 'External users email';
$string['emailexternalusers_help'] = 'Please enter one email address in the box below.';
$string['embedded'] = 'Embedded';
$string['embeddedaccessnotes'] = '<strong>Warning:</strong> Embedded reports may have their own access restrictions applied to the page they are embedded into. They may ignore the settings below, or they may apply them as well as their own restrictions.';
$string['embeddedcontentnotes'] = '<strong>Warning:</strong> Embedded reports may have further content restrictions applied via <em>embedded parameters</em>. These can further limit the content that is shown in the report';
$string['embeddedreports'] = 'Embedded Reports';
$string['enablereportcaching'] = 'Enable report caching';
$string['enableglobalrestrictions'] = 'Enable report restrictions';
$string['enableglobalrestrictions_desc'] = 'Global user report restrictions are designed to restrict the content visible in report builder reports. Turning this feature on allows for fine grained control over what records are visible to users viewing a report builder report but can have a significant impact on performance.';
$string['enablereportgraphs'] = 'Enable report builder graphs';
$string['enablereportgraphsinfo'] = 'This option will let you: enable (show) or disable report builder graphs on this site.

* If Show is selected, all features related to report builder graphs will be visible and accessible.
* If Disable is selected, no report builder graphs features will be visible or accessible.';
$string['enrol'] = 'Enrol';
$string['enrolledcoursecohortids'] = 'Enrolled course audience IDs';
$string['enrolledprogramcohortids'] = 'Enrolled program audience IDs';
$string['enrolusing'] = 'Enrol with - {$a}';
$string['error:addscheduledreport'] = 'Error adding new Scheduled Report';
$string['error:allowedscheduledrecipients'] = 'At least one option is required for Scheduled reports recipients.';
$string['error:bad_sesskey'] = 'There was an error because the session key did not match';
$string['error:cachenotfound'] = 'Cannot purge cache. Seems it is already clean.';
$string['error:column_not_deleted'] = 'There was a problem deleting that column';
$string['error:column_not_moved'] = 'There was a problem moving that column';
$string['error:column_vis_not_updated'] = 'Column visibility could not be updated';
$string['error:columnextranameid'] = 'Column extra field \'{$a}\' alias must not be \'id\''; // Obsolete.
$string['error:columnnameid'] = 'Field \'{$a}\' alias must not be \'id\'';
$string['error:columnoptiontypexandvalueynotfoundinz'] = 'Column option with type "{$a->type}" and value "{$a->value}" not found in source "{$a->source}"';
$string['error:columns_not_updated'] = 'There was a problem updating the columns.';
$string['error:couldnotcreatenewreport'] = 'Could not create new report';
$string['error:couldnotgenerateembeddedreport'] = 'There was a problem generating that report';
$string['error:couldnotsavesearch'] = 'Could not save search';
$string['error:couldnotupdateglobalsettings'] = 'There was an error while updating the global settings';
$string['error:couldnotupdatereport'] = 'Could not update report';
$string['error:creatingembeddedrecord'] = 'Error creating embedded record: {$a}';
$string['error:emailrequired'] = 'At least one recipient email address is required for export option you selected';
$string['error:emptyexportfilesystempath'] = 'If you enabled export to file system, you need to specify file system path.';
$string['error:failedtoremovetempfile'] = 'Failed to remove temporary report export file';
$string['error:filter_not_deleted'] = 'There was a problem deleting that filter';
$string['error:filter_not_moved'] = 'There was a problem moving that filter';
$string['error:filteroptiontypexandvalueynotfoundinz'] = 'Filter option with type "{$a->type}" and value "{$a->value}" not found in source "{$a->source}"';
$string['error:filters_not_updated'] = 'There was a problem updating the filters';
$string['error:fusion_oauthnotsupported'] = 'Fusion export via OAuth is not currently supported.';
$string['error:globalrestrictionrequired'] = 'You must select at least one restriction.';
$string['error:graphdeleteseries'] = 'This column is the data source for Graph construction. Please delete the column first under Graph tab.';
$string['error:graphisnotvalid'] = 'The report graph settings are invalid, please review.';
$string['error:grouphasreports'] = 'You cannot delete a group that is being used by reports.';
$string['error:groupnotcreated'] = 'Group could not be created';
$string['error:groupnotcreatedinitfail'] = 'Group could not be created - failed to initialize tables!';
$string['error:groupnotcreatedpreproc'] = 'Group could not be created - preprocessor not found!';
$string['error:groupnotdeleted'] = 'Group could not be deleted';
$string['error:invaliddate'] = 'Please enter a valid date or date range';
$string['error:invalidreportid'] = 'Invalid report ID';
$string['error:invalidreportscheduleid'] = 'Invalid scheduled report ID';
$string['error:invalidsavedsearchid'] = 'Invalid saved search ID';
$string['error:invalidsourceforfilter'] = 'Filter cannot be used with report source.';
$string['error:invaliduserid'] = 'Invalid user ID';
$string['error:joinsforfiltertypexandvalueynotfoundinz'] = 'Joins for filter with type "{$a->type}" and value "{$a->value}" not found in source "{$a->source}"';
$string['error:joinsfortypexandvalueynotfoundinz'] = 'Joins for columns with type "{$a->type}" and value "{$a->value}" not found in source "{$a->source}"';
$string['error:joinxhasdependencyyinz'] = 'Join name "{$a->join}" contains a dependency "{$a->dependency}" that does not exist in the joinlist for source "{$a->source}"';
$string['error:joinxisreservediny'] = 'Join name "{$a->join}" in source "{$a->source}" is an SQL reserved word. Please rename the join';
$string['error:joinxusedmorethanonceiny'] = 'Join name "{$a->join}" used more than once in source "{$a->source}"';
$string['error:missingdependencytable'] = 'In report source {$a->source}, missing dependency table in joinlist: {$a->join}!';
$string['error:mustselectsource'] = 'You must pick a source for the report';
$string['error:nocolumns'] = 'No columns found. Ask your developer to add column options to the \'{$a}\' source.';
$string['error:nocolumnsdefined'] = 'No columns have been defined for this report. Ask you site administrator to add some columns.';
$string['error:nocontentrestrictions'] = 'No content restrictions are available for this source. To use restrictions, ask your developer to add the necessary code to the \'{$a}\' source.';
$string['error:nographseries'] = 'There are no columns suitable for construction of a graph. You need to add some columns with numeric data to this report or set "Graph type" to "None".';
$string['error:nopdf'] = 'No PDF plugin found';
$string['error:norolesfound'] = 'No roles found';
$string['error:nosavedsearches'] = 'This report does not yet have any saved searches';
$string['error:nosources'] = 'No sources found. You must have at least one source before you can add reports. Ask your developer to add the necessary files to the codebase.';
$string['error:nosvg'] = 'SVG not supported';
$string['error:notapathexportfilesystempath'] = 'Specified file system path contains invalid characters.';
$string['error:notdirexportfilesystempath'] = 'Specified file system path does not exist or is not a directory.';
$string['error:notwriteableexportfilesystempath'] = 'Specified file system path is not writeable.';
$string['error:problemobtainingcachedreportdata'] = 'There was a problem obtaining the cached data for this report. It might be due to cache regeneration. Please, try again. If problem persist, disable cache for this report. <br /><br />{$a}';
$string['error:problemobtainingreportdata'] = 'There was a problem obtaining the data for this report: {$a}';
$string['error:processfile'] = 'Unable to create process file. Please, try later.';
$string['error:propertyxmustbesetiny'] = 'Property "{$a->property}" must be set in class "{$a->class}"';
$string['error:reportcacheinitialize'] = 'Cache is disabled for this report';
$string['error:reportgraphsdisabled'] = 'Report Builder graphs are not enabled on this site.';
$string['error:savedsearchnotdeleted'] = 'Saved search could not be deleted';
$string['error:unknownbuttonclicked'] = 'Unknown button clicked';
$string['error:updatescheduledreport'] = 'Error updating Scheduled Report';
$string['excelformat'] = 'Excel format';
$string['excludetags'] = 'Exclude records tagged with';
$string['export'] = 'Export';
$string['exportas'] = 'Export as';
$string['exportcsv'] = 'Export in CSV format';
$string['exportfilesystemoptions'] = 'Export options';
$string['exportfilesystempath'] = 'File export path';
$string['exportfilesystempath_help'] = 'Absolute file system path to a writeable directory where reports can be exported and stored.

**Warning!** Make sure to configure a correct system path if you are going to export reports to file system.';
$string['exportfusion'] = 'Export to Google Fusion';
$string['exportods'] = 'Export in ODS format';
$string['exportoptions'] = 'Format export options';
$string['exportpdf_landscape'] = 'Export in PDF (Landscape) format';
$string['exportpdf_mramlimitexceeded'] = 'Notice: Ram memory limit exceeded! Probably the report being exported is too big, as it took almost {$a} MB of ram memory to create it, please consider reducing the size of the report, applying filters or splitting the report in several files.';
$string['exportpdf_portrait'] = 'Export in PDF (Portrait) format';
$string['exportproblem'] = 'There was a problem downloading the file';
$string['exporttoemail'] = 'Email scheduled report';
$string['exporttoemailshort'] = 'Email';
$string['exporttoemailandsave'] = 'Email and save scheduled report to file';
$string['exporttoemailandsaveshort'] = 'Email and file';
$string['exporttofilesystem'] = 'Export to file system';
$string['exporttofilesystemenable'] = 'Enable exporting to file system';
$string['exporttosave'] = 'Save scheduled report to file system only';
$string['exporttosaveshort'] = 'File';
$string['exportxls'] = 'Export in Excel format';
$string['externalemail'] = 'External email address to add';
$string['extrasqlshouldusenamedparams'] = 'get_sql_filter() extra sql should use named parameters';
$string['eventreportcloned'] = 'Report cloned';
$string['eventreportcreated'] = 'Report created';
$string['eventreportdeleted'] = 'Report deleted';
$string['eventreportexported'] = 'Report exported';
$string['eventreportupdated'] = 'Report updated';
$string['eventreportviewed'] = 'Report viewed';
$string['eventscheduledreportcreated'] = 'Scheduled report created';
$string['eventscheduledreportdeleted'] = 'Scheduled report deleted';
$string['eventscheduledreportupdated'] = 'Scheduled report updated';
$string['filter'] = 'Filter';
$string['filterby'] = 'Filter by';
$string['filtercheckboxallyes'] = 'All values "Yes"';
$string['filtercheckboxallno'] = 'All values "No"';
$string['filtercheckboxanyyes'] = 'Any value "Yes"';
$string['filtercheckboxanyno'] = 'Any value "No"';
$string['filterdeleted'] = 'Filter deleted';
$string['filtermoved'] = 'Filter moved';
$string['filternameformatincorrect'] = 'get_filter_joins(): filter name format incorrect. Query snippets may have included a dash character.';
$string['filters'] = 'Filters';
$string['filters_updated'] = 'Filters updated';
$string['filter_assetavailable'] = 'Available between';
$string['filter_assetavailable_help'] = 'This filter allows you to find assets that are available for a session by specifying the session start and end date.';
$string['filter_roomavailable'] = 'Available between';
$string['filter_roomavailable_help'] = 'This filter allows you to find rooms that are available for a session by specifying the session start and end date.';
$string['filtercontains'] = 'Any of the selected';
$string['filtercontainsnot'] = 'None of the selected';
$string['filterdisabledwarning'] = 'This report has changed due to the removal of one or more filters. Contact your site administrator for more details.';
$string['filterequals'] = 'All of the selected';
$string['filterequalsnot'] = 'Not all of the selected';
$string['financialyear'] = 'Financial year start';
$string['financialyeardaystart'] = 'Financial year day start';
$string['financialyearmonthstart'] = 'Financial year month start';
$string['format'] = 'Format';
$string['general'] = 'General';
$string['generalperformancesettings'] = 'General Performance Settings';
$string['globalinitialdisplay'] = 'Restrict initial display in all report builder reports';
$string['globalinitialdisplay_desc'] = 'When enabled, all user-generated reports with one or more filters will not display automatically upon page load. This improves performance by avoiding display of unwanted reports. Note: Reports with no filters will display automatically.';
$string['globalinitialdisplay_enabled'] = '\'Restrict initial display in all report builder reports\' setting has been enabled.';
$string['globalrestriction'] = 'Global report restrictions';
$string['globalrestriction_help'] = 'Specify if global report restrictions are enabled in this report.';
$string['globalrestrictiondefault'] = 'Enable global restrictions in new user reports';
$string['globalrestrictiondefault_desc'] = 'If checked all newly created user reports will have global report restrictions enabled.';
$string['globalrestrictionnotsupported'] = 'Report source does not support global report restrictions.';
$string['globalrestrictionrecordsperpage'] = 'Global report restrictions number of records per page';
$string['globalrestrictionrecordsperpage_desc'] = 'Number of records per page allows you define how many records display on a report page.';
$string['globalrestrictions'] = 'Global report restrictions for report builder reports';
$string['globalrestrictionsdisabled'] = 'Global report restrictions disabled';
$string['globalrestrictiondescription'] = 'Global report restrictions are enabled separately for each report in the report\'s Content tab. Administrator may configure if global report restrictions are enabled automatically in all new reports.';
$string['globalrestrictionnew'] = 'New restriction';
$string['globalsettings'] = 'General settings';
$string['globalsettingsupdated'] = 'Global settings updated';
$string['gotofacetofacesettings'] = 'To view this report go to a seminar activity and use the \'Declared interest report\' link in the \'Seminar administration\' admin menu.';
$string['gradeandgradetocomplete'] = '{$a->grade}% ({$a->pass}% to complete)';
$string['graph'] = 'Graph';
$string['graphadvancedoptions'] = 'Advanced options';
$string['graphcategory'] = 'Category';
$string['graphlegend'] = 'Legend';
$string['graphmaxrecords'] = 'Maximum number of used records';
$string['graphnocategory'] = 'Numbered';
$string['graphorientation'] = 'Orientation';
$string['graphorientation_help'] = 'Determines how the report data is interpreted to build the graph. If **Data series in columns** is selected, then report builder will treat report columns as data series. In most cases this is what you want. If **Data series in rows** is selected, report builder treats every item in the column as a separate data series - data rows will be treated as data points. Typically you only want to select **Data series in rows** if you have more columns in your report than rows.';
$string['graphorientationcolumn'] = 'Data series in columns';
$string['graphorientationrow'] = 'Data series in rows';
$string['graphseries'] = 'Data sources';
$string['graphseries_help'] = 'Select one or more columns to use as data sources for the graph. Only columns with compatible numeric data are included.';
$string['graphsettings'] = 'Custom settings';
$string['graphsettings_help'] = 'Advanced SVGGraph settings in PHP ini file format. See <a href="http://www.goat1000.com/svggraph-settings.php" target="_blank">http://www.goat1000.com/svggraph-settings.php</a> for more information.';
$string['graphstacked'] = 'Stacked';
$string['graphtype'] = 'Graph type';
$string['graphtype_help'] = 'Select graph type to display a graph in report, select **None** to remove the graph from report.';
$string['graphtypearea'] = 'Area';
$string['graphtypebar'] = 'Horizontal bar';
$string['graphtypecolumn'] = 'Column';
$string['graphtypeline'] = 'Line';
$string['graphtypepie'] = 'Pie';
$string['graphtypescatter'] = 'Scatter';
$string['graph_updated'] = 'Graph updated';
$string['groupassignlist'] = '{$a->group}: {$a->entries}';
$string['groupconfirmdelete'] = 'Are you sure you want to delete this group?';
$string['groupcontents'] = 'This group currently contains {$a->count} feedback activities tagged with the <strong>\'{$a->tag}\'</strong> official tag:';
$string['groupdeleted'] = 'Group deleted.';
$string['groupingfuncnotinfieldoftypeandvalue'] = 'Grouping function \'{$a->groupfunc}\' doesn\'t exist in field of type \'{$a->type}\' and value \'{$a->$value}\'';
$string['groupname'] = 'Group name';
$string['grouptag'] = 'Group tag';
$string['heading'] = 'Heading';
$string['headingformat'] = '{$a->column} ({$a->type})';;
$string['help:columnsdesc'] = 'The choices below determine which columns appear in the report and how those columns are labelled.';
$string['help:restrictionoptions'] = 'The checkboxes below determine who has access to this report, and which records they are able to view. If no options are checked no results are visible. Click the help icon for more information';
$string['hidden'] = 'Hide in My Reports';
$string['hiddencellvalue'] = '&lt;hidden&gt;';
$string['hide'] = 'Hide';
$string['hierarchyfiltermusthavetype'] = 'Hierarchy filter of type "{$a->type}" and value "{$a->value}" must have "hierarchytype" set in source "{$a->source}"';
$string['includechildorgs'] = 'Include records from child organisations';
$string['includechildpos'] = 'Include records from child positions';
$string['includeemptydates'] = 'Include record if date is missing';
$string['includerecordsfrom'] = 'Include records from';
$string['includesessionroles'] = 'Show event roles where user holds any of the selected event roles';
$string['includetags'] = 'Include records tagged with';
$string['includetrainerrecords'] = 'Include records from particular trainers';
$string['includeuserrecords'] = 'Include records from particular users';
$string['initialdisplay'] = 'Restrict Initial Display';
$string['initialdisplay_disabled'] = 'This setting is not available when there are no filters enabled';
$string['initialdisplay_error'] = 'The last filter can not be deleted when initial display is restricted';
$string['initialdisplay_heading'] = 'Filters Performance Settings';
$string['initialdisplay_help'] = 'This setting controls how the report is initially displayed and is recommended for larger reports where you will be filtering the results (e.g. sitelogs). It increases the speed of the report by allowing you to apply filters and display only the results instead of initially trying to display **all** the data.

* **Disabled**: The report will display all results immediately (default).
* **Enabled**: The report will not generate results until a filter is applied or an empty search is run.';
$string['initialdisplay_pending'] = 'Please apply a filter to view the results of this report, or hit search without adding any filters to view all entries';
$string['is'] = 'is';
$string['isaftertoday'] = 'days after today (date of report generation)';
$string['isbeforetoday'] = 'days before today (date of report generation)';
$string['isbelow'] = 'is below';
$string['isnotempty'] = 'is not empty (NOT NULL)';
$string['isnotfound'] = ' is NOT FOUND';
$string['isnt'] = 'isn\'t';
$string['isnttaggedwith'] = 'isn\'t tagged with';
$string['istaggedwith'] = 'is tagged with';
$string['jobassign_appraiser'] = 'User\'s Appraiser(s)';
$string['jobassign_children'] = 'Include children';
$string['jobassign_jobtitle'] = 'User\'s Job Title(s)';
$string['jobassign_jobstart'] = 'User\'s Job Start Date(s)';
$string['jobassign_jobend'] = 'User\'s Job End Date(s)';
$string['jobassign_manager'] = 'All Assigned Manager(s)';
$string['jobassign_organisation'] = 'All Assigned Organisation(s)';
$string['jobassign_position'] = 'All Assigned Position(s)';
$string['joinnotinjoinlist'] = '\'{$a->join}\' not in join list for {$a->usage}';
$string['last30days'] = 'The last 30 days';
$string['lastcached'] = 'Last cached at {$a}';
$string['lastchecked'] = 'Last process date';
$string['lastfinancial'] = 'The previous financial year';
$string['lastlogin'] = 'Last Login';
$string['legacyreportlink'] = 'Looking for the original version of this report? {$a->link_start}You can find it here.{$a->link_end}';
$string['manageactivitygroups'] = 'Manage activity groups';
$string['manageembeddedreports'] = 'Manage embedded reports';
$string['managescheduledreports'] = 'Manage scheduled reports';
$string['manageglobalrestrictions'] = 'Global report restrictions';
$string['managereports'] = 'Manage reports';
$string['managername'] = 'Manager\'s Name';
$string['managesavedsearches'] = 'Manage searches';
$string['manageuserreports'] = 'Manage user reports';
$string['missingsearchname'] = 'Missing search name';
$string['mnetuser'] = 'Mnet user';
$string['mnetnotsupported'] = 'Mnet is no longer supported';
$string['monthly'] = 'Monthly';
$string['movedown'] = 'Move Down';
$string['moveup'] = 'Move Up';
$string['myreports'] = 'My Reports';
$string['name'] = 'Name';
$string['name_help'] = 'This name will be used to identify the restriction on reports.';
$string['newgroup'] = 'Create a new activity group';
$string['newreport'] = 'New Report';
$string['newrestriction'] = 'Create a new restriction';
$string['newreportcreated'] = 'New report created. Click settings to edit filters and columns';
$string['next30days'] = 'The next 30 days';
$string['nice_time_unknown_timezone'] = 'Unknown Timezone';
$string['noactiverestrictionsbehaviour'] = 'Global restriction behaviour for users with no active restrictions';
$string['noactiverestrictionsbehaviour_desc'] = 'Specifies what users will see when viewing a report with global restrictions enabled when they don\'t have any restrictions applied to them.';
$string['noactiverestrictionsbehaviournone'] = 'Show no records';
$string['noactiverestrictionsbehaviourall'] = 'Show all records';
$string['nocolumnsyet'] = 'No columns have been created yet - add them by selecting a column name in the pulldown below.';
$string['nocontentrestriction'] = 'Show all records';
$string['nodeletereport'] = 'Report could not be deleted';
$string['noembeddedreports'] = 'There are no embedded reports. Embedded reports are reports that are hard-coded directly into a page. Typically they will be set up by your site developer.';
$string['noemptycols'] = 'You must include a column heading';
$string['nofilteraskdeveloper'] = 'No filters found. Ask your developer to add filter options to the \'{$a}\' source.';
$string['nofilteroptions'] = 'This filter has no options to select';
$string['nofiltersetfortypewithvalue'] = 'get_field(): no filter set in filteroptions for type\'{$a->type}\' with value \'{$a->value}\'';
$string['nofiltersyet'] = 'No search fields have been created yet - add them by selecting a search term in the pulldown below.';
$string['noglobalrestrictionsfound'] = 'There are no global restrictions.';
$string['nogroups'] = 'There are currently no activity groups';
$string['noheadingcolumnsdefined'] = 'No heading columns defined';
$string['noneselected'] = 'None selected';
$string['nonglobalrestrictionsources'] = 'Warning: Reportbuilder sources: {$a} do not support Global Restrictions. Developers of these sources should update them to work with the Global Restrictions API.';
$string['nopermission'] = 'You do not have permission to view this page';
$string['norecordsinreport'] = 'There are no records in this report';
$string['norecordswithfilter'] = 'There are no records that match your selected criteria';
$string['noreloadreport'] = 'Report settings could not be reset';
$string['norepeatcols'] = 'You cannot include the same column more than once';
$string['norepeatfilters'] = 'You cannot include the same filter more than once';
$string['noreports'] = 'No reports have been created. You can create a report using the form below.';
$string['noreportscount'] = 'No reports using this group';
$string['norestriction'] = 'All users can view this report';
$string['norestrictionsfound'] = 'No restrictions found. Ask your developer to add restrictions to /totara/reportbuilder/sources/{$a}/restrictionoptions.php';
$string['noroleselected'] = 'No role selected';
$string['noscheduledreports'] = 'There are no scheduled reports';
$string['nosearchcolumnsaskdeveloper'] = 'No search columns found. Ask your developer to define text and long text fields as searchable in the \'{$a}\' source.';
$string['nosearchcolumnsyet'] = 'No search columns have been added yet - add them by selecting a column in the pulldown below.';
$string['noshortnameorid'] = 'Invalid report id or shortname';
$string['notags'] = 'No official tags exist. You must create one or more official tags to base your groups on.';
$string['notassigned'] = 'Not assigned';
$string['notassignedanyrole'] = 'Not assigned any role';
$string['notassignedrole'] = 'Not assigned role \'{$a->role}\'';
$string['notcached'] = 'Not cached yet';
$string['notspecified'] = 'Not specified';
$string['notyetchecked'] = 'Not yet processed';
$string['nouserreports'] = 'You do not have any reports. Report access is configured by your site administrator. If you are expecting to see a report, ask them to check the access permissions on the report.';
$string['numcolumns'] = 'Number of columns';
$string['numfilters'] = 'Number of filters';
$string['numresponses'] = '{$a} response(s).';
$string['numscheduled'] = 'Number of scheduled reports';
$string['numsaved'] = 'Number of saved searches';
$string['occurredafter'] = 'occurred after';
$string['occurredbefore'] = 'occurred before';
$string['occurredprevfinancialyear'] = 'occurred in the previous financial year';
$string['occurredthisfinancialyear'] = 'occurred in this finanicial year';
$string['odsformat'] = 'ODS format';
$string['on'] = 'on';
$string['onlydisplayrecordsfor'] = 'Only display records for';
$string['onthe'] = 'on the';
$string['options'] = 'Options';
$string['or'] = ' or ';
$string['organisationframework'] = 'User\'s Organisation Framework';
$string['organisationframeworkdescription'] = 'User\'s Organisation Framework Description';
$string['organisationframeworkid'] = 'User\'s Organisation Framework ID';
$string['organisationframeworkidnumber'] = 'User\'s Organisation Framework ID Number';
$string['organisationtype'] = 'User\'s Organisation Type';
$string['organisationtypeid'] = 'User\'s Organisation Type ID';
$string['orsuborg'] = '(or a sub organisation)';
$string['orsubpos'] = '(or a sub position)';
$string['otherrecipients'] = 'Other recipients';
$string['otherrecipient:systemusers'] = 'User: {$a}';
$string['otherrecipient:emailexternalusers'] = 'External email: {$a}';
$string['otherrecipient:audiences'] = 'Audience: {$a}';
$string['pdffont'] = 'PDF export font';
$string['pdffont_help'] = 'When exporting a report from the report builder as a PDF this is the font that will be used. If appropriate default is selected Totara will select a font that is suitable for the users language.';
$string['pdflandscapeformat'] = 'PDF format (landscape)';
$string['pdfportraitformat'] = 'PDF format (portrait)';
$string['performance'] = 'Performance';
$string['pluginadministration'] = 'Report Builder administration';
$string['pluginname'] = 'Report Builder';
$string['posenddate'] = 'User\'s Job Assignment End Date';
$string['positionframework'] = 'User\'s Position Framework';
$string['positionframeworkdescription'] = 'User\'s Position Framework Description';
$string['positionframeworkid'] = 'User\'s Position Framework ID';
$string['positionframeworkidnumber'] = 'User\'s Position Framework ID Number';
$string['positiontype'] = 'User\'s Position Type';
$string['positiontypeid'] = 'User\'s Position Type ID';
$string['posstartdate'] = 'User\'s Job Assignment Start Date';
$string['preprocessgrouptask'] = 'Preprocess report groups';
$string['processscheduledtask'] = 'Generate scheduled reports';
$string['programenrolledincohort'] = 'Program is enrolled in by audience';
$string['publicallyavailable'] = 'Let other users view';
$string['publicsearch'] = 'Is search public?';
$string['records'] = 'Records';
$string['recordstoview'] = 'View records related to';
$string['recordstoviewdescription'] = 'The reports will only display records related to users selected in the "View records related to" tab.';
$string['recordsperpage'] = 'Number of records per page';
$string['refreshcachetask'] = 'Refresh report cache';
$string['refreshdataforthisgroup'] = 'Refresh data for this group';
$string['reloadreport'] = 'Report settings have been reset';
$string['report'] = 'Report';
$string['report:cachelast'] = 'Report data last updated: {$a}';
$string['report:cachenext'] = 'Next update due: {$a}';
$string['report:completiondate'] = 'Completion date';
$string['report:coursetitle'] = 'Course title';
$string['report:enddate'] = 'End date';
$string['report:learner'] = 'Learner';
$string['report:learningrecords'] = 'Learning records';
$string['report:nodata'] = 'There is no available data for that combination of criteria, start date and end date';
$string['report:organisation'] = 'Office';
$string['report:startdate'] = 'Start date';
$string['reportaccess'] = 'Report access';
$string['reportactions'] = 'Actions';
$string['reportbuilder'] = 'Report builder';
$string['reportbuilder:createscheduledreports'] = 'Create scheduled reports';
$string['reportbuilder:manageembeddedreports'] = 'Create, edit and reset report builder embedded reports';
$string['reportbuilder:managereports'] = 'Create, edit and delete report builder reports';
$string['reportbuilder:managescheduledreports'] = 'Manage scheduled reports';
$string['reportbuilder:overridescheduledfrequency'] = 'Override minimum scheduled report frequency';
$string['reportbuilderaccessmode'] = 'Access Mode';
$string['reportbuilderaccessmode_help'] = 'Access controls are used to restrict which users can view the report.

**Restrict access** sets the overall access setting for the report.

When set to **All users can view this report** there are no restrictions applied to the report and all users will be able to view the report.

When set to **Only certain users can view this report** the report will be restricted to the user groups selected below.

Note that access restrictions only control who can view the report, not which records it contains. See the **Content** tab for controlling the report contents.';
$string['reportbuilderbaseitem'] = 'Report Builder: Base item';
$string['reportbuilderbaseitem_help'] = 'By grouping a set of activities you are saying that they have something in common, which will allow reports to be generated for all the activities in a group. The base item defines the properties that are considered when aggregation is performed on each member of the group.';
$string['reportbuildercache'] = 'Enable report caching';
$string['reportbuildercache_disabled'] = 'This setting is not available for this report source';
$string['reportbuildercache_heading'] = 'Caching Performance Settings';
$string['reportbuildercache_help'] = 'If **Enable report caching** is checked, then a copy of this report will be generated on a set schedule, and users will see data from the stored report. This will make displaying and filtering of the report faster, but the data displayed will be from the last time the report was generated rather than \'live\' data. We recommend enabling this setting only if necessary (reports are taking too long to be displayed), and only for specific reports where this is a problem.';
$string['reportbuildercachescheduler'] = 'Cache Schedule (Server Time)';
$string['reportbuildercachescheduler_help'] = 'Determines the schedule used to control how often a new version of the report is generated. The report will be generated on the cron that immediately follows the specified time.

For example, if you have set up your cron to run every 20 minutes at 10, 30 and 50 minutes past the hour and you schedule a report to run at midnight, it will actually run at 10 minutes past midnight.';
$string['reportbuildercacheservertime'] = 'Current Server Time';
$string['reportbuildercacheservertime_help'] = 'All reports are being cached based on server time. Cache status shows you current local time which might be different from server time. Make sure to take into account your server time when scheduling cache.';
$string['reportbuildercolumns'] = 'Columns';
$string['reportbuildercolumns_help'] = '**Report Columns** allows you to customise the columns that appear on your report. The available columns are determined by the data **Source** of the report. Each report source has a set of default columns set up.

Columns can be added, removed, renamed and sorted.

**Adding Columns:** To add a new column to the report choose the required column from the **Add another column...** dropdown list and click **Save changes**. The new column will be added to the end of the list.

Note that you can only create one column of each type within a single report. You will receive a validation error if you try to include the same column more than once.

**Hiding columns:** By default all columns appear when a user views the report. Use the \'show/hide\' button (the eye icon) to hide columns you do not want users to see by default.

Note that a hidden column is still available to a user viewing the report. Delete columns (the cross icon) that you do not want users to see at all.

**Moving columns:** The columns will appear on the report in the order they are listed. Use the up and down arrows to change the order.

**Deleting columns:** Click the **Delete** button (the cross icon) to the right of the report column to remove that column from the report.

**Renaming columns:** You can customise the name of a column by changing the **Heading** name and clicking **Save changes**. The **Heading** is the name that will appear on the report.

**Changing multiple column types:** You can modify multiple column types at the same time by selecting a different column from the dropdown menu and clicking **Save changes**.';
$string['reportbuildercompletedorg'] = 'Show by Completed Organisation';
$string['reportbuildercompletedorg_help'] = 'When **Show records completed in the user\'s organisation** is selected the report displays different completed records depending on the organisation the user has been assigned to. (A user is assigned an organisation in their **User Profile** on the **Positions** tab).

When **Include records from child organisations** is set to:

*   **Yes**: The user viewing the report will be able to view completed records related to their organisation and any child organisations of that organisation.
*   **No**: The user can only view completed records related to their organisation.';
$string['reportbuildercontentmode'] = 'Content Mode';
$string['reportbuildercontentmode_help'] = 'Content controls allow you to restrict the records and information that are available when a report is viewed.

**Report content** allows you to select the overall content control settings for this report:

When **Show all records** is selected, every available record for this source will be shown and no restrictions will be placed on the content available.

When **Show records matching any of the checked criteria** is selected the report will display records that match any of the criteria set below.

Note that if no criteria is set the report will display no records.

When **Show records matching all of the checked criteria** is selected the report will display records that match all the criteria set below.
Note that if no criteria is set the report will display no records.';
$string['reportbuildercontext'] = 'Restrict Access by Role';
$string['reportbuildercontext_help'] = 'Context is the location or level within the system that the user has access to. For example a Site Administrator would have System level access (context), while a learner may only have Course level access (context).

**Context** allows you to set the context in which a user has been assigned a role to view the report.

A user can be assigned a role at the system level giving them site wide access or just within a particular context. For instance a trainer may only be assigned the role at the course level.

When **Users must have role in the system context** is selected the user must be assigned the role at a system level (i.e. at a site-wide level) to be able to view the report.

When **User may have role in any context** is selected a user can view the report when they have been assigned the selected role anywhere in the system.';
$string['reportbuildercurrentorg'] = 'Show by Current Organisation';
$string['reportbuildercurrentorg_help'] = 'When **Show records from staff in the user\'s organisation** is selected the report displays different results depending on the organisation the user has been assigned to. (A user is assigned an organisation in their **User Profile** on the **Positions** tab).

When **Include records from child organisations** is set to:

*   **Yes**: The user viewing the report will be able to view records related to their organisation and any child organisations of that organisation.
*   **No**: The user can only view records related to their organisation.';
$string['reportbuildercurrentpos'] = 'Show by Current Position';
$string['reportbuildercurrentpos_help'] = 'When **Show records from staff in the user\'s position** is selected the report will display different records depending on their assigned position (A user is assigned a position in their **User Profile** on the **Positions** tab).

When **Include records from child positions** is set to:

*   **Yes**: The user viewing the report can view records related to their positions and any child positions related to their positions.
*   **No**: The user viewing the report can only view records related to their position.';
$string['reportbuilderdate'] = 'Show by date';
$string['reportbuilderdate_help'] = 'When **Show records based on the record date** is selected the report only displays records within the selected timeframe.

The **Include records from** options allow you to set the timeframe for the report:

*   When set to **The past** the report only shows records with a date older than the current date.
*   When set to **The future** the report only shows records with a future date set from the current date.
*   When set to **The last 30 days** the report only shows records between the current time and 30 days before.
*   When set to **The next 30 days** the report only shows records between the current time and 30 days into the future.';
$string['reportbuilderdescription'] = 'Description';
$string['reportbuilderdescription_help'] = 'When a report description is created the information displays in a box above the search filters on the report page.';
$string['reportbuilderdialogfilter'] = 'Report Builder: Dialog filter';
$string['reportbuilderdialogfilter_help'] = 'This filter allows you to filter information based on a hierarchy. The filter has the following options:

*   **is any value**: This option disables the filter (i.e. all information is accepted by this filter).
*   **is equal to**: This option allows only information that is equal to the value selected from the list.
*   **is not equal to**: This option allows only information that is different from the value selected from the list.

Once a framework item has been selected you can use the **Include children?** checkbox to choose whether to match only that item, or match that item and any sub-items belonging to that item.';
$string['reportbuilderexportoptions'] = 'Report Export Settings';
$string['reportbuilderexportoptions_help'] = 'Report export settings allows a user to specify the export options that are available for users at the bottom of a report page. This setting affects all Report builder reports.

When multiple options are selected the user can choose their preferred options from the export dropdown menu.

When no options are selected the export function is disabled.';
$string['reportbuilderexporttofilesystem'] = 'Enable exporting to file system';
$string['reportbuilderexporttofilesystem_help'] = 'Exporting to file system allows reports to be saved to a directory on the web server\'s file system, instead of only emailing the report to the user scheduling the report.

This can be useful when the report needs to be accessed by an external system automation, and the report directory might have SFTP access enabled.

Reports saved to the filesystem are saved as **\'Export file system root path\'**/username/report.ext where **username** is an internal username of a user who owns the scheduled report, **report** is the name of the scheduled report with non alpha-numeric characters removed, and **ext** is the appropriate export file name extension.';
$string['reportbuilderfilters'] = 'Search Options (Filters)';
$string['reportbuilderfilters_help'] = '**Search Options** allows you to customise the filters that appear on your report. The available filters are determined by the **Source** of the report. Each report source has a set of default filters.

Filters can be added, sorted and removed.

**Adding filters:** To add a new filter to the report choose the required filter from the **Add another filter...** dropdown menu and click **Save changes**. When **Advanced** is checked the filter will not appear in the **Search by** box by default, you can click **Show advanced** when viewing a report to see these filters.

**Moving filters:** The filters will appear in the **Search by** box in the order they are listed. Use the up and down arrows to change the order.

**Deleting filters:** Click the **Delete** button (the cross icon) to the right of the report filter to remove that filter from the report.

**Changing multiple filter types:** You can modify multiple filter types at the same time by selecting a different filter from the dropdown menu and clicking **Save changes**.';
$string['reportbuilderfinancialyear'] = 'Report Financial Year Settings';
$string['reportbuilderfinancialyear_help'] = 'This setting allows to set the start date of the financial year which is used in the reports content controls.';
$string['reportbuilderfullname'] = 'Report Name';
$string['reportbuilderfullname_help'] = 'This is the name that will appear at the top of your report page and in the **Report Manager** block.';
$string['reportbuilderglobalsettings'] = 'Report Builder Global Settings';
$string['reportbuildergroupname'] = 'Report Builder: Group Name';
$string['reportbuildergroupname_help'] = 'The name of the group. This will allow you to identify the group when you want to create a new report based on it. Look for the name in the report source pulldown menu.';
$string['reportbuildergrouptag'] = 'Report Builder: Group Tag';
$string['reportbuildergrouptag_help'] = 'When you create a group using a tag, any activities that are tagged with the official tag specified automatically form part of the group. If you add or remove tags from an activity, the group will be updated to include/exclude that activity.';
$string['reportbuilderhidden'] = 'Hide in My Reports';
$string['reportbuilderhidden_help'] = 'When **Hide in My Reports** is checked the report will not appear on the **My Reports** page for any logged in users.Note that the **Hide in My Reports** option only hides the link to the report. Users with the correct access permissions may still access the report using the URL.';
$string['reportbuilderinitcache'] = 'Cache Status (User Time)';
$string['reportbuilderjobassignmentfilter'] = 'Job assignment concantenated filter';
$string['reportbuilderjobassignmentfilter_help'] = 'This filter allows you to filter information based on all job assignments. The filter has the following options:

*   **is any value**: This option disables the filter (i.e. all information is accepted by this filter).
*   **Any of the selected**: This option will show users that have any of the selected items in any of their job assignments.
*   **None of the selected**: This option will show users that have none of the selected items in any of their job assignments.
*   **All of the selected**: This option will show users that have all of the selected items in any of their job assignments. (Note that they can have more than the selected items).
*   **Not all of the selected**: This option will show users that don\'t have all of the selected items in their job assignments.

For positions and organisations once items have been selected you can use the **Include children?** checkbox to choose whether to match only that item, or match that item and any sub-items belonging to that item.';
$string['reportbuilderrecordsperpage'] = 'Number of Records per Page';
$string['reportbuilderrecordsperpage_help'] = '**Number of records per page** allows you define how many records display on a report page.

The maximum number of records that can be displayed on a page is 9999. The more records set to display on a page the longer the report pages take to display.

Recommendation is to **limit the number of records per page to 40**.';
$string['reportbuilderrolesaccess'] = 'Roles with Access';
$string['reportbuilderrolesaccess_help'] = 'When **Restrict access** is set to **Only certain users can view this report** you can specify which roles can view the report using **Roles with permission to view the report**.

You can select one or multiple roles from the list.

When **Restrict access** is set to **All users can view this report** these options will be disabled.';
$string['reportbuildershortname'] = 'Report Builder: Unique name';
$string['reportbuildershortname_help'] = 'The shortname is used by Totara to keep track of this report. No two reports can be given the same shortname, even if they are based on the same source. Avoid using special characters in this field (text, numbers and underscores are okay).';
$string['reportbuildersorting'] = 'Sorting';
$string['reportbuildersorting_help'] = '**Sorting** allows you to set a default column and sort order on a report.

A user is still able to manually sort a report while viewing it. The users preferences will be saved during the active session. When they finish the session the report will return to the default sort settings set here.';
$string['reportbuildersource'] = 'Source';
$string['reportbuildersource_help'] = 'The **Source** of a report defines the primary type of data used. Further filtering options are available once you start editing the report.

Once saved, the report source cannot be changed.

Note that if no options are available in the **Source** field, or the source you require does not appear you will need your Totara installation to be configured to include the source data you require (this cannot be done via the Totara interface).';
$string['reportbuildertag'] = 'Report Builder: Show by tag';
$string['reportbuildertag_help'] = 'This criteria is enabled by selecting the **Show records by tag** checkbox. If selected, the report will show results based on whether the record belongs to an item that is tagged with particular tags.

If any tags in the **Include records tagged with** section are selected, only records belonging to an item tagged with all the selected tags will be shown. Records belonging to items with no tags will **not** be shown.

If any tags in the **Exclude records tagged with** section are selected, records belonging to a coures tagged with the selected tags will **not** be shown. All records belonging to items without any tags will be shown.

It is possible to include and exclude tags at the same time, but a single tag cannot be both included and excluded.';
$string['reportbuildertrainer'] = 'Report Builder: Show by trainer';
$string['reportbuildertrainer_help'] = 'This criteria is enabled by selecting the **Show records by trainer** checkbox. If selected, then the report will show different records depending on who the seminar trainer was for the feedback being given.

If **Show records where the user is the trainer** is selected, the report will show feedback for sessions where the user viewing the report was the trainer.

If **Records where one of the user\'s direct reports is the trainer** is selected, then the report will show records for sessions trained by staff of the person viewing the report.

If **Both** is selected, then both of the above records will be shown.';
$string['reportbuilderuser'] = 'Show by User';
$string['reportbuilderuser_help'] = 'When **Show records by user** is selected the report will show different records depending on the user viewing the report and their relationship to other users.

**Include records from a particular user** controls what records a user viewing the report can see:

*   When **A user\'s own records** is checked the user can see their own records.
*   When **Records for user\'s direct reports** is checked the user can see the records belonging to any user who reports to them (A user is assigned a manager in their user profile on the **Positions** tab).
*   When **Records for user\'s indirect reports** is checked the user can see the records belonging to any user who reports any user below them in the management hierarchy, excluding their direct reports.

If multiple options are selected the user sees records that match any of the selected options.';
$string['reportcachingdisabled'] = 'Report caching is disabled. <a href="{$a}">Enable report caching here</a>';
$string['reportcachingincompatiblefilter'] = 'Filter "{$a}" is not compatible with report caching.';
$string['reportcolumns'] = 'Report Columns';
$string['reportconfirmdelete'] = 'Are you sure you want to delete the report "{$a}"?';
$string['reportconfirmreload'] = '"{$a}" is an embedded report so you cannot delete it (that must be done by your site developer). You can choose to reset the report settings to their original values. Do you want to continue?';
$string['reportcontents'] = 'This report contains records matching the following criteria:';
$string['reportcount'] = '{$a} report(s) based on this group:';
$string['reportembedded'] = 'Is embedded report?';
$string['reporthidden'] = 'Is hidden on My Reports?';
$string['reportid'] = 'Report ID';
$string['reportmustbedefined'] = 'Report must be defined';
$string['reportname'] = 'Report Name';
$string['reportnamelinkedit'] = 'Name (linked to edit report)';
$string['reportnamelinkeditview'] = 'Name (linked to edit report) and view link';
$string['reportnamelinkview'] = 'Name (linked to view report)';
$string['reportperformance'] = 'Performance settings';
$string['reports'] = 'Reports';
$string['reportsdirectlyto'] = 'reports directly to';
$string['reportsindirectlyto'] = 'reports indirectly to';
$string['reportsettings'] = 'Report Settings';
$string['reportshortname'] = 'Short Name';
$string['reportshortnamemustbedefined'] = 'Report shortname must be defined';
$string['reportsource'] = 'Source';
$string['reporttitle'] = 'Report Title';
$string['reporttype'] = 'Report type';
$string['reportupdated'] = 'Report Updated';
$string['reportwithidnotfound'] = 'Report with id of \'{$a}\' not found in database.';
$string['restoredefaults'] = 'Restore Default Settings';
$string['restrictaccess'] = 'Restrict access';
$string['restrictcontent'] = 'Report content';
$string['restriction'] = 'Restriction';
$string['restrictionallrecords'] = 'All records without any restrictions.';
$string['restrictionallusers'] = 'Restriction is available to all users.';
$string['restrictionactivated'] = 'Restriction "{$a}" has been activated.';
$string['restrictioncreated'] = 'New restriction "{$a}" has been created.';
$string['restrictiondeactivated'] = 'Restriction "{$a}" has been deactivated.';
$string['restrictiondeleted'] = 'Restriction "{$a}" has been deleted.';
$string['restrictiondisableallrecords'] = 'Restrict which records can be viewed';
$string['restrictiondisableallusers'] = 'Restrict which users can use this restriction';
$string['restrictionenableallrecords'] = 'Allow all records to be viewed with this restriction';
$string['restrictionenableallusers'] = 'Make this restriction available to all users';
$string['restrictionupdated'] = 'Restriction "{$a}" has been updated.';
$string['restrictedusers'] = 'Users allowed to select restriction';
$string['restrictedusersdescription'] = 'Users selected in the "Users allowed to select restriction" tab will be allowed to use the restriction in reports with enabled "Global report restrictions".<br/>Please note: Users with only one restriction will have it automatically applied and they will not see any restriction choice notifications.';
$string['restrictionswarning'] = '<strong>Warning:</strong> If none of these boxes are checked, all users will be able to view all available records from this source.';
$string['resultsfromfeedback'] = 'Results from <strong>{$a}</strong> completed feedback(s).';
$string['roleswithaccess'] = 'Roles with permission to view this report';
$string['savedsearch'] = 'Saved Search';
$string['savedsearchconfirmdelete'] = 'Are you sure you want to delete this saved search  \'{$a}\'?';
$string['savedsearchdeleted'] = 'Saved search deleted';
$string['savedsearchdesc'] = 'By giving this search a name you will be able to easily access it later or save it to your bookmarks.';
$string['savedsearches'] = 'Saved Searches';
$string['savedsearchinscheduleddelete'] = 'This saved search is currently being used in the following scheduled reports: <br/> {$a} <br/> Deleting this saved search will delete these scheduled reports.';
$string['savedsearchmessage'] = 'Only the data matching the \'{$a}\' search is included.';
$string['savedsearchname'] = 'Saved search name';
$string['savedsearchnotfoundornotpublic'] = 'Saved search not found or search is not public';
$string['savesearch'] = 'Save this search';
$string['saving'] = 'Saving...';
$string['schedule'] = 'Schedule';
$string['scheduledaily'] = 'Daily';
$string['scheduledemailtosettings'] = 'Email Settings';
$string['scheduledreportfrequency'] = 'Minimum scheduled report frequency';
$string['scheduledreportfrequency_desc'] = 'This setting allows you to set the minimum period a report can be run in, this is useful to prevent reports being run too frequently on larger sites and thus causing slowness for your system';
$string['scheduledreportmessage'] = 'Attached is a copy of the \'{$a->reportname}\' report in {$a->exporttype}. {$a->savedtext}

You have been sent this report by {$a->sender}.
The report shows the data {$a->sender} has access to; YOU may see different results when viewing the report online.

You can also view this report online at:

{$a->reporturl}

You are scheduled to receive this report {$a->schedule}.
To delete or update your scheduled report settings, visit:

{$a->scheduledreportsindex}';
$string['scheduledreports'] = 'Scheduled Reports';
$string['scheduledreportsettings'] = 'Scheduled report settings';
$string['scheduledreportsrecipients'] = 'Scheduled reports recipients';
$string['schedulemonthly'] = 'Monthly';
$string['scheduleneedssavedfilters'] = 'This report cannot be scheduled without a saved search.
To view the report, click <a href="{$a}">here</a>';
$string['schedulenotset'] = 'Schedule not set';
$string['scheduleweekly'] = 'Weekly';
$string['search'] = 'Search';
$string['searchby'] = 'Search by';
$string['searchcolumndeleted']=  'Search column deleted';
$string['searchfield'] = 'Search Field';
$string['searchname'] = 'Search Name';
$string['searchoptions'] = 'Report Search Options';
$string['selectedglobalrestrictionsmany'] = 'Viewing records restricted by: {$a->appliednamesstr}. {$a->chooselink}';
$string['selectedglobalrestrictionsselect'] = 'No global data restriction selected. {$a}';
$string['selectitem'] = 'Select item';
$string['selectmanagers'] = 'Select Managers';
$string['selectorganisations'] = 'Select Organisations';
$string['selectpositions'] = 'Select Positions';
$string['selectsource'] = 'Select a source...';
$string['sendtoself'] = 'Send to self';
$string['sessionroles_txtrestr'] = '{$a->rolelocalnames} {$a->title} AND {$a->userfullname}';
$string['settings'] = 'Settings';
$string['shortnametaken'] = 'That shortname is already in use';
$string['show'] = 'Show';
$string['showbasedonx'] = 'Show records based on {$a}';
$string['showbycompletedorg'] = 'Show by completed organisation';
$string['showbycurrentorg'] = 'Show by current organisation';
$string['showbycurrentpos'] = 'Show by current position';
$string['showbydate'] = 'Show by date';
$string['showbytag'] = 'Show by tag';
$string['showbytrainer'] = 'Show by trainer';
$string['showbyuser'] = 'Show by user';
$string['showbyx'] = 'Show by {$a}';
$string['showhidecolumns'] = 'Show/Hide Columns';
$string['showing'] = 'Showing';
$string['showrecordsbeloworgonly'] = 'Staff below any of the user\'s assigned organisations';
$string['showrecordsbelowposonly'] = 'Staff below any of the user\'s assigned positions';
$string['showrecordsinorg'] = 'Staff in any of the user\'s assigned organisations';
$string['showrecordsinorgandbelow'] = 'Staff at or below any of the user\'s assigned organisations';
$string['showrecordsinpos'] = 'Staff in any of the user\'s assigned positions';
$string['showrecordsinposandbelow'] = 'Staff at or below any of the user\'s assigned positions';
$string['showtotalcount'] = 'Display a total count of records';
$string['showtotalcount_help'] = 'When enabled the report will display a total count of records when not filtered. For performance reasons we recommend you leave this setting off.';
$string['sidebarfilter'] = 'Sidebar filter options';
$string['sidebarfilterdesc'] = 'The choices below determine which filters appear to the side of the report and how they are labelled.';
$string['sidebarfilter_help'] = '**Sidebar filter options** allows you to customise the filters that appear to the side of your report. Sidebar filters have
instant filtering enabled - each change made to a filter will automatically refresh the report data (if certain system
requirements are met). The available filters are determined by the **Source** of the report. Only some types of filters can
be placed in the sidebar, so not all standard filters can be placed there. Each report source has a set of default filters.

A filter can appear in either the standard filter area or the sidebar filter area, but not both. Filters can be added, sorted
and removed.

**Adding filters:** To add a new filter to the report choose the required filter from the **Add another filter...** dropdown
menu and click **Save changes**. When **Advanced** is checked the filter will not appear in the **Search by** box by default,
you can click **Show advanced** when viewing a report to see these filters.

**Moving filters:** The filters will appear in the **Search by** box in the order they are listed. Use the up and down arrows
to change the order.

**Deleting filters:** Click the **Delete** button (the cross icon) to the right of the report filter to remove that filter
from the report.

**Changing multiple filter types:** You can modify multiple filter types at the same time by selecting a different filter
from the dropdown menu and clicking **Save changes**.';
$string['sorting'] = 'Sorting';
$string['source'] = 'Source';
$string['standardfilter'] = 'Standard filter options';
$string['standardfilterdesc'] = 'The choices below determine which filter will appear above the report and how they are labelled.';
$string['standardfilter_help'] = '**Standard filter options** allows you to customise the filters that appear above your report. The available filters are
determined by the **Source** of the report. Each report source has a set of default filters.

A filter can appear in either the standard filter area or the sidebar filter area, but not both. Filters can be added, sorted
and removed.

**Adding filters:** To add a new filter to the report choose the required filter from the **Add another filter...** dropdown
menu and click **Save changes**. When **Advanced** is checked the filter will not appear in the **Search by** box by default,
you can click **Show advanced** when viewing a report to see these filters.

**Moving filters:** The filters will appear in the **Search by** box in the order they are listed. Use the up and down arrows
to change the order.

**Deleting filters:** Click the **Delete** button (the cross icon) to the right of the report filter to remove that filter
from the report.

**Changing multiple filter types:** You can modify multiple filter types at the same time by selecting a different filter
from the dropdown menu and clicking **Save changes**.';
$string['status'] = 'Status';
$string['suspendrecord'] = 'Suspend {$a}';
$string['suspendeduser'] = 'Suspended';
$string['systemcontext'] = 'Users must have role in the system context';
$string['systemusers'] = 'System users';
$string['tagenable'] = 'Show records by tag';
$string['taggedx'] = 'Tagged \'{$a}\'';
$string['tagids'] = 'Tag IDs';
$string['tags'] = 'Tags';
$string['thefuture'] = 'The future';
$string['thepast'] = 'The past';
$string['toolbarsearch'] = 'Toolbar search box';
$string['toolbarsearch_help'] = '**Toolbar search box** allows you to customise the fields that will be searched when using the search box in the report header.
The available filters are determined by the **Source** of the report. Each report source has a set of default fields. If no
fields are specified then the search box is not displayed.

You can specify that a field is searched, even if it is not included as a column in the report, although this may cause
confusion for users if they cannot see why a particular record is included in their search results.

**Adding search fields:** To add a new search field to the report choose the required field from the **Add another search
field...** dropdown menu and click **Save changes**.

**Delete search fields:** Click the **Delete** button (the cross icon) to the right of the report field to remove that
search field.

**Changing multiple search fields:** You can modify multiple search fields at the same time by selecting a different field
from the dropdown menu and clicking **Save changes**.';
$string['toolbarsearchdesc'] = 'The choices below determine which fields will be searched when a user enters text in the toolbar search box.';
$string['toolbarsearchdisabled'] = 'Disable toolbar search box';
$string['toolbarsearchdisabled_help'] = 'Checking this box will prevent the search box from appearing in the header of the
report. This has the same result as removing all search fields.';
$string['toolbarsearchtextiscontainedinsingle'] = '"{$a->searchtext}" is contained in the column "{$a->field}"';
$string['toolbarsearchtextiscontainedinmultiple'] = '"{$a}" is contained in one or more of the following columns: ';
$string['totarasyncenableduser'] = 'HR Import';
$string['trainerownrecords'] = 'Show records where the user is the trainer';
$string['trainerstaffrecords'] = 'Records where one of the user\'s direct reports is the trainer';
$string['transformtypeday_heading'] = '{$a} - day of month';
$string['transformtypeday_name'] = 'Day of month';
$string['transformtypedayyear_heading'] = '{$a} - day of year';
$string['transformtypedayyear_name'] = 'Day of year';
$string['transformtypehour_heading'] = '{$a} - hour of day';
$string['transformtypehour_name'] = 'Hour of day';
$string['transformtypemonth_heading'] = '{$a} - month of year';
$string['transformtypemonth_name'] = 'Month of year';
$string['transformtypemonthtextual_heading'] = '{$a} - month of year';
$string['transformtypemonthtextual_name'] = 'Month of year(textual)';
$string['transformtypequarter_heading'] = '{$a} - quarter of year';
$string['transformtypequarter_name'] = 'Quarter of year';
$string['transformtypeweekday_heading'] = '{$a} - week day';
$string['transformtypeweekday_name'] = 'Week day';
$string['transformtypeweekdaytextual_heading'] = '{$a} - week day';
$string['transformtypeweekdaytextual_name'] = 'Week day(textual)';
$string['transformtypeyear_heading'] = '{$a}';
$string['transformtypeyear_name'] = 'Date YYYY';
$string['transformtypeyearmonth_heading'] = '{$a}';
$string['transformtypeyearmonth_name'] = 'Date YYYY-MM';
$string['transformtypeyearmonthday_heading'] = '{$a}';
$string['transformtypeyearmonthday_name'] = 'Date YYYY-MM-DD';
$string['transformtypeyearquarter_heading'] = '{$a} - year quarter';
$string['transformtypeyearquarter_name'] = 'Date YYYY-Q';
$string['type'] = 'Type';
$string['type_cohort'] = 'Audience';
$string['type_comp_type'] = 'Competency custom fields';
$string['type_course'] = 'Course';
$string['type_course_category'] = 'Category';
$string['type_course_custom_fields'] = 'Course Custom Fields';
$string['type_dp_plan_evidence'] = 'Evidence';
$string['type_facetoface'] = 'Seminar';
$string['type_job_assignment'] = 'All User\'s Job Assignments';
$string['type_org_type'] = 'Organisation custom fields';
$string['type_pos_type'] = 'Position custom fields';
$string['type_prog'] = 'Program';
$string['type_saved'] = 'Saved search';
$string['type_statistics'] = 'Statistics';
$string['type_tags'] = 'Tags';
$string['type_user'] = 'User';
$string['type_userto'] = 'Recipient User';
$string['type_user_profile'] = 'User Profile';
$string['unconfirmeduser'] = 'Unconfirmed';
$string['uniquename'] = 'Unique Name';
$string['unknown'] = 'Unknown';
$string['unknownlanguage'] = 'Unknown Language ({$a})';
$string['uninstalledlanguage'] = 'Uninstalled Language {$a->name} ({$a->code})';
$string['updatescheduledreport'] = 'Successfully updated Scheduled Report';
$string['useclonedb'] = 'Use database clone';
$string['useclonedb_help'] = 'If enabled the report will use the database clone. This may improve performance, but the data may be outdated if the clone is not synchronised properly with the main database. This option is not compatible with standard report caching.';
$string['useclonedbheader'] = 'Database connection';
$string['useralternatename'] = 'User Alternate Name';
$string['useraddress'] = 'User\'s Address';
$string['userauth'] = 'User\'s Authentication Method';
$string['usercity'] = 'User\'s City';
$string['usercohortids'] = 'User audience IDs';
$string['usercountry'] = 'User\'s Country';
$string['userdataitemglobal_report_restrictions'] = 'Global report restrictions';
$string['userdataitemglobal_report_restrictions_help'] = 'When purging, both the user’s assignment to appear in restricted reports and their access to restricted reports will be removed.';
$string['userdataitemsaved_search_private'] = 'Private saved searches';
$string['userdataitemsaved_search_private_help'] = 'When purging, this will also remove scheduled reports that are based on these saved searches.';
$string['userdataitemsaved_search_public'] = 'Public saved searches';
$string['userdataitemsaved_search_public_help'] = 'When purging, this will also remove scheduled reports that are based on these saved searches.';
$string['userdataitemscheduled_reports'] = 'Scheduled reports';
$string['userdepartment'] = 'User\'s Department';
$string['userdirectreports'] = 'Records for user\'s direct reports for any of the user\'s job assignments';
$string['useremail'] = 'User\'s Email';
$string['useremailprivate'] = 'Email is private';
$string['useremailunobscured'] = 'User\'s Email (ignoring user display setting)';
$string['userfirstaccess'] = 'User First Access';
$string['userfirstaccessrelative'] = 'User First Access (Relative)';
$string['userfirstname'] = 'User First Name';
$string['userfirstnamephonetic'] = 'User First Name - phonetic';
$string['userfullname'] = 'User\'s Fullname';
$string['usergenerated'] = 'User generated';
$string['usergeneratedreports'] = 'User generated Reports';
$string['userid'] = 'User ID';
$string['useridnumber'] = 'User ID Number';
$string['userincohort'] = 'User is a member of audience';
$string['userindirectreports'] = 'Records for user\'s indirect reports for any of the user\'s job assignments';
$string['userinstitution'] = 'User\'s Institution';
$string['userlang'] = 'User\'s Preferred Language';
$string['userlastlogin'] = 'User Last Login';
$string['userlastloginrelative'] = 'User Last Login (Relative)';
$string['userlastname'] = 'User Last Name';
$string['userlastnamephonetic'] = 'User Last Name - phonetic';
$string['usermiddlename'] = 'User Middle Name';
$string['username'] = 'Username';
$string['usernamelink'] = 'User\'s Fullname (linked to profile)';
$string['usernamelinkicon'] = 'User\'s Fullname (linked to profile with icon)';
$string['userownrecords'] = 'A user\'s own records';
$string['userphone'] = 'User\'s Phone number';
$string['userreportheading'] = 'Browse list of users: {$a}';
$string['userreports'] = 'User reports';
$string['usersappraisernameall'] = 'User\'s Appraiser Name(s)';
$string['usersjobtitle'] = 'User\'s Job Title';
$string['usersjobtitlenameall'] = 'User\'s Job Title(s)';
$string['usersjobstartdateall'] = 'User\'s Job Start Date(s)';
$string['usersjobenddateall'] = 'User\'s Job End Date(s)';
$string['usersmanagerall'] = 'User\'s Manager(s)';
$string['usersmanagerfirstname'] = 'User\'s Manager\'s First Name';
$string['usersmanagerfirstnameall'] = 'User\'s Manager\'s First Name(s)';
$string['usersmanageremail'] = 'User\'s Manager Email';
$string['usersmanageremailunobscured'] = 'User\'s Manager\'s Email (ignoring user display setting)';
$string['usersmanagerid'] = 'User\'s Manager ID';
$string['usersmanageridall'] = 'User\'s Manager ID(s)';
$string['usersmanageridnumber'] = 'User\'s Manager ID Number';
$string['usersmanageridnumberall'] = 'User\'s Manager ID Number(s)';
$string['usersmanagerlastname'] = 'User\'s Manager\'s Last Name';
$string['usersmanagerlastnameall'] = 'User\'s Manager\'s Last Name(s)';
$string['usersmanagername'] = 'User\'s Manager Name';
$string['usersmanagernameall'] = 'User\'s Manager Name(s)';
$string['usersmanagerobsemailall'] = 'User\'s Manager Email(s)';
$string['usersmanagerunobsemailall'] = 'User\'s Manager Email(s) (ignoring user display setting)';
$string['usersnumjobassignments'] = 'Number of Job Assignments';
$string['usersorg'] = 'User\'s Organisation';
$string['usersorgall'] = 'User\'s Organisation(s)';
$string['usersorgbasic'] = 'User\'s Organisation (basic)';
$string['usersorgframedescall'] = 'User\'s Organisation Framework Description(s)';
$string['usersorgframeidall'] = 'User\'s Organisation Framework ID(s)';
$string['usersorgframeidnumberall'] = 'User\'s Organisation Framework ID Number(s)';
$string['usersorgframenameall'] = 'User\'s Organisation Framework(s)';
$string['usersorgid'] = 'User\'s Organisation ID';
$string['usersorgidall'] = 'User\'s Organisation ID(s)';
$string['usersorgidnumber'] = 'User\'s Organisation ID Number';
$string['usersorgidnumberall'] = 'User\'s Organisation ID Number(s)';
$string['usersorgmulti'] = 'User\'s Organisation (multi-item)';
$string['usersorgname'] = 'User\'s Organisation Name';
$string['usersorgnameall'] = 'User\'s Organisation Name(s)';
$string['usersorgpathids'] = 'User\'s Organisation Path IDs';
$string['usersorgtypeall'] = 'User\'s Organisation Type(s)';
$string['userspos'] = 'User\'s Position';
$string['usersposall'] = 'User\'s Position(s)';
$string['usersposbasic'] = 'User\'s Position (basic)';
$string['usersposframedescall'] = 'User\'s Position Framework Description(s)';
$string['usersposframeidall'] = 'User\'s Position Framework ID(s)';
$string['usersposframeidnumberall'] = 'User\'s Position Framework ID Number(s)';
$string['usersposframenameall'] = 'User\'s Position Framework(s)';
$string['usersposid'] = 'User\'s Position ID';
$string['usersposidall'] = 'User\'s Position ID(s)';
$string['usersposidnumber'] = 'User\'s Position ID Number';
$string['usersposidnumberall'] = 'User\'s Position ID Number(s)';
$string['usersposname'] = 'User\'s Position Name';
$string['usersposnameall'] = 'User\'s Position Name(s)';
$string['usersposmulti'] = 'User\'s Position (multi-item)';
$string['userspospathids'] = 'User\'s Position Path IDs';
$string['userspostypeall'] = 'User\'s Position Type(s)';
$string['userstatus'] = 'User Status';
$string['userstempmanagernameall'] = 'User\'s Temporary Manager Name(s)';
$string['usersystemrole'] = 'User System Role';
$string['usertempreports'] = 'Records for user\'s temporary reports for any of the user\'s job assignments';
$string['usertimecreated'] = 'User Creation Time';
$string['usertimemodified'] = 'User Last Modified';
$string['undeleterecord'] = 'Undelete {$a}';
$string['unsuspendrecord'] = 'Unsuspend {$a}';
$string['unlockrecord'] = 'Unlock {$a}';
$string['value'] = 'Value';
$string['viewreport'] = 'View This Report';
$string['viewsavedsearch'] = 'View a saved search...';
$string['warngroupaggregation'] = 'This report is using data aggregation internally, custom aggregation of columns may produce unexpected results.';
$string['warngrrvisibility'] = 'Recipients of this report will be sent the report as YOU see it. If you have access to different data, ensure you are happy for recipients to see what you see.';
$string['warnrequiredcolumns'] = 'This report uses some columns internally in order to obtain the data. Custom aggregation of columns may produce unexpected results.';
$string['weekly'] = 'Weekly';
$string['withcontentrestrictionall'] = 'Show records matching <strong>all</strong> of the checked criteria below';
$string['withcontentrestrictionany'] = 'Show records matching <strong>any</strong> of the checked criteria below';
$string['withrestriction'] = 'Only certain users can view this report (see below)';
$string['xlsformat'] = 'Excel format';
$string['xofyrecord'] = '{$a->filtered} of {$a->unfiltered} record shown';
$string['xofyrecords'] = '{$a->filtered} of {$a->unfiltered} records shown';
$string['xrecord'] = '{$a} record shown';
$string['xrecords'] = '{$a} records shown';

/**
 * Deprecated strings.
 *
 * @deprecated since Totara 10.0.
 */

$string['allreports'] = 'All Reports';
$string['isrelativetotoday'] = ' (date of report generation)';
$string['managereports'] = 'Manage reports';
$string['pdf_landscapeformat'] = 'pdf format (landscape)';
$string['pdf_portraitformat'] = 'pdf format (portrait)';

/**
 * @deprecated since Totara 11.0.
 */

$string['activeonly'] = 'Active users only';
$string['deletedonly'] = 'Deleted users only';
$string['error:reporturlnotset'] = 'The url property for report {$a} is missing, please ask your developers to check your code';
$string['suspendedonly'] = 'Suspended users only';

// Custom column add by Yashco Systems
$string['catcertifstatusname'] = 'Category Certification Status Name';
$string['catcertifstatus'] = 'Category Certification Status';
$string['subcatstausid'] = 'Sub Category Status ID';
$string['subcatcertifstatus'] = 'Sub Category Certificate Status';
$string['parsubcatcertifid'] = 'Parent Category Certificate ID';
$string['parentcatid'] = 'Parent Category ID';
$string['coursecategoryparent'] = 'Course Category Parent';
$string['aggregatetypecertcount_heading'] = 'Certification Count of {$a}';
$string['aggregatetypecertcount_name'] = 'Certification Count';
$string['aggregatetypeparentcertcount_heading'] = 'Parent Certification Count of {$a}';
$string['aggregatetypeparentcertcount_name'] = 'Parent Certification Count';
$string['aggregatetypegoal_achieve_heading'] = 'Goal Achieve Status of {$a}';
$string['aggregatetypegoal_achieve_name'] = 'Goal Achieve Status';
$string['aggregatetypecatcertifstatus_heading'] = 'Category Certification Status of {$a}';
$string['aggregatetypecatcertifstatus_name'] = 'Category Certification Status';
$string['aggregatetypeparentcatcertifstatus_heading'] = 'Parent Category Certification Status of {$a}';
$string['aggregatetypeparentcatcertifstatus_name'] = 'Parent Category Certification Status';
$string['aggregatetypecertifstatus7_heading'] = 'Certification Status 7 of {$a}';
$string['aggregatetypecertifstatus7_name'] = 'Certification Status 7';
$string['aggregatetypesubcatpercent_heading'] = 'Status of {$a}';
$string['aggregatetypesubcatpercent_name'] = 'Sub-Category';
$string['aggregatetypesubcatpercentiscertif_heading'] = 'Name of {$a}';
$string['aggregatetypesubcatpercentiscertif_name'] = 'Sub-Category of Parent';
$string['aggregatetypeiscertified6_heading'] = 'Sum of {$a}';
$string['aggregatetypeiscertified6_name'] = 'Sub-Category Goal Status';
$string['aggregatetypeiscertified5_heading'] = 'All of {$a}';
$string['aggregatetypeiscertified5_name'] = 'Is Certification Name';

$string['aggregatetypeiscertifiedorg_heading'] = 'Certif Count of {$a}';
$string['aggregatetypeiscertifiedorg_name'] = 'Org Compliant';

$string['aggregatetypeiscertifiedorgnoncomp_heading'] = 'Certif Count of {$a}';
$string['aggregatetypeiscertifiedorgnoncomp_name'] = 'Org Non-Compliant';

$string['aggregatetypeorgtarget_heading'] = 'Percentage of {$a}';
$string['aggregatetypeorgtarget_name'] = 'Org Target';

$string['aggregatetypeorgidname_heading'] = 'Hierarchy of {$a}';
$string['aggregatetypeorgidname_name'] = 'Org ID Name';