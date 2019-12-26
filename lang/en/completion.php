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
 * Strings for core_completion subsystem.
 *
 * @package     core_completion
 * @category    string
 * @copyright   &copy; 2008 The Open University
 * @author      Sam Marshall
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['achievinggrade'] = 'Achieving grade';
$string['achievedgrade'] = 'Achieved grade';
$string['activities'] = 'Activities';
$string['activityaggregation'] = 'Condition requires';
$string['activityaggregation_all'] = 'ALL selected activities to be completed';
$string['activityaggregation_any'] = 'ANY selected activities to be completed';
$string['activitiescompleted'] = 'Activity completion';
$string['activitiescompletednote'] = 'Note: Activity completion must be set for an activity to appear in the above list.';
$string['activitycompletion'] = 'Activity completion';
$string['aggregationmethod'] = 'Aggregation method';
$string['all'] = 'All';
$string['any'] = 'Any';
$string['approval'] = 'Approval';
$string['badautocompletion'] = 'When you select automatic completion, you must also enable at least one requirement (below).';
$string['complete'] = 'Complete';
$string['completed'] = 'Completed';
$string['completedunlocked'] = 'Completion options unlocked';
$string['completedunlockedtext'] = 'When you save changes, completion state for all learners will be erased. If you change your mind about this, do not save the form.';
$string['completedwarning'] = 'Completion options locked';
$string['completedwarningtext'] = 'This activity has already been marked as completed for {$a} participant(s). Changing completion options will erase their completion state and may cause confusion. Thus the options have been locked and should not be unlocked unless absolutely necessary.';
$string['completeviarpl'] = 'Complete via rpl';
$string['completedviarpl-on'] = 'Completed via rpl ({$a->rpl}) on {$a->timecompleted}';
$string['completed-on'] = 'Completed on {$a->timecompleted}';
$string['completion'] = 'Completion tracking';
$string['completion-alt-auto-enabled'] = 'The system marks this item complete according to conditions: {$a}';
$string['completion-alt-auto-fail'] = 'Completed: {$a} (did not achieve pass grade)';
$string['completion-alt-auto-n'] = 'Not completed: {$a}';
$string['completion-alt-auto-pass'] = 'Completed: {$a} (achieved pass grade)';
$string['completion-alt-auto-y'] = 'Completed: {$a}';
$string['completion-alt-manual-enabled'] = 'Learners can manually mark this item complete: {$a}';
$string['completion-alt-manual-n'] = 'Not completed: {$a}. Select to mark as complete.';
$string['completion-alt-manual-y'] = 'Completed: {$a}. Select to mark as not complete.';
$string['completion-fail'] = 'Completed (did not achieve pass grade)';
$string['completion-n'] = 'Not completed';
$string['completion-pass'] = 'Completed (achieved pass grade)';
$string['completion-title-manual-n'] = 'Mark as complete: {$a}';
$string['completion-title-manual-y'] = 'Mark as not complete: {$a}';
$string['completion-y'] = 'Completed';
$string['completion_automatic'] = 'Show activity as complete when conditions are met';
$string['completion_help'] = 'If enabled, activity completion is tracked, either manually or automatically, based on certain conditions. Multiple conditions may be set if desired. If so, the activity will only be considered complete when ALL conditions are met.

A tick next to the activity name on the course page indicates when the activity is complete.';
$string['completion_link'] = 'activity/completion';
$string['completion_manual'] = 'Learners can manually mark the activity as completed';
$string['completion_none'] = 'Do not indicate activity completion';
$string['completionactivitydefault'] = 'Use activity default';
$string['completioncriteriachanged'] = 'Course completion criteria changes have been saved. If any active participants already match the criteria for completion then their status will be updated on the next cron run';
$string['completiondefault'] = 'Default completion tracking';
$string['completiondisabled'] = 'Disabled, not shown in activity settings';
$string['completionenabled'] = 'Enabled, control via completion and activity settings';
$string['completionexpected'] = 'Expect completed on';
$string['completionexpected_help'] = 'This setting specifies the date when the activity is expected to be completed. The date is not shown to learners and is only displayed in the activity completion report.';
$string['completionexpectedfor'] = 'Expected completion for \'{$a->modulename}\' activity \'{$a->instancename}\'';
$string['completionicons'] = 'Completion tick boxes';
$string['completionicons_help'] = 'A tick next to activity name indicates completion. This helps track your progress through courses accurately.

Round checkbox / auto tick: Applied automatically once you complete the activity and meet required criteria.

Square checkbox / manual: You can tick it to mark activity as complete, or un-tick to mark it incomplete.';
$string['completionmenuitem'] = 'Completion';
$string['completionnotenabled'] = 'Completion is not enabled';
$string['completionnotenabledforcourse'] = 'Completion is not enabled for this course';
$string['completionnotenabledforsite'] = 'Completion is not enabled for this site';
$string['completionondate'] = 'Date';
$string['completionondatevalue'] = 'User must remain enrolled until';
$string['completionprogressonview'] = 'Mark as In Progress on first view';
$string['completionprogressonviewhelp'] = 'Mark course completion status as In Progress as soon as learners view the course the first time (instead of when they meet the first criterion).';
$string['completionprogressonview_help'] = 'Mark course completion status as **In Progress** as soon as learners view the course the first time (instead of when they meet the first criterion).';
$string['completionduration'] = 'Enrolment';
$string['completionsettingslocked'] = 'Completion settings locked';
$string['completionsettingsunlocked'] = 'Completion settings unlocked';
$string['completionusegrade'] = 'Require grade';
$string['completionusegrade_desc'] = 'Learner must receive a grade to complete this activity';
$string['completionusegrade_help'] = 'If enabled, the activity is considered complete when a learner receives a grade. Pass and fail icons may be displayed if a pass grade for the activity has been set.';
$string['completionview'] = 'Require view';
$string['completionview_desc'] = 'Learner must view this activity to complete it';
$string['completionview_help'] = 'If this activity has **Require view** in combination with other criteria, the user will only be marked complete if they view the activity at a moment when all other criteria are already complete.

For example, consider an activity with **Require view** and **Require grade** both enabled. If a user first viewed the activity, then later was granted a grade by an administrator, the user would need to view the activity again (after the grade was received) to trigger activity completion.

For this reason, it is recommended to not use **Require view** in combination with other criteria, unless this specific behaviour is required. In most cases, the other criteria should be sufficient. E.g. a user can\'t have been granted a grade without having viewed the activity, so including **Require view** would be superfluous.';
$string['configcompletiondefault'] = 'The default setting for completion tracking when creating new activities.';
$string['configenablecompletion'] = 'When enabled, this lets you turn on completion tracking (progress) features at course level.';
$string['configenablecourserpl'] = 'When enabled, a course can be marked as completed by assigning the user a Record of Prior Learning.';
$string['configenablemodulerpl'] = 'When enabled for a module, any Course Completion criteria for that module type can be marked as completed by assigning the user a Record of Prior Learning.';
$string['confirmselfcompletion'] = 'Confirm self completion';
$string['courseaggregation'] = 'Condition requires';
$string['courseaggregation_all'] = 'ALL selected courses to be completed';
$string['courseaggregation_any'] = 'ANY selected courses to be completed';
$string['coursealreadycompleted'] = 'You have already completed this course';
$string['coursecomplete'] = 'Course complete';
$string['coursecompleted'] = 'Course completed';
$string['coursecompletion'] = 'Course completion';
$string['coursecompletioncondition'] = 'Condition: {$a}';
$string['coursegrade'] = 'Course grade';
$string['coursesavailable'] = 'Courses available';
$string['coursesavailableexplaination'] = 'Note: Course completion conditions must be set for a course to appear in the above list.';
$string['courserpl'] = 'Course RPL';
$string['courserplorallcriteriagroups'] = 'RPL for course or <br />all critera groups';
$string['courserploranycriteriagroup'] = 'RPL for course or <br />any critera group';
$string['criteria'] = 'Criteria';
$string['criteriagroup'] = 'Criteria group';
$string['criteriarequiredall'] = 'All criteria below are required';
$string['criteriarequiredany'] = 'Any criteria below are required';
$string['csvdownload'] = 'Download in spreadsheet format (UTF-8 .csv)';
$string['datepassed'] = 'Date passed';
$string['days'] = 'Days';
$string['daysoftotal'] = '{$a->days} of {$a->total}';
$string['deletecompletiondata'] = 'Delete completion data';
$string['dependencies'] = 'Dependencies';
$string['dependenciescompleted'] = 'Completion of other courses';
$string['editcoursecompletionsettings'] = 'Edit course completion settings';
$string['enablecourserpl'] = 'Enable RPL for courses';
$string['enablemodulerpl'] = 'Enable RPL for modules';
$string['enablecompletion'] = 'Enable completion tracking';
$string['enablecompletion_help'] = 'If enabled, activity completion conditions may be set in the activity settings and/or course completion conditions may be set.';
$string['enrolmentduration'] = 'Enrolment duration';
$string['enrolmentdurationlength'] = 'User must remain enrolled for';
$string['err_noactivities'] = 'Completion information is not enabled for any activity, so none can be displayed. You can enable completion information by editing the settings for an activity.';
$string['err_nocourses'] = 'Course completion is not enabled for any other courses, so none can be displayed. You can enable course completion in the course settings.';
$string['err_nograde'] = 'A course pass grade has not been set for this course. To enable this criteria type you must create a pass grade for this course.';
$string['err_noroles'] = 'There are no roles with the capability moodle/course:markcomplete in this course.';
$string['err_nousers'] = 'There are no learners on this course or group for whom completion information is displayed. (By default, completion information is displayed only for learners, so if there are no learners, you will see this error. Administrators can alter this option via the admin screens.)';
$string['err_settingslocked'] = 'One or more learners have already completed a criterion so the settings have been locked. Unlocking the completion criteria settings will delete any existing user data and may cause confusion.';
$string['err_settingsunlockable'] = '<p>Modifying course completion criteria after some users have already completed the course is not recommended since it means different users will be marked as complete for different reasons.</p><p>At this point you can choose to delete all completion records for users who have already achieved this course. Their completion status will be recalculated using the new criteria next time the cron runs, so they may be marked as complete again.</p><p>Alternatively you can choose to keep all existing course completion records and accept that different users may have received their status for different accomplishments.</p>';
$string['err_system'] = 'An internal error occurred in the completion system. (System administrators can enable debugging information to see more detail.)';
$string['error:cannotarchiveprogcourse'] = 'Courses which are a part of a Program or Certification can not be manually archived.';
$string['error:coursestatuscomplete-rplgradenotempty'] = 'RPL grade should be empty when user is complete (without RPL).';
$string['error:coursestatuscomplete-rplnotempty'] = 'RPL should be empty when user is complete (without RPL).';
$string['error:coursestatuscomplete-timecompletedempty'] = 'Time completed should not be empty when user is complete.';
$string['error:coursestatusinprogress-rplgradenotempty'] = 'RPL grade should be empty when user is in progress.';
$string['error:coursestatusinprogress-rplnotempty'] = 'RPL should be empty when user is in progress.';
$string['error:coursestatusinprogress-timecompletednotempty'] = 'Time completed should be empty when user is in progress.';
$string['error:coursestatusnotyetstarted-rplgradenotempty'] = 'RPL grade should be empty when user is not yet started.';
$string['error:coursestatusnotyetstarted-rplnotempty'] = 'RPL should be empty when user is not yet started.';
$string['error:coursestatusnotyetstarted-timecompletednotempty'] = 'Time completed should be empty when user is not yet started.';
$string['error:coursestatusrplcomplete-rplempty'] = 'RPL should not be empty when user is complete via rpl.';
$string['error:coursestatusrplcomplete-timecompletedempty'] = 'Time completed should not be empty when user is complete via rpl.';
$string['error:criteriaincomplete-rplnotempty'] = 'RPL must be empty when criteria is not complete';
$string['error:criterianotmodule-rplnotempty'] = 'RPL must be empty when criteria is not for activity completion';
$string['error:databaseupdatefailed'] = 'Database update failed';
$string['error:rplsaredisabled'] = 'Record of Prior Learning has been disabled by an Administrator';
$string['error:stateinvalid'] = 'Invalid - select a valid status';
$string['eventcoursecompleted'] = 'Course completed';
$string['eventcoursecompletionupdated'] = 'Course completion updated';
$string['eventcoursemodulecompletionupdated'] = 'Course module completion updated';
$string['excelcsvdownload'] = 'Download in Excel-compatible format (.csv)';
$string['fraction'] = 'Fraction';
$string['graderequired'] = 'Required course grade';
$string['gradexrequired'] = '{$a} required';
$string['inprogress'] = 'In progress';
$string['ihavecompleted'] = 'I have completed this activity';
$string['manualcompletionby'] = 'Manual completion by others';
$string['manualcompletionbynote'] = 'Note: The capability moodle/course:markcomplete must be allowed for a role to appear in the list.';
$string['manualselfcompletion'] = 'Manual self completion';
$string['manualselfcompletionnote'] = 'Note: The self completion block should be added to the course if manual self completion is enabled.';
$string['markcomplete'] = 'Mark complete';
$string['markedcompleteby'] = 'Marked complete by {$a}';
$string['markingyourselfcomplete'] = 'Marking yourself complete';
$string['moredetails'] = 'More details';
$string['notachievedgrade'] = 'Has not achieved grade';
$string['nocriteriaset'] = 'No completion criteria set for this course';
$string['notcompleted'] = 'Not completed';
$string['notenroled'] = 'You are not enrolled in this course';
$string['nottracked'] = 'You are currently not being tracked by completion in this course';
$string['notviewedactivity'] = 'Has not viewed the {$a}';
$string['notyetstarted'] = 'Not yet started';
$string['overallaggregation'] = 'Completion requirements';
$string['overallaggregation_all'] = 'Course is complete when ALL conditions are met';
$string['overallaggregation_any'] = 'Course is complete when ANY of the conditions are met';
$string['pending'] = 'Pending';
$string['periodpostenrolment'] = 'Period post enrolment';
$string['progress'] = 'Learner progress';
$string['progress-title'] = '{$a->user}, {$a->activity}: {$a->state} {$a->date}';
$string['progresstotal'] = 'Progress: {$a->complete} / {$a->total}';
$string['recognitionofpriorlearning'] = 'Recognition of prior learning';
$string['remainingenroledfortime'] = 'Remaining enrolled for a specified period of time';
$string['remainingenroleduntildate'] = 'Remaining enrolled until a specified date';
$string['reportpage'] = 'Showing users {$a->from} to {$a->to} of {$a->total}.';
$string['requiredcriteria'] = 'Required criteria';
$string['restoringcompletiondata'] = 'Writing completion data';
$string['roleaggregation'] = 'Condition requires';
$string['roleaggregation_all'] = 'ALL selected roles to mark when the condition is met';
$string['roleaggregation_any'] = 'ANY selected roles to mark when the condition is met';
$string['roleidnotfound'] = 'Role ID {$a} not found';
$string['rpl'] = 'RPL';
$string['save'] = 'Save';
$string['saved'] = 'Saved';
$string['seedetails'] = 'See details';
$string['selectnone'] = 'Select none';
$string['self'] = 'Self';
$string['selfcompletion'] = 'Self completion';
$string['showinguser'] = 'Showing user';
$string['showrpl'] = 'Show RPL';
$string['showrpls'] = 'Show RPLs';
$string['unenrolingfromcourse'] = 'Unenrolling from course';
$string['unenrolment'] = 'Unenrolment';
$string['unit'] = 'Unit';
$string['unlockcompletion'] = 'Unlock completion options';
$string['unlockcompletiondelete'] = 'Unlock criteria and delete existing completion data';
$string['unlockcompletionwithoutdelete'] = 'Unlock criteria without deleting';
$string['usealternateselector'] = 'Use the alternate course selector';
$string['usernotenroled'] = 'User is not enrolled in this course';
$string['viewcoursereport'] = 'View course report';
$string['viewingactivity'] = 'Viewing the {$a}';
$string['viewedactivity'] = 'Viewed the {$a}';
$string['writingcompletiondata'] = 'Writing completion data';
$string['xdays'] = '{$a} days';
$string['yourprogress'] = 'Your progress';
$string['activitiescompleted_help'] = 'These are activities that a learner is required to complete to complete this criteria. Activities are required to have **Activity completion** enabled in order to appear in this list.';
$string['activityaggregationmethod']='Aggregation method';
$string['activityaggregationmethod_help'] = 'An aggregation method of **All** means this criteria will be marked as complete when the learner has complete all the selected activities. If the aggregation method is set to **Any** only one of the selected activities will be required for the learner to complete the course.';
$string['activityrpl']='Activity RPL';
$string['afterspecifieddate']='After specified date';
$string['aggregationmethod']='Aggregation method';
$string['aggregationmethod_help'] = 'An aggregation method of **All** means the course will be marked as complete when the learner meets all the criteria set on this page. If the aggregation method is set to **Any** only one criteria type for the course will be required for the learner to complete the course.';
$string['aggregateall']='All';
$string['aggregateany']='Any';
$string['completiondependencies']='Completion dependencies';
$string['completiondependencies_help'] = 'These are courses that a learner is required to complete before this course can be marked as complete.';
$string['courseaggregationmethod']='Aggregation method';
$string['courseaggregationmethod_help'] = 'An aggregation method of **All** means this criteria will be marked as complete when the learner has complete all the selected courses. If the aggregation method is set to **Any** only one of the selected courses will be required for the learner to complete the course.';
$string['coursegrade_help'] = 'When enabled this criteria will be marked complete for a learner when they achieve the grade specified or higher.';
$string['criteriagradenote'] = 'Please note that updating the required grade here will not update the current course pass grade.';
$string['date']='Date';
$string['date_help'] = 'When enabled this criteria will be marked as complete for all users where the specified date is reached.';
$string['enrolmentduration']='Days left';
$string['err_nocriteria']='No course completion criteria have been set for this course';
$string['err_noroles']='There are no roles with the capability \'moodle/course:markcomplete\' in this course. You can enable this criteria type by adding this capability to role(s).';
$string['datepassed']='Date passed';
$string['daysafterenrolment']='Days after enrolment';
$string['deletedcourse']='Deleted course';
$string['durationafterenrolment']='Duration after enrolment';
$string['durationafterenrolment_help'] = 'When enabled this criteria will be marked as complete when the duration of a user\'s enrolment has reached the specified length.';
$string['manualcompletionby_help'] = 'Enabling this criteria allows you to select a role (or multiple roles) to manually mark a learner as complete in a course.';
$string['manuallymarkwhencomplete'] = 'Manually mark this activity when complete';
$string['manualselfcompletion_help'] = 'A learner can mark themselves complete in this course using the **Self completion** block.';
$string['overallcriteriaaggregation']='Overall criteria type aggregation';
$string['overallcriteriaaggregation_help'] = 'How the course completion system determines if a learner is complete.';
$string['roleaggregationmethod']='Aggregation method';
$string['roleaggregationmethod_help'] = 'An aggregation method of **All** means this criteria will be marked as complete when the learner has been marked complete by all the selected roles. If the aggregation method is set to **Any** only one of the selected roles will be required to mark the learner complete for them to complete the course.';
$string['returntocourse']='Return to course';
$string['selectnone'] = 'Select none';

$string['archivecompletions'] = 'Completions archive';
$string['cannotarchivecompletions'] = 'No permission to archive this course completions';
$string['archivingcompletions'] = 'Archiving completions for course {$a}';
$string['archivedcompletions'] = 'Archived completions for course : {$a}';
$string['archivecompletionscheck'] = 'The course completion data that will be archived is limited to: id; courseid; userid; timecompleted; grade.
<br />
All other completion data is <strong>permanently deleted</strong>. A record of completion will exist in the learner\'s Record of Learning.';
$string['archivecheck'] = 'Archive {$a} ?';
$string['nouserstoarchive'] = 'There are no users that have completed this course';
$string['archiveusersaffected'] = '{$a} users will be affected';
$string['usersarchived'] = '{$a} users completion records have been successfully archived';
$string['statusnottracked'] = 'Not tracked';
$string['statusnocriteria'] = 'No criteria';
$string['tooltipcourseaggregate'] = '<strong>{$a}</strong> of the following criteria need to be met to complete this course';
$string['tooltipcompletionself'] = 'You must mark yourself as complete';
$string['tooltipcompletiondate'] = 'You must remain enrolled until {$a}';
$string['tooltipcompletionactivityone'] = 'One activity needs to be completed';
$string['tooltipcompletionactivitymany'] = '{$a} activities need to be completed';
$string['tooltipcompletionduration'] = 'You must be enrolled for a total of {$a}';
$string['tooltipcompletiongrade0'] = 'You must achieve a grade';
$string['tooltipcompletiongrade'] = 'You must achieve a grade of {$a}';
$string['tooltipcompletionroleone'] = 'You must be marked as complete by a {$a}';
$string['tooltipcompletionroleany'] = 'You must be marked as complete by <strong>{$a->aggregation}</strong> of the following roles: {$a->roles}';
$string['tooltipcompletioncourseone'] = 'One other course needs to be completed';
$string['tooltipcompletioncoursemany'] = '{$a} other courses need to be completed';
$string['userdatacomponentname'] = 'Completion';
$string['userdataitemcourse_completion'] = 'Course completions (including activity completion)';


// Deprecated since 12.0.

$string['completionstartonenrol']='Completion tracking begins on enrolment';
$string['completionstartonenrolhelp']='Begin tracking a learner\'s progress in course completion after course enrolment';
$string['completionstartonenrol_help'] = 'Begin tracking a learner\'s progress in course completion after course enrolment.';
