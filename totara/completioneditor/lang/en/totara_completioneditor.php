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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

$string['allrequired'] = 'all required';
$string['anyrequired'] = 'any required';
$string['certificationcompletionedit'] = 'Edit certification completion';
$string['certificationcompletionnoedit'] = 'You can\'t edit this certification completion record';
$string['checkcoursecompletions'] = 'Check course completions';
$string['completionchangessaved'] = 'Completion changes have been saved';
$string['completionchangeuser'] = 'For the user, the consequences of these changes are:';
$string['completioneditor:editcoursecompletion'] = 'Can edit users\' course completion data';
$string['completionfilterbycourse'] = 'Filter by course: {$a}';
$string['completionfilterbyuser'] = 'Filter by user: {$a}';
$string['completionofcoursex'] = 'Completion of course: {$a}';
$string['completionprobleminformation'] = 'Information';
$string['completionrecordcountproblem'] = 'Problem records: {$a}';
$string['completionrecordcounttotal'] = 'Total records: {$a}';
$string['completionsforuserin'] = 'Completion records for {$a->user} in {$a->object}';
$string['completionswithproblems'] = 'Completion records with problems';
$string['completionupdatecancelled'] = 'Completion update cancelled';
$string['count'] = 'Count';
$string['coursecompletioncritcomplid'] = 'CCCCID';
$string['coursecompletioncriteria'] = 'Course completion criteria';
$string['coursecompletioncriteriaandmodules'] = 'Criteria and Activities';
$string['coursecompletioncriteriacompletecopiedfrommodule'] = 'Complete if activity is complete';
$string['coursecompletioncriteriacompletecopiedfrommodulenotfailed'] = 'Complete if \'Activity status\' is complete and not failed';
$string['coursecompletioncriteriastatus'] = 'Criteria status';
$string['coursecompletioncriteriatimecompleted'] = 'Criteria time completed';
$string['coursecompletioncriteriatimecompletedcopiedfrommodule'] = 'Copied from activity time completed';
$string['coursecompletioncriteriatimecompletedcopiedfrommodulenotfailed'] = 'Copied from \'Activity time completed\' if not failed';
$string['coursecompletioncurrent'] = 'Current completion';
$string['coursecompletioncurrentrecord'] = 'Current course completion record';
$string['coursecompletiondelete'] = 'Are you sure you want to delete this course completion record? This cannot be undone. Associated logs, criteria, activity and history completion data will be kept.';
$string['coursecompletiondeleted'] = 'Completion data for this user in this course has been deleted';
$string['coursecompletiondoesntexist'] = 'The user has no current course completion record.';
$string['coursecompletionedit'] = 'Edit course completion';
$string['coursecompletioneditor'] = 'Completion editor';
$string['coursecompletiongrade'] = 'Grade';
$string['coursecompletionhistory'] = 'Course completion history';
$string['coursecompletionhistoryadd'] = 'Add history';
$string['coursecompletionhistorydelete'] = 'Delete completion history';
$string['coursecompletionhistorydeleted'] = 'Course completion history deleted';
$string['coursecompletionhistoryedit'] = 'Edit history';
$string['coursecompletionhistoryid'] = 'CCHID';
$string['coursecompletionmodulecompletion'] = 'Activity completion';
$string['coursecompletionmodulecriteriaeditingmode'] = 'Editing mode';
$string['coursecompletionmodulecriteriaeditingmodemodule'] = 'Use activity completion';
$string['coursecompletionmodulecriteriaeditingmodeseparate'] = 'Use separate completion data';
$string['coursecompletionmodulescompletionid'] = 'CMCID';
$string['coursecompletionmodulesnonecompletable'] = 'The course has no activities';
$string['coursecompletionmodulestatus'] = 'Activity status';
$string['coursecompletionmoduletimecompleted'] = 'Activity time completed';
$string['coursecompletionorphanedcritcompldelete'] = 'Delete orphaned criteria completion';
$string['coursecompletionorphanedcritcompldeleted'] = 'Orphaned criteria completion deleted';
$string['coursecompletionorphanedcritcompls'] = 'Orphaned course criteria completions';
$string['coursecompletionorphanedcritcomplsexplained'] = 'These records should not exist. The course completion criteria which they related to no longer exist. You may be able to identify the course completion criteria that these records belonged to by looking for the CCCCID in the Transactions log. If the course completion criteria which they related to are re-added to the course completion, these records will not be automatically matched to them. For these reasons, these records should be deleted.';
$string['coursecompletionorphanedcritcomplunknown'] = 'Unknown';
$string['coursecompletionprogsandcerts'] = 'Related programs and certifications';
$string['coursecompletionrpl'] = 'RPL';
$string['coursecompletionrplgrade'] = 'RPL Grade (%)';
$string['coursecompletionsaveconfirm'] = 'Changing the completion record may lead to changes in course completions, the completion state of other activities, conditional access, and any other systems that are dependent upon or are observing the users completion state.';
$string['coursecompletionstatus'] = 'Course completion status';
$string['coursecompletionstatus_help'] = 'Select the status of the user\'s course. The options below may be changed or limited depending on this choice.';
$string['coursecompletiontimecompleted'] = 'Time completed';
$string['coursecompletiontimeenrolled'] = 'Time enrolled';
$string['coursecompletiontimestarted'] = 'Time started';
$string['coursecompletionviewed'] = 'Viewed';
$string['coursemembership'] = 'Course membership';
$string['cronautomatic'] = 'Cron / Automatic';
$string['deletecoursecompletion'] = 'Delete the current course completion record';
$string['edit'] = 'Edit';
$string['error:impossibledatasubmitted'] = 'The data submitted is not valid and cannot be processed';
$string['error:info_unknowncombination'] = 'There is no specific information relating to this error or particular combination of errors. It may be that it is a combination of other explainable errors. The records can be fixed manually, but care should be taken to ensure that the correct solution is chosen. It should be reported to Totara support if there are many instances or if it occurs frequently.';
$string['fixconfirmone'] = '<p>Are you sure you want to apply the selected fix to this completion record?</p><p>The action will be logged, but cannot be automatically undone.</p>';
$string['fixconfirmsome'] = '<p>Are you sure you want to apply the selected fix to <strong>all completion records in this list</strong>?</p><p>If some records need to be fixed by a different method (such as by a different fix, or manually) then you should select <strong>No</strong>.</p><p>The action will be logged for each completion record, but cannot be automatically undone.</p>';
$string['fixconfirmtitle'] = 'Confirm auto-fix records';
$string['hasproblem'] = 'Has problem?';
$string['history'] = 'History';
$string['invalidstatus'] = 'Invalid - Select a valid status';
$string['notapplicable'] = 'Not applicable';
$string['notapplicableshort'] = 'N/A';
$string['notenrolled'] = 'The user is no longer enrolled in this course';
$string['overview'] = 'Overview';
$string['pluginname'] = 'Completion editor';
$string['problem'] = 'Problem';
$string['programcompletionedit'] = 'Edit program completion';
$string['programcompletionnoedit'] = 'You can\'t edit this program completion record';
$string['progorcerttimecompleted'] = 'Time completed or certified';
$string['transactiondatetime'] = 'Date / time';
$string['transactions'] = 'Transactions';
$string['transactionuser'] = 'Change made by';
$string['unknown'] = 'Unknown';
