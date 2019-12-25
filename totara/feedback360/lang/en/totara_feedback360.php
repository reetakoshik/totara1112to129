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
 * @subpackage totara_feedback360
 *
 * totara_feedback360 specific language strings.
 * these should be called like get_string('key', 'totara_feedback360');
 */

$string['active'] = 'Active';
$string['activate'] = 'Activate';
$string['activatenow'] = '(Activate Now)';
$string['addsystemusers'] = 'Add user(s)';
$string['alreadyreplied'] = 'This user has already replied';
$string['anoncancel'] = 'Can not cancel anonymous feedback360s';
$string['anonrequestform'] = 'Requests can not be removed from anonymous feedbacks';
$string['anonymous'] = 'Anonymous';
$string['anonymousfeedback'] = 'Anonymous feedback';
$string['anonymousfeedback_help'] = 'If checked feedback requests made using this form will be anonymous, meaning the recipient will see the responses but will not know which response came from which user. To ensure anonymity, once an anonymous feedback request has been created the list of responders cannot be edited.';
$string['anonymoususer'] = 'Anonymous user';
$string['answernow'] = 'Respond now';
$string['assigncurrentgroups'] = 'Assigned Groups';
$string['assigncurrentusers'] = 'Assigned Users';
$string['assignedtoxdraftusers'] = 'Assigned to {$a} draft user(s)';
$string['assignedtoxusers'] = 'Assigned to {$a} user(s)';
$string['assignincludechildren'] = 'Include Child Groups?';
$string['assigngroup'] = 'Assign Group to 360&deg; Feedback?';
$string['assigngrouptype'] = 'Assignment Type';
$string['assignments'] = 'Assignments';
$string['assignnumusers'] = 'Assigned Users';
$string['assignsourcename'] = 'Assigned Group';
$string['backtofeedback360'] = 'Back to feedback';
$string['backtofeedbackrequest'] = 'Back to feedback request';
$string['byduedate'] = ' by {$a}';
$string['cleanuptask'] = 'Cleanup Feedback360';
$string['cancellationalert'] = '{$a->userfrom} has cancelled their feedback request "{$a->feedbackname}", you no longer need to respond.';
$string['cancellationemail'] = '{$a->userfrom} has cancelled their feedback request "{$a->feedbackname}", you no longer need to respond.';
$string['cancellationsubject'] = '{$a->userfrom} Feedback request cancellation';
$string['cancelrequest'] = 'Cancel Feedback Request';
$string['cancelrequestconfirm'] = 'Are you sure you want to cancel this feedback request?';
$string['cancelrequestcontinued'] = ' No further feedback will be accepted but all current replies will be kept.';
$string['cancelrequestsuccess'] = 'Request successfully cancelled';
$string['cancelusersemail'] = 'Cancel existing requests to these external users:';
$string['close'] = 'Close';
$string['closed'] = 'Closed';
$string['closenow'] = '(Close Now)';
$string['configenablefeedback360'] = 'This option will let you: Enable(show)/Disable 360 Feedback features from users on this site.

* If Show is chosen, all links, menus, tabs and option related to 360 feedbacks will be accessible.
* If Disable is chosen, 360 feedbacks will disappear from any menu on the site and will not be accessible.';
$string['confirmactivatefeedback360'] = 'Are you sure you want to activate Feedback - {$a}?';
$string['confirmclosefeedback360'] = 'Are you sure you want to close Feedback - {$a}?';
$string['confirmdeletefeedback360'] = 'Are you sure you want to delete Feedback - {$a}?';
$string['confirmdeletequestion'] = 'Are you sure you want to delete question - {$a}?';
$string['completed'] = 'Completed';
$string['content'] = 'Content';
$string['contentupdated'] = 'Content updated';
$string['copy'] = 'Copy';
$string['createfeedback360'] = 'Create Feedback';
$string['createfeedback360heading'] = 'Create a new 360&deg; Feedback';
$string['currentrequestees'] = 'Current requestees';
$string['currentusers'] = 'Current users';
$string['currentusersmatching'] = 'Current matching users \'{$a}\'';
$string['delete'] = 'Delete';
$string['deletedfeedback360'] = 'Successfully Deleted Feedback';
$string['deletefeedback360s'] = 'Delete Feedback - {$a}';
$string['deletefeedback360conformation'] = 'Are you sure you want to delete {$a}?';
$string['deletefeedback360responses'] = 'All content will be permanently deleted including {$a} completed responses.';
$string['description'] = 'Description';
$string['description_help'] = 'When a feedback description is created the information displays after feedback name.';
$string['draft'] = 'Draft';
$string['duedate'] = 'Due Date';
$string['duedate_help'] = 'The date requested users should reply by.';
$string['emailmissing'] = 'Email missing';
$string['emailrequestsexisting'] = 'Existing External Feedback Request(s)';
$string['emailrequestsnew'] = 'Add External Feedback Request(s)';
$string['emailrequestsnew_help'] = 'To request feedback from external users, enter one email address per line.';
$string['emailrequesthtml'] = '<p>{$a->fullname} has requested you fill in their feedback form. Please click the link below and fill in the displayed form:<p>
<p>{$a->link}</p>';
$string['enablefeedback360'] = 'Enable 360 Feedbacks';
$string['emailrequeststr'] = '{$a->fullname} has requested you fill in their feedback form. Please visit the URL below and fill in the displayed form:
{$a->url}';
$string['emailrequestsubject'] = 'Feedback request from {$a->fullname}';
$string['emailrequesturlmask'] = 'here';
$string['error:accessdenied'] = 'Access Denied, you do not have permission to view this page';
$string['error:activationconfirmation'] = 'Feedback is not ready to be activated';
$string['error:activationstatus'] = 'Cannot activate a Feedback that is neither draft or closed';
$string['error:cannotchangestatus'] = 'Current status {$a->oldstatus} cannot be changed to {$a->newstatus}';
$string['error:deletefromanon'] = 'Can not delete requests from an anonymous feedback';
$string['error:duedateformat'] = 'Due date is not in recognised format.';
$string['error:duedatepast'] = 'Due date is in the past, please select a value in the future.';
$string['error:emailformat'] = 'These emails do not match the expected format: ';
$string['error:emailduplicate'] = 'You have duplicate emails, please enter an email only once: ';
$string['error:emptyuserrequests'] = 'You have no recipients, please select a user or enter an email';
$string['error:respassignmentaccess'] = 'You do not have permission to access this page';
$string['error:requestdeletefailure'] = 'Failed to delete feedback request, authentication failure';
$string['error:feedback360isactive'] = 'Feedback must be in \'Draft\' or \'Closed\' state to be removed';
$string['error:feedback360noteditable'] = 'Feedback can not be edited while in an \'Active\' state';
$string['error:feedbacknotactive'] = 'Feedback can not be edited unless the form is active';
$string['error:feedbacktablecreation'] = 'Feedback must be saved before creating answers table';
$string['error:invalidparams'] = 'Invalid Parameters';
$string['error:learnersrequired'] = 'Feedback must be assigned to users';
$string['error:questionsrequired'] = 'Feedback must have at least one basic or review question';
$string['error:recipientsrequired'] = 'At least one group of recipients must be selected';
$string['error:newduedatebeforeold'] = 'The due date can not be set to an earlier date, please set it to a date equal to or after the existing due date.';
$string['error:noformselected'] = 'Error no feedback form selected. Please select a feedback form before continuing.';
$string['error:readonly'] = 'Cannot submit form in read only mode';
$string['error:submitform'] = 'Please, fill form with valid required data before submit or save progress to finish later.';
$string['error:unexpectedtype'] = 'The variable {$a} does not meet the expected type';
$string['error:unrecognisedaction'] = 'Trying to preform an unrecognised action: {$a}';
$string['error:previewpermissions'] = 'You do not have permission to preview this feedback';
$string['evaluateyourself'] = 'Evaluate yourself';
$string['eventcreatedfeedback'] = 'Created Feedback360';
$string['eventcreatedrequest'] = 'Created Feedback360 Request';
$string['eventdeletedfeedback'] = 'Deleted Feedback360';
$string['eventdeletedrequest'] = 'Deleted Feedback360 Request';
$string['eventupdatedfeedback'] = 'Updated Feedback360';
$string['feedback360'] = '360&deg; Feedback';
$string['feedback360:managefeedback360'] = 'Manage Feedback forms';
$string['feedback360:managestafffeedback'] = 'Manage staff members Feedback requests';
$string['feedback360:clonefeedback360'] = 'Clone Feedback';
$string['feedback360:assignfeedback360togroup'] = 'Assign Feedback to group';
$string['feedback360:viewassignedusers'] = 'View users assigned to a Feedback';
$string['feedback360:manageactivation'] = 'Manage Feedback activation';
$string['feedback360:managepageelements'] = 'Manage Feedback content';
$string['feedback360:viewstaffreceivedfeedback360'] = 'View staff members Feedback requests';
$string['feedback360:viewstaffrequestedfeedback360'] = 'View Feedback requested of staff members and their responses';
$string['feedback360:viewownreceivedfeedback360'] = 'View own Feedback requests';
$string['feedback360:viewownrequestedfeedback360'] = 'View Feedback requested of you and your responses';
$string['feedback360:manageownfeedback360'] = 'Manage own Feedback requests';
$string['feedback360aboutcolleagues'] = 'Give feedback about your colleagues';
$string['feedback360aboutuser'] = 'Feedback about {$a}';
$string['feedback360aboutyou'] = 'Feedback about you';
$string['feedback360activated'] = 'Successfully activated Feedback';
$string['feedback360activenochangesallowed'] = 'This 360 Feedback is active, no changes can be made to learner assignments';
$string['feedback360closednochangesallowed'] = 'This 360 Feedback is closed, no changes can be made to learner assignments';
$string['feedback360cloned'] = 'Successfully cloned Feedback';
$string['feedback360closed'] = 'Successfully closed Feedback';
$string['feedback360created'] = 'Successfully created Feedback';
$string['feedback360disabled'] = '360 Feedbacks are not enabled on this site';
$string['feedback360fixerrors'] = 'You must fix the following errors prior to feedback activation';
$string['feedback360notfound'] = 'Feedback form not found';
$string['feedback360requestdeleted'] = 'Feedback request successfully deleted';
$string['feedback360updated'] = 'Successfully updated Feedback';
$string['feedbackanonymous'] = 'This feedback is anonymous. The recipient will be able to read the feedback but they will not know who each reponse is from. This feedback request has been sent to {$a} users (including you).';
$string['feedbacknotanonymous'] = 'Note this feedback will be submitted with your name attached. The recipient will be able to read the feedback and know that it is from you.';
$string['feedbacksubmitted'] = 'Feedback submitted';
$string['givefeedback'] = 'Give Feedback';
$string['invalidsesskey'] = 'The given session key is not valid. Please resend data again';
$string['loadfeedback360failure'] = 'Failed to load the 360 Feedback';
$string['managefeedback360'] = 'Manage Feedback';
$string['managefeedback360s'] = 'Manage 360&deg; Feedbacks';
$string['managercancellationalert'] = '{$a->userfrom} has cancelled their staff member {$a->staffname}\'s feedback request "{$a->feedbackname}", you no longer need to respond.';
$string['managercancellationemail'] = '{$a->userfrom} has cancelled their staff member {$a->staffname}\'s feedback request "{$a->feedbackname}", you no longer need to respond.';
$string['managercancellationsubject'] = '{$a->staffname} Feedback request cancellation';
$string['manageremailrequesthtml'] = '<p>{$a->fullname} has requested you fill in their staff member {$a->staffname}\'s feedback form. Please click the link below to start filling out the form:<\p>
<p>{$a->link}</p>';
$string['manageremailrequeststr'] = '{$a->fullname} has requested you fill in their staff member {$a->staffname}\'s feedback form. Please visit the url below to start filling out the form:

{$a->url}';
$string['manageremailrequestsubject'] = '{$a->fullname} requests Feedback about their staff member {$a->staffname}';
$string['managerreminderemailbody'] = 'I wanted to remind you to fill in the feedback request about my staff member {$a->staffname}. Can you please fill in the form{$a->timedue}, visit the URL below to get started.
{$a->url}

Thank you,
{$a->userfrom}';
$string['managerreminderemailbodyhtml'] = 'I wanted to remind you to fill in the feedback request about my staff member {$a->staffname}. Can you please fill in the form{$a->timedue}, visit the URL below to get started.
{$a->link}

Thank you,
{$a->userfrom}';
$string['managerreminderemailsubject'] = 'Reminder: {$a->staffname}\'s feedback request';
$string['manageuserrequests'] = 'Manage user requests';
$string['messages'] = 'Messages';
$string['myfeedback'] = 'My Feedback';
$string['name'] = 'Name';
$string['nameemail'] = 'Name/Email';
$string['name_help'] = 'This is the name that will appear at the top of your feedback forms and reports.';
$string['next'] = 'Next';
$string['nofeedbackavailable'] = 'There is no feedback available yet';
$string['nofeedback360togive'] = 'You have not been requested to give any feedback yet';
$string['nofeedback360s'] = 'No feedbacks have been created';
$string['nogroupassignments'] = 'There are currently no groups assigned';
$string['notallowed'] = 'Not allowed';
$string['notcompleted'] = 'Not Completed';
$string['nonerequested'] = 'None requested';
$string['nochangestobemade'] = 'No changes to be made';
$string['nouserassignments'] = 'There are currently no users assigned';
$string['options'] = 'Options';
$string['optional'] = 'Optional';
$string['overdue'] = 'Overdue';
$string['pending'] = '(pending)';
$string['pluginname'] = 'Totara Feedback';
$string['potentialrequestees'] = 'Potential requestees';
$string['potentialusers'] = 'Potential users';
$string['potentialusersmatching'] = 'Potential matching users \'{$a}\'';
$string['progresssaved'] = 'Progress saved';
$string['preview'] = 'Preview';
$string['previewencased'] = '(Preview)';
$string['previewheader'] = 'Previewing "{$a}"';
$string['previewsubheader'] = 'This shows the page as someone responding to a feedback request would view it.';
$string['recipient:email'] = 'External email addresses';
$string['recipient:anyuser'] = 'Any site user';
$string['recipient:linemanager'] = 'Their line manager';
$string['recipient:directreports'] = 'Their direct reports (managers only)';
$string['recipient:audiencies'] = 'Audiencies they are member of';
$string['recipient:samepos'] = 'People with the same position';
$string['recipient:sameorg'] = 'People with the same organisation';
$string['recipientdesc'] = 'Select groups of users the assignee can request feedback';
$string['recipients'] = 'Recipients';
$string['recipientsupdated'] = 'Recipients updated';
$string['remind'] = 'remind';
$string['reminderconfirm'] = 'This will send a reminder message to everyone you have requested feedback from that has yet to respond:';
$string['reminderconfirmanonymous'] = 'This will send a reminder message to the {$a} user(s) that have yet to respond.';
$string['reminderssent'] = 'Reminders sent for {$a}';
$string['remindresponders'] = 'Send Feedback Reminders';
$string['reminderemailsubject'] = 'Reminder: {$a->requestername}\'s feedback request';
$string['reminderemailbody'] = 'This is to remind you that {$a->requestername} asked you to complete a feedback form about them{$a->timedue}, please visit the URL below and complete the form. {$a->url}';
$string['reminderemailbodyhtml'] = 'This is to remind you that {$a->requestername} asked you to complete a feedback form about them{$a->timedue}, please visit the URL below and complete the form. {$a->link}';
$string['removerequest'] = 'Delete Feedback Request';
$string['removerequestconfirm'] = 'Are you sure you want to remove the feedback request to {$a}?';
$string['removeuserfromrequest'] = 'Remove feedback request from {$a}';
$string['responsecount'] = '{$a->responded} out of {$a->total}';
$string['responsecountnew'] = ' {$a->new} New';
$string['response'] = 'Response';
$string['responses'] = 'Responses';
$string['request'] = 'Request';
$string['requestcreatedsuccessfully'] = 'Successfully created feedback request';
$string['requestupdatedsuccessfully'] = 'Successfully updated feedback request';
$string['requested'] = 'Requested: ';
$string['requestfeedback360'] = 'Request Feedback';
$string['requestfeedback360confirm'] = 'Are you sure that you want to...';
$string['requestfeedback360create'] = 'Create new feedback requests for:';
$string['requestfeedback360delete'] = 'Cancel existing requests for:';
$string['requestfeedback360keep'] = 'Send due date updates to:';
$string['requestfeedbackincludeselfevaluation'] = 'Include a self evaluation.';
$string['requestfeedbackoptoutselfevaluation'] = 'Opt out of self evaluation.';
$string['requestuserssystem'] = 'Request Feedback from system users';
$string['requestuserssystemchoose'] = 'Choose the users who you would like to get feedback from:';
$string['requestusersemail'] = 'Request Feedback from external users';
$string['required'] = 'Required';
$string['reviewnow'] = 'View your response';
$string['reviewnowmanager'] = 'View their response';
$string['saveprogress'] = 'Save progress';
$string['selfevaluation'] = 'Self evaluation';
$string['selfevaluation_help'] = 'The Learner can provide an evaluation of their own performance.';
$string['selfevaluationlearner'] = 'Self evaluation';
$string['selfevaluationlearner_help'] = 'Include your own response within this feedback.';
$string['selfevaluationemailrequestsubject'] = 'Request for self evaluation for {$a->feedbackname}';
$string['selfevaluationemailrequestoptionalbody'] = 'You have been invited to complete a self evaluation feedback form. Your participation is optional.

Can you please fill in the form{$a->timedue}, visit the URL below to get started.
{$a->link}

Thank you';
$string['selfevaluationemailrequestoptionalbodyhtml'] = 'You have been invited to complete a self evaluation feedback form. Your participation is optional.
<br /><br />
Can you please fill in the form{$a->timedue}, visit the URL below to get started.
<br /><br />
{$a->link}
<br /><br />
Thank you';
$string['selfevaluationemailrequestrequiredbody'] = 'You have been invited to complete a self evaluation feedback form. Your participation is required.

Can you please fill in the form{$a->timedue}, visit the URL below to get started.
{$a->link}

Thank you';
$string['selfevaluationemailrequestrequiredbodyhtml'] = 'You have been invited to complete a self evaluation feedback form. Your participation is required.
<br /><br />
Can you please fill in the form{$a->timedue}, visit the URL below to get started.
<br /><br />
{$a->link}
<br /><br />
Thank you';
$string['selfevaluationemailremindersubject'] = 'Request for self evaluation for {$a->feedbackname}';
$string['selfevaluationemailreminderoptionalbody'] = 'You have been invited to complete a self evaluation feedback form. Your participation is optional.

Can you please fill in the form{$a->timedue}, visit the URL below to get started.
{$a->link}

Thank you';
$string['selfevaluationemailreminderoptionalbodyhtml'] = 'You have been invited to complete a self evaluation feedback form. Your participation is optional.
<br /><br />
Can you please fill in the form{$a->timedue}, visit the URL below to get started.
<br /><br />
{$a->link}
<br /><br />
Thank you';
$string['selfevaluationemailreminderrequiredbody'] = 'You have been invited to complete a self evaluation feedback form. Your participation is required.

Can you please fill in the form{$a->timedue}, visit the URL below to get started.
{$a->link}

Thank you';
$string['selfevaluationemailreminderrequiredbodyhtml'] = 'You have been invited to complete a self evaluation feedback form. Your participation is required.
<br /><br />
Can you please fill in the form{$a->timedue}, visit the URL below to get started.
<br /><br />
{$a->link}
<br /><br />
Thank you';
$string['settings'] = 'Settings';
$string['status'] = 'Status';
$string['statusat'] = 'Status: ';
$string['stop'] = 'stop';
$string['submitfeedback'] = 'Submit feedback';
$string['timedue'] = 'Due: ';
$string['update'] = 'Update';
$string['updatealert'] = '{$a->userfrom} has updated the due date of their feedback request "{$a->feedbackname}", you now have until {$a->timedue} to respond.';
$string['updateemail'] = '{$a->userfrom} has updated the due date of their feedback request "{$a->feedbackname}", you now have until {$a->timedue} to respond.';
$string['updatesubject'] = '{$a->userfrom} Feedback update';
$string['userdataitemresp_assignments'] = 'User responses to Feedback360 requests';
$string['userdataitemresp_assignments_help'] = 'The user\'s response to any Feedback360 requests they have received, including their responses to requests derived from feedback they’ve been assigned to (self evaluation).';
$string['userdataitemuser_assignments'] = 'Feedback360 about the user';
$string['userdataitemuser_assignments_help'] = 'Any Feedback360 the user has been assigned to, and any responses users have made to their requests, including their own self evaluation.';
$string['managerupdatealert'] = '{$a->userfrom} has updated the due date of their staff member {$a->staffname}\'s feedback request "{$a->feedbackname}", you now have until {$a->timedue} to respond.';
$string['managerupdateemail'] = '{$a->userfrom} has updated the due date of their staff member {$a->staffname}\'s feedback request "{$a->feedbackname}", you now have until {$a->timedue} to respond.';
$string['managerupdatesubject'] = '{$a->staffname} Feedback update';
$string['urlrequesturlmask'] = 'here';
$string['userassignmentnotfound'] = 'User assignment not found';
$string['userheaderfeedback'] = 'Feedback for {$a->username} <a href="{$a->site}/user/view.php?id={$a->userid}" target="_BLANK">View profile</a>';
$string['userheaderfeedbackbyemail'] = 'Feedback for {$a->username} <a href="{$a->profileurl}" target="_blank">View profile</a> from external user {$a->responder}';
$string['userownheaderfeedback'] = 'Feedback about you';
$string['userownheaderselfevalfeedback'] = 'Self evaluation for {$a->username}
<br /><br />
This is your 360&deg; Feedback Self Evaluation. Authorised users such as your manager may be able to review the feedback you provide.';
$string['userxfeedback360'] = '{$a}\'s Feedback';
$string['validationfailed'] = 'Validation Failed';
$string['viewinguserxfeedback360'] = 'You are viewing {$a}\'s Feedback';
$string['viewrequest'] = 'View Request';
$string['viewresponse'] = 'View Response';
$string['viewuserxresponses'] = 'View {$a}\'s responses';
$string['you'] = 'You';


// Deprecated since Totara 10
$string['deletefeedback360questions'] = 'And all related questions';
$string['deletefeedback360assignments'] = 'And all related assignments';
$string['feedback360selectform'] = 'Select the type of feedback you want to receive:';
$string['feedback360selectform_help'] = 'This is the form the users will have to fill in and submit as your feedback, click the preview link next to the name to see the form.';
$string['newrequest'] = 'New Feedback Request';
$string['noavailableforms'] = 'You have no unused forms available';
$string['nofeedback360requested'] = 'There are no feedback requests yet';
