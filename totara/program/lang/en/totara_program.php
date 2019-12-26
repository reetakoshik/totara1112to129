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
 * @package totara
 * @subpackage program
 */

$string['action'] = 'Action';
$string['actualduedate'] = 'Actual due date';
$string['actualduedates'] = 'Actual due dates';
$string['addcohortstoprogram'] = 'Add audiences to program';
$string['addcohorttoprogram'] = 'Add audience to program';
$string['addcompetency'] = 'Add competency';
$string['addcourse'] = 'Add course';
$string['addcourses'] = 'Add courses';
$string['addcourseset'] = 'Add course set';
$string['added'] = 'Added';
$string['addindividualstoprogram'] = 'Add individuals to program';
$string['addindividualtoprogram'] = 'Add individual to program';
$string['addmanagerstoprogram'] = 'Add managers to program';
$string['addmanagertoprogram'] = 'Add manager to program';
$string['addnew'] = 'Add a new';
$string['addnewprogram'] = 'Add a new program';
$string['addorganisationstoprogram'] = 'Add organisations to program';
$string['addorganisationtoprogram'] = 'Add organisation to program';
$string['addorremovecourses'] = 'Add/remove courses';
$string['addpositionstoprogram'] = 'Add positions to program';
$string['addpositiontoprogram'] = 'Add position to program';
$string['addprogramcontent'] = 'Add program content';
$string['addprogramcontent_help'] = 'By adding sets of courses you can build up the learning path of the program. Once sets are added the relationships between them can be defined. Sets can be created from manually adding courses, selecting a predefined competency or setting up a single course with recurrence.

Once a number of sets have been created, set dividers are employed to allow the creation of sequences (i.e. dependencies) between each set. An example program with four course sets defined could have dependencies as follows:

*   From set one the learner must complete one course (courseA or courseB) before proceeding to set two.
*   From set two the learner must complete all courses (courseC and courseD and courseE) before proceeding to set three or set four.
*   From set three the learner must complete one course (courseE) or all courses from set four (courseF and courseG).

Once the learning path is completed, the learner has finished the program.

Sets can be created by adding:

* **Set of courses**: Allows creation of multiple sets of courses with dependencies.
* **Competency **: Allows creation of multiple sets of courses from predefined competency evidence. When a competency is used to create a set, it becomes rigid and cannot be changed.
* **Single course**: Forces the allowance of a single course with recurrence. Once a set of courses or competency is chosen, the single course with recurrence is removed from the list.';
$string['affectedusercount'] = 'Number of learners affected by these changes: ';
$string['afterprogramiscompleted'] = 'After program is completed';
$string['afterprogramisdue'] = 'After program is due';
$string['aftersetisdue'] = 'After set is due';
$string['allbelow'] = 'All below';
$string['allbelowlower'] = 'all below';
$string['allcourses'] = 'All courses';
$string['allcoursesfrom'] = 'all courses from';
$string['allextensionrequestissues'] = 'All "extension request" issues';
$string['alllearners'] = 'All learners';
$string['allowedtimeforprogramaslearner'] = 'You are allowed {$a->num} {$a->periodstr} to complete this program.';
$string['allowedtimeforprogramasmanager'] = '{$a->fullname} will require at least {$a->num} {$a->periodstr} to complete this program.';
$string['allowedtimeforprograminfinity'] = 'There is no time limit to complete this program.';
$string['allowedtimeforprogramviewing'] = 'A learner is allowed {$a->num} {$a->periodstr} to complete this program.';
$string['allowextensionrequests'] = 'Allow extension requests';
$string['allowextensionrequests_help'] = 'When enabled, learners can submit extension requests for this program which can then be approved or denied by their manager.';
$string['allowextensionrequestsdisabled'] = 'Program extension requests are disabled. To approve or deny the request, enable extension requests site-wide and for the program.';
$string['allowtimeforprogram'] = 'Allow at least {$a->num} {$a->periodstr} to complete this program.';
$string['allowtimeforprogramdays'] = 'Allow at least {$a} day(s) to complete this program.';
$string['allowtimeforprogramhours'] = 'Allow at least {$a} hour(s) to complete this program.';
$string['allowtimeforprogrammonths'] = 'Allow at least {$a} month(s) to complete this program.';
$string['allowtimeforprogramweeks'] = 'Allow at least {$a} week(s) to complete this program.';
$string['allowtimeforprogramyears'] = 'Allow at least {$a} year(s) to complete this program.';
$string['allowtimeforprograminfinity'] = 'There is no time limit to complete this program.';
$string['allowtimeforset'] = 'Allow at least {$a->num} {$a->periodstr} to complete this set.';
$string['allowtimeforsetdays'] = 'Allow at least {$a} day(s) to complete this set.';
$string['allowtimeforsethours'] = 'Allow at least {$a} hour(s) to complete this set.';
$string['allowtimeforsetmonths'] = 'Allow at least {$a} month(s) to complete this set.';
$string['allowtimeforsetweeks'] = 'Allow at least {$a} week(s) to complete this set.';
$string['allowtimeforsetyears'] = 'Allow at least {$a} year(s) to complete this set.';
$string['allowtimeforsetinfinity'] = 'There is no time limit to complete this set.';
$string['and'] = 'and';
$string['anothercourse'] = 'another course';
$string['areyousuredeletemessage'] = 'Are you sure you want to delete this message?';
$string['assignedasindividual'] = 'Assigned as an individual.';
$string['assignedvialearningplan'] = 'Assigned via learning plan.';
$string['assignindividual'] = '{$a->fullname} ({$a->email})';
$string['assignmentadded'] = '\'{$a}\' has been added to the program';
$string['assignmentcriterialearner'] = 'You are required to complete this program under the following criteria:';
$string['assignmentcriteriamanager'] = 'The learner is required to complete this program under the following criteria:';
$string['assignmentduedate'] = 'Assignment due date';
$string['assignmentduedate_help'] = 'The due date for users in the assignment can be calculated with these options:* **Fixed completion date**: Select a fixed time to be set as the due date for all the users in the assignment.

* **First login**: Relative to the users first log in to the system.
* **Position assignment date**: Relative to the time a position was assigned in any of the users job assignments with the specified position.
* **Job assignment start date**: Relative to the startdate field in any of the users job assignments with the specified position.
* **Program completion**: Relative to the time a specified program was completed by the user.
* **Course completion**: Relative to the time a specified program was completed by the user.
* **Profile field date**: Relative to the value of a date/time custom field in the users profile.
* **Program enrolment date**: Relative to the time a user is enrolled in this program.';
$string['assignments'] = 'Assignments';
$string['assignmentsaddedaudience'] = '{$a} audiences have been added to the program';
$string['assignmentsaddedindividual'] = '{$a} individuals have been added to the program';
$string['assignmentsaddedmanagementhierrachy'] = '{$a} management hierarchies have been added to the program';
$string['assignmentsaddedorgainisation'] = '{$a} organisations have been added to the program';
$string['assignmentsaddedposition'] = '{$a} positions have been added to the program';
$string['assignmentsdeferred'] = 'Program assignment changes have been deferred and will be applied during the next cron run';
$string['assignmentsdeferredtask'] = 'Deferred program assignments changes';
$string['assigntoposition'] = 'being assigned position';
$string['audiencevisibilityconflictmessage'] = '<strong>Important:</strong>
Users assigned to this program will not necessarily have access to the courses in it. Please review visibility of the courses.';
$string['availability'] = 'Availability';
$string['availabilitycheckstask'] = 'Program availability checks';
$string['availablefrom'] = 'Available From';
$string['availabletostudents'] = 'Available to learners';
$string['availabletostudentsnot'] = 'Not available to learners';
$string['availableuntil'] = 'Available Until';
$string['backtoallextrequests'] = 'Back to all extension requests';
$string['beforecourserepeats'] = 'before course repeats';
$string['beforeprogramisdue'] = 'Before program is due';
$string['beforesetisdue'] = 'Before set is due';
$string['bestguess'] = 'Best guess: {$a}';
$string['browsecategories'] = 'Browse categories';
$string['cachedef_course_order'] = 'Program courses cache';
$string['cachedef_program_progressinfo'] = 'Program completion cache';
$string['cachedef_program_users'] = 'Program users cache';
$string['cachedef_user_programs'] = 'User programs cache';
$string['cancel'] = 'Cancel';
$string['cancelprogramblurb'] = 'Cancelling will remove any unsaved changes';
$string['cancelprogrammanagement'] = 'Clear unsaved changes';
$string['catalog_already_completed'] = 'You have completed this program';
$string['catalog_already_enrolled'] = 'You are already enrolled in this program';
$string['catalog_cannot_view'] = 'You cannot view this program';
$string['catalog_go_to_program'] = 'Go to program';
$string['catalog_not_enrolled'] = 'You are not enrolled in this program';
$string['category'] = 'Category';
$string['certificationduedate'] = 'Certification due date';
$string['certificationname'] = 'Certification name';
$string['certification'] = 'Certification';
$string['certifications'] = 'Certifications';
$string['certnamelinkedicon'] = 'Certification name and linked icon';
$string['changecourse'] = 'Change course';
$string['checkcompletions'] = 'Check completions for problems';
$string['checkprogramdelete'] = 'Are you sure you want to delete this program and all its related items?';
$string['chooseicon'] = 'Choose icon';
$string['chooseitem'] = 'Choose item';
$string['choseautomaticallydetermine'] = 'You have chosen to let the system automatically determine a realistic time-frame for the completion of this program';
$string['chosedenyextensionexception'] = 'You have chosen to deny the selected extension request(s)';
$string['chosegrantextensionexception'] = 'You have chosen to grant the selected extension request(s)';
$string['choseoverrideexception'] = 'You have chosen to override the exception and continue with the assignment';
$string['cleanenrolmentpluginstask'] = 'Clean enrolment plugins in programs';
$string['clicktofixcompletions'] = 'Click here to automatically fix currently selected completion records affected by this problem';
$string['cohort'] = 'Audience';
$string['cohortname'] = 'Audience name';
$string['cohorts'] = 'Audiences';
$string['cohorts_category'] = 'audience(s)';
$string['competency'] = 'Competency';
$string['competencycourseset'] = 'Competency course set';
$string['competencycourseset_help'] = 'This set has been created from a predefined competency.

When a competency is used to create a set, it becomes rigid and cannot be changed. Courses within the set cannot be edited. If the courses within this set need to be modified a manual set of courses must be created and courses added individually.

The operator options within a competency course set (**one course** or **all courses**) are determined by the predefined competency settings.';
$string['complete'] = 'Complete';
$string['completeallcourses'] = 'All courses in this set must be completed (unless this is an optional set).';
$string['completeallcoursestoprogress'] = 'Complete all courses to progress';
$string['completeanycourse'] = 'Any one course in this set must be completed.';
$string['completeanycoursetoprogress'] = 'Complete any course to progress';
$string['completemincoursesminsum'] = 'At least {$a->mincourses} course(s) and at least {$a->sumfieldtotal} {$a->sumfield} must be completed to complete this set.';
$string['completemincourses'] = 'At least {$a->mincourses} course(s) in this set must be completed.';
$string['completeminsumfield'] = 'At least {$a->sumfieldtotal} {$a->sumfield} must be completed to complete this set.';
$string['completeoptionalcourses'] = 'All courses are optional.';
$string['completeby'] = 'Complete by';
$string['completebytime'] = 'Complete by {$a}';
$string['completecourse'] = 'Course completion';
$string['completedcoursemanual'] = 'Course marked as manually completed';
$string['completedcourserpl'] = 'Course marked as completed by record of prior learning';
$string['completewithin'] = 'Complete within';
$string['completewithinevent'] = 'Complete within {$a->num} {$a->period} of {$a->event} {$a->instance}';
$string['completexcoursestoprogress'] = 'Complete 1 more course to progress';
$string['completexcoursestoprogressplural'] = 'Complete {$a} more courses to progress';
$string['completion'] = 'Completion';
$string['completionaddhistory'] = 'Add history';
$string['completionassignedbecause'] = 'This user is assigned for the following reasons:';
$string['completionassignedreasondeleted'] = 'The user has been deleted and they are not currently assigned.';
$string['completionassignedreasonnotfound'] = 'No current assignment details could be found.';
$string['completionassignedreasonunapprovedplan'] = 'This program has been added to their learning plan. However, please note that this has not been approved.';
$string['completionchangedatewarning'] = 'All dates will be rounded down to the nearest five minutes.';
$string['completionchangessaved'] = 'Completion changes have been saved';
$string['completioncriteria'] = 'Completion criteria';
$string['completioncriterianotdefined'] = 'Completion criteria not defined';
$string['completiondate'] = 'Completion date';
$string['completiondatenotapplicable'] = 'Not applicable (0 in database)';
$string['completionfilterbyprogram'] = 'Filter by program: {$a}';
$string['completionfilterbyuser'] = 'Filter by user: {$a}';
$string['completionhasproblem'] = 'Has problem';
$string['completionhistory'] = 'Completion history';
$string['completionhistorydeleted'] = 'Completion history deleted';
$string['completionid'] = 'ID';
$string['completionofcourse'] = 'completion of course';
$string['completionofprogram'] = 'completion of program';
$string['completionprobleminformation'] = 'Information';
$string['completionprogstatus'] = 'Status';
$string['completionprogstatus_help'] = 'This is the status as it appears in the **Record of Learning: Programs** report.';
$string['completionrecordcountproblem'] = 'Problem records: {$a}';
$string['completionrecordcounttotal'] = 'Total records: {$a}';
$string['completionrecordusersuspended'] = 'This user is suspended. Automated processes such as cron tasks are unlikely to update this user\'s records.';
$string['completionreturntoprogram'] = 'Return to program';
$string['completionsforuserinprog'] = 'Completion records for {$a->user} in {$a->prog}';
$string['completionoptional'] = 'All courses are optional';
$string['completionstask'] = 'Program completions';
$string['completionstatus'] = 'Status';
$string['completionstateedited'] = 'Program completion state edited';
$string['completionswithproblems'] = 'Program completion records with problems';
$string['completiontimecompleted'] = 'Completion date';
$string['completiontimedue'] = 'Due date';
$string['completiontimeunknown'] = 'Completion time unknown';
$string['completiontransactions'] = 'Transactions';
$string['completiontype'] = 'Completion type';
$string['completiontype_help'] = 'The operator options (**Learner must complete**) within the set are: * **one course**: Meaning **or**.* **all courses**: Meaning **and**.The idea is to keep the flow humanly readable. Depending on the option chosen, the text in front of the courses changes automatically.';
$string['completionupdatecancelled'] = 'Completion update cancelled';
$string['configenablecertifications'] = 'This option will let you: Enable(show)/Disable Certifications features from users on this site.

* If Show is chosen, all links, menus, tabs and option related to certifications will be accessible.
* If Disable is chosen, certifications will disappear from any menu on the site and will not be accessible.
';
$string['configenableprograms'] = 'This option will let you: Enable(show)/Disable Programs features from users on this site.

* If Show is chosen, all links, menus, tabs and option related to programs will be accessible.
* If Disable is chosen, programs will disappear from any menu on the site and will not be accessible.
';
$string['confirmassignmentchanges'] = 'Confirm assignment changes';
$string['confirmcontentchanges'] = 'Confirm content changes';
$string['confirmdeletecompletion'] = 'Are you sure that you want to delete this completion record?';
$string['confirmmessagechanges'] = 'Confirm message changes';
$string['confirmresolution'] = 'Confirm issue resolution';
$string['content'] = 'Content';
$string['contenttypenotfound'] = 'Content type not found';
$string['contentupdatednotsaved'] = 'Program content updated (not yet saved)';
$string['continue'] = 'Continue';
$string['copyrecurringcoursestask'] = 'Copy recurring courses in programs';
$string['couldnotinsertnewrecord'] = 'Unable to insert new record';
$string['count'] = 'Count';
$string['course'] = 'Course';
$string['coursecompletion'] = 'Course completion';
$string['coursecreation'] = 'Course creation';
$string['coursecreation_help'] = 'Course creation defines when the course should be copied and recreated.

It relies on the start and end date specified in the course settings.';
$string['coursename'] = 'Course name';
$string['coursenamelink'] = 'Course name';
$string['courses'] = 'Courses';
$string['coursesetcompleted'] = 'Course set completed';
$string['coursesetcompletedmessage'] = 'Course set completed message';
$string['coursesetcompletedmessage_help'] = 'This message will be sent whenever a course set is completed.';
$string['coursesetdue'] = 'Course set due';
$string['coursesetduemessage'] = 'Course set due message';
$string['coursesetduemessage_help'] = 'This message will be sent at the specified time before a course set is due.

The \'Minimum time required\' field within the course set is used to calculate when a course set is due.';
$string['coursesetoverdue'] = 'Course set overdue';
$string['coursesetoverduemessage'] = 'Course set overdue message';
$string['coursesetoverduemessage_help'] = 'This message will be sent at the specified time after a course set becomes due.

The \'Minimum time required\' field within the course set is used to calculate when a course set is due.';
$string['coursescorefield'] = 'Course score field';
$string['coursescorefield_help'] = 'Specify a course custom field which is used to allocate a score to each course in the set. This is used in conjunction with **Minimum score** below to determine the course set\'s completion criteria.

The selected field should contain only whole numerical values. To ensure only whole numerical values are entered a **Text input** type custom field should be used with the **Regular expression validation** field set as **/^[0-9]+$/**';
$string['createandnext'] = 'Create and go to next step';
$string['createandreturn'] = 'Create and return to overview';
$string['createcourse'] = 'Create course';
$string['createnewprogram'] = 'Create new program';
$string['createprogram'] = 'Create program';
$string['criteriacompletioncourseset'] = 'Criteria completion for this course set is based on: {$a}';
$string['cronautomatic'] = 'Cron/Automatic';
$string['currentcompletionrecord'] = 'Current completion record';
$string['currentduedate'] = 'Current due date';
$string['currenticon'] = 'Current icon';
$string['dateassigned'] = 'Date assigned';
$string['datecompleted'] = 'Date completed';
$string['dateinprofilefield'] = 'date in profile field';
$string['days'] = 'Day(s)';
$string['daysremaining'] = '{$a} days remaining';
$string['defaultenrolmentmessage_message'] = 'You are now enrolled on program %programfullname%.';
$string['defaultenrolmentmessage_subject'] = 'You have been enrolled on program %programfullname%';
$string['defaultexceptionreportmessage_message'] = 'There are exceptions in program %programfullname% which need to be resolved.';
$string['defaultexceptionreportmessage_subject'] = 'Exceptions require attention in program %programfullname%';
$string['defaultprogramfullname'] = 'Program fullname 101';
$string['defaultprogramshortname'] = 'P101';
$string['delete'] = 'Delete';
$string['deletecourse'] = 'Delete course';
$string['deleteprogram'] = 'Delete program "{$a}"';
$string['deleteprogrambutton'] = 'Delete program';
$string['deny'] = 'Deny';
$string['denyextensionrequest'] = 'Deny extension request';
$string['description'] = 'Summary';
$string['details'] = 'Details';
$string['directteam'] = 'direct team';
$string['duedate'] = 'Due date';
$string['duedatecanbechanged'] = 'Can be changed';
$string['duedatenotset'] = 'No due date set';
$string['duedatesettings'] = 'Due date settings';
$string['duestatus'] = 'Due/Status';
$string['editassignments'] = 'Edit assignments';
$string['editcompletion'] = 'Edit completion records';
$string['editcontent'] = 'Edit content';
$string['editmessages'] = 'Edit messages';
$string['editprogramassignments'] = 'Edit program assignments';
$string['editprogramcontent'] = 'Edit program content';
$string['editprogramdetails'] = 'Edit program details';
$string['editprogrammessages'] = 'Edit program messages';
$string['editprogramroleassignments'] = 'Edit program role assignments';
$string['editprograms'] = 'Add/edit programs';
$string['enablecertifications'] = 'Enable Certifications';
$string['enablelegacyprogramassignments'] = 'Enable legacy program assignment interface';
$string['enablelegacyprogramassignments_help'] = 'When enabled, this will revert the program assignment interface back to the legacy view. If your site uses large-scale program assignments we recommend using the using the improved interface.';
$string['enableprogramcompletioneditor'] = 'Enable program completion editor';
$string['enableprogramcompletioneditor_desc'] = 'When enabled, the Completion tab will be made available to users with the \'totara/program:editcompletion\' capability when editing a program or certification. This will allow them to edit completion records, both historical and current.';
$string['enableprograms'] = 'Enable Programs';
$string['endnote'] = 'Endnote';
$string['endnote_help'] = 'Note to be displayed at the end of the program.';
$string['enrolment'] = 'Enrolment';
$string['enrolmentmessage'] = 'Enrolment message';
$string['enrolmentmessage_help'] = 'This message will be sent whenever a user is automatically assigned to a program.';
$string['error:addinguserassignment'] = 'An error occurred when adding a user assignment';
$string['error:assignmentnotfound'] = 'Assignment record not found';
$string['error:availibileuntilearlierthanfrom'] = 'Available until date cannot be earlier than from date';
$string['error:badcheckvariable'] = 'The check variable was wrong - try again';
$string['error:cannotloadcompletionrecord'] = 'Tried to load prog_completion record which doesn\'t exist for programid: {$a->programid}, userid: {$a->userid}';
$string['error:cannotupdateassignment'] = 'Program assignment cannot be updated';
$string['error:cannotupdateduedate'] = 'Program assignment due date cannot be updated';
$string['error:completioneditornotenabled'] = 'Completion editor is not enabled on this site';
$string['error:couldnotloadextension'] = 'Error, could not load extension.';
$string['error:coursecreationrepeat_nonzero'] = 'Course creation must be more than zero days before course repeats';
$string['error:courses_endenroldate'] = 'You must set an enrolment end date for this course if you want it to recur';
$string['error:courses_nocourses'] = 'Course sets must contain at least one course.';
$string['error:deleteset'] = 'Unable to delete set. Set not found.';
$string['error:determineprogcat'] = 'Unable to determine the program\'s category';
$string['error:info_fixtimedueunknown'] = 'The program \'Due date\' should never be \'Unknown\'. This should be fixed by either disabling the \'Due date\' or setting a valid \'Due date\'. This problem can safely be fixed automatically by setting the \'Due date\' to \'Not set\' using the provided link.';
$string['error:info_unknowncombination'] = 'There is no specific information relating to this error or particular combination of errors. It may be that it is a combination of other explainable errors. The records can be fixed manually, but care should be taken to ensure that the correct solution is chosen.';
$string['error:extensionnotprocessed'] = 'Sorry, the program extension\'s request could not be processed. Please ensure \'Program extension requests\' are enabled
on the site and for the program.';
$string['error:failedsendextensiondenyalert'] = 'Error, failed to alert user of denied extension';
$string['error:failedsendextensiongrantalert'] = 'Error, failed to alert user of granted extension';
$string['error:failedtofindmanagerrole'] = 'Could not find role with shortname manager';
$string['error:failedtofindstudentrole'] = 'Could not find role with shortname learner';
$string['error:failedtofinduser'] = 'Failed to find user with id {$a}';
$string['error:failedupdateextension'] = 'Error, failed to update program with new due date';
$string['error:failfixprogsortorder'] = 'Failed to fix program sortorder';
$string['error:findingprogram'] = 'Error finding program {$a}';
$string['error:impossibledatasubmitted'] = 'The data submitted is not valid and cannot be processed';
$string['error:inaccessible'] = 'You cannot currently access this program';
$string['error:info_fixmissingprogcompletion'] = 'The user is assigned, but program completion record is missing. This fix will create a program completion record.';
$string['error:info_fixorphanedexceptionassign'] = 'When this occurs, the user will not show in the \'Exception Report\' tab, but they will not be able to complete the certification. There are two ways to resolve this problem.<br/>The first is to assign the user, disregarding any exceptions. This is the equivalent of selecting \'Assign\' when resolving exceptions:';
$string['error:info_fixorphanedexceptionrecalculate'] = 'The second is to have the exception recalculated. If the exception is no longer applicable then the user will become assigned. Otherwise a new exception will be generated, but it can be resolved in the \'Exception Report\' tab:';
$string['error:info_fixunassignedincompletecompletionrecord'] = 'The user has a program completion record, which is in the incomplete state, and they are not assigned. This fix will remove the program completion record.';
$string['error:invalidcoursesetgroupoperator'] = 'Invalid course set group operator found';
$string['error:invaliddate'] = 'Date is not valid';
$string['error:invalidid'] = 'That\'s an invalid program id';
$string['error:invalidshortname'] = 'That\'s an invalid program short name';
$string['error:invalidstatus'] = 'Invalid status';
$string['error:invaliduser'] = 'Error, invalid user';
$string['error:mainmessage_empty'] = 'Message is required';
$string['error:messagesubject_empty'] = 'Message subject is required';
$string['error:missingprogcompletion'] = 'Program completion record is missing';
$string['error:nextcoursesetmissing'] = "Next course set operator OR or AND specified, but next course doesn't exist";
$string['error:nocompetency'] = 'A competency must be selected.';
$string['error:nocompletionrecord'] = 'Error, could not find completion record for assignment';
$string['error:nopermissions'] = 'You do not have the necessary permissions to perform this action';
$string['error:noprogramid'] = 'Must specify program id or short name';
$string['error:notaprogram'] = 'This is not a program, it is a certification';
$string['error:notextensionallowed'] = 'Program extension request is not enable on this site.';
$string['error:notmanagerornopermissions'] = 'You are not a manager of this user or do not have permissions to perform this action.';
$string['error:notrequiredlearning'] = 'This program is not required learning';
$string['error:notusersmanager'] = 'You are not the manager of the user who requested this extension';
$string['error:orphanedexception'] = 'Program user assignment has exception but no matching program exception record exists';
$string['error:processingextrequest'] = 'An error occurred when processing extension request';
$string['error:prognotmoved'] = 'Error, program not moved!';
$string['error:programdoesnotbelongtocategory'] = 'The program doesn\'t belong to this category';
$string['error:progsnotmoved'] = 'Error, programs not moved from {$a}!';
$string['error:progstatusinvalid'] = 'Program status for courseset 0 record must be STATUS_PROGRAM_INCOMPLETE or STATUS_PROGRAM_COMPLETE! This indicates a potentially major problem and should be reported to Totara support.';
$string['error:recertduedatenotset'] = 'Error, recertifications must have an expiry date';
$string['error:recur_nocourse'] = 'A course must be selected.';
$string['error:recurrence_nonzero'] = 'Recurrence must be higher than zero';
$string['error:setunableaddcompetency'] = 'Unable to add competency to set. Set or competency not found.';
$string['error:setunabletoaddcourse'] = 'Unable to add course to set. Set or course not found.';
$string['error:setunabletodeletecourse'] = 'Unable to delete course from set {$a}';
$string['error:setupprogcontent'] = 'Unable to set up program content.';
$string['error:setupprogrammessages'] = 'Unable to set up program messages';
$string['error:stateincomplete-timecompletednotempty'] = 'Time completed should be empty when user has not completed the program.';
$string['error:stateinvalid'] = 'Invalid - select a valid status';
$string['error:statecomplete-timecompletedempty'] = 'Time completed should not be empty when user has completed the program.';
$string['error:timeallowednum_nonzero'] = 'Time allowance must be zero or higher';
$string['error:timedueunknown'] = 'Due date should not be \'UNKNOWN\' (0).';
$string['error:unableaddmessagetypeunrecog'] = 'Unable to add new message. Message type not recognised.';
$string['error:unabledeletemessagenotfound'] = 'Unable to delete message. Message not found';
$string['error:unabletoaddset'] = 'Unable to add new set. Set type not recognised.';
$string['error:unabletosetupprogcontent'] = 'Unable to set up program content.';
$string['error:unassignedincompleteprogcompletion'] = 'Program completion record exists for a user who is unassigned and incomplete';
$string['error:updateextensionstatus'] = 'Error, failed to update extension status';
$string['error:updateuserassignment'] = 'An error occurred when updating a user assignment record';
$string['error:updatingcompletionrecord'] = 'An error occurred when updating a completion record';
$string['error:updatinginvalidcompletionrecord'] = 'Call to prog_write_completion with completion record that does not match the existing record';
$string['error:updatingprogramassignment'] = 'An error occurred when updating a program assignment';
$string['error:userassignmentclassnotfound'] = 'User assignment class not found';
$string['error:userassignmenttypenotfound'] = 'User assignment type not found';
$string['errorsinform'] = 'There are errors in this form. Please review the list below and fix any errors before saving.';
$string['eventassigned'] = 'Program assigned';
$string['eventassignmentsupdated'] = 'Program assignments updated';
$string['eventbulkfutureassignmentended'] = 'Bulk future user assignment ended';
$string['eventbulkfutureassignmentstarted'] = 'Bulk future user assignment started';
$string['eventbulklearnersassignedended'] = 'Bulk learner assignment ended';
$string['eventbulklearnersassignedstarted'] = 'Bulk learner assignment started';
$string['eventcompletion'] = 'Program completed';
$string['eventcompletionedited'] = 'Program completion edited';
$string['eventcompletionhistoryadded'] = 'Program completion history added';
$string['eventcompletionhistorydeleted'] = 'Program completion history deleted';
$string['eventcompletionhistoryedited'] = 'Program completion history edited';
$string['eventcontentupdated'] = 'Program content updated';
$string['eventcoursesetcompletion'] = 'Program course set completed';
$string['eventcreated'] = 'Program created';
$string['eventdeleted'] = 'Program deleted';
$string['eventextensiondenied'] = 'Program extension denied';
$string['eventextensiongranted'] = 'Program extension granted';
$string['eventfutureassigned'] = 'Program future assigned';
$string['eventmessagesupdated'] = 'Program messages updated';
$string['eventsettimedue'] = 'User\'s program time due set';
$string['eventunassigned'] = 'Program unassigned';
$string['eventupdated'] = 'Program updated';
$string['eventviewed'] = 'Program Viewed';
$string['eventnotfound'] = 'Could not find program assignment event with id {$a}';
$string['exceptionactionassign'] = 'Assign';
$string['exceptionactiondonotassign'] = 'Do not assign';
$string['exceptionactionsetduedate'] = 'Set realistic due date and assign';
$string['exceptionchosennotassign'] = 'You have chosen to not assign the selected user(s)';
$string['exceptionoverridden'] = 'Program assignment exception overridden, and user assignment successfully completed.';
$string['exceptionreportmessage'] = 'Exception report message';
$string['exceptionreportmessage_help'] = 'This message will be sent to the site administrator whenever new exceptions are added to a program\'s exception report.';
$string['exceptions'] = 'Exception Report ({$a})';
$string['exceptionsreport'] = 'Exceptions report';
$string['exceptiontypealreadyassigned'] = 'Already assigned to program';
$string['exceptiontypeduplicatecourse'] = 'Duplicate course in different certifications';
$string['extenduntil'] = 'Extend until';
$string['extensionacceptbutton'] = 'Grant Extension';
$string['extensionaccepttext'] = 'Extension Granted';
$string['extensionbeforenow'] = 'Cannot request extension that is earlier than current date';
$string['extensiondate'] = 'Extension date';
$string['extensiondenied'] = 'Extension denied by {$a}';
$string['extensiondeniedmessage'] = 'Your extension request for the program {$a} has been refused.';
$string['extensionearlierthanduedate'] = 'Cannot request extension that is before current program due date';
$string['extensionexpirywarning'] = 'Granting an extension when a user is on a recertification path will extend their current certification only. This will not affect future expiry dates that are calculated when the user recertifies – these are based on the original (pre-extension) expiry date.';
$string['extensiongranted'] = 'Extension granted by {$a}';
$string['extensiongrantedmessage'] = 'You have been granted an extension until {$a}.';
$string['extensioninfo_button'] = 'Extension details';
$string['extensioninfo_text'] = 'Extension request details';
$string['extensionrejectbutton'] = 'Deny Extension';
$string['extensionrejecttext'] = 'Extension Denied';
$string['extensionrequest'] = 'Request for program extension by {$a}';
$string['extensionrequestfailed'] = 'The extension request failed. Please try again.';
$string['extensionrequestmessage'] = '<p>A user has requested an extension for program <em>{$a->programfullname}</em>. The details of the request are:</p><ul><li>Date: {$a->extensiondatestr}</li><li>Reason: {$a->extensionreason}</li></ul> <b>Granting an extension when a user is on a recertification path will extend their current certification only. This will not affect future expiry dates that are calculated when the user recertifies – these are based on the original (pre-extension) expiry date.</b> <p><a href=\'{$a->manageurl}\'>Click here to manage extensions</a></p>';
$string['extensionrequestmessage_help'] = 'This message will be sent to the learner\'s manager whenever a program extension request is made.';
$string['extensionrequestnotsent'] = 'The extension request could NOT be sent. Please try again.';
$string['extensionrequestsent'] = 'Request for program extension has been sent to your manager(s)';
$string['extensions'] = 'Extensions';
$string['failedtoresolve'] = 'Failed to resolve the following exceptions';
$string['filter'] = 'Filter';
$string['findprograms'] = 'Find Programs';
$string['fixconfirmone'] = '<p>Are you sure you want to apply the selected fix to this completion record?</p><p>The action will be logged, but cannot be automatically undone.</p>';
$string['fixconfirmsome'] = '<p>Are you sure you want to apply the selected fix to <strong>all completion records in this list</strong>?</p><p>If some records need to be fixed by a different method (such as by a different fix, or manually) then you should select <strong>No</strong>.</p><p>The action will be logged for each completion record, but cannot be automatically undone.</p>';
$string['fixconfirmtitle'] = 'Confirm auto-fix records';
$string['fixexceptionbeforeeditingcompletion'] = 'This user has an exception. You must resolve the exception before you can edit the current completion record.';
$string['firstlogin'] = 'First login';
$string['firstloginassignmentstask'] = 'First login program assignments';
$string['for'] = 'For';
$string['fulllistofprograms'] = 'All programs';
$string['fullname'] = 'Full name';
$string['grant'] = 'Grant';
$string['grantdeny'] = 'Grant / Deny';
$string['grantextensionrequest'] = 'Grant extension request';
$string['groupactualduedate'] = 'Group assignment due date';
$string['groupactualduedate_help'] = 'Use the **Assignment due date** column to assign a due date to the group of users. Due dates for assigned users can only be increased. If an earlier assignment due date is applied to a user who already has
a due date set, then the earlier date will not be applied to the user. If a fixed due date is set, the date change will be applied immediately. If a relative due date is set, the due date will be applied to the users as soon as it can be successfully determined. The assignment due date will be assigned to other users as they join the group. The actual due date cannot be changed if this is a program and the user has already completed it, or if this is a certification
and the due date has automatically been updated after the user completed certification.
Users\' actual due dates may differ from the assignment due date for various reasons, including:

* If another assignment type includes the same user, the other assignment due date may override this one.
* If this assignment has a relative due date, which is not yet applicable to the user.
* A user may have been granted an extension by a manager.
* If an exception occurred and the **Set realistic time allowance** action was used to resolve it.
* This assignment due date may have changed, but the user still has the due date that was originally given to them.
* This is a certification, and the due date has automatically been set to the expiration date.

Note that assignment due dates are recorded when you press the **Save changes** button, but are not applied until the user assignments
are updated. If you see a message indicating that user assignments have been deferred then any user due date changes will
not yet be applied.';
$string['header:hash'] = '#';
$string['header:id'] = 'ID';
$string['header:issue'] = 'Issue';
$string['header:learners'] = 'Learners';
$string['hiddenfield'] = '**Hidden field**';
$string['historycompletionrecord'] = 'History completion record';
$string['holdposof'] = 'Hold position of \'{$a}\'';
$string['hours'] = 'Hour(s)';
$string['icon'] = 'Icon';
$string['idnumberprogram'] = 'ID';
$string['ifreactivatinglearnerupdate'] = 'Note: If program is reactivated, the assigned learners may be updated based on any changes within selected groups.';
$string['image'] = 'Image';
$string['image_help'] = 'Upload an image that will get displayed with the program. If no image is uploaded a default image will be used.';
$string['imagealt'] = 'Image for the program';
$string['imagedefault'] = 'Default image';
$string['imagedefault_help'] = 'Set a default image to display if no image is uploaded by the program creator.';
$string['imagedefaultlink'] = 'Set default image for all programs';
$string['imagenone'] = 'There is no image';
$string['includeallbelow'] = 'Include all below';
$string['includedirect'] = 'Include direct team';
$string['incomplete'] = 'Not complete';
$string['incompletecourse'] = 'Course marked as incomplete';
$string['individualactualduedate'] = 'Individual assignment due date';
$string['individualactualduedate_help'] = 'Use the **Assignment due date** column to assign a due date to the individual. Due dates for assigned users can only be increased. If an earlier assignment due date is applied to a user who already has
a due date set, then the earlier date will not be applied to the user. If a fixed due date is set, the date change will be applied immediately. If a relative due date is set, the due date will be applied to the user as soon as it can be successfully determined. The actual due date cannot be changed if this is a program and the user has already completed it, or if this is a certification
and the due date has automatically been updated after the user completed certification.
Users\' actual due dates may differ from the assignment due date for various reasons, including:

* If another assignment type includes the same user, the other assignment due date may override this one.
* If this assignment has a relative due date, which is not yet applicable to the user.
* A user may have been granted an extension by a manager.
* If an exception occurred and the **Set realistic time allowance** action was used to resolve it.
* This assignment due date may have changed, but the user still has the due date that was originally given to them.
* This is a certification, and the due date has automatically been set to the expiration date.

Note that assignment due dates are recorded when you press the **Save changes** button, but are not applied until the user assignments
are updated. If you see a message indicating that user assignments have been deferred then any user due date changes will
not yet be applied.';
$string['individualassignmentupdated'] = 'Assignment \'{$a}\' updated';
$string['individualname'] = 'Individual name';
$string['individual'] = 'Individual';
$string['individuals'] = 'Individuals';
$string['individuals_category'] = 'individual(s)';
$string['infinity'] = 'No time limit';
$string['instructions:assignments1'] = 'Categories can be used to assign the program to sets of learners.';
$string['instructions:messages1'] = 'Configure event and reminder triggers associated with the program.';
$string['instructions:programassignments'] = 'Assign learners in bulk and set fixed or relative completion criteria';
$string['instructions:programcontent'] = 'Define the program content by adding sets of courses and / or competencies';
$string['instructions:programdetails'] = 'Define the program name, availability and description';
$string['instructions:programexceptions'] = 'Quickly resolve assignment issues by selecting \'type\' and applying an \'action\'';
$string['instructions:programmessages'] = 'Define program messages and reminders as required';
$string['invalidtype'] = 'Invalid type param';
$string['itemdeleted'] = 'Item Deleted';
$string['jobassignmentstartdate'] = 'Job assignment start date';
$string['label:competencyname'] = 'Competency name';
$string['label:coursecreation'] = 'When to create new course';
$string['label:coursescorefield'] = 'Course score field';
$string['label:learnermustcomplete'] = 'Learner must complete';
$string['label:managersubject'] = 'Manager Subject';
$string['label:message'] = 'Message';
$string['label:minimumscore'] = 'Minimum score';
$string['label:minimumtimerequired'] = 'Minimum time required';
$string['label:nextsetoperator'] = 'Next set operator';
$string['label:noticeformanager'] = 'Notice for manager';
$string['label:recurcreation'] = 'Course creation';
$string['label:recurrence'] = 'Recurrence';
$string['label:sendnoticetomanager'] = 'Send notice to manager';
$string['label:setname'] = 'Set name';
$string['label:subject'] = 'Subject';
$string['label:timeallowance'] = 'Time allowance';
$string['label:trigger'] = 'Trigger';
$string['launchcourse'] = 'Launch course';
$string['launchprogram'] = 'Launch program';
$string['learnerenrolled'] = 'Learner enrolled';
$string['learnerfollowup'] = 'Learner follow-up';
$string['learnerfollowupmessage'] = 'Follow-up message';
$string['learnerfollowupmessage_help'] = 'This message will be sent to the learner at the specified time after the program has been completed.';
$string['learnersassignedbreakdown'] = '{$a->total} learner(s) assigned: {$a->assignments} active, {$a->exceptions} exception(s).';
$string['learnerswereassigned'] = '{$a} learner(s) were assigned via the following criteria:';
$string['learnersselected'] = 'learners selected';
$string['learnerunenrolled'] = 'Learner un-enrolled';
$string['legend:courseset'] = 'Course set {$a}';
$string['legend:coursesetcompletedmessage'] = 'COURSE SET COMPLETED MESSAGE';
$string['legend:coursesetduemessage'] = 'COURSE SET DUE MESSAGE';
$string['legend:coursesetoverduemessage'] = 'COURSE SET OVERDUE MESSAGE';
$string['legend:enrolmentmessage'] = 'ENROLMENT MESSAGE';
$string['legend:exceptionreportmessage'] = 'EXCEPTION REPORT MESSAGE';
$string['legend:extensionrequestmessage'] = 'EXTENSION REQUEST MESSAGE';
$string['legend:learnerfollowupmessage'] = 'LEARNER FOLLOW-UP MESSAGE';
$string['legend:programcompletedmessage'] = 'PROGRAM COMPLETED MESSAGE';
$string['legend:programduemessage'] = 'PROGRAM DUE MESSAGE';
$string['legend:programoverduemessage'] = 'PROGRAM OVERDUE MESSAGE';
$string['legend:recurringcourseset'] = 'Recurring course set';
$string['legend:unenrolmentmessage'] = 'UN-ENROLMENT MESSAGE';
$string['mainmessage'] = 'Message body';
$string['mainmessage_help'] = 'The message body will be displayed to message recipients in their dashboard.

The message body can contain variables which will be replaced when the message is sent.

## Variable substitution

In program messages, certain variables can be inserted into the subject and/or body of a message so that they will be replaced with real values when the message is sent. The variables should be inserted into the text exactly as they are shown below. The following variables can be used:

%userfullname%
:   This will be replaced by the recipient\'s full name.

%username%
:   This will be replaced by the user\'s username.

%programfullname%
:   This will be replaced by the program\'s full name.

%completioncriteria%
:   This will be replaced by the completion criteria set in the assignment tab.

%duedate%
:   This will be replaced by the date assigned to the user to complete the program.

%managername%
:   This will be replaced by the manager\'s name.

%manageremail%
:   This will be replaced by the manager\'s email.

%setlabel%
:   This will be replaced by the course set label (it will only be replaced if the message relates to a course set.';
$string['managecoursesinthiscat'] = 'Manage courses in this category';
$string['manageextensionrequests'] = 'View exception report to grant or deny extension requests';
$string['manageextensions'] = 'Manage Extensions';
$string['managementhierarchy'] = 'Management hierarchy';
$string['manageprogramsinthiscat'] = 'Manage programs in this category';
$string['manager'] = 'Manager';
$string['managermessage'] = 'Notice for manager';
$string['managermessage_help'] = 'If the **Send notice to manager** box is checked, the message recipient\'s manager will also be sent a notification which can be specified in this field.

The notice for manager can contain variables which will be replaced when the message is sent.

## Variable substitution

In program messages, certain variables can be inserted into the subject and/or body of a message so that they will be replaced with real values when the message is sent. The variables should be inserted into the text exactly as they are shown below. The following variables can be used:

%userfullname%
:   This will be replaced by the recipient\'s full name.

%username%
:   This will be replaced by the user\'s username.

%programfullname%
:   This will be replaced by the program\'s full name.

%completioncriteria%
:   This will be replaced by the completion criteria set in the assignment tab.

%duedate%
:   This will be replaced by the date assigned to the user to complete the program.

%managername%
:   This will be replaced by the manager\'s name.

%manageremail%
:   This will be replaced by the manager\'s email.

%setlabel%
:   This will be replaced by the course set label (it will only be replaced if the message relates to a course set.';
$string['managername'] = 'Manager name';
$string['managers_category'] = 'management team(s)';
$string['managersubject'] = 'Manager Subject';
$string['managersubject_help'] = 'This is the subject line that the message for the manager will use.';
$string['mandatory'] = 'Mandatory';
$string['markcompletheading'] = 'Mark complete';
$string['meesagetypenotfound'] = 'Program messagetype class not found';
$string['memberofcohort'] = 'Member of audience \'{$a}\'.';
$string['memberoforg'] = 'Member of organisation \'{$a}\'.';
$string['messages'] = 'Messages';
$string['messagesubject'] = 'Message subject';
$string['messagesubject_help'] = 'The subject of the message will be displayed to message recipients in their dashboard. Max 255 characters.

The subject can contain variables which will be replaced when the message is sent.

## Variable substitution

In program messages, certain variables can be inserted into the subject and/or body of a message so that they will be replaced with real values when the message is sent. The variables should be inserted into the text exactly as they are shown below. The following variables can be used:

%userfullname%
:   This will be replaced by the recipient\'s full name.

%username%
:   This will be replaced by the user\'s username.

%programfullname%
:   This will be replaced by the program\'s full name.

%completioncriteria%
:   This will be replaced by the completion criteria set in the assignment tab.

%duedate%
:   This will be replaced by the date assigned to the user to complete the program.

%managername%
:   This will be replaced by the manager\'s name.

%manageremail%
:   This will be replaced by the manager\'s email.

%setlabel%
:   This will be replaced by the course set label (it will only be replaced if the message relates to a course set.';
$string['mincourses'] = 'Minimum courses completed';
$string['mincourses_help'] = 'The minimum number of courses within this course set the assignees must complete for this course set to be considered complete.';
$string['minimumscore'] = 'Minimum score';
$string['minimumscore_help'] = 'A minimum score that must be reached for the course set to be marked as complete. The score is calculated by summing the values from the **Course score field** for all the courses a user has completed. For example, you might have a **Credits** custom field on your courses, so you could use this setting to mark a course set as complete when a certain number of credits have been achieved.

Only whole numerical values should be used in this field.';
$string['minimumtimerequired'] = 'Minimum time required';
$string['minimumtimerequired_help'] = 'This value indicates a minimum amount of time that a user might realistically need to be able to complete the course set. It is used to determine if the completion period set on the **assignments** tab is realistic for a particular group of users. If the assignment is not realistic, a **time allowance** exception will be generated and the user will not be assigned to the program until the exception has been resolved.

For example, consider a program consisting of a single course set with a minimum time required of 10 days. If a user was assigned with completion criteria that required them to complete it in less than 10 days, then it would raise an exception report for that user.

When using completion criteria relative to a user, it is possible for some users to generate exceptions but not others. For example, when using the **days since first login** criteria, each user would have their own deadline that may or may not be realistic.

When multiple course sets exist in a program the overall minimum time required for the program is calculated based on the worst-case scenario taking into account the course set logic. For example if a program consists of:

Course set1 [10 days] THEN Course set2 [5 days] OR Course set3 [7 days]

then the overall time allowance would be 17 days.

This minimum time value is also used to determine when the \'Course set due message\' and \'Course set overdue message\' should be sent.';
$string['minprogramtimerequired'] = 'Programs total minimum time required: ';
$string['missingshortname'] = 'Missing short name';
$string['months'] = 'Month(s)';
$string['movedown'] = 'Move down';
$string['moveselectedprogramsto'] = 'Move selected programs to...';
$string['moveup'] = 'Move up';
$string['multicourseset'] = 'Set of courses';
$string['multicourseset_help'] = 'This is a set of courses chosen individually from the course catalogue.

You can define the set name, whether the Learner must complete one or all courses and the general time allowance to complete the set.';
$string['nocertificationlearning'] = 'No certifications';
$string['nocontent'] = 'Does not contain any content';
$string['nocoursecontent'] = 'No course content.';
$string['nocourses'] = 'No courses';
$string['nocoursesfrom'] = 'no courses from';
$string['noduedate'] = 'No due date';
$string['noextensions'] = 'You have no staff who have pending extension requests';
$string['nolongeravailabletolearners'] = 'This program is no longer available to learners.';
$string['nominimumtime'] = 'No minimum time';
$string['noprogramassignments'] = 'Program does not contain any assignments';
$string['noprogramcontent'] = 'Program does not contain any content';
$string['noprogramexceptions'] = 'No exceptions';
$string['noprogrammessages'] = 'Program does not contain any messages';
$string['noprograms'] = 'No programs';
$string['noprogramsfound'] = 'No programs were found with the words \'{$a}\'';
$string['noprogramsyet'] = 'No programs in this category';
$string['norequiredlearning'] = 'No required learning';
$string['noresults'] = 'No results';
$string['nostartdate'] = 'No start date';
$string['notassigned'] = 'Not assigned';
$string['notavailable'] = 'Not available';
$string['notduetostartuntil'] = 'This program is not yet available. Learner assignments will be applied following the start date.';
$string['notifymanager'] = 'Send notice to manager';
$string['notifymanager_help'] = 'Check this box if you also want to send a notice to the message recipient\'s manager.';
$string['notmanager'] = 'You are not a manager';
$string['notyetknown'] = 'Not yet known';
$string['notyetset'] = 'Not yet set';
$string['nouserextensions'] = '{$a} does not have any pending extension requests';
$string['novalidprograms'] = 'No valid programs';
$string['numberofprograms'] = 'Number of programs';
$string['numoflearners'] = 'Number of learners';
$string['numlearners'] = '# learners';
$string['obtainxpointsandcompletexcoursestoprogress'] = 'Obtain {$a->pointsrequired} more {$a->scorefieldname} and complete 1 more course to progress';
$string['obtainxpointsandcompletexcoursestoprogressplural'] = 'Obtain {$a->pointsrequired} more {$a->scorefieldname} and complete {$a->coursesrequired} more courses to progress';
$string['obtainxpointstoprogress'] = 'Obtain {$a->pointsrequired} {$a->scorefieldname} or more to progress';
$string['of'] = 'of';
$string['ok'] = 'Ok';
$string['onecourse'] = 'One course';
$string['onecoursesfrom'] = 'one course from';
$string['onedayremaining'] = '1 day remaining';
$string['or'] = 'or';
$string['organisationname'] = 'Organisation name';
$string['organisation'] = 'Organisation';
$string['organisations'] = 'Organisations';
$string['organisations_category'] = 'organisation(s)';
$string['orviewprograms'] = 'or view programs in this category ({$a})';
$string['othersettings'] = 'Settings';
$string['overdue'] = 'Overdue!';
$string['overrideandassign'] = 'Override and assign';
$string['overview'] = 'Overview';
$string['partofteam'] = 'Part of \'{$a}\' team.';
$string['pendingextension'] = '(Pending extension request)';
$string['pleaseentervaliddate'] = 'Please enter a valid date in the format {$a}.';
$string['pleaseentervalidreason'] = 'Please enter a valid reason';
$string['pleaseentervalidunit'] = 'Please enter a valid unit between 0 and 999';
$string['pleasepickaninstance'] = 'Please choose an item';
$string['pleaseselectoption'] = 'Please select an option';
$string['pluginname'] = 'Program Management';
$string['positionassigneddate'] = 'Position assigned date';
$string['position'] = 'Position';
$string['positions'] = 'Positions';
$string['positions_category'] = 'position(s)';
$string['positionsname'] = 'Positions name';
$string['positionstartdate'] = 'Position start date';
$string['problem'] = 'Problem';
$string['problemcategory'] = 'Category';
$string['problemcategory_help'] = 'Problems can belong to one of these categories:

* **Consistency**: The completion records are not internally consistent, e.g. if a record has status **complete** but the date of completion is empty.
* **Files**: One or more files are either missing or should not be present, e.g. if a user has an assignment record but the corresponding completion record is missing.
* **History**: There is a problem with completion history, e.g. if a history record is marked **unassigned** but the user is currently assigned.';
$string['problemcategoryconsistency'] = 'Consistency';
$string['problemcategoryexceptions'] = 'Exceptions';
$string['problemcategoryfiles'] = 'Files';
$string['problemcategoryhistory'] = 'History';
$string['proceed'] = 'Proceed with this action';
$string['profilefielddate'] = 'Profile field date';
$string['prog_courseset_completed_message'] = 'Course set completed message';
$string['prog_courseset_due_message'] = 'Course set due message';
$string['prog_courseset_overdue_message'] = 'Course set overdue message';
$string['prog_enrolment_message'] = 'Enrolment message';
$string['prog_exception_report_message'] = 'Exception report message';
$string['prog_extension_request_message'] = 'Extension request message';
$string['prog_learner_followup_message'] = 'Learner follow-up message';
$string['prog_program_completed_message'] = 'Program completed message';
$string['prog_program_due_message'] = 'Program due message';
$string['prog_program_overdue_message'] = 'Program overdue message';
$string['prog_unenrolment_message'] = 'Un-enrolment message';
$string['progmessageupdated'] = 'Program messages updated (not yet saved)';
$string['prognamelinkedicon'] = 'Program Name and Linked Icon';
$string['program'] = 'Program';
$string['programexpandlink'] = 'Program Name (expanding details)';
$string['program:accessanyprogram'] = 'Access any program';
$string['program:configureassignments'] = 'Configure program assignments';
$string['program:configurecontent'] = 'Configure program content';
$string['program:configuredetails'] = 'Edit program details';
$string['program:configuremessages'] = 'Configure program messages';
$string['program:configureprogram'] = 'Configure programs';
$string['program:createprogram'] = 'Create programs';
$string['program:deleteprogram'] = 'Delete programs';
$string['program:editcompletion'] = 'Edit program completion records';
$string['programenrolledincohort'] = 'Program with enrolled audience(s)';
$string['program:handleexceptions'] = 'Handle program exceptions';
$string['program:manageextensions'] = 'Manage extensions';
$string['program:markcoursecomplete'] = 'Mark a course in a program complete via a user\'s required learning';
$string['program:markstaffcoursecomplete'] = 'Mark staff course as complete';
$string['program:viewhiddenprograms'] = 'View hidden programs';
$string['program:viewprogram'] = 'View programs';
$string['program:visibility'] = 'Hide/show programs';
$string['programadministration'] = 'Program Administration';
$string['programassignmentduedates'] = 'Program assignment due dates';
$string['programassignments'] = 'Program assignments';
$string['programassignmentsdeferred'] = 'Program assignment changes have been saved, but users will not see the changes until after the next cron run';
$string['programassignmentssaved'] = 'Program assignments saved successfully';
$string['programavailability'] = 'Program Availability';
$string['programavailability_help'] = 'This option allows you to hide your program completely.

It will not appear on any program listings, except to administrators.

Even if learners try to access the program URL directly, they will not be allowed to enter.

If you set the **Available from** and **Available until** dates, learners will be able to find and enter the program during the period specified by the dates but will be prevented from accessing the program outside of those dates.';
$string['programcategories'] = 'Program Categories';
$string['programcategory'] = 'Program Category';
$string['programcategory_help'] = 'Your Totara administrator may have set up several program/course categories.

For example, \'Human Resources\', \'Software development\', \'Marketing\' etc.

Choose the one most applicable for your program. This choice will affect where your program is displayed on the program listing and may make it easier for learners to find your program.';
$string['programcompleted'] = 'Program completed';
$string['programcompletedmessage'] = 'Program completed message';
$string['programcompletedmessage_help'] = 'This message will be sent whenever a program is completed.';
$string['programcompletion'] = 'Program completion';
$string['programcontent'] = 'Program content';
$string['programcontentsaved'] = 'Program content saved successfully';
$string['programcreatefail'] = 'Program could not be created. Reason: "{$a}"';
$string['programcreatesuccess'] = 'Program creation successful';
$string['programdefaults'] = 'Program default settings';
$string['programdeletefail'] = 'Could not delete program "{$a}"';
$string['programdeletesuccess'] = 'Successfully deleted program "{$a}"';
$string['programdetails'] = 'Program details';
$string['programdetailssaved'] = 'Program details saved successfully';
$string['programdue'] = 'Program due';
$string['programduedate'] = 'Program due date';
$string['programduemessage'] = 'Program due message';
$string['programduemessage_help'] = 'This message will be sent at the specified time before a program is due.';
$string['programduex'] = 'Program due {$a}';
$string['programends'] = 'Program ends';
$string['programenrollmentdate'] = 'Program enrollment date';
$string['programexceptions'] = 'Program exceptions';
$string['programfullname'] = 'Program Fullname';
$string['programfullname_help'] = 'The full name of the program is displayed at the top of the screen and in the program listings.';
$string['programicon'] = 'Program icon';
$string['programid'] = 'Program ID';
$string['programidnotfound'] = 'Program does not exist for ID : {$a}';
$string['programidnumber'] = 'Program ID number';
$string['programidnumber_help'] = 'The ID number of a program is only used when matching this course against external systems - it is never displayed within Totara. If you have an official code name for this program then use it here, otherwise you can leave it blank.';
$string['programlive'] = 'Caution: Program is live - there are learners who will see or be affected by changes you make';
$string['programmandatory'] = 'Program mandatory';
$string['programmembership'] = 'Program Membership';
$string['programmessages'] = 'Program messages';
$string['programmessagessaved'] = 'Program messages saved';
$string['programmessagessavedsuccessfully'] = 'Program messages saved successfully';
$string['programname'] = 'Program Name';
$string['programnotavailable'] = 'Program is not available to learners';
$string['programnotcurrentlyavailable'] = 'This program is not currently available to learners';
$string['programnotlive'] = 'Program is not live';
$string['programoverdue'] = 'Program overdue';
$string['programoverduemessage'] = 'Program overdue message';
$string['programoverduemessage_help'] = 'This message will be sent at the specified time after a program becomes due.';
$string['programoverviewfiles'] = 'Summary files';
$string['programoverviewfiles_help'] = 'Program summary files, such as images, are displayed in the list of programs together with the summary.';
$string['programrecurring'] = 'Program recurring';
$string['programs'] = 'Programs';
$string['programsandcertificationsdisabled'] = 'Programs and Certifications are disabled on this site';
$string['programscomplete'] = 'Programs complete';
$string['programscerts'] = 'Programs / Certifications';
$string['programsdisabled'] = 'Programs are not enabled on this site';
$string['programshortname'] = 'Program Short Name';
$string['programshortname_help'] = 'The program shortname will be used in several places where the full name isn\'t appropriate (such us in the subject line of an alert message).';
$string['programsinthiscategory'] = 'Programs in this category ({$a})';
$string['programsmovedout'] = 'Programs moved out from {$a}';
$string['programsummary'] = 'Program Summary';
$string['programupdatecancelled'] = 'Program update cancelled';
$string['programupdatefail'] = 'Program update failed';
$string['programupdatesuccess'] = 'Program update successful';
$string['programvisibility'] = 'Program Visibility';
$string['programvisibility_help'] = 'If the program is visible, it will appear in program listings and search results and learners will be able to view the program contents.

If the program is not visble, it will not appear in program listings or search results but the program will still be displayed in the learning plans of any learners who have been assigned to the program and learners can still access the program if they know the program\'s URL.';
$string['programvisible'] = 'Program Visible';
$string['programvisibledisabled'] = 'Program Visible (not applicable)';
$string['progress'] = 'Progress';
$string['reason'] = 'Extension reason';
$string['reasonapprovedmessage'] = 'The reason given for approving the extension was: {$a}';
$string['reasondeniedmessage'] = 'The reason given for denying the extension was: {$a}';
$string['reasonforextension'] = 'Reason for extension';
$string['recentlyadded'] = 'Recently added';
$string['recurrence'] = 'Recurrence';
$string['recurrence_help'] = 'Recurrence defines the time period when the recurring course must be repeated. Recurrence can be specified by any number of days, weeks or months.';
$string['recurrencehistorytask'] = 'Program recurrence history';
$string['recurrencetask'] = 'Program reccurence';
$string['recurring'] = 'Recurring';
$string['recurringcourse'] = 'Recurring course';
$string['recurringcourse_help'] = 'Displays the selected recurring course.

Only one course can be chosen for recurrence. To change the course, select a new course from the dropdown menu and click **Save Changes** to save the change.';
$string['recurringcourseset'] = 'Recurring course set';
$string['recurringcourseset_help'] = 'A recurring course set only allows the selection of a single course. Multiple courses from courses sets and competencies can not be defined.';
$string['recurringprogramhistory'] = 'History record for recurring program {$a}';
$string['recurringprogramhistoryfor'] = 'History record for {$a->username} for recurring program {$a->progname}';
$string['recurringprograms'] = 'Recurring programs';
$string['remove'] = 'Remove';
$string['removeassignment'] = 'Remove program assignment';
$string['removeassignmentconfirmation'] = 'Are you sure you want to remove this program assignment?';
$string['removecompletiondate'] = 'Remove completion date';
$string['removeduedate'] = 'Remove due date';
$string['removed'] = 'Removed';
$string['removedfromprogram'] = '\'{$a}\' has been removed from the program';
$string['repeatevery'] = 'Repeat every';
$string['requestextension'] = '(Request an extension)';
$string['requiredlearning'] = 'Required Learning';
$string['requiredlearninginstructions'] = 'Your required learning is shown below.';
$string['requiredlearninginstructionsuser'] = '{$a}\'s required learning is shown below.';
$string['requiredlearningmenu'] = 'Required Learning';
$string['resortprogramsbyname'] = 'Re-sort programs by name';
$string['returntoprogram'] = 'Return to program';
$string['rolprogramsourcename'] = 'Record of Learning: Programs';
$string['rplcomments'] = 'Comments';
$string['rplgrade'] = 'Grade';
$string['saveallchanges'] = 'Save all changes';
$string['saveprogram'] = 'Save program';
$string['searchforindividual'] = 'Search for individual by name or ID';
$string['searchprograms'] = 'Search programs';
$string['select'] = 'Select';
$string['selectcompetency'] = 'Select a competency...';
$string['selectcourse'] = 'Select a course...';
$string['sendmessagestask'] = 'Send messages related to programs';
$string['searchprogramassignments'] = 'Search assignments';
$string['setcompletion'] = 'Set completion';
$string['setduedate'] = 'Set due date';
$string['setfixedcompletiondate'] = 'Set fixed completion date';
$string['setlabel'] = 'Course set label';
$string['setlabel_help'] = 'Use the course set label to describe the grouping of courses within the set.

The aim is to make each set more readable and aid the Learners understanding of the learning path. For example the first set of courses could be called \'Phase One - Induction\' and the second set of courses \'Phase Two - Health & Safety\'.';
$string['setofcourses'] = 'Set of courses';
$string['settimerelativetoevent'] = 'Set time relative to event';
$string['shortname'] = 'Short name';
$string['showingresults'] = 'Showing results {$a->from} - {$a->to} of {$a->total}';
$string['somecourses'] = 'Some courses';
$string['somecoursesfrom'] = 'some courses from';
$string['source'] = 'Source';
$string['startdate'] = 'Start date';
$string['startinposition'] = 'start in position';
$string['status'] = 'Status';
$string['statusprogramcomplete'] = 'Program complete';
$string['statusprogramincomplete'] = 'Program incomplete';
$string['successfullyresolvedexceptions'] = 'Successfully resolved exceptions';
$string['summary'] = 'Summary';
$string['summary_help'] = 'Summary description of the program.';
$string['switchrecurringcourses'] = 'Switch recurring courses in programs';
$string['tagarea_prog'] = 'Programs & Certifications';
$string['then'] = 'then';
$string['therearenoprogramstodisplay'] = 'There are no programs to display.';
$string['thisactioncannotbeundone'] = 'This action cannot be undone';
$string['thiswillaffect'] = 'This will affect {$a} learners';
$string['timeallowance'] = 'Time allowance';
$string['timeduefixedcert'] = 'Cannot change - set automatically';
$string['timeduefixedprog'] = 'Cannot change - user complete';
$string['timeperiod'] = 'Time period';
$string['tomanyresults'] = 'Too many results';
$string['tomanyresults_detail'] = 'Viewing limit of 100 results exceeded. Use <b>search and filter</b> in order to meet the viewing limit';
$string['toprogram'] = 'to program';
$string['tosaveassignments'] = 'To save all assignment changes click \'Save all changes\'. To edit assignment changes click \'Edit assignments\'. Saving assignments cannot be undone.';
$string['tosavecontent'] = 'To save content changes click \'Save all changes\'. To edit content changes click \'Edit content\'. Saving content changes cannot be undone.';
$string['tosavemessages'] = 'To save all message changes, click \'Save all changes\'. To edit message changes click \'Edit messages\'. Saving message changes cannot be undone.';
$string['totallearnersassigned'] = 'Total learners assigned';
$string['totallearnersassigned_help'] = 'This total may differ from the sum of learners in groups below. This is because some users\' assignments may not have been processed by the system or the program may not currently be active. Some users may also be in multiple assigned groups, but they are only assigned once.';
$string['totalusersinassignment'] = 'Number of users in this assignment: {$a}';
$string['transactiondatetime'] = 'Date / time';
$string['transactionuser'] = 'Change made by';
$string['transactionuserhasnone'] = 'No transactions are recorded for this user.';
$string['trigger'] = 'Trigger';
$string['trigger_help'] = 'The trigger time determines when the message will be sent in relation to the event described (e.g. 4 weeks after the program is completed).';
$string['type'] = 'Type';
$string['unenrolment'] = 'Un-enrolment';
$string['unenrolmentmessage'] = 'Un-enrolment message';
$string['unenrolmentmessage_help'] = 'This message will be sent whenever a user is un-assigned from a program.';
$string['unknown'] = 'Unknown';
$string['unknowncompletiontype'] = 'Unrecognised completion type for course set {$a}';
$string['unknownexception'] = 'Unknown exception';
$string['unknownusersrequiredlearning'] = 'Unknown User\'s Required Learning';
$string['unresolvedexceptions'] = '{$a} unresolved issue(s)';
$string['untitledset'] = 'Untitled set';
$string['update'] = 'Update';
$string['updateextensionfailall'] = 'Failed to update all extensions';
$string['updateextensionfailcount'] = 'Failed to update {$a} extension(s)';
$string['updateextensions'] = 'Update Extensions';
$string['updateextensionsuccess'] = 'All extensions successfully updated';
$string['userassignmentstask'] = 'Program user assignments';
$string['userdataitemassignment_completion'] = 'Program assignments and completion';
$string['userdataitemassignment_completion_help'] = 'When purging, a new assignment may be triggered after purging is complete if the user still meets the assignment criteria (due to audience, organisation, position or management hierarchy).';
$string['userhasnocompletionhistory'] = 'The user has no historical completion records.';
$string['userid'] = 'User ID';
$string['usernotcurrentlyassigned'] = 'The user is not currently assigned. A user cannot have a current completion record unless they are assigned.';
$string['userhasdismissedexception'] = 'The user currently has a dismissed exception. You must override the exception before you can edit the current completion record.';
$string['variablesubstitution_help'] = '## Variable substitution

In program messages, certain variables can be inserted into the subject and/or body of a message so that they will be replaced with real values when the message is sent. The variables should be inserted into the text exactly as they are shown below. The following variables can be used:

%userfullname%
:   This will be replaced by the recipient\'s full name.

%username%
:   This will be replaced by the user\'s username.

%programfullname%
:   This will be replaced by the program\'s full name.

%completioncriteria%
:   This will be replaced by the completion criteria set in the assignment tab.

%duedate%
:   This will be replaced by the date assigned to the user to complete the program.

%managername%
:   This will be replaced by the manager\'s name.

%manageremail%
:   This will be replaced by the manager\'s email.

%setlabel%
:   This will be replaced by the course set label (it will only be replaced if the message relates to a course set.';
$string['viewallprograms'] = 'View all programs';
$string['viewallrequiredlearning'] = 'View all';
$string['viewcourse'] = 'View course';
$string['viewdates'] = 'View dates';
$string['viewexceptions'] = 'View exception report to resolve issue(s).';
$string['viewinguserextrequests'] = 'Viewing extension requests for {$a}';
$string['viewingxusersprogram'] = 'You are viewing <a href="{$a->wwwroot}/user/view.php?id={$a->id}">{$a->fullname}\'s</a> progress on this program.';
$string['viewprogram'] = 'View program';
$string['viewprogramassignments'] = 'View program assignments';
$string['viewprogramdetails'] = 'View program details';
$string['viewrecurringprogramhistory'] = 'View history';
$string['visible'] = 'Visible';
$string['weeks'] = 'Week(s)';
$string['xdays'] = '{$a} Day(s)';
$string['xlearnerscurrentlyenrolled'] = 'There are {$a} learners currently enrolled on this program.';
$string['xmonths'] = '{$a} Month(s)';
$string['xpoints'] = '{$a} points';
$string['xsrequiredlearning'] = '{$a}\'s Required Learning';
$string['xweeks'] = '{$a} Week(s)';
$string['xyears'] = '{$a} Year(s)';
$string['years'] = 'Year(s)';
$string['youareassigned'] = 'You are assigned to this program';
$string['youareviewingxsrequiredlearning'] = 'You are viewing <a href="{$a->site}/user/view.php?id={$a->userid}">{$a->name}\'s</a> required learning.';
$string['youhaveadded'] = 'You have added {$a->itemnames} to this program<br />
<br />
<strong>This will asign {$a->affectedusers} users to the program</strong><br />
<br />
This change will be applied once the \'Save all changes\' button is clicked on the main Program assignments screen';
$string['youhavemadefollowingchanges'] = 'You have made the following changes to this program';
$string['youhaveremoved'] = 'You have removed {$a->itemname} from this program<br />
<br />
<strong>This will unasign {$a->affectedusers} users from the program</strong><br />
<br />
This change will be applied once the \'Save all changes\' button is clicked on the main Program assignments screen';
$string['youhaveunsavedchanges'] = 'You have unsaved changes.';
$string['youmustcompletebeforeproceedingtolearner'] = 'You must complete {$a->mustcomplete} before proceeding to complete {$a->proceedto}';
$string['youmustcompletebeforeproceedingtomanager'] = 'must complete {$a->mustcomplete} before proceeding to complete {$a->proceedto}';
$string['youmustcompletebeforeproceedingtoviewing'] = 'A learner must complete {$a->mustcomplete} before proceeding to complete {$a->proceedto}';
$string['youmustcompleteorlearner'] = 'You must complete {$a}';
$string['youmustcompleteormanager'] = 'must complete {$a}';
$string['youmustcompleteorviewing'] = 'A learner must complete {$a}';
$string['z:incompleterecurringprogrammessage'] = 'A course in a recurring program that you are enrolled on has reached its end date but you have not completed the course. This course must be completed in order to meet the requirements of the program.';
$string['z:incompleterecurringprogramsubject'] = 'Incomplete recurring course';

// Deprecated since Totara 11

$string['error:cannotrequestextnotuser'] = 'You cannot request an extension for another user';
$string['extensionrequestfailed:nomanager'] = 'Extension request was not sent. Manager could not be found';
$string['error:noprogramcompletionfound'] = 'No program completion record was found';

// Deprecated since Totara 12

$string['contentavailability'] = 'Hide currently unavailable content';
$string['contentavailability_help'] = 'Sets whether the report will include programs before/after the available from/until settings';
