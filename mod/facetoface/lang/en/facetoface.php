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
 * @package mod_facetoface
 */

$string['actions'] = 'Actions';
$string['activate'] = 'Activate';
$string['activitydefaults'] = 'Activity defaults';
$string['addattendees'] = 'Add users';
$string['addattendeestep1'] = "Select users to add (step 1 of 2)";
$string['addattendeestep2'] = "Add users (step 2 of 2)";
$string['addattendeesviaidlist'] = 'Add users via list of IDs';
$string['addattendeesviafileupload'] = 'Add users via file upload';
$string['addasset'] = 'Add an asset';
$string['addeditattendeeserror'] = 'Add/edit attendees error';
$string['addeditattendeesresults'] = 'Add/edit attendees results';
$string['addeditattendeessuccess'] = 'Add/edit attendees success';
$string['addedsuccessfully'] = 'Added successfully';
$string['addingsession'] = 'Adding a new event in {$a}';
$string['additionalfeaturesheading'] = 'Additional features';
$string['addnewasset'] = 'Add a new asset';
$string['addnewfield'] = 'Add a new custom field';
$string['addnewfieldlink'] = 'Create a new custom field';
$string['addnewroom'] = 'Add a new room';
$string['addnotification'] = 'Add notification';
$string['addnotificationtemplate'] = 'Add notification template';
$string['address'] = 'Address';
$string['addremoveattendees'] = 'Add/remove attendees';
$string['addremoveattendeeswaitlisted'] = 'Please Note: Attendees added will be automatically added to the waiting list';
$string['addroom'] = 'Add a room';
$string['addsession'] = 'Add a new event';
$string['addstudent'] = 'Add learner';
$string['afterendofsession'] = 'after end of event';
$string['alljamanagersdesc'] = 'Managers from all job assignements will be chosen if left empty.';
$string['alllocations'] = 'All locations';
$string['allocate'] = 'Allocate spaces for team';
$string['allocatenoteam'] = 'There are no team members you can allocate to this event';
$string['allocationfull_noreserve'] = 'Without replacing your current reservations, you can only allocate {$a} space(s) for this event';
$string['allocationfull_reserve'] = 'You can only allocate {$a} space(s) for this event';
$string['allowassetconflicts'] = 'Allow asset booking conflicts';
$string['allowassetconflicts_help'] = 'Checking this box will allow an asset to be assigned to two or more sessions that are running at the same time.';
$string['allowcancellationanytime'] = 'At any time';
$string['allowcancellationcutoff'] = 'Until specified period';
$string['allowcancellationnever'] = 'Never';
$string['allowcancellations'] = 'Allow cancellations';
$string['allowcancellationsdefault'] = 'Allow cancellations default';
$string['allowcancellationsdefault_help'] = 'Whether events in this activity will allow cancellations by default, can be overridden in the event settings.';
$string['allowcancellations_help'] = 'Whether event attendees will be able to cancel their bookings.';
$string['allowoverbook'] = 'Enable waitlist';
$string['allowroomconflicts'] = 'Allow room booking conflicts';
$string['allowroomconflicts_help'] = 'This will allow room scheduling conflicts to exist.';
$string['allowroomconflictswarning'] = 'Note: Room scheduling conflicts are not automatically prevented. Please ensure that this room is available before creating this booking.';
$string['allowscheduleconflicts'] = 'Allow scheduling conflicts';
$string['allowschedulingconflicts'] = 'Allow user scheduling conflicts';
$string['allowschedulingconflictsnote'] = 'Default: No.
Switch to "Yes" before saving the event if you wish to allow scheduling conflicts. It will switch back to "No" after the event is saved.';
$string['allowschedulingconflicts_help'] = 'If trainers or users are already assigned or booked onto another seminar event at the same time as this event then the administrator will be warned, but can override such warnings and proceed anyway by selecting **Yes** from the dropdown menu.';
$string['allowselectedschedulingconflicts'] = 'Allow selected scheduling conflicts';
$string['allrooms'] = 'All rooms';
$string['allsessionsin'] = 'All events in {$a}';
$string['alreadysignedup'] = 'You have already signed-up for this seminar activity.';
$string['anytime'] = 'Any time';
$string['answer'] = 'Sign in';
$string['answercancel'] = 'Sign out';
$string['approvalreqd'] = 'Approval required';
$string['applyfilter'] = 'Apply filter';
$string['approval_activityapprover'] = '{$a} (activity level approver)';
$string['approval_addapprover'] = 'Add approver';
$string['approval_admin'] = 'Manager and Administrative approval';
$string['approval_manager'] = 'Manager Approval';
$string['approval_managerselect'] = 'Users Select Manager';
$string['approval_none'] = 'No Approval';
$string['approval_role'] = 'Role Approval';
$string['approval_self'] = 'Learner accepts terms and conditions';
$string['approval_siteapprover'] = '{$a} (site level approver)';
$string['approvalusers'] = 'Approval Administrators';
$string['approvalusers_help'] = 'The approval administrators will receive a notification about your request to book into this session. If there are none displayed, please contact the Site administrator.';
$string['approvertime'] = 'Approval Time';
$string['approveremail'] = 'Approver Email';
$string['approvaloptions'] = 'Require approval by';
$string['approvaloptions_help'] = 'Available options are defined by the approval options setting on the seminar administration settings page:

* **No approval**: A user will be immediately booked into an event when signing up.
* **Learner accepts terms and conditions**: A user will be presented the text defined in the text area below, and required to accept the terms and conditions.
* **Event role*: All user\'s assigned to the role in the event will be immediately sent a notification with instructions to approve the user\'s request to sign-up for the event.
* **Manager approval**: The learner\'s manager will be immediately sent a notification with instructions to approve the user\'s request to sign-up for the event.
* **Manager and Administrative approval**: All users selected as an approver will be immediately sent a notification with instructions to approve the user\'s request to sign-up for the event. Approval can then be given by the Manager followed by the Administrator, or the Administrator may finalise the request without Manager approval.';
$string['approvaloptionsheader'] = 'Approval Options';
$string['approvalrequiredby'] = 'Approval required by: ';
$string['approvalterms'] = 'Terms and conditions';
$string['approvaltime'] = 'Time approved';
$string['approve'] = 'Approve';
$string['approved'] = 'Approved';
$string['approvername'] = 'Approver name';
$string['approverrolename'] = 'Approver role';
$string['approveuserevent'] = 'Approve {$a} for this event';
$string['areyousureconfirmwaitlist'] = 'This will be over the event maximum bookings allowance. Are you sure you want to continue?';
$string['assessmentyour'] = 'Your assessment';
$string['assetalreadybooked'] = ' (asset unavailable on selected dates)';
$string['assetcreatesuccess'] = 'Successfully created asset';
$string['assetcustom'] = '(Custom Asset)';
$string['assetcustomfieldtab'] =' Asset';
$string['assetdeleted'] = 'Asset deleted';
$string['assetdescription'] = 'Asset description';
$string['assetdoesnotexist'] = 'Asset does not exist';
$string['assethide'] = 'Hide from users when choosing an asset on the Add/Edit event page';
$string['assethidden'] = 'Asset hidden successfully';
$string['assetname'] = 'Asset name';
$string['assets'] = 'Assets';
$string['assetshow'] = 'Show to users when choosing an asset on the Add/Edit event page';
$string['assetshown'] = 'Asset shown successfully';
$string['assetupdatesuccess'] = 'Successfully updated asset';
$string['attendance'] = 'Attendance';
$string['attendanceinstructions'] = 'Select users who attended the event:';
$string['attendancerequestsupdated'] = 'Attendance requests updated';
$string['attendedsession'] = 'Attended event';
$string['attendeeactions'] = 'Attendee actions';
$string['attendeenote'] = 'Attendee\'s note';
$string['attendees'] = 'Attendees';
$string['approvalnocapacity'] = 'There are {$a->waiting} learners awaiting approval but no spaces available, you cannot approve any more learners at this time.';
$string['approvalnocapacitywaitlist'] = 'There are {$a->waiting} learners awaiting approval but no spaces available - any approvals will be added to the waitlist instead.';
$string['approvalovercapacity'] = 'There are {$a->waiting} learners awaiting approval but only {$a->available} spaces available. Only the first {$a->available} learners you approve will be added to the event.';
$string['approvalovercapacitywaitlist'] = 'There are {$a->waiting} learners awaiting approval but only {$a->available} spaces available.<br /> Only the first {$a->available} learners you approve will be added to the event - additional approvals will be added to the waitlist.';
$string['potentialattendees'] = 'Potential Attendees';
$string['attendeestablesummary'] = 'People planning on or having attended this event.';
$string['available'] = 'Available';
$string['requeststablesummary'] = 'People requesting to attended this event.';
$string['beforeregistrationends'] = 'before registration closes';
$string['beforestartofsession'] = 'before start of event';
$string['backtoassets'] = 'Back to assets';
$string['backtorooms'] = 'Back to rooms';
$string['beforestartofsession'] = 'before start of session';
$string['body'] = 'Body';
$string['body_help'] = 'This is the body of the notification to be sent.

In the notification there are a number of placeholders that can be used, these placeholders will be replaced with the appropriate values when the message is sent.

All place holders are enclosed within square brackets.
There are several types of place holders, the patterns for each are as follows:

* Event details: [placeholder]
* Event custom fields: [session:placeholder]
* Event cancellation custom fields: [sessioncancel:placeholder]
* Multiple session event details: [#sessions][session:placeholder][/sessions]
* Room details for each session: [#sessions][session:room:placeholder][/sessions]
* Room custom fields for each session: [#sessions][session:room:placeholder][/sessions]
* User details: [placeholder]
* User custom fields: [user:placeholder]

You can find out which placeholder are available, and how custom field placeholders work for each type under the relevant heading below.

### 1. Event details

The details of the seminar can be added to the notification by using the desired placeholders from the following list.

* [coursename] - Name of course
* [facetofacename] - Name of seminar activity
* [cost] - Cost of event
* [reminderperiod] - Amount of time before the event that the reminder message is sent
* [sessiondate] - Date of the event the learner is booked on
* [startdate] - Date at the start of the event. If there are multiple sessions it will use the first one.
* [finishdate] - Date at the end of the event. If there are multiple sessions it will use the first one.
* [starttime] - Start time of the event. If there are multiple sessions it will use the first one.
* [finishtime] - Finish time of the event. If there are multiple sessions it will use the first one.
* [duration] - Length of the event
* [details] - Details about the event
* [attendeeslink] - Link to the attendees page for the event
* [lateststarttime] - Start time of the event. If there are multiple sessions it will use the last one.
* [lateststartdate] - Date at the start of the event. If there are multiple sessions it will use the last one.
* [latestfinishtime] - Finish time of the event. If there are multiple sessions it will use the last one.
* [latestfinishdate] - Date at the end of the event. If there are multiple sessions it will use the last one.
* [registrationcutoff] - The deadline for registrations, if not set this will default to [starttime].

### 2. Event custom fields

If you have created event custom fields, and wish to use these in your notification body you can do so using the following placeholder, replacing \'placeholder\' with the shortname for the custom field.

    [session:placeholder]

For example if you have an event custom field with the shortname \'department\', to use the value recorded in the custom field in the notification body you would use the placeholder [session:department].

### 3. Event cancellation custom fields

If you have created event cancellation custom fields, and wish to use these in your notification body you can do so using the following placeholder, replacing \'placeholder\' with the shortname for the custom field.

    [sessioncancel:placeholder]

For example if you have an event custom field with the shortname \'cancellationreason\', to use the value recorded in the custom field in the notification body you would use the placeholder [sessioncancel:cancellationreason].

### 4. Multiple session event details

It is possible to include details of each session if you have multiple sessions for an event.
A segment of the notification can be repeated to include the details of each individual session. To do this, add [#sessions] where you would like the loop to start, add [/sessions] where you would like it to end.

You can then add the following placeholders between these tags:

* [session:starttime] - start time of the session.
* [session:startdate] - start date of the session.
* [session:finishtime] - finish time of the session.
* [session:finishdate] - finish date of the session.
* [session:timezone] - timezone of the session.
* [session:duration] - session duration.

### 4.1. Room details for each session

The details of any rooms used for the session can also be added to the notification by using the desired placeholders from the following list.

* [session:room:name] - name of room assigned to this session.
* [session:room:link] - link to details page for this room.

### 4.2. Room custom fields for each session

In addition to the above, room custom field information for each session can be added to the notification by using the following placeholder, replacing \'placeholder\' with the shortname for the custom field.

    [session:room:cf_placeholder]

For example if you have a room custom field with the shortname \'building\', to use the value recorded in the custom field in the notification body you would use the placeholder [session:room:building].

### 5. User details

Available user placeholders:

* [firstname] - User\'s first name
* [lastname] - User\'s last name
* [middlename] - User\'s middle name
* [firstnamephonetic] - Phonetic spelling of the User\'s first name
* [lastnamephonetic] - Phonetic spelling of the User\'s last name
* [alternatename] - Alternate name the user is known by
* [fullname] - User\'s full name
* [username] - User\'s username
* [idnumber] - User\'s ID Number
* [email] - User\'s email address
* [address] - User\'s address
* [city] - User\'s city
* [country] - User\'s country
* [department] - User\'s department
* [description] - User\'s description
* [institution] - User\'s institution
* [lang] - User\'s language
* [icq] - User\'s ICQ number
* [aim] - User\'s AIM ID
* [msn] - Users\'s MSN ID
* [yahoo] - User\'s Yahoo ID
* [skype] - User\'s Skype ID
* [phone1] - User\'s phone number
* [phone2] - User\'s mobile phone number
* [timezone] - User\'s timezone
* [url] - User\'s URL

### 6. User custom fields

There are also placeholders available for user custom profile fields that can be added to the notification by using the following placeholder, replacing the word placeholder with the user custom profile field shortname:

    [user:placeholder]

For example if you have a user custom profile field with the shortname \'suburb\' that you wish to use in the notification you would [user:suburb] as the placeholder.

### 7. Trusted content

To use placeholders in URL\'s, an admin will need to enable the **Enable trusted content** setting and grant **Trust submitted content** capability to approved roles.';
$string['booked'] = 'Booked';
$string['bookingcancelled'] = 'Your booking has been cancelled.';
$string['bookingconflict'] = 'Booking conflict';
$string['bookingcompleted'] = 'Your request was accepted.';
$string['bookingcompleted_approvalrequired'] = 'Your request was sent to your manager for approval.';
$string['bookingcompleted_roleapprovalrequired'] = 'Your request was sent for approval to each {$a} in this session.';
$string['bookingfull'] = 'Booking full';
$string['bookingopen'] = 'Booking open';
$string['bookingoptions'] = 'Booking options';
$string['bookingrestricted'] = 'Booking restricted';
$string['bookings'] = 'Bookings';
$string['bookingstatus'] = 'You are booked for the following event';
$string['bookingsessioncancelled'] = 'Event cancelled';
$string['building'] = 'Building';
$string['bulkactions'] = 'Bulk actions';
$string['bulkaddattendeeserror'] = 'Bulk add attendees error';
$string['bulkaddattendeesresults'] = 'Bulk add attendees results';
$string['bulkaddattendeessuccess'] = 'Bulk add attendees success';
$string['bulkaddhelptext'] = 'Note: Users must be referenced by their {$a} and must be delimited by a comma or newline';
$string['bulkaddsourceidnumber'] = 'ID number';
$string['bulkaddsourceuserid'] = 'user id';
$string['bulkaddsourceusername'] = 'username';
$string['bulkremoveattendeessuccess'] = 'Bulk remove users success';
$string['calendareventdescriptionbooking'] = 'You are booked for this <a href="{$a}">Seminar event</a>.';
$string['calendareventdescriptionsession'] = 'You have created this <a href="{$a}">Seminar event</a>.';
$string['calendaroptions'] = 'Calendar options';
$string['cancelattendance'] = 'Cancel attendance';
$string['cancelattendees'] = 'Remove from waitlist';
$string['cancelsession'] = 'Cancel event';
$string['cancelingsession'] = 'Cancelling event in {$a}';
$string['cancelsessionconfirm'] = 'Are you completely sure you want to cancel this event? All attendees will be notified. This action cannot be un-done.';
$string['cancellationfields'] = 'Cancellation fields';
$string['cancellationfieldslimitation'] = 'The values entered below will be populated for all selected users.';
$string['cancelledstatus'] = 'Cancelled status';
$string['eventsessioncancelled'] = 'Event cancelled';
$string['cancelbooking'] = 'Cancel booking';
$string['cancelbookingfor'] = 'Cancel booking for {$a}';
$string['cancellationreasoncourseunenrollment'] = '{$a->username} has been unenrolled from the course {$a->coursename}.';
$string['cancellationsent'] = 'You should immediately receive a cancellation email.';
$string['cancellationnotsent'] = 'Seminar activity email notifications are turned off.';
$string['cancellationsentmgr'] = 'You and your manager should immediately receive a cancellation email.';
$string['cancellationstablesummary'] = 'List of people who have cancelled their event signups.';
$string['cancelreason'] = 'Reason';
$string['cancelwaitlist'] = 'Cancel waitlist';
$string['cancelwaitlistfor'] = 'Cancel place on the waitlist for {$a}';
$string['capacity'] = 'Capacity';
$string['capacityallowoverbook'] = '{$a} (enable waitlist)';
$string['capacitycurrentofmaximum'] = '{$a->current} / {$a->maximum}';
$string['capacityoverbooked'] = ' (Overbooked)';
$string['capacityoverbookedlong'] = 'This event is overbooked ({$a->current} / {$a->maximum})';
$string['cancelreservation'] = 'Cancel reservation';
$string['cannotsignupguest'] = 'Cannot sign up guest';
$string['cannotsignupsessioninprogress'] = 'You cannot sign up, this event is in progress';
$string['cannotsignupsessionover'] = 'You cannot sign up, this event is over.';
$string['cannotapproveatcapacity'] = 'You cannot approve any more attendees as this event is full.';
$string['ccmanager'] = 'Manager copy';
$string['ccmanager_note'] = 'Send a copy of this notification to the user\'s manager';
$string['changeselectedusers'] = 'Change selected users';
$string['chooseapprovers'] = 'Select activity level approvers';
$string['cannotapproveatcapacity'] = 'You cannot approve any more attendees as this event is full.';
$string['chooseassets'] = 'Choose assets';
$string['chooseroom'] = 'Choose a room';
$string['cannotapproveatcapacity'] = 'You cannot approve any more attendees as this session is full.';
$string['cleanuptask'] = 'Cleanup seminar';
$string['clearall'] = 'Clear all';
$string['close'] = 'Close';
$string['closed'] = 'Closed';
$string['closeregistrationstask'] = 'Close seminar events registration';
$string['confirm'] = 'Confirm';
$string['confirmattendees'] = 'Confirm';
$string['confirmlotteryheader'] = 'Confirm Play Lottery';
$string['confirmlotterybody'] = '"Play Lottery" randomly chooses attendees from the selected users in order to fill the event to its capacity. The chosen users will be immediately booked to the event and sent a booking confirmation email. Do you want to continue?';
$string['confirmanager'] = 'Confirm manager\'s email address';
$string['confirmation'] = 'Confirmation';
$string['confirmationmessage'] = 'Confirmation message';
$string['confirmationsent'] = 'You will receive a booking confirmation email shortly.';
$string['confirmationsentmgr'] = 'You will be notified about their decision.';
$string['completionstatusrequired'] = 'Require status';
$string['completionstatusrequired_help'] = 'Checking one or more statuses will require a user to achieve at least one of the checked statuses in order to be marked complete in this seminar activity, as well as any other Activity Completion requirements.';
$string['copyingsession'] = 'Copying as a new event in {$a}';
$string['copynotification'] = 'Copy notification';
$string['copynotificationcreated'] = 'Copy of the notification is created.';
$string['copynotificationconfirm'] = 'Confirm you would like to copy the notification <strong>"{$a}"</strong>:';
$string['copynotificationtitle'] = 'Copy of {$a}';
$string['copysession'] = 'Copy event';
$string['cost'] = 'Cost';
$string['cancelbooking'] = 'Cancel booking';
$string['cancellation'] = 'Cancellation';
$string['cancellationcustomfieldtab'] = 'User cancellation';
$string['cancellations'] = 'Cancellations';
$string['cancellationmessage'] = 'Cancellation message';
$string['cancellationconfirm'] = 'Are you sure you want to cancel your booking to this event?';
$string['canceltype'] = 'Cancellation type';
$string['close'] = 'Close';
$string['currentlyselected'] = 'Currently selected';
$string['customfieldother'] = '{$a}';
$string['customfieldroom'] = 'Room: {$a}';
$string['customfieldsession'] = 'Event: {$a}';
$string['cutoff'] = 'Cut-off';
$string['cutoffnote'] = 'before event starts';
$string['cutoff_help'] = 'The amount of time before the first event that messages about minimum bookings will be sent.
This must be at least 24 hours before the event.
The start date of the earliest event must be at least this far in the future.';
$string['created'] = 'Created';
$string['createnewasset'] = 'Create new asset';
$string['createnewroom'] = 'Create new room';
$string['csvtextfile'] = 'CSV text file';
$string['csvtextfile_help'] = 'Preparing a file for upload: Use .CSV text file with a heading row and one or more data rows. Columns must be indicated by commas (,).

All rows must have the following columns:

* \'username\' **OR** \'idnumber\' **OR** \'email\' (use only one)
{$a->customfields}';
$string['csvtextinput'] = 'CSV text input';
$string['currentallocations'] = 'Current allocations ({$a->allocated} / {$a->max})';
$string['currentattendees'] = 'Current attendees';
$string['currentmanager'] = 'Current manager: ';
$string['currentlyassigned'] = 'Currently assigned to an event';
$string['currentstatus'] = 'Current status';
$string['customfieldsheading'] = 'Custom fields';
$string['customfieldsheadingaction'] = '{$a} Custom Fields';
$string['dataoptional'] = 'data may be empty';
$string['date'] = 'Date';
$string['dateadd'] = 'Add a new session';
$string['dateandtime'] = 'Date and time';
$string['dateremove'] = 'Remove this date';
$string['dateselect'] = 'Select date';
$string['datetext'] = 'You are signed in for date';
$string['deactivate'] = 'Deactivate';
$string['decidelater'] = 'Decide Later';
$string['declareinterest'] = 'Declare interest';
$string['declareinterest_help'] = 'Displays an option within the seminar activity to allow a user to flag their interest and write a message without signing up.
Information about those who have declared an interest can be reported on from within the activity.';
$string['declareinterestalways'] = 'Always';
$string['declareinterestfiltercheckbox'] = 'Show only users who declared interest in this activity';
$string['declareinterestin'] = 'Declare interest in {$a}';
$string['declareinterestinconfirm'] = 'You can declare an interest in {$a} in order to be considered when new events are added or places become available in existing events.';
$string['declareinterestenable'] = 'Users can declare interest';
$string['declareinterestnever'] = 'Never';
$string['declareinterestnoupcoming'] = 'When no upcoming events are available for booking';
$string['declareinterestreason'] = 'Reason for interest:';
$string['declareinterestreport'] = 'Declared interest report';
$string['declareinterestreportdate'] = 'Date of declared interest';
$string['declareinterestreportreason'] = 'Stated reason for interest';
$string['declareinterestwithdraw'] = 'Withdraw interest';
$string['declareinterestwithdrawfrom'] = 'Withdraw interest declaration from {$a}';
$string['declareinterestwithdrawfromconfirm'] = 'Are you sure you want to withdraw your interest declaration from {$a}?';
$string['defaultsessiontimes'] = 'Default event times';
$string['defaultstarttime'] = 'Default start time';
$string['defaultstarttimehelp'] = 'Default start time for new events';
$string['defaultfinishtime'] = 'Default finish time';
$string['defaultfinishtimehelp'] = 'Default finish time for new events';
$string['defaultdaysbetweenstartfinish'] = 'Default days between start and finish';
$string['defaultdaysbetweenstartfinish_desc'] = 'The default number of days between the event start and finish.';
$string['defaultdaysskipweekends'] = 'Default days ahead on week days only';
$string['defaultdaysskipweekends_desc'] = 'When defaulting the start and finish dates only count week days';
$string['defaultdaystosession'] = 'Default days ahead for added events';
$string['defaultdaystosession_desc'] = 'When creating a new event it\'s start and finish dates will default to this many days in the future.';
$string['deleteall'] = 'Delete all';
$string['deleteassetconfirm'] = 'Are you sure you want to delete asset <strong>"{$a}"</strong>:';
$string['deletenotificationconfirm'] = 'Confirm you would like to delete the notification <strong>"{$a}"</strong>:';
$string['deletenotificationtemplateconfirm'] = 'Confirm you would like to delete the notification template <strong>"{$a}"</strong>:';
$string['deletereservation'] = 'Delete reservations';
$string['deletereservationconfirm'] = 'Are you sure you want to delete all reservations in this event made by "{$a}"?';
$string['deleteroomconfirm'] = 'Are you sure you want to delete room <strong>"{$a}"</strong>:';
$string['deletesession'] = 'Delete event';
$string['deletesessionconfirm'] = 'Are you completely sure you want to delete this event and all sign-ups and attendance for this event?';
$string['deletingsession'] = 'Deleting event in {$a}';
$string['decideuserlater'] = 'Decide later for {$a}';
$string['decline'] = 'Decline';
$string['declineuserevent'] = 'Decline {$a} for this event';
$string['description'] = 'Introduction text';
$string['details'] = 'Details';
$string['discardmessage'] = 'Discard message';
$string['discountcode'] = 'Discount code';
$string['discountcost'] = 'Discount cost';
$string['discountcosthinttext'] = '';
$string['dismiss'] = 'Dismiss';
$string['dismissedwarning'] = 'The warning will no longer be displayed for this seminar';
$string['downloadsigninsheet'] = 'Download sign-in sheet';
$string['due'] = 'due';
$string['duration'] = 'Duration';
$string['early'] = '{$a} early';
$string['editasset'] = 'Edit asset';
$string['editdate'] = 'Edit session';
$string['editmessagerecipientsindividually'] = 'Edit recipients individually';
$string['editnotificationx'] = 'Edit "{$a}"';
$string['editnotificationtemplate'] = 'Edit notification template';
$string['editsession'] = 'Edit event';
$string['editroom'] = 'Edit room';
$string['editingsession'] = 'Editing event in {$a}';
$string['emailmanager'] = 'Send notice to manager';
$string['email:instrmngr'] = 'Notice for manager';
$string['email:message'] = 'Message';
$string['email:subject'] = 'Subject';
$string['embedded:seminarassets'] = 'Seminars: Manage assets';
$string['embedded:seminarassetsupcoming'] = 'Seminars: Upcoming events using asset';
$string['embedded:seminareventattendance'] = 'Seminars: Event attendees';
$string['embedded:seminarevents'] = 'Seminars: View and manage events';
$string['embedded:seminarinterest'] = 'Seminars: Declared interest';
$string['embedded:seminarrooms'] = 'Seminars: Manage rooms';
$string['embedded:seminarroomsupcoming'] = 'Seminars: Upcoming events using room';
$string['embedded:seminarsessionattendance'] = 'Seminars: Event sign-in sheet';
$string['embedded:seminarsessions'] = 'Seminars: View and manage sessions';
$string['embedded:seminarsignups'] = 'Seminars: Sign ups';
$string['emptylocation'] = 'Location was empty';
$string['enablemincapacity'] = 'Enable minimum bookings';
$string['enablemincapacitynotification'] = 'Notify about minimum bookings';
$string['enablemincapacitynotification_help'] = 'If the minimum bookings have not been reached by the cut-off point, then the appropriate users will be notified. Users to be notified is determined by the role assignments and the configuration setting under *Site administration > Seminars > General Settings > Notification*.';
$string['enrolled'] = 'enrolled';
$string['error:alreadysignedup'] = 'Already signed up';
$string['error:addalreadysignedupattendee'] = 'This user is already signed-up for this seminar activity.';
$string['error:addalreadysignedupattendeeaddself'] = 'You are already signed-up for this seminar activity.';
$string['error:addattendee'] = 'Could not add {$a} to the event.';
$string['error:approvaladminnotactive'] = 'Manager and Administrative approval is not activated';
$string['error:approvalinvalidmanager'] = 'Attendee {$a} is currently not assigned to you in this event';
$string['error:approvalinvalidstatus'] = 'Invalid signup status for attendee {$a}';
$string['error:approverinactive'] = 'User ID: {$a} does not exist or is not active.';
$string['error:approvernotselected'] = 'Please select an approver';
$string['error:approverselected'] = 'User {$a} selected more than once.';
$string['error:approversystem'] = 'User {$a} is system wide approver and cannot be selected here.';
$string['error:assetisinuse'] = 'This asset is used in one or more events';
$string['error:assetconflicts'] = 'Asset has conflicting usage';
$string['error:cancellationsnotallowed'] = 'You are not allowed to cancel this booking.';
$string['error:cancelbooking'] = 'There was a problem cancelling your booking';
$string['error:cannotdeclareinterest'] = 'Cannot declare interest in this seminar activity.';
$string['error:cannotapprovefull'] = 'One or more users were not assigned because the event is fully booked and over booking is not allowed';
$string['error:cannoteditcancelledevent'] = 'This event has been cancelled and can no longer be edited.';
$string['error:cannotemailmanager'] = 'Sent reminder mail for submission id {$a->submissionid} to user {$a->userid}, but could not send the message for the user\'s manager email address ({$a->manageremail}).';
$string['error:cannotemailuser'] = 'Could not send out mail for submission id {$a->submissionid} to user {$a->userid} ({$a->useremail}).';
$string['error:cannotsendconfirmationmanager'] = 'A confirmation message was sent to your email account, but there was a problem sending the confirmation messsage to your manager\'s email address.';
$string['error:cannotsendconfirmationthirdparty'] = 'A confirmation message was sent to your email account and your manager\'s email account, but there was a problem sending the confirmation messsage to the third party\'s email address.';
$string['error:cannotsendconfirmationuser'] = 'There was a problem sending the confirmation message to your email account.';
$string['error:cannotsendrequestuser'] = 'There was a problem sending the signup request message to your email account.';
$string['error:cannotsendrequestmanager'] = 'There was a problem sending the signup request message to your manager\'s email account.';
$string['error:cannotsendconfirmationusermanager'] = 'A confirmation message could not be sent to your email address and to your manager\'s email address.';
$string['error:cannotsignupforacancelledevent'] = 'This event has been cancelled. It is no longer possible to sign up for it.';
$string['error:canttakeattendanceforunstartedsession'] = 'Can not take attendance for an event that has yet to start.';
$string['error:capabilityaddattendees'] = 'You do not have the necessary permissions to add attendees';
$string['error:capabilityremoveattendees'] = 'You do not have the necessary permissions to remove attendees';
$string['error:capacitynotnumeric'] = 'Event maximum bookings is not a number';
$string['error:capacityzero'] = 'Event maximum bookings must be greater than zero';
$string['error:conflictingsession'] = 'The user {$a} is already signed up for another event';
$string['error:couldnotaddfield'] = 'Could not add custom event field.';
$string['error:couldnotaddnotice'] = 'Could not add site notice.';
$string['error:couldnotaddsession'] = 'Could not add event';
$string['error:couldnotaddtrainer'] = 'Could not save new seminar event trainer';
$string['error:couldnotcopysession'] = 'Could not copy event';
$string['error:couldnotdeletefield'] = 'Could not delete custom event field';
$string['error:couldnotdeletenotice'] = 'Could not delete site notice';
$string['error:couldnotdeletesession'] = 'Could not delete event';
$string['error:couldnotcancelsession'] = 'Could not cancel session';
$string['error:couldnotdeletetrainer'] = 'Could not delete a seminar event trainer';
$string['error:couldnotfindsession'] = 'Could not find the newly inserted event';
$string['error:couldnotsavecustomfield'] = 'Could not save custom field';
$string['error:couldnotsaveroom'] = 'There is a room conflict - another event is using the room at the same time';
$string['error:couldnotupdatecalendar'] = 'Could not update event in the calendar.';
$string['error:couldnotupdatefield'] = 'Could not update custom event field.';
$string['error:couldnotupdatef2frecord'] = 'Could not update seminar signup record in database';
$string['error:couldnotupdatenotice'] = 'Could not update site notice.';
$string['error:couldnotupdatesession'] = 'Could not update event';
$string['error:coursemisconfigured'] = 'Course is misconfigured';
$string['error:cronprefix'] = 'Error: seminar cron:';
$string['error:csvcannotparse'] = 'Cannot parse submitted CSV file.';
$string['error:csvinconsistentrows'] = 'Rows {$a} of your file contain a different number of columns than the header row';
$string['error:csvnoidfields'] = 'You did not provide a column called \'username\', \'email\', or \'idnumber\'';
$string['error:csvnorequiredcf'] = 'You did not provide a column called \'{$a}\'';
$string['error:csvtoomanyidfields'] = 'Your file contained more than one of the following columns: \'username\', \'email\', \'idnumber\'';
$string['error:cutofftooclose'] = 'The cut-off time for minimum bookings is too close to the events earliest start date, please set at least a 24 hours cut-off';
$string['error:cutofftoolate'] = 'The cut-off for minimum bookings is after the events earliest start date, it must be before to have any effect.';
$string['error:datesunavailablestuff'] = 'The new dates you have selected are unavailable due to a scheduling conflict with the following resources:<br/>{$a}Please choose different dates or change the selected room/assets.';
$string['error:emailnotfound'] = 'No users were found with the following emails: {$a}';
$string['error:emptylocation'] = 'Location was empty.';
$string['error:emptyvenue'] = 'Venue was empty.';
$string['error:enrolmentfailed'] = 'Could not enrol {$a} into the course.';
$string['error:eventoccurred'] = 'You cannot cancel an event that has already occurred.';
$string['error:fieldidincorrect'] = 'Field ID is incorrect: {$a}';
$string['error:f2ffailedupdatestatus'] = 'Seminar failed to update the user\'s status';
$string['error:idnumbernotfound'] = 'No users were found with the following ID numbers: {$a}';
$string['error:incorrectassetid'] = 'Asset ID was incorrect';
$string['error:incorrectcoursemodule'] = 'Course module is incorrect';
$string['error:incorrectcoursemoduleid'] = 'Course Module ID was incorrect';
$string['error:incorrectcoursemodulesession'] = 'Course Module Seminar Event was incorrect';
$string['error:incorrectfacetofaceid'] = 'Seminar ID was incorrect';
$string['error:incorrectnotificationtype'] = 'Incorrect notification type supplied';
$string['error:invalidstatus'] = 'Invalid signup status';
$string['error:incorrectroomid'] = 'Room ID was incorrect';
$string['error:invaliduserid'] = 'Invalid user ID';
$string['error:isalreadybooked'] = '{$a} is already booked';
$string['error:jobassignementsonsignupdisabled'] = 'Select job assignments on sign up is not enabled for this Seminar activity.';
$string['error:manageremailaddressmissing'] = 'You are currently not assigned to a manager in the system. Please contact the site administrator.';
$string['error:mincapacitymissing'] = 'Session minimum bookings value is missing';
$string['error:mincapacitynotnumeric'] = 'Event minimum bookings is not a number';
$string['error:mincapacitytoolarge'] = 'Event minimum bookings cannot be greater than the capacity';
$string['error:mincapacityzero'] = 'Event minimum bookings cannot be zero';
$string['error:missingrequiredmanager'] = 'This seminar requires manager approval, you are currently not assigned to a manager in the system. Please contact the site administrator.';
$string['error:missingrequiredrole'] = 'This seminar requires role approval, there are no users assigned to this role. Please contact the site administrator.';
$string['error:missingselectedmanager'] = 'This seminar requires manager approval, please select a manager to request approval';
$string['error:mustspecifycoursemodulefacetoface'] = 'Must specify a course module or a seminar ID';
$string['error:mustspecifytimezone'] = 'You must set the timezone for each date';
$string['error:nodatasupplied'] = 'No data supplied';
$string['error:nomanagersemailset'] = 'No manager email is set';
$string['error:nopermissiontosignup'] = 'You don\'t have permission to signup to this seminar event.';
$string['error:nojobassignmentselected'] = 'You must have a suitable job assignment to sign up for this seminar event.';
$string['error:nojobassignmentselectedactivity'] = 'You must have a suitable job assignment to sign up for this seminar activity.';
$string['error:nojobassignmentselectedlist'] = 'You must select a suitable job assignment for all attendees in this list.';
$string['error:nopredefinedassets'] = 'No pre-defined assets';
$string['error:nopredefinedrooms'] = 'No pre-defined rooms';
$string['error:norecipientsselected'] = 'You must choose which learners will receive this notification';
$string['error:noticeidincorrect'] = 'Notice ID is incorrect: {$a}';
$string['error:notificationdoesnotexist'] = 'Notification does not exist';
$string['error:notificationnonduplicate'] = 'Can not delete non-duplicate auto notification';
$string['error:notificationtitletoolong'] = 'The title you have used is too long. The title is typically used as an email subject and should be no more than 78 characters long. Longer titles may be truncated by the users email client.';
$string['error:notificationtemplatemissing'] = 'The following notification templates are missing (notifications could not be created for them):';
$string['error:notificationnocopy'] = 'Notification copy failed.';
$string['error:problemsigningup'] = 'There was a problem signing you up.';
$string['error:removeattendee'] = 'Could not remove {$a} from the event.';
$string['error:roomconflicts'] = 'Room has conflicting usage';
$string['error:roomisinuse'] = 'Room is in use';
$string['error:roomunavailable'] = 'The "{$a}" room is no longer available.';
$string['error:rolerequired'] = 'Seminar require approval by "{$a}" role. Please select at least one user with {$a} role.';
$string['error:selfapprovalupgrade'] = 'Self approval has been moved from sessions to a seminar setting, please run the admin tool to resolve any conflicts before upgrading.';
$string['error:sessionstartafterend'] = 'Event start date/time is after end.';
$string['error:sessiondatesconflict'] = 'This date conflicts with an earlier date in this event';
$string['error:sessiondatesbookingconflict'] = 'Booking conflict: {$a->users} user(s) have another booking on the selected date and time. Change event time or exclude these users to continue. {$a->link}';
$string['error:signedupinothersession'] = 'You are already signed up in another event for this activity. You can only sign up for one event per seminar activity.';
$string['error:shortnamecustomfield'] = 'This shortname is reserved by Totara seminar custom field';
$string['error:takeattendance'] = 'An error occurred while taking attendance';
$string['error:therearexconflicts'] = 'There are ({$a}) conflicts with the proposed time period.';
$string['error:thereisaconflict'] = 'There is a conflict with the proposed time period.';
$string['error:unrecognisedapprovaltype'] = 'Unrecognised approval type passed to facetoface_get_approvaltype_string()';
$string['error:unknownbuttonclicked'] = 'No action associated with the button that was clicked';
$string['error:unknownuserfield'] = 'This field is not supported for user search.';
$string['error:userassignedsessionconflictsameday'] = '{$a->fullname} is already assigned as a \'{$a->participation}\' for {$a->session} at {$a->timestart} to {$a->timefinish} on {$a->datestart}. Please select another user or change the session';
$string['error:userassignedsessionconflictsamedayselfsignup'] = 'You are already assigned as a \'{$a->participation}\' for {$a->session} at {$a->timestart} to {$a->timefinish} on {$a->datestart}.';
$string['error:userbookedsessionconflictsameday'] = '{$a->fullname} is already booked to attend {$a->session} at {$a->timestart} to {$a->timefinish} on {$a->datestart}. Please select another user or change the session';
$string['error:userbookedsessionconflictsamedayselfsignup'] = 'You are already booked to attend {$a->session} at {$a->timestart} to {$a->timefinish} on {$a->datestart}.';
$string['error:userassignedsessionconflictmultiday'] = '{$a->fullname} is already assigned as a \'{$a->participation}\' for {$a->session} at {$a->datetimestart} to {$a->datetimefinish}. Please select another user or change the session';
$string['error:userassignedsessionconflictmultidayselfsignup'] = 'You are already assigned as a \'{$a->participation}\' for {$a->session} at {$a->datetimestart} to {$a->datetimefinish}.';
$string['error:userbookedsessionconflictmultiday'] = '{$a->fullname} is already booked to attend {$a->session} at {$a->datetimestart} to {$a->datetimefinish}. Please select another user or change the session';
$string['error:userbookedsessionconflictmultidayselfsignup'] = 'You are already booked to attend {$a->session} at {$a->datetimestart} to {$a->datetimefinish}.';
$string['error:userdeleted'] = 'Can not add deleted user {$a} to the seminar.';
$string['error:userimportuseridnotanint'] = 'Cannot add user with user id {$a} because it is not an integer';
$string['error:usernamenotfound'] = 'No users were found with the following usernames: {$a}';
$string['error:usersuspended'] = 'Can not add suspended user {$a} to the seminar.';
$string['error:xinvalidjaidnumber'] = 'Job assignment with idnumber {$a->idnumber} not found for user {$a->user}';
$string['eventattendancerequestsapproved'] = 'Attendance requests approved';
$string['eventattendancerequestsdeclined'] = 'Attendance requests declined';
$string['eventattendanceupdated'] = 'Attendance updated';
$string['eventattendeenoteupdated'] = 'Attendee note updated';
$string['eventattendeejobassignmentupdated'] = 'Attendee job assignment updated';
$string['eventattendeesedited'] = 'Attendees edited';
$string['eventattendeesviewed'] = 'Attendees viewed';
$string['eventinterestdeclared'] = 'Interest declared';
$string['eventinterestreportviewed'] = 'Interest report viewed';
$string['eventinterestwithdrawn'] = 'Interest withdrawn';
$string['eventsreport'] = 'Events report';
$string['eventsview'] = 'Events view';
$string['eventreportcnt'] = 'Seminar events: {$a}';
$string['eventsigninsheetexported'] = 'Sign-in sheet exported';
$string['eventsessioncreated'] = 'Event created';
$string['eventsessiondeleted'] = 'Event deleted';
$string['eventsessionupdated'] = 'Event updated';
$string['eventsessionsignup'] = 'Event signup';
$string['eventsignupstatusupdated'] = 'Signup status updated';
$string['eventbookingcancelled'] = 'Event booking cancelled';
$string['eventbookingrequestapproved'] = 'Event booking request approved';
$string['eventbookingrequestrejected'] = 'Event booking request rejected';
$string['excelformat'] = 'Excel';
$string['existingbookings'] = 'Bookings in other events';
$string['existingrecipients'] = 'Existing recipients';
$string['export'] = 'Export';
$string['exportattendanceods'] = 'Export attendance form (ods)';
$string['exportattendancetxt'] = 'Export attendance form (txt)';
$string['exportattendancexls'] = 'Export attendance form (xls)';
$string['exporttofile'] = 'Export to file';
$string['exportattendance'] = 'Export attendance';
$string['exportcustomprofilefields'] = 'Export custom profile fields';
$string['exportcustomprofilefields_desc'] = 'Include these custom user profile fields (short names) in seminar exports, separated by commas.';
$string['exportuserprofilefields'] = 'Export user profile fields';
$string['exportuserprofilefields_desc'] = 'Include these user profile fields in the seminar exports, separated by commas.';
$string['external'] = 'Allow room conflicts';
$string['f2f-waitlist-actions'] = 'Actions';
$string['f2f-waitlist-actions_help'] = 'The actions are:

* **Confirm**: Book the selected users into the event and remove them from the wait-list.
* **Cancel**: Cancel the selected user\'s requests and remove them from the wait-list.
* **Play Lottery**: Fill the available places on the events with a random selection of the users from the wait-list. Users who are not selected will be left on the wait-list.';
$string['facetoface'] = 'Seminar';
$string['facetoface:addattendees'] = 'Add attendees to a seminar event';
$string['facetoface:addinstance'] = 'Add a new seminar';
$string['facetoface:addrecipients'] = 'Add recipients to a seminar\'s message';
$string['facetoface:approveanyrequest'] = 'Approve any booking requests';
$string['facetoface:configurecancellation'] = 'Allow the configuration of booking cancellations, upon adding/editing a seminar activity.';
$string['facetoface:changesignedupjobassignment'] = 'Change signed up job assignment';
$string['facetoface:exportsessionsigninsheet'] = 'Export the seminar session sign-in sheet';
$string['facetoface:managecustomfield'] = 'Manage seminar custom fields';
$string['facetoface:editevents'] = 'Add, edit, copy and delete seminar events';
$string['facetoface:managereservations'] = 'Manage reservations for an event';
$string['facetoface:manageattendeesnote'] = 'Manage event attendee\'s notes';
$string['facetoface:signupwaitlist'] = 'Sign-up to full events';
$string['facetoface:removeattendees'] = 'Remove attendees from a seminar event';
$string['facetoface:removerecipients'] = 'Remove recipients from a seminar\'s message';
$string['facetoface:reserveother'] = 'Reserve on behalf of other managers';
$string['facetoface:reservespace'] = 'Reserve or allocate spaces for team members';
$string['facetoface:signup'] = 'Sign-up for an event';
$string['facetoface:takeattendance'] = 'Take attendance';
$string['facetoface:viewallsessions'] = 'View all seminar sessions';
$string['facetoface:view'] = 'View seminar activities and events';
$string['facetoface:viewattendees'] = 'View attendance list and attendees';
$string['facetoface:viewattendeesnote'] = 'View event attendee\'s notes';
$string['facetoface:viewcancellations'] = 'View cancellations';
$string['facetoface:viewemptyactivities'] = 'View empty seminar activities';
$string['facetoface:viewinterestreport'] = 'View seminar declared interest report';
$string['facetofacebooking'] = 'Seminar booking';
$string['facetofacename'] = 'Seminar name';
$string['facetofacesession'] = 'Seminar event';
$string['feedback'] = 'Feedback';
$string['feedbackupdated'] = 'Feedback updated for \{$a} people';
$string['field:text'] = 'Text';
$string['field:multiselect'] = 'Multiple selection';
$string['field:select'] = 'Menu of choices';
$string['fielddeleteconfirm'] = 'Delete field \'{$a}\' and all event data associated with it?';
$string['filterbyroom'] = 'Filter by Room';
$string['filter_assetavailable'] = 'Available between';
$string['filter_assetavailable_help'] = 'This filter allows you to find assets that are available for a session by specifying the session start and end date.';
$string['filter_roomavailable'] = 'Available between';
$string['filter_roomavailable_help'] = 'This filter allows you to find rooms that are available for a session by specifying the session start and end date.';
$string['floor'] = 'Floor';
$string['forceselectjobassignment'] = 'Prevent signup if no job assignment is selected or can be found';
$string['format'] = 'Format';
$string['freebetween'] = 'Free between the following times';
$string['freebetweendates'] = 'Free between {$a->start} and {$a->end}';
$string['full'] = 'Date is fully occupied';
$string['generalsettings'] = 'General settings';
$string['gettointerestreport'] = 'To view the declare interest report go to the seminar activity and follow the \'Declared interest report\' link in the module admin menu.';
$string['gettosigninreport'] = 'To view the sign-in sheet go to the event attendees page and press the Download sign-in sheet button.';
$string['globalsettings'] = 'Global settings';
$string['goback'] = 'Go back';
$string['guestsno'] = 'Sorry, guests are not allowed to sign up for events.';
$string['header:approvalstate'] = 'Manager Approval';
$string['header:approvaltime'] = 'Approval Time';
$string['header:managername'] = 'Manager\'s Name';
$string['icalendarheading'] = 'iCalendar Attachments';
$string['id'] = 'Id';
$string['icaldescription'] = 'This calendar event is for the "{$a->name}" seminar event you have been booked on to.';
$string['icallocationstringdelimiter'] = ',';
$string['ignoreapprovalwhenaddingattendees'] = 'Book users without requiring approval';
$string['import'] = 'Import';
$string['individuals'] = 'Individuals';
$string['info'] = 'Info';
$string['itemstoadd'] = 'Items to add';
$string['jobassignment'] = 'Job assignment';
$string['joinwaitlist'] = 'Join waitlist';
$string['joinwaitlistcompleted'] = 'You have been placed on the waitlist for this event.';
$string['lastreservation'] = 'Last reservations are {$a->reservedays} days before the event starts. Unallocated reservations will be deleted {$a->reservecanceldays} days before the event starts.';
$string['late'] = '\{$a} late';
$string['location'] = 'Location';
$string['locationtimetbd'] = 'Location and time to be announced later.';
$string['lookfor'] = 'Search';
$string['manageassets'] = 'Manage assets';
$string['manageevents'] = 'View and manage events';
$string['managenotificationtemplates'] = 'Manage notification templates';
$string['managerooms'] = 'Manage rooms';
$string['managerbookings'] = 'Bookings / reservations made by {$a}';
$string['managername'] = 'Manager\'s name';
$string['managername_help'] = 'Your manager will receive a notification about your request to book into this session. If the name and email address shown here do not belong not your manager, please contact the Site Administrator.';
$string['managereservations'] = 'Manage reservations';
$string['managerprefix'] = 'Manager copy prefix';
$string['managerreservationdeleted'] = 'Manager reservation deleted successfully';
$string['managerreservationdeletionfailed'] = 'Manager reservation deletion failed';
$string['managerreserve'] = 'Allow manager reservations';
$string['managerreserve_help'] = 'Managers are able to make reservations or bookings on behalf of their team members';
$string['managerreserveheader'] = 'Manager reservations';
$string['managesessions'] = 'View and manage sessions';
$string['mark_selected_as'] = 'Mark all selected as: ';
$string['maxbookings'] = 'Maximum bookings';
$string['maxbookings_help'] = '**Maximum bookings** is the number of seats available in an event.

When a seminar event reaches maximum bookings the event details do not appear on the course page. The details will appear greyed out on the **View all events** page and the learner cannot enrol on the event.';
$string['maximumpoints'] = 'Maximum number of points';
$string['maximumsize'] = 'Maximum number of attendees';
$string['maxmanagerreserves'] = 'Maximum reservations';
$string['maxmanagerreserves_help'] = 'The maximum number of reservations / bookings that a manager can make for their team.';
$string['message'] = 'Change in booking in the course {$a->coursename}!

There has been a free place in the event on {$a->duedate} ({$a->name}) in the course {$a->coursename}.
You have been registered. If the date does not suit you anymore, please unregister at <a href=\'{$a->url}\'>{$a->url}</a>.';
$string['messagebody'] = 'Body';
$string['messagecc'] = 'CC recipient\'s managers';
$string['messageheader'] = 'Message';
$string['messagerecipients'] = 'Recipients';
$string['messagerecipientgroups'] = 'Recipient Groups';
$string['messagesenttostaffmember'] = 'The following message has been sent to your staff member {$a}';
$string['messagesubject'] = 'Subject';
$string['messageusers'] = 'Message users';
$string['minbookings'] = 'Minimum bookings';
$string['minbookings_help'] = 'Sessions with a minimum bookings are highlighted when the number of booked users is less than the minimum bookings. To prevent highlighting, set the minimum bookings to zero.';
$string['mincapacity'] = 'Minimum bookings';
$string['mincapacity_help'] = 'If the minimum bookings has not been reached by the cut-off point, then the appropriate users will be automatically notified. Users to be notified is determined by role assignments and the configuration setting under Site administration > Seminars > Global Settings > Notification recipients';
$string['missingjobassignment'] = 'The job assignment was <strong>deleted</strong>';
$string['modulename'] = 'Seminar';
$string['modulenameplural'] = 'Seminars';
$string['moreinfo'] = 'More info';
$string['multidate'] = '(multi-date)';
$string['multiplesessions'] = 'Users can sign-up to multiple events';
$string['namewithmanager'] = '{$a->attendeename} ({$a->managername})';
$string['noactionableunapprovedrequests'] = 'No actionable unapproved requests';
$string['nocancellations'] = 'There have been no cancellations';
$string['nocustomassetedit'] = 'Custom Asset can only be edited in event dialog box';
$string['nocustomfields'] = '<p>No custom fields are defined.</p>';
$string['nocustomroomedit'] = 'Custom Room can only be edited in event dialog box';
$string['nodatesyet'] = 'This event has no sessions. Attendees can sign-up to the waitlist for this event and will be booked automatically once one or more sessions are added.';
$string['nofacetofaces'] = 'There are no seminar activities';
$string['nojobassignment'] = 'User has no active job assignments';
$string['none'] = 'None';
$string['nonotifications'] = 'No notifications';
$string['nonotificationsmatchingsearch'] = 'No notifications matching search';
$string['nonotificationsofthistype'] = 'No notifications of this type';
$string['nonotificationtemplates'] = 'No notification templates';
$string['nonotificationtemplatesmatchingsearch'] = 'No notification templates matching search';
$string['nopendingapprovals'] = 'No pending approvals';
$string['norecipients'] = 'No recipients';
$string['normalcost'] = 'Normal cost';
$string['normalcosthinttext'] = '';
$string['noremindersneedtobesent'] = 'No reminders need to be sent.';
$string['noreservations'] = 'None';
$string['noreservationsforsession'] = 'There are no reservations for this event.';
$string['nosignedupusers'] = 'No users have signed-up for this event.';
$string['nosignedupusersnumrequests'] = 'No users are fully booked for this event. {$a} users are awaiting approval.';
$string['nosignedupusersonerequest'] = 'No users are fully booked for this event. 1 user is awaiting approval.';
$string['nostarttime'] = 'No dates specified';
$string['notallowedtocancel'] = 'You are not allowed to cancel your booking in this event.';
$string['notapplicable'] = 'N/A';
$string['note'] = 'Note';
$string['notefull'] = 'Even if the event is fully booked you can still register. You will be queued (marked in red). If someone signs out, the first learner in the queue will be moved into registeres learners and a notification will be sent to him/her by mail.';
$string['notificationalreadysent'] = 'This notification has already been sent, so can no longer be edited.';
$string['notificationdeleted'] = 'Notification deleted';
$string['notificationduplicatesfound'] = 'Duplicates of auto notifications found. Please remove them manually on <a href="{$a}">Notifications page</a> to avoid wrong notifications being sent.';
$string['notificationduplicatesmessage'] = 'This message has duplicate. Please remove all duplicates manually.';
$string['notificationnotesetonintendeddate'] = 'NOTE: This notification was not sent on it\'s originally intended date';
$string['notifications'] = 'Notifications';
$string['notificationsaved'] = 'Notification saved';
$string['notificationssuccessfullyreset'] = 'Notifications successfully reset';
$string['notificationtemplatedeleted'] = 'Notification template deleted';
$string['notificationtemplaterestored'] = 'Notification template restored. {$a} seminars are affected.';
$string['notificationtemplates'] = 'Notification templates';
$string['notificationtemplatestatus'] = 'Notification template status';
$string['notificationtemplatestatus_help'] = 'This status allows a notification template to be marked as **Active** or **Inactive**. Inactive notification templates will not be available to be used when setting up notifications for a seminar activity.';
$string['notificationtemplatesaved'] = 'Notification template saved';
$string['notificationtitle'] = 'Notification title';
$string['notificationtype'] = 'Receive confirmation by';
$string['notificationtype_1'] = 'Instant';
$string['notificationtype_2'] = 'Scheduled';
$string['notificationtype_4'] = 'Auto';
$string['notificationboth'] = 'Email with iCalendar appointment';
$string['notificationemail'] = 'Email only';
$string['notificationnone'] = 'Do not send confirmation';
$string['notifications_help'] = 'Here you can manage notifications for this Seminar acitivity.';
$string['notifycancelleduser'] = 'Notify cancelled attendees';
$string['notifycancelledusermanager'] = 'Notify cancelled attendees\' managers';
$string['notifynewuser'] = 'Send booking confirmation to new attendees';
$string['notifynewusermanager'] = 'Send booking confirmation to new attendees\' managers';
$string['notificationsheading'] = 'Notifications';
$string['notrequired'] = 'Not required';
$string['notsignedup'] = 'You are not signed up for this event.';
$string['notspecified'] = 'N/A';
$string['notsubmittedyet'] = 'Not yet evaluated';
$string['noupcoming'] = '<p><i>No upcoming events</i></p>';
$string['noupcomingsessionsinroom'] = 'No upcoming events in this room';
$string['numberofattendees'] = 'Number of attendees';
$string['uploadfile'] = 'Upload file';
$string['occuredonx'] = 'Occured on {$a}';
$string['occurswhenenabled'] = 'Occurs when enabled';
$string['occurswhenuserbookssession'] = 'Occurs when a learner books an event';
$string['occurswhenuserrequestssessionwithmanagerapproval'] = 'Occurs when a user attempts to book an event with manager approval required';
$string['occurswhenuserrequestssessionwithmanagerdecline'] = 'Occurs when a user attempts to declined an event with manager approval required';
$string['occurswhenusersbookingiscancelled'] = 'Occurs when a learner\'s booking is cancelled';
$string['occurswhenuserwaitlistssession'] = 'Occurs when a learner is waitlisted on an event';
$string['occursxaftersession'] = 'Occurs {$a} after end of event';
$string['occursxbeforesession'] = 'Occurs {$a} before start of event';
$string['odsformat'] = 'OpenDocument';
$string['onehour'] = '1 hour';
$string['oneminute'] = '1 minute';
$string['options'] = 'Options';
$string['optionscolon'] = 'Options:';
$string['or'] = 'or';
$string['order'] = 'Order';
$string['otherbookedby'] = 'Booked by another manager';
$string['otherroom'] = 'Other room';
$string['othersession'] = 'Other event(s) in this activity';
$string['place'] = 'Room';
$string['placeholder:address'] = '[address]';
$string['placeholder:aim'] = '[aim]';
$string['placeholder:alternatename'] = '[alternatename]';
$string['placeholder:city'] = '[city]';
$string['placeholder:country'] = '[country]';
$string['placeholder:department'] = '[department]';
$string['placeholder:description'] = '[description]';
$string['placeholder:email'] = '[email]';
$string['placeholder:firstname'] = '[firstname]';
$string['placeholder:firstnamephonetic'] = '[firstnamephonetic]';
$string['placeholder:fullname'] = '[fullname]';
$string['placeholder:icq'] = '[icq]';
$string['placeholder:idnumber'] = '[idnumber]';
$string['placeholder:institution'] = '[institution]';
$string['placeholder:lang'] = '[lang]';
$string['placeholder:lastname'] = '[lastname]';
$string['placeholder:lastnamephonetic'] = '[lastnamephonetic]';
$string['placeholder:middlename'] = '[middlename]';
$string['placeholder:msn'] = '[msn]';
$string['placeholder:phone1'] = '[phone1]';
$string['placeholder:phone2'] = '[phone2]';
$string['placeholder:registrationcutoff'] = '[registrationcutoff]';
$string['placeholder:location'] = '[session:location]';
$string['placeholder:venue'] = '[session:venue]';
$string['placeholder:room'] = '[session:room]';
$string['placeholder:skype'] = '[skype]';
$string['placeholder:timezone'] = '[timezone]';
$string['placeholder:url'] = '[url]';
$string['placeholder:username'] = '[username]';
$string['placeholder:yahoo'] = '[yahoo]';
$string['playlottery'] = 'Play Lottery';
$string['publishreuse'] = 'Publish for reuse by other events';
$string['reserve'] = 'Reserve spaces for team';
$string['reserveallallocated'] = 'You have already allocated the maximum number of spaces you are able for this activity, you cannot reserve any more';
$string['reserveallallocatedother'] = 'This manager has already allocated the maximum number of spaces they are able to for this activity, you cannot reserve any more for them';
$string['reservecancel'] = 'Automatically cancel reservations';
$string['reservecanceldays'] = 'Reservation cancellation days';
$string['reservecanceldays_help'] = 'The number of days in advance of the event that reservations will be automatically cancelled, if not confirmed.';
$string['reservecapacitywarning'] = '* Any new reservations over the current event maximum bookings ({$a} left) will be added to the waiting list';
$string['reserved'] = 'Reserved';
$string['reservedby'] = 'Reserved ({$a})';
$string['reservedays'] = 'Reservation deadline';
$string['reservedays_help'] = 'The number of days before the event starts after which no more reservations are allowed (must be greater than the cancellation days).';
$string['reservegtcancel'] = 'The reservation deadline must be greater than the cancellation days';
$string['reserveintro'] = 'You can use this form to change the number of reservations you have for this event - to cancel existing reservations, just reduce the number below.';
$string['reserveintroother'] = 'You can use this form to change the number of reservations {$a} has for this event - to cancel existing reservations, just reduce the number below.';
$string['reservenocapacity'] = 'There are no spaces left on this course, so you will not be able to make any reservations unless one of the participants cancels';
$string['reservenopermissionother'] = 'This manager does not have capabilities to reserve places in Seminar';
$string['reserveother'] = 'Reserve for another manager';
$string['reservepastdeadline'] = 'You cannot make any further reservations within {$a} days of the event starting';
$string['restore'] = 'Restore this default notification template for all seminars';
$string['restoremissingdefaultnotifications'] = 'Restore missing default notifications';
$string['restoremissingdefaultnotificationsconfirm'] = 'This will restore any missing default notifications for "{$a}" these notifications are used by built in seminar functionality and may result in messages being sent to users. Do you want to continue?';
$string['result'] = 'Result';
$string['return'] = 'Return';
$string['room'] = 'Room';
$string['roomcustom'] = '(Custom Room)';
$string['roomdeleted'] = 'Room deleted';
$string['roomdoesnotexist'] = 'Room does not exist';
$string['roomisinuse'] = 'Room is in use';
$string['roomdescription'] = 'Room description';
$string['roomdescriptionedit'] = 'Description';
$string['roomdetails'] = 'Room details';
$string['roomhidden'] = 'Room hidden successfully';
$string['roomname'] = 'Room name';
$string['roomnameedit'] = 'Name';
$string['roomnameedittoolong'] = 'Room name can only be upto {$a} characters in length';
$string['room'] = 'Room';
$string['rooms'] = 'Rooms';
$string['roomshown'] = 'Room shown successfully';
$string['roomcapacity'] = 'Room capacity';
$string['roomcreatesuccess'] = 'Successfully created room';
$string['roomtype'] = 'Room type';
$string['roomtype_help'] = 'If checked, allows the room to be booked for different events that are running at the same time.';
$string['roomupdatesuccess'] = 'Successfully updated room';
$string['placeholder:coursename'] = '[coursename]';
$string['placeholder:facetofacename'] = '[facetofacename]';
$string['placeholder:firstname'] = '[firstname]';
$string['placeholder:lastname'] = '[lastname]';
$string['placeholder:cost'] = '[cost]';
$string['placeholder:alldates'] = '[alldates]';
$string['placeholder:reminderperiod'] = '[reminderperiod]';
$string['placeholder:sessiondate'] = '[sessiondate]';
$string['placeholder:sessionrole'] = '[sessionrole]';
$string['placeholder:startdate'] = '[startdate]';
$string['placeholder:finishdate'] = '[finishdate]';
$string['placeholder:starttime'] = '[starttime]';
$string['placeholder:finishtime'] = '[finishtime]';
$string['placeholder:duration'] = '[duration]';
$string['placeholder:details'] = '[details]';
$string['placeholder:attendeeslink'] = '[attendeeslink]';
$string['placeholder:lateststarttime'] = '[lateststarttime]';
$string['placeholder:lateststartdate'] = '[lateststartdate]';
$string['placeholder:latestfinishtime'] = '[latestfinishtime]';
$string['placeholder:latestfinishdate'] = '[latestfinishdate]';
$string['pleaseselectusers'] = 'Please select users before continuing.';
$string['pluginadministration'] = 'Seminar administration';
$string['pluginname'] = 'Seminar';
$string['points'] = 'Points';
$string['pointsplural'] = 'Points';
$string['potentialallocations'] = 'Potential allocations ({$a} left)';
$string['potentialrecipients'] = 'Potential recipients';
$string['previoussessions'] = 'Previous events';
$string['previoussessionslist'] = 'List of all past events for this seminar activity';
$string['printversionid'] = 'Print version: without name';
$string['printversionname'] = 'Print version: with name';
$string['really'] = 'Do you really want to delete all results for this seminar?';
$string['recipients'] = 'Recipients';
$string['recipients_allbooked'] = 'All booked';
$string['recipients_attendedonly'] = 'Attended only';
$string['recipients_noshowsonly'] = 'No shows only';
$string['registeredon'] = 'Registered On';
$string['registrations'] = 'Registrations';
$string['reminder'] = 'Reminder';
$string['remindermessage'] = 'Reminder message';
$string['removeattendees'] = 'Remove users';
$string['removeattendeestep1'] = "Select users to remove (step 1 of 2)";
$string['removeattendeestep2'] = "Remove users (step 2 of 2)";
$string['removecfdatawarning'] = 'Removing users from this session also deletes their sign up data.';
$string['removedsuccessfully'] = 'Removed successfully';
$string['removeroominuse'] = 'This room is currently being used';
$string['replaceallocations'] = 'Create reservations when removing allocations';
$string['replacereservations'] = 'Replace reservations when adding allocations';
$string['requestmessage'] = 'Request message';
$string['reservations'] = '{$a} reservation(s)';
$string['restorenotificationtemplateconfirm'] = 'Confirm you would like to restore <strong>"{$a}"</strong> notification template for the ALL existing seminars:';
$string['room'] = 'Room';
$string['roomalreadybooked'] = ' (Room unavailable)';
$string['saveallfeedback'] = 'Save all responses';
$string['saveattendance'] = 'Save attendance';
$string['savenote'] = 'Save note';
$string['schedule_unit_1'] = '{$a} hours';
$string['schedule_unit_1_singular'] = '1 hour';
$string['schedule_unit_2'] = '{$a} days';
$string['schedule_unit_2_singular'] = '1 day';
$string['schedule_unit_4'] = '{$a} weeks';
$string['schedule_unit_4_singular'] = '1 week';
$string['scheduledsession'] = 'Scheduled event';
$string['scheduledsessions'] = 'Scheduled events';
$string['scheduling'] = 'Scheduling';
$string['seatsavailable'] = 'Seats available';
$string['seeattendees'] = 'See attendees';
$string['selected'] = 'Selected';
$string['select'] = ' Select ';
$string['selectall'] = 'Select all';
$string['selectedjob'] = 'Job on sign up';
$string['selectedjobassignment_help'] = 'Select the job assignment that this training is for.';
$string['selectedjobassignment'] = 'Job assignment on sign up';
$string['selectedjobassignmentedit'] = 'Job assignment';
$string['selectedjobassignmentname'] = 'Job assignment name on sign up';
$string['selectjobassignment'] = 'Select a job assignment';
$string['selectjobassignmentsignup'] = 'Select job assignment on signup';
$string['selectedposition_help'] = 'Select the position that this training is for.';
$string['selectedposition'] = 'Position on sign up';
$string['selectnone'] = 'Select none';
$string['selectallop'] = 'All';
$string['selectassets'] = 'Select assets';
$string['selectmanager'] = 'Select manager';
$string['selectnoneop'] = 'None';
$string['selectnotsetop'] = 'Not Set';
$string['selectoptionbefore'] = ' Please choose an option (All, Set or Not set) before selecting this option';
$string['selectroom'] = 'Select room';
$string['selectsetop'] = 'Set';
$string['selfauthorisation'] = 'Self authorisation';
$string['selfauthorisation_help'] = 'You must read and agree to the terms and conditions before signing up for the session.';
$string['selfauthorisationdesc'] = 'By checking this box, I confirm that I have read and agreed to the {$a} (opens a new window).';
$string['selfapproval'] = 'Self Approval';
$string['selfapproval_help'] = 'This setting allows a user to confirm that they have sought approval to attend the event. Instead of their manager needing to approve their booking the user is presented with a check box when signing up and must confirm they have met the specified terms and conditions.
This setting will be disabled unless **Requires approval** is enabled in the seminar activity settings.';
$string['selfapprovalsought'] = 'Self Approval Sought';
$string['selfapprovalsoughtbrief'] = 'I accept the terms and conditions.';
$string['selfapprovalsoughtdesc'] = 'By checking this box, I confirm that I have read and agreed to the {$a} (opens a new window).';
$string['selfapprovaltandc'] = 'Self Approval Terms and Conditions';
$string['selfapprovaltandc_help'] = 'Where an activity has approval required and an event has self approval enabled these are the terms and conditions that will be displayed when a user signs up.';
$string['selfapprovaltandccontents'] = 'By checking the box you confirm that permission to sign up to this seminar activity has been granted by your manager.

Falsely claiming that approval has been granted can result in non-admittance and disciplinary action.
';
$string['selfbooked'] = 'Self booked';
$string['sendlater'] = 'Send later';
$string['sendmessage'] = 'Send message';
$string['sendnotificationstask'] = 'Send seminar notifications';
$string['sendnow'] = 'Send now';
$string['sentxnotifications'] = 'Send {$a} notifications';
$string['sentremindermanager'] = 'Sent reminder email to user manager';
$string['sentreminderuser'] = 'Sent reminder email to user';
$string['sessionattendees'] = 'Session attendees';
$string['sessioncancelled'] = 'Event cancellation';
$string['sessioncustomfieldtab'] = 'Event';
$string['roomcustomfieldtab'] = 'Room';
$string['roomhide'] = 'Hide from users when choosing a room on the Add/Edit event page';
$string['roomshow'] = 'Show to users when choosing a room on the Add/Edit event page';
$string['sessiondate'] = 'Session';
$string['sessiondatecolumn_html'] = '{$a->startdate} {$a->starttime} -<br>{$a->enddate} {$a->endtime}<br>{$a->timezone}';
$string['sessiondatetime'] = 'Event date/time';
$string['sessiondatetimecourseformat'] = '{$a->startdate}, {$a->starttime} - {$a->endtime} (time zone: {$a->timezone})';
$string['sessiondatetimecourseformatwithouttimezone'] = '{$a->startdate}, {$a->starttime} - {$a->endtime}';
$string['sessiondates'] = 'Specify sessions details';
$string['sessiondefaults'] = 'Event defaults';
$string['sessionenddate'] = 'Session end date';
$string['sessionenddatewithtime'] = '{$a->enddate}, {$a->endtime} {$a->timezone}';
$string['sessionsdetailstablesummary'] = 'Full description of the current event.';
$string['sessionfinishdateshort'] = 'Finish date';
$string['sessionfinishtime'] = 'Event finish time';
$string['sessionfinishtime_help'] = 'When creating or editing an event, the event timezone may differ from the timezone for the **Finish time**. This is because the timezone for **Finish time** is determined by the timezone of the user creating or editing the event. The timezone of the user creating or editing the event does not affect the event timezone itself.';
$string['sessioninprogress'] = 'Event in progress';
$string['sessionisfull'] = 'This event is now full. You will need to pick another time or talk to the instructor.';
$string['sessionnoattendeesaswaitlist'] = 'This event does not have any attendees because it does not have a known date and time.<br />See the wait-list tab for users that have signed up.';
$string['sessionover'] = 'Event over';
$string['sessionreport'] = 'Seminar sessions';
$string['sessionreportcnt'] = 'Seminar sessions: {$a}';
$string['sessions'] = 'Events';
$string['sessionsoncoursepage'] = 'Events displayed on course page';
$string['sessionstartdateandtime'] = '{$a->startdate}, {$a->starttime} - {$a->endtime} (time zone: {$a->timezone})';
$string['sessionstartdateandtimewithouttimezone'] = '{$a->startdate}, {$a->starttime} - {$a->endtime}';
$string['sessionstartdatewithtime'] = '{$a->startdate}, {$a->starttime} {$a->timezone}';
$string['sessionstartfinishdateandtime'] = '{$a->startdate} - {$a->enddate}, {$a->starttime} - {$a->endtime} (time zone: {$a->timezone})';
$string['sessionstartfinishdateandtimewithouttimezone'] = '{$a->startdate} - {$a->enddate}, {$a->starttime} - {$a->endtime}';
$string['sessionsview'] = 'Sessions view';
$string['sessionrequiresmanagerapproval'] = 'This event requires manager approval to book.';
$string['sessionroles'] = 'Event roles';
$string['sessionsreport'] = 'Sessions report';
$string['sessionstartdate'] = 'Session start';
$string['sessionstartdateshort'] = 'Start date';
$string['sessionstarttime'] = 'Event start time';
$string['sessionstarttime_help'] = 'When creating or editing an event, the event timezone may differ from the timezone for the **Start time**. This is because the timezone for **Start time** is determined by the timezone of the user creating or editing the event. The timezone of the user creating or editing the event does not affect the event timezone itself.';
$string['sessiontimezone'] = 'Timezone displayed';
$string['sessiontimezone_help'] = 'Select the timezone you want this event to be displayed in e.g. \'Pacific/Auckland\'. This will display the start time, finish time and timezone in accordance with the timezone selected. If you choose **User timezone**, this will display the start time, finish date and timezone in relation to the timezone of the user viewing the event.';
$string['sessiontimezoneunknown'] = 'Unknown Timezone';
$string['sessionundercapacity'] = 'Event under minimum bookings for: {$a}';
$string['sessioncancellationcustomfieldtab'] = 'Event cancellation';
$string['sessionundercapacity_body'] = 'The following event is under minimum bookings:

Name: {$a->name}
Event start: {$a->starttime}
Capacity: {$a->booked} / {$a->capacity} (minimum: {$a->mincapacity})
{$a->link}';
$string['sessionvenue'] = 'Event venue';
$string['setactive'] = 'Set active';
$string['setinactive'] = 'Set inactive';
$string['setting:signupapproval_header'] = 'Signup Approvals';
$string['setting:approvaloptions_caption'] = 'Available Approval Options';
$string['setting:approvaloptions_default'] = 'The options selected above will be available in the \'require approval by\' setting for all seminar activities. Text entered below the \'Learner accepts terms and conditions\' option will be default for all activities and can be edited for each activity. Selected \'site level administrative approvers\' are automatically added to all seminar activities and can not be removed from within any given activity (note: other administrative approvers can be added to each seminar activity)';
$string['setting:managerselect_caption'] = 'Users Select Manager';
$string['setting:managerselect_format'] = 'Recommended when manager assignment data is not available. When enabled and an activity requires approval by Manager, the event sign-up page will force users to search for and select a user to approve their request to attend an event each time they sign-up. The selected user will receive a notification about the request and instructions on how to approve or decline it.';
$string['setting:termsandconditions_caption'] = 'Terms and conditions';
$string['setting:termsandconditions_format'] = 'Text entered in this setting will be the default for the \'Learner accepts terms and conditions\' setting but can be edited for each activity.';
$string['setting:termsandconditions_default'] = 'By checking the box you confirm that permission to sign up to this seminar activity has been granted by your manager.

Falsely claiming that approval has been granted can result in non-admittance and disciplinary action.
';
$string['setting:adminapprovers_caption'] = 'Site level administrative approvers';
$string['setting:adminapprovers_format'] = 'Selected users \'site level administrative approvers\' are automatically added to all seminar activities and can not be removed from within any given activity (note: other administrative approvers can be added to each seminar activity)';
$string['setting:approval_none'] = 'No approval required';
$string['setting:approval_self'] = 'Learner accepts terms and conditions';
$string['setting:approval_role'] = 'TODO - session role name?';
$string['setting:approval_manager'] = 'Manager approval';
$string['setting:approval_admin'] = 'Manager and Administrative approval';
$string['setting:allowschedulingconflicts_caption'] = 'Allow override user conflicts:';
$string['setting:allowschedulingconflicts'] = 'Allow user scheduling conflicts when saving a seminar event.';
$string['setting:allowwaitlisteveryone_caption'] = 'Everyone on waiting list';
$string['setting:allowwaitlisteveryone'] = 'When enabled a setting will appear in seminar event settings to put all users onto the waiting list when they signup regardless of event maximum bookings.';
$string['setting:calendarfilters'] = 'Selected fields will be displayed as filters in the user\'s calendar';
$string['setting:calendarfilterscaption'] = 'Add calendar filters:';
$string['setting:defaultcancellationinstrmngr'] = 'Default cancellation message sent to managers.';
$string['setting:defaultcancellationinstrmngr_caption'] = 'Cancellation message (managers)';
$string['setting:defaultcancellationinstrmngrdefault'] = '*** Advice only ****

This is to advise that [firstname] [lastname] is no longer signed-up for the following course and listed you as their Team Leader / Manager.

';
$string['setting:defaultcancellationinstrmngrdefault_v92'] = '*** Advice only ****

This is to advise that [firstname] [lastname] is no longer signed up for the following course and listed you as their Team Leader / Manager.

Below is the message that was sent to the learner:

';
$string['setting:defaultcancellationinstrmngrcopybelow'] = '*** [firstname] [lastname]\'s booking cancellation is copied below ****';
$string['setting:defaultcancellationmessage'] = 'Default cancellation message sent to the user.';
$string['setting:defaultcancellationmessage_caption'] = 'Cancellation message';
$string['setting:defaultcancellationmessagedefault'] = 'This is to advise that your booking on the following course has been cancelled:

***BOOKING CANCELLED***

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaultcancellationmessagedefault_v9'] = 'This is to advise that your booking on the following course has been cancelled:

***BOOKING CANCELLED***

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultcancellationsubject'] = 'Default subject line for cancellation emails.';
$string['setting:defaultcancellationsubject_caption'] = 'Cancellation subject';
$string['setting:defaultcancellationsubjectdefault'] = 'Face-to-face booking cancellation';
$string['setting:defaultcancellationsubjectdefault_v9'] = 'Seminar booking cancellation';
$string['setting:defaultcancelallreservationssubjectdefault'] = 'All reservations cancelled';
$string['setting:defaultcancelallreservationssubjectdefault_v9'] = 'All reservations cancelled';
$string['setting:defaultcancelallreservationsmessagedefault'] = 'This is to advise you that all unallocated reservations for the following course have been automatically cancelled, as the course will be starting soon:

***ALL RESERVATIONS CANCELLED***

Course:   [facetofacename]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaultcancelallreservationsmessagedefault_v9'] = 'This is to advise you that all unallocated reservations for the following course have been automatically cancelled, as the course will be starting soon:

***ALL RESERVATIONS CANCELLED***

Course:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultcancelreservationsubjectdefault'] = 'Reservation cancellation';
$string['setting:defaultcancelreservationsubjectdefault_v9'] = 'Reservation cancellation';
$string['setting:defaultcancelreservationmessagedefault'] = 'This is to advise you that your reservation for the following course has been cancelled:

***RESERVATION CANCELLED***

Course:   [facetofacename]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]';
$string['setting:defaultcancelreservationmessagedefault_v9'] = 'This is to advise you that your reservation for the following course has been cancelled:

***RESERVATION CANCELLED***

Course:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultdeclineinstrmngr'] = 'Default decline message sent to managers.';
$string['setting:defaultdeclineinstrmngr_caption'] = 'Decline message (managers)';
$string['setting:defaultdeclineinstrmngrdefault'] = '*** Advice only ****

This is to advise that [firstname] [lastname] is no longer signed-up for the following course and listed you as their Team Leader / Manager.

';
$string['setting:defaultdeclineinstrmngrdefault_v92'] = '*** Advice only ****

This is to advise that [firstname] [lastname] is no longer signed-up for the following course and listed you as their Team Leader / Manager.

Below is the message that was sent to the learner:

';
$string['setting:defaultdeclineinstrmngrcopybelow'] = '*** [firstname] [lastname]\'s booking decline is copied below ****';
$string['setting:defaultdeclinemessage'] = 'Default decline message sent to the user.';
$string['setting:defaultdeclinemessage_caption'] = 'Decline message';
$string['setting:defaultdeclinemessagedefault'] = 'This is to advise that your booking on the following course has been declined:

***BOOKING DECLINED***

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaultdeclinemessagedefault_v9'] = 'This is to advise that your booking on the following course has been declined:

***BOOKING DECLINED***

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultdeclinesubject'] = 'Default subject line for decline emails.';
$string['setting:defaultdeclinesubject_caption'] = 'Decline subject';
$string['setting:defaultdeclinesubjectdefault'] = 'Face-to-face booking decline';
$string['setting:defaultdeclinesubjectdefault_v9'] = 'Seminar booking decline';
$string['setting:defaultconfirmationinstrmngr'] = 'Default confirmation message sent to managers.';
$string['setting:defaultconfirmationinstrmngr_caption'] = 'Confirmation message (managers)';
$string['setting:defaultconfirmationinstrmngrdefault'] = '*** Advice only ****

This is to advise that [firstname] [lastname] has been booked for the following course and listed you as their Team Leader / Manager.

If you are not their Team Leader / Manager and believe you have received this email by mistake please reply to this email.  If you have concerns about your staff member taking this course please discuss this with them directly.

';
$string['setting:defaultconfirmationinstrmngrdefault_v92'] = '*** Advice only ****

This is to advise that [firstname] [lastname] has been booked for the following course and listed you as their Team Leader / Manager.

If you are not their Team Leader / Manager and believe you have received this email by mistake please reply to this email.  If you have concerns about your staff member taking this course please discuss this with them directly.

Below is the message that was sent to the learner:

';
$string['setting:defaultconfirmationinstrmngrcopybelow'] = '*** [firstname] [lastname]\'s booking confirmation is copied below ****';
$string['setting:defaultconfirmationmessage'] = 'Default confirmation message sent to users.';
$string['setting:defaultconfirmationmessage_caption'] = 'Confirmation message';
$string['setting:defaultconfirmationmessagedefault'] = 'This is to confirm that you are now booked on the following course:

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]
Cost:   [cost]

Duration:    [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]

***Please arrive ten minutes before the course starts***

To re-schedule or cancel your booking
To re-schedule your booking you need to cancel this booking and then re-book a new session.  To cancel your booking, return to the site, then to the page for this course, and then select \'cancel\' from the booking information screen.

[details]
';
$string['setting:defaultconfirmationmessagedefault_v9'] = 'This is to confirm that you are now booked on the following course:

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]
Cost:   [cost]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]

***Please arrive ten minutes before the course starts***

To re-schedule or cancel your booking
To re-schedule your booking you need to cancel this booking and then re-book a new event.  To cancel your booking, return to the site, then to the page for this course, and then select \'cancel\' from the booking information screen.

[details]
';
$string['setting:defaultconfirmationsubject'] = 'Default subject line for confirmation emails.';
$string['setting:defaultconfirmationsubject_caption'] = 'Confirmation subject';
$string['setting:defaultconfirmationsubjectdefault'] = 'Face-to-face booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultconfirmationsubjectdefault_v9'] = 'Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultdatetimechangemessagedefault'] = 'Your session date/time has changed:

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaultdatetimechangemessagedefault_v9'] = 'The session you are booked on (or on the waitlist) has changed:

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultdatetimechangesubject'] = 'Default subject line for date/time change emails.';
$string['setting:defaultdatetimechangesubject_caption'] = 'Date/time change subject';
$string['setting:defaultpendingreqclosureinstrmngr'] = 'Default registration closure message sent to managers.';
$string['setting:defaultpendingreqclosureinstrmngrcopybelow'] = '*** Advice only ****
Your staff member [firstname] [lastname] had a pending request to attend the below seminar event and has also received this closure email.

If you are not their Team Leader / Manager and believe you have received this email by mistake please reply to this email.
';
$string['setting:defaultpendingreqclosureinstrmngrcopybelow_v92'] = '*** Advice only ****
Your staff member [firstname] [lastname] had a pending request to attend the below seminar event and has also received this closure email.

If you are not their Team Leader / Manager and believe you have received this email by mistake please reply to this email.

Below is the message that was sent to the learner:

';
$string['setting:defaultpendingreqclosuremessage'] = 'Default cancellation message sent to the user.';
$string['setting:defaultpendingreqclosuremessage_caption'] = 'Registration closure message';
$string['setting:defaultpendingreqclosuremessagedefault_v9'] = 'This is to advise that your pending booking request for the following seminar event has expired:
This seminar events registration period has closed while your request was pending, please request a booking on a different event.

Course: [coursename]

Seminar: [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultpendingreqclosuresubject'] = 'Default subject line for registration closure emails.';
$string['setting:defaultpendingreqclosuresubject_caption'] = 'registration closure subject';
$string['setting:defaultpendingreqclosuresubjectdefault'] = 'Seminar event registration closure';
$string['setting:defaultregistrationexpiredsubjectdefault'] = 'Seminar registration closed: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultregistrationexpiredinstrmngr'] = '
*** Advice only ****

This is to advise that [firstname] [lastname] has been sent the following email and you are listed as their Team Leader / Manager.

If you are not their Team Leader / Manager and believe you have received this email by mistake please reply to this email.

';
$string['setting:defaultregistrationexpiredinstrmngr_v92'] = '
*** Advice only ****

This is to advise that [firstname] [lastname] has been sent the following email and you are listed as their Team Leader / Manager.

If you are not their Team Leader / Manager and believe you have received this email by mistake please reply to this email.

Below is the message that was sent to the learner:

';
$string['setting:defaultregistrationexpiredmessagedefault'] = 'The registration period for the following session has been closed:

Course: [coursename]

Face-to-face: [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultregistrationexpiredmessagedefault_v9'] = 'The registration period for the following session has been closed:

Course: [coursename]

Seminar: [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultdatetimechangesubjectdefault'] = 'Face-to-face booking date/time changed: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultdatetimechangesubjectdefault_v9'] = 'Seminar date/time changed: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultminbookings'] = 'Default minimum bookings';
$string['setting:defaultminbookings_help'] = 'Default value for all seminar events. All events can still have a custom minimum bookings when setting up a new seminar event.';
$string['setting:defaultreminderinstrmngr'] = 'Default reminder message sent to managers.';
$string['setting:defaultreminderinstrmngr_caption'] = 'Reminder message (managers)';
$string['setting:defaultreminderinstrmngrdefault'] = '*** Reminder only ****

Your staff member [firstname] [lastname] is booked to attend and above course and has also received this reminder email.

If you are not their Team Leader / Manager and believe you have received this email by mistake please reply to this email.

';
$string['setting:defaultreminderinstrmngrdefault_v92'] = '*** Reminder only ****

Your staff member [firstname] [lastname] is booked to attend and above course and has also received this reminder email.

If you are not their Team Leader / Manager and believe you have received this email by mistake please reply to this email.

Below is the message that was sent to the learner:

';
$string['setting:defaultreminderinstrmngrcopybelow'] = '*** [firstname] [lastname]\'s reminder email is copied below ****';
$string['setting:defaultremindermessage'] = 'Default reminder message sent to users.';
$string['setting:defaultremindermessage_caption'] = 'Reminder message';
$string['setting:defaultremindermessagedefault'] = 'This is a reminder that you are booked on the following course:

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]
Cost:   [cost]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]

***Please arrive ten minutes before the course starts***

To re-schedule or cancel your booking
To re-schedule your booking you need to cancel this booking and then re-book a new session.  To cancel your booking, return to the site, then to the page for this course, and then select \'cancel\' from the booking information screen.

[details]
';
$string['setting:defaultremindermessagedefault_v9'] = 'This is a reminder that you are booked on the following course:

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]
Cost:   [cost]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]

***Please arrive ten minutes before the course starts***

To re-schedule or cancel your booking
To re-schedule your booking you need to cancel this booking and then re-book a new event.  To cancel your booking, return to the site, then to the page for this course, and then select \'cancel\' from the booking information screen.

[details]
';
$string['setting:defaultremindersubject'] = 'Default subject line for reminder emails.';
$string['setting:defaultremindersubject_caption'] = 'Reminder subject';
$string['setting:defaultremindersubjectdefault'] = 'Face-to-face booking reminder: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultremindersubjectdefault_v9'] = 'Seminar booking reminder: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultrequestinstrmngrdefault_v24'] = 'This is to advise that [firstname] [lastname] has requested to be booked into the following course, and you are listed as their Team Leader / Manager.

Course:   [facetofacename]
Cost:   [cost]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]

Please follow the link below to approve the request:
[attendeeslink]

';
$string['setting:defaultrequestinstrmngrdefault'] = 'This is to advise that [firstname] [lastname] has requested to be booked into the following course, and you are listed as their Team Leader / Manager.

Please review this request before registration closes on [registrationcutoff]

Follow the link below to approve the request:
[attendeeslink]
';
$string['setting:defaultrequestinstrmngrdefault_v92'] = 'This is to advise that [firstname] [lastname] has requested to be booked into the following course, and you are listed as their Team Leader / Manager.

Please review this request before registration closes on [registrationcutoff]

Follow the link below to approve the request:
[attendeeslink]

Below is the message that was sent to the learner:

';
$string['setting:defaultrequestinstrmngrcopybelow'] = '*** [firstname] [lastname]\'s booking request is copied below ****';
$string['setting:defaultrequestmessagedefault'] = 'Your request to book into the following course has been sent to your manager:

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]
Cost:   [cost]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaultrequestmessagedefault_v9'] = 'Your request to book into the following course has been sent to your manager:

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]
Cost:   [cost]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]

This request will expire on [registrationcutoff]
';
$string['setting:defaultrequestsubjectdefault'] = 'Face-to-face booking request: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultrequestsubjectdefault_v9'] = 'Seminar booking request: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultrolerequestinstrmngrdefault'] = 'This is to advise that [firstname] [lastname] has requested to be booked into the following course, and you are listed as a [sessionrole] for the session.

Please review this request before registration closes on [registrationcutoff]

Follow the link below to review the request:
[attendeeslink]
';
$string['setting:defaultrolerequestinstrmngrdefault_v92'] = 'This is to advise that [firstname] [lastname] has requested to be booked into the following course, and you are listed as a [sessionrole] for the session.

Please review this request before registration closes on [registrationcutoff]

Follow the link below to review the request:
[attendeeslink]

Below is the message that was sent to the learner:

';
$string['setting:defaultrolerequestinstrmngrcopybelow'] = '*** [firstname] [lastname]\'s booking request is copied below ****';
$string['setting:defaultrolerequestmessagedefault'] = 'Your request to book into the following course has been sent to the sessions [sessionrole](s):

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]
Cost:   [cost]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaultrolerequestmessagedefault_v9'] = 'Your request to book into the following course has been sent to the sessions [sessionrole](s):

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]
Cost:   [cost]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]

This request will expire on [registrationcutoff]
';
$string['setting:defaultrolerequestsubjectdefault'] = 'Seminar booking role request: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaultadminrequestinstrmngrdefault'] = 'This is to advise that [firstname] [lastname] has requested to be booked into the following course, and you are listed as an approver for the session.

Please review this request before registration closes on [registrationcutoff]

Follow the link below to approve the request:
[attendeeslink]
';
$string['setting:defaultadminrequestinstrmngrdefault_v92'] = 'This is to advise that [firstname] [lastname] has requested to be booked into the following course, and you are listed as an approver for the session.

Please review this request before registration closes on [registrationcutoff]

Follow the link below to approve the request:
[attendeeslink]

Below is the message that was sent to the learner:

';
$string['setting:defaultadminrequestinstrmngrcopybelow'] = '*** [firstname] [lastname]\'s booking request is copied below ****';
$string['setting:defaultadminrequestmessagedefault'] = 'Your request to book into the following course has been sent to the sessions approvers:

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]
Cost:   [cost]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaultadminrequestmessagedefault_v9'] = 'Your request to book into the following course has been sent to the sessions approvers:

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]
Cost:   [cost]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]

This request will expire on [registrationcutoff]
';
$string['setting:defaultadminrequestsubjectdefault'] = 'Seminar booking admin request: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaulttrainerconfirmationmessage'] = 'Default message sent to trainers when assigned to an event.';
$string['setting:defaulttrainerconfirmationmessage_caption'] = 'Trainer confirmation message';
$string['setting:defaulttrainerconfirmationmessagedefault'] = 'This is to confirm that you are now assigned to deliver training on the following course:

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]

Duration:    [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]

***Please arrive ten minutes before the course starts***

[details]
';
$string['setting:defaulttrainerconfirmationmessagedefault_v9'] = 'This is to confirm that you are now assigned to deliver training on the following course:

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]

***Please arrive ten minutes before the course starts***

[details]
';
$string['setting:defaulttrainerconfirmationsubject'] = 'Default subject line for trainer confirmation emails.';
$string['setting:defaulttrainerconfirmationsubject_caption'] = 'Trainer confirmation subject';
$string['setting:defaulttrainerconfirmationsubjectdefault'] = 'Face-to-face trainer confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]';
$string['setting:defaulttrainerconfirmationsubjectdefault_v9'] = 'Seminar trainer confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]';

$string['setting:defaulttrainersessioncancellationmessage'] = 'Default event cancellation message sent to the trainer.';
$string['setting:defaulttrainersessioncancellationmessage_caption'] = 'Trainer event cancellation message';
$string['setting:defaulttrainersessioncancellationmessagedefault'] = 'This is to advise that your assigned training session the following course has been cancelled:

***SESSION CANCELLED***

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaulttrainersessioncancellationmessagedefault_v9'] = 'This is to advise that your assigned training event the following course has been cancelled:

***EVENT CANCELLED***

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaulttrainersessioncancellationsubject'] = 'Default subject line for trainer event cancellation emails.';
$string['setting:defaulttrainersessioncancellationsubject_caption'] = 'Trainer event cancellation subject';
$string['setting:defaulttrainersessioncancellationsubjectdefault'] = 'Face-to-face session trainer cancellation';
$string['setting:defaulttrainersessioncancellationsubjectdefault_v9'] = 'Seminar event trainer cancellation';

$string['setting:defaulttrainersessionunassignedmessage'] = 'Default event unassigned message sent to the trainer.';
$string['setting:defaulttrainersessionunassignedmessage_caption'] = 'Trainer event unassigned message';
$string['setting:defaulttrainersessionunassignedmessagedefault'] = 'This is to advise that you have been unassigned from training for following course:

***SESSION UNASSIGNED***

Participant:   [firstname] [lastname]
Course:   [coursename]
Face-to-face:   [facetofacename]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaulttrainersessionunassignedmessagedefault_v9'] = 'This is to advise that you have been unassigned from training for following course:

***EVENT UNASSIGNED***

Participant:   [firstname] [lastname]
Course:   [coursename]
Seminar:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaulttrainersessionunassignedsubject'] = 'Default subject line for trainer event unassigned emails.';
$string['setting:defaulttrainersessionunassignedsubject_caption'] = 'Trainer event unassigned subject';
$string['setting:defaulttrainersessionunassignedsubjectdefault'] = 'Face-to-face session trainer unassigned';
$string['setting:defaulttrainersessionunassignedsubjectdefault_v9'] = 'Seminar event trainer unassigned';
$string['setting:defaultvalue'] = 'Default value';
$string['setting:defaultwaitlistedmessage'] = 'Default wait-listed message sent to users.';
$string['setting:defaultwaitlistedmessage_caption'] = 'Wait-listed message';
$string['setting:defaultwaitlistedmessagedefault'] = 'This is to advise that you have been added to the waitlist for:

Course:   [coursename]
Face-to-face:   [facetofacename]
Location:  [session:location]
Participant:   [firstname] [lastname]

***Please note this is not a course booking confirmation***

By waitlisting you have registered your interest in this course and will be contacted directly when sessions become available.

To remove yourself from this waitlist please return to this course and click Cancel waitlist. Please note there is no waitlist removal confirmation email.
';
$string['setting:defaultwaitlistedmessagedefault_v27'] = 'This is to advise that you have been added to the waitlist for:

Course:   [coursename]
Face-to-face:   [facetofacename]
Location:  [session:location]
Participant:   [firstname] [lastname]

***Please note this is not a course booking confirmation***

By waitlisting you have registered your interest in this course and will be contacted directly when sessions become available.

To remove yourself from this waitlist please return to this course and click Cancel Booking. Please note there is no waitlist removal confirmation email.
';
$string['setting:defaultwaitlistedmessagedefault_v9'] = 'This is to advise that you have been added to the waitlist for:

Course:   [coursename]
Seminar:   [facetofacename]
Participant:   [firstname] [lastname]

Location(s):
[#sessions]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]

***Please note this is not a course booking confirmation***

By waitlisting you have registered your interest in this course and will be contacted directly when events become available.

To remove yourself from this waitlist please return to this course and click Cancel waitlist. Please note there is no waitlist removal confirmation email.
';
$string['setting:defaultsessioncancellationinstrmngrcopybelow'] = '*** [firstname] [lastname]\'s session cancellation is copied below ****';
$string['setting:defaultsessioncancellationmessage'] = 'Default session cancellation message sent to the user.';
$string['setting:defaultsessioncancellationmessage_caption'] = 'Session cancellation message';
$string['setting:defaultsessioncancellationmessagedefault'] = 'This is to advise that the following session has been cancelled:

***EVENT CANCELLED***

Course:   [coursename]
Face-to-face:   [facetofacename]

Duration:   [duration]
Date(s):
[alldates]

Location:   [session:location]
Venue:   [session:venue]
Room:   [session:room]
';
$string['setting:defaultsessioncancellationmessagedefault_v9'] = 'This is to advise that the following session has been cancelled:

***EVENT CANCELLED***

Course:   [coursename]
Seminar:   [facetofacename]

Date(s) and location(s):
[#sessions]
[session:startdate], [session:starttime] - [session:finishdate], [session:finishtime] [session:timezone]
Duration: [session:duration]
Room: [session:room:name]
Building: [session:room:cf_building]
Location: [session:room:cf_location]
[session:room:link]
[/sessions]
';
$string['setting:defaultsessioncancellationsubject'] = 'Default subject line for session cancellation emails.';
$string['setting:defaultsessioncancellationsubject_caption'] = 'Event cancellation subject';
$string['setting:defaultsessioncancellationsubjectdefault'] = 'Seminar event cancellation';
$string['setting:defaultwaitlistedsubject'] = 'Default subject line for wait-listed emails.';
$string['setting:defaultwaitlistedsubject_caption'] = 'Wait-listed subject';
$string['setting:defaultwaitlistedsubjectdefault'] = 'Waitlisting advice for [facetofacename]';
$string['setting:defaultwaitlistedsubjectdefault_v9'] = 'Waitlisting advice for [facetofacename]';
$string['setting:displaysessiontimezones'] = 'When enabled the timezone of the Seminar event will be shown otherwise it will be hidden (selecting a timezone for an event will also be enabled/disabled).';
$string['setting:displaysessiontimezones_caption'] = 'Display event timezones';
$string['setting:disableicalcancel'] = 'Disable cancellation emails with an iCalendar information.';
$string['setting:disableicalcancel_caption'] = 'Disable iCalendar cancellations:';
$string['setting:fromaddress'] = 'What will appear in the From field of email reminders sent by this module, unless "Always send email from the no-reply address" is set.';
$string['setting:fromaddress_caption'] = 'Sender address:';
$string['setting:fromaddressdefault'] = 'totara@example.com';
$string['setting:lotteryenabled_caption'] = 'Waitlist lottery';
$string['setting:lotteryenabled'] = 'Enable or disable waitlist lottery';
$string['setting:managerreserve'] = 'Allow reserve/assign';
$string['setting:managerreserve_desc'] = 'Managers are able to make reservations or bookings on behalf of their team members';
$string['setting:managerreserveheader'] = 'Manager reservations';
$string['setting:maxmanagerreserves'] = 'Max reservations';
$string['setting:maxmanagerreserves_desc'] = 'The total number of reservations / bookings that a manager can make for their team';
$string['setting:multiplesessions'] = 'Default value for allowing multiple events signup per user';
$string['setting:multiplesessions_caption'] = 'Multiple events default';
$string['setting:oneemailperday'] = 'Send multiple confirmation emails for multi-date events. Note: If there is more than one event date on a single day then each session will generate an email. One session spanning over multiple days will generate only one email.';
$string['setting:oneemailperday_caption'] = 'One message per date:';
$string['setting:hidecost'] = 'Hide the normal cost, discount cost and user discount code fields.';
$string['setting:hidecost_caption'] = 'Hide cost and discount:';
$string['setting:hidediscount'] = 'Hide the discount cost and user discount code fields.';
$string['setting:hidediscount_caption'] = 'Hide discount:';
$string['setting:selectjobassignmentonsignupglobal'] = 'Select job assignment on signup';
$string['setting:selectjobassignmentonsignupglobal_caption'] = 'When enabled a setting will appear in seminar activity settings to force users with multiple job assignments to choose which capacity they will be signing up on.';
$string['setting:possiblevalues'] = 'List of possible values';
$string['setting:reservecanceldays'] = 'Reservation cancellation days';
$string['setting:reservecanceldays_desc'] = 'The number of days in advance of the event that reservations will be automatically cancelled, if not confirmed.';
$string['setting:reservedays'] = 'Reservation deadline';
$string['setting:reservedays_desc'] = 'The number of days before the event starts after which no more reservations are allowed (must be greater than the cancellation days)';
$string['setting:showinsummary'] = 'Show in exports and lists';
$string['setting:sessionroles'] = 'Users assigned to the selected roles in a course can be tracked with each seminar event';
$string['setting:sessionroles_caption'] = 'Event roles:';
$string['setting:sessionrolesnotify'] = 'This setting affects <b>minimum booking</b> and <b>minimum booking cut-off</b> notifications. Make sure you select roles that can manage seminar events. Automated warnings will be sent to all users with selected role(s) in seminar activity, course, category, or system level.';
$string['setting:sessionrolesnotify_caption'] = 'Notification recipients';
$string['setting:type'] = 'Field type';
$string['setting:notificationdisable'] = 'Turn on/off seminar activity notification emails to users';
$string['setting:notificationdisable_caption'] = 'Disable notifications';
$string['showattendeesnote'] = 'Show attendee\'s note';
$string['showbylocation'] = 'Show by location';
$string['showcancelreason'] = 'Show cancellation reason';
$string['showoncalendar'] = 'Calendar display settings';
$string['sign-ups'] = 'Sign-ups';
$string['signature'] = 'Signature';
$string['signinsheetreport'] = 'Session attendance sheet';
$string['signup'] = 'Sign-up';
$string['signupandaccept'] = 'Agree and submit';
$string['signupandrequest'] = 'Request approval';
$string['signupcustomfieldtab'] = 'Sign-up';
$string['signupdata'] = 'Sign-up data';
$string['signupfields'] = 'Sign-up fields';
$string['signupfieldslimitation'] = 'The values entered below will be populated for all selected users. To enter different values for each user use the <a href="{$a}">file import</a>';
$string['signupfor'] = 'Sign-up for {$a}';
$string['signupforsession'] = 'Sign-up for an available upcoming event';
$string['signupforthissession'] = 'Sign-up for this seminar event';
$string['signups'] = 'Sign-ups';
$string['signupunavailable'] = 'Sign-up unavailable';
$string['spacesreserved'] = 'Spaces reserved';
$string['startdateafter'] = 'Start date after';
$string['finishdatebefore'] = 'Finish date before';
$string['subject'] = 'Change in booking in the course {$a->coursename} ({$a->duedate})';
$string['submissions'] = 'Submissions';
$string['submitted'] = 'Submitted';
$string['submit'] = 'Submit';
$string['suppressccmanager'] = 'Suppress notifications to manager about added and removed attendees';
$string['suppressemail'] = 'Suppress email notification';
$string['suppressemailforattendees'] = 'Suppress the confirmation and calendar invite emails for newly added attendees and the cancellation emails for removed attendees';
$string['status'] = 'Status';
$string['status_booked'] = 'Booked';
$string['status_fully_attended'] = 'Fully attended';
$string['status_no_show'] = 'No show';
$string['status_not_set'] = 'Not set';
$string['status_partially_attended'] = 'Partially attended';
$string['status_pending_requests'] = 'Pending Requests';
$string['status_requested'] = 'Requested';
$string['status_requestedadmin'] = 'Requested (2step)';
$string['status_user_cancelled'] = 'User Cancelled';
$string['status_waitlisted'] = 'Wait-listed';
$string['status_approved'] = 'Approved';
$string['status_declined'] = 'Declined';
$string['status_session_cancelled'] = 'Event Cancelled';
$string['submitcsvtext'] = 'Submit CSV text';
$string['successfullyaddededitedxattendees'] = 'Successfully added/edited {$a} attendees.';
$string['successfullyremovedxattendees'] = 'Successfully removed {$a} attendees.';
$string['summary'] = 'Summary';
$string['takeattendance'] = 'Take attendance';
$string['template'] = 'Template';
$string['templateadminrequest'] = 'Booking admin request';
$string['templateallreservationcancel'] = 'All reservations cancelled';
$string['templatecancellation'] = 'Cancellation';
$string['templateconfirmation'] = 'Booking request confirmation';
$string['templatecontainsoldplaceholders'] = 'This template contains a deprecated placeholder';
$string['templatedecline'] = 'Booking request declined';
$string['templatemanagerrequest'] = 'Booking manager request';
$string['templatesoldplaceholders'] = 'Some templates contain deprecated placeholders. Please review the templates marked with a warning icon and update where necessary.';
$string['templatereminder'] = 'Reminder';
$string['templatereservationcancel'] = 'Reservation cancelled';
$string['templaterolerequest'] = 'Booking role request';
$string['templatetimechange'] = 'Time changed';
$string['templatetrainercancel'] = 'Trainer cancelled';
$string['templatetrainerconfirm'] = 'Trainer confirmed';
$string['templatetrainerunassign'] = 'Trainer unassigned learner';
$string['templatewaitlist'] = 'Waitlisted';
$string['thissession'] = 'This event';
$string['time'] = 'Time';
$string['timeandtimezone'] = 'Time and Time Zone';
$string['timedue'] = 'Registration deadline';
$string['timefinish'] = 'Finish time';
$string['timestart'] = 'Start time';
$string['timecancelled'] = 'Time Cancelled';
$string['timerequested'] = 'Time Requested';
$string['timesignedup'] = 'Time Signed Up';
$string['timestampbyuser'] = '{$a->time} by {$a->user}';
$string['title'] = 'Title';
$string['timezoneupgradeinfomessage'] = 'WARNING : This upgrade to seminar adds the ability to specify the timezone in which a seminar event will occur.<br /><br />It is <b>strongly</b> recommended that you check the event timezones, start times and end times for all upcoming seminar events that were created prior to this upgrade.';
$string['tutor'] = 'Tutor';
$string['datesignedup'] = 'Date Signed Up';
$string['thirdpartyemailaddress'] = 'Third-party email address(es)';
$string['thirdpartywaitlist'] = 'Notify third-party about wait-listed events';
$string['type'] = 'Type';
$string['unapprovedrequests'] = 'Unapproved Requests';
$string['unavailable'] = 'Unavailable';
$string['unavailablenotifications'] = 'This seminar has unavailable notifications.<br/><a href=\'{$a->url1}\'>Restore them for this seminar only</a> or go to <a href=\'{$a->url2}\'>Manage notification templates</a> to restore them for all seminars.';
$string['unavailabletemplates'] = '{$a} notification templates below are <strong>unavailable</strong> to any seminars created in an older version of this product. Please review and restore where necessary.';
$string['undo'] = 'Undo';
$string['unknowndate'] = '(unknown date)';
$string['unknowntime'] = '(unknown time)';
$string['upcomingsessions'] = 'Upcoming events';
$string['upcomingsessionslist'] = 'List of all upcoming events for this seminar activity';
$string['upcomingsessionsinasset'] = 'Upcoming sessions using this asset';
$string['upcomingsessionsinroom'] = 'Upcoming sessions in this room';
$string['upcomingsessionsinroomlist'] = 'A list of upcoming sessions taking place in this room';
$string['updateactivities'] = 'Update all activities';
$string['updateactivities_help'] = 'When checked, saving and updating the template will update all activities that have notifications based on this template.';
$string['updateactivitieswarning'] = '<b>Warning:</b> If you choose to update all activities then all seminar activities that have notifications based off this template will be updated with the changes that have been made.';
$string['updateattendeessuccessful'] = 'Successfully updated attendance';
$string['updateattendeesunsuccessful'] = 'An error has occurred, attendance could not be updated';
$string['updatejobassignment'] = 'Update job assignment';
$string['updaterequests'] = 'Update requests';
$string['updatewaitlist'] = 'Update waitlist';
$string['upgradefixstatusapprovedlimbousersdescription'] = 'Learners with an invalid seminar signup status were detected.
The following learners were removed from events:<br>{$a}';
$string['upgradefixstatusapprovedlimbousersdetail'] = '{$a->user} was removed from seminar {$a->f2f}';
$string['upgradeprocessinggrades'] = 'Processing seminar grades, this may take a while if there are many events...';
$string['usercancelledon'] = 'User cancelled on {$a}';
$string['userdataitemcustomfields'] = 'Sign-up and cancellation custom fields';
$string['userdataiteminterest'] = 'Declared interest';
$string['userdataitemsignups'] = 'Attendance records';
$string['userdoesnotexist'] = 'User with {$a->fieldname} "{$a->value}" does not exist';
$string['useriddoesnotexist'] = 'User with ID "{$a}" does not exist';
$string['useroomcapacity'] = 'Use room capacity';
$string['userstoadd'] = 'Users to add';
$string['userstoaddcomment'] = 'Enter one user per line:';
$string['userstobeadded'] = 'Users to be added';
$string['userstoremove'] = 'Users to remove';
$string['allowbookingscancellations'] = 'Allow cancellations';
$string['allowbookingscancellationsdefault'] = 'Default &lsquo;allow cancellations&rsquo; setting for all events';
$string['allowbookingscancellations_help'] = 'Allow users to cancel their bookings; at any time, never, or until cut-off reached (x amount of time before the event starts).';
$string['allowbookingscancellationsdefault_help'] = 'Set the default cancellation settings for this seminar and allow users to cancel their bookings at any time, never or until cut-off reached (x amount of time before the event starts).';
$string['usercalentry'] = 'Show entry on user\'s calendar';
$string['usercancelled'] = 'User cancellation';
$string['usercancellationnoteheading'] = '{$a} - Cancellation note';
$string['userdatapurgedcancel'] = 'User data has been purged';
$string['userdeletedcancel'] = 'User has been deleted';
$string['userjobassignmentheading'] = '{$a} - update selected job assignment';
$string['usersuspendedcancel'] = 'User has been suspended';
$string['usernotsignedup'] = 'Status: not signed up';
$string['usernote'] = 'Sign-up note';
$string['usernoteupdated'] = 'Attendee\'s note updated';
$string['usernoteheading'] = '{$a} - update note';
$string['usersignedup'] = 'Status: signed up';
$string['usersignedupmultiple'] = 'User signed up on {$a} events';
$string['usersignedupon'] = 'User signed up on {$a}';
$string['userwillbewaitlisted'] = 'This event is currently full. By clicking the "Join waitlist" button, you will be placed on the event\'s waitlist.';
$string['validation:needatleastonedate'] = 'You need to provide at least one date, or else mark the event as wait-listed.';
$string['venue'] = 'Venue';
$string['versioncontrol'] = 'Version control';
$string['viewallsessions'] = 'View all events';
$string['viewasset'] = 'View asset';
$string['viewattendees'] = 'View attendees';
$string['viewdetails'] = 'View details';
$string['viewresults'] = 'View results';
$string['viewroom'] = 'View room';
$string['viewsubmissions'] = 'View submissions';
$string['waitlistedmessage'] = 'Wait-listed message';
$string['waitlisteveryone'] = 'Send all bookings to the waiting list';
$string['waitlisteveryone_help'] = 'Everyone who signs up for this event will be added to the waiting list.';
$string['waitlistselectoneormoreusers'] = 'Please select one or more users to update';
$string['wait-list'] = 'Wait-list';
$string['wait-listed'] = 'Wait-listed';
$string['xerrorsencounteredduringimport'] = '{$a} problem(s) encountered during import.';
$string['xhours'] = '{$a} hour(s)';
$string['xmessagesfailed'] = '{$a} message(s) failed to send';
$string['xmessagessenttoattendees'] = '{$a} message(s) successfully sent to attendees';
$string['xmessagessenttoattendeesandmanagers'] = '{$a} message(s) successfully sent to attendees and their managers';
$string['xminutes'] = '{$a} minute(s)';
$string['xusers'] = '{$a} user(s)';
$string['youarebooked'] = 'You are booked for the following event';
$string['yourbookings'] = 'Your bookings / reservations';
$string['youremailaddress'] = 'Your email address';
$string['youwillbeaddedtothewaitinglist'] = 'Please Note: You will be added to the waiting list for this event';
$string['error:shortnametaken'] = 'Custom field with this short name already exists.';

// -------------------------------------------------------
// Help Text

$string['allowoverbook_help'] = 'When **Enable waitlist** is checked, learners will be able to sign up for a seminar event even if it is already full.

When a learner signs up for an event that is already full, they will receive an email advising that they have been waitlisted for the event and will be notified when a booking becomes available.';
$string['approvalreqd_help'] = 'When **Approval required** is checked, a learner will need approval from their manager to be permitted to attend a seminar event.';
$string['cancellationinstrmngr'] = '# Notice for manager';
$string['cancellationinstrmngr_help'] = 'When **Send notice to manager** is checked, the text in the **Notice for manager** field is sent to a learner\'s manager advising that they have cancelled a seminar booking.';
$string['cancellationmessage_help'] = 'This message is sent out whenever users cancel their booking for an event.';
$string['confirmationinstrmngr'] = '# Notice for manager';
$string['confirmationinstrmngr_help'] = 'When **Send notice to manager** is checked, the text in the **Notice for manager** field is sent to a manager advising that a staff member has signed up for a seminar event.';
$string['confirmationmessage_help'] = 'This message is sent out whenever users sign up for an event.';
$string['description_help'] = '**Description** is the course description that displays when a learner enrols on a seminar event.

The **Description** also displays in the training calendar.';
$string['details_help'] = 'Details are tracked per event basis.
If text is populated in the details field, the details text will be displayed on the user signup page.
By default, the details text also appears in the confirmation, reminder, waitlist, and cancellation email messages.';
$string['discountcode_help'] = 'Discount code is the code required for the discount cost to be tracked for the training of a staff member.
If the staff member does not enter the discount code, the normal cost appears in the training record.';
$string['discountcodelearner'] = 'Discount Code';
$string['discountcodelearner_help'] = 'If you know the discount code enter it here. If you leave this field blank you will be charged the normal cost for this event.';
$string['discountcost_help'] = 'Discount cost is the amount charged to staff members who have a membership ID.';
$string['duration_help'] = '**Duration** is the total length of the training in hours.
If the training occurs over two or more time periods, the duration is the combined total.
If the session date is known then this value is automatically recalculated when the session is saved.';
$string['emailmanagercancellation'] = '# Send notice to manager';
$string['emailmanagercancellation_help'] = 'When **Send notice to manager** is checked, an email will be sent to the learner\'s manager advising them that the seminar booking has been cancelled.';
$string['emailmanagerconfirmation'] = '# Send notice to manager';
$string['emailmanagerconfirmation_help'] = 'When **Send notice to manager** is checked, a confirmation email will be sent to the learner\'s manager when the learner signs up for a seminar event.';
$string['emailmanagerreminder'] = '# Send notice to manager';
$string['emailmanagerreminder_help'] = 'When **Send notice to manager** is checked, a reminder message will be sent to the learner\'s manager a few days before the start date of the seminar event.';
$string['location_help'] = '**Location** describes the vicinity of the event (city, county, region, etc).

**Location** displays on the course page, **Sign-up page**, the **View all events** page, and in all email notifications.

On the **View all events** page, the listed events can be filtered by location.';
$string['modulename_help'] = 'The seminar activity module enables a trainer to set up a booking system for one or many in-person/classroom based events.

Each event within a seminar activity can have customised settings around room, start time, finish time, cost, capacity, etc. These can be set to run over multiple days or allow for unscheduled and waitlisted events.

An Activity may be set to require manager approval and trainers can configure automated notifications and event reminders for attendees.

learners can view and sign-up for events with their attendance tracked and recorded within the Grades area.';
$string['mods_help'] = 'Seminar activities are used to keep track of in-person trainings which require advance booking.

Each activity is offered in one or more identical events. These events can be given over multiple days.

Reminder messages are sent to users and their managers a few days before the event is scheduled to start. Confirmation messages are sent when users sign-up for an event or cancel.';
$string['multiplesessions_help'] = 'Use this option if you want users be able to sign up to multiple events . When this option is toggled, users can sign up for multiple events in the activity.';
$string['normalcost_help'] = 'Normal cost is the amount charged to staff members who do not have a membership ID.';
$string['notificationtype_help'] = 'Notification Type allows the learner to select how they would like to be notified of their booking.

* Email notification and iCalendar appointment.
* Email notification only.
* No Email notification.';
$string['recipients_help'] = 'The options are* **Booked**: Allows you to send the notification to all users who were booked, only those who attended, or only those who did not attend.

Please note, when selecting **All booked**, notifications will be issued to all booked users, regardless of their attendance status, for events past and present.* **Wait-listed**: Will send a notification to those who are signed up for an event which allows overbooking, but are not yet booked.* **User cancelled**: Will send a notification to users for whom an event was cancelled or users who removed themselves from an event.* **Pending Requests**: Will send a notification to users for whom have requested a booking from their manager/role/admin that has not been approved or declined yet.';
$string['reminderinstrmngr'] = '# Notice for Manager';
$string['reminderinstrmngr_help'] = 'When **Send notice to manager** is checked, the text in the **Notice for Manager** field is sent to a learner\'s manager advising that they have signed up for a seminar event.';
$string['remindermessage_help'] = 'This message is sent out a few days before an event\'s start date.';
$string['requestmessage_help'] = 'When **Approval required** is enabled, the **Request message** section is available.

The **Request message** section displays the notices sent to the learner and their manager regarding the approval process for the learner to attend the seminar event.

* **Subject:** Is the subject line that appears on the request approval emails sent to the manager and the learner.
* **Message:** Is the email text sent to the learner advising them that their request to attend the seminar event has been sent to their manager for approval.
* **Notice for manager:** Is the email text sent to the learner\'s manager requesting approval to attend the seminar event.';
$string['room_help'] = '**Room** is the name/number/identifier of the room being used for the training event.

The **Room** displays on the **Sign-up** page, the **View all events** page, and in all email notifications.';
$string['sessionsoncoursepage_help'] = 'This is the number of events for each seminar activity that will be shown on the main course page.';
$string['shortname'] = '# Short Name';
$string['shortname_help'] = '**Short name** is the description of the event that appears on the training calendar when **Show on the calendar** is enabled.';
$string['showoncalendar_help'] = 'When **Site** is selected the seminar activity events will be displayed on the site calendar as a Global Event.  All site users will be able to view these events.

When **Course** is selected all of the seminar activity events will be displayed on the course calendar and as Course Event on the site level calendar and visible to all users enrolled in the course.

When **None** is selected, seminar activity events will only be displayed as User Events on a confirmed attendee\'s calendar, provided the **Show on user\'s calendar** option has been selected.';
$string['suppressemail_help'] = 'Use this option if you want to silently add/remove users from a seminar event. When this option is toggled, the usual email confirmation is not sent to the selected users.';
$string['thirdpartyemailaddress_help'] = '**Third-party email address(es)** is an optional field used to specify the email address of a third-party (such as an external instructor) who will then receive confirmation messages whenever a user signs-up for an event.
When entering **multiple email addresses**, separate each address with a comma. For example: bob@example.com,joe@example.com';
$string['thirdpartywaitlist_help'] = 'When **Notify third-party about wait-listed events** is selected the third-party(s) will be notified when a learner signs up for a wait-listed event. When

**Notify third-party about wait-listed events** is not enabled third-party(s) will only be notified when a user signs up (or cancels) for a scheduled event.';
$string['timefinish_help'] = 'Finish time is the time when the event ends.';
$string['timestart_help'] = 'Start time is the time when the event begins.';
$string['useridentifier'] = 'User identifier';

$string['usercalentry_help'] = 'When active this setting adds a User Event entry to the calendar of an attendee of a seminar event. When turned off this prevents a duplicate event appearing in an event attendee\'s calendar, where you have calendar display settings set to Course or Site.';
$string['venue_help'] = '**Venue** is the building the event will be held in.

The **Venue** displays on the **Sign-up** page, the **View all events** page and in all email notifications.';
$string['waitlistedmessage_help'] = 'This message is sent out whenever users sign-up for a wait-listed event.';
$string['usernote_help'] = 'Any specific requirements that the event organiser might need to know about:

* Dietary requirements.
* Disabilities.';

//Totara Messaging strings
$string['approveinstruction'] = 'To approve event registration, press accept';
$string['bookedforsession'] = 'Booked for event {$a}';
$string['cancelledforsession'] = 'Cancelled for event {$a}';
$string['cancelusersession'] = 'Cancelled for {$a->usermsg} event {$a->url}';
$string['rejectinstruction'] = 'To reject event registration, press reject';
$string['registrationnotopen'] = 'Sign-up period not open';
$string['registrationnotopenalert'] = 'The Sign-up period for your selected session is currently not open, it opens on the {$a}';
$string['registrationclosed'] = 'Sign-up period is now closed';
$string['registrationclosedalert'] = 'The Sign-up period for your selected session has closed, please select another session';
$string['registrationhoverhintstart'] = 'Sign-up period opens: {$a->startdate}, {$a->starttime}';
$string['registrationhoverhintstarttz'] = 'Sign-up period opens: {$a->startdate}, {$a->starttime} (time zone: {$a->timezone})';
$string['registrationhoverhintend'] = 'Sign-up period closes: {$a->enddate}, {$a->endtime}';
$string['registrationhoverhintendtz'] = 'Sign-up period closes: {$a->enddate}, {$a->endtime} (time zone: {$a->timezone})';
$string['registrationdatetime'] = 'Sign-up period date/time';
$string['registrationtimestart'] = 'Sign-up opens';
$string['registrationtimestart_help'] = 'If enabled, learners will not be able to sign up for this session until this time has arrived.';
$string['registrationtimefinish'] = 'Sign-up closes';
$string['registrationtimefinish_help'] = 'If enabled, learners will not be able to sign up for this session once this time has passed.';
$string['registrationerrorstartfinish'] = 'Sign-up period start time must be before sign-up finish time';
$string['registrationstartsession'] = 'Sign-up period opening time must be before session start time';
$string['registrationfinishsession'] = 'Sign-up period closing time must be on or before session start time';
$string['registrationdetails'] = 'Sign-up period';
$string['registrationopens'] = 'Opens';
$string['registrationcloses'] = 'Closes';
$string['registrationclosingblurb'] = 'The following people have indicated their interest. You may wish to inform them if you create a new session.';
$string['registrationclosingconfirmation'] = 'Are you sure you want to close the sign-up period for this session?';
$string['requestattendsession'] = 'Request to attend session {$a}';
$string['requestattendsession_message'] = 'Request to attend event {$a->linkname} {$a->status}';
$string['requestattendsession_subject'] = 'Request to attend event {$a->name} {$a->status} by {$a->user}';
$string['requestattendsessionsent'] = 'Request to attend event {$a} sent to manager';
$string['requestuserattendsession'] = 'Request for {$a->usermsg} to attend event {$a->url}';
$string['nosignupperiodopendate'] = 'Now';
$string['nosignupperiodclosedate'] = 'Session start date';
$string['sessiondate_help'] = 'Session is the date on which the event occurs.';
$string['signupexpired'] = 'Sending expired sign-up period date notifications to the admin user of that session';
$string['signupperiodheader'] = 'Sign-up period';
$string['signupregistrationclosed'] = 'The sign-up period for your selected session closed on the {$a->date}, {$a->time} {$a->timezone}. Please select another session.';
$string['signupregistrationnotyetopen'] = 'The sign-up period for your selected session is currently not open, it opens on the {$a->date}, {$a->time} {$a->timezone}.';
$string['signupstartend'] = '{$a->startdate} {$a->starttime} {$a->timezone} to {$a->enddate} {$a->endtime} {$a->timezone}';
$string['signupstartsonly'] = 'After {$a->startdate} {$a->starttime} {$a->timezone}';
$string['signupendsonly'] = 'Before {$a->enddate} {$a->endtime} {$a->timezone}';
$string['waitlistcancelled'] = 'Your place on the waitlist has been cancelled.';
$string['waitlistcancellationconfirm'] = 'Are you sure you want to cancel your place on the waiting list for this event?';
$string['waitlistedforsession'] = 'Waitlisted for event {$a}';
$string['waitlistfor'] = 'Waitlist for {$a}';
$string['waitliststatus'] = 'You have a place on the waitlist of the following event';
$string['warning:mixedapprovaltypes'] = 'This seminar was previously using mixed approval types, it is currently set to manager approval. Please check all self approval sessions.';

// Deprecated since Totara 9.0
$string['addmanageremailaddress'] = 'Add manager email address';
$string['addmanageremailinstruction'] = 'You have not previously entered your manager\'s email address. Please enter it below to sign-up for this event. ';
$string['addnewnotice'] = 'Add a new site notice';
$string['addnewnoticelink'] = 'Create a new site notice';
$string['allowsignupnotedefault'] = 'Default &lsquo;Users can enter requests when signing up&rsquo; setting';
$string['allowsignupnotedefault_help'] = 'Whether events in this activity will allow a sign-up note by default, can be overridden in the event settings.';
$string['availablesignupnote'] = 'Users can enter requests when signing up';
$string['availablesignupnote_help'] = 'When **Users can enter requests when signing up** is checked, learners will be able to enter any specific requirements that the event organiser might need to know about:

* Dietary requirements.
* Disabilities.';
$string['bulkaddattendeesfromfile'] = 'Bulk add attendees from file';
$string['bulkaddattendeesfrominput'] = 'Bulk add attendees from text input';
$string['bulkaddheading'] = 'Bulk Add';
$string['calendarfiltersheading'] = 'Seminar calendar filters';
$string['changemanageremailaddress'] = 'Change manager email address';
$string['changemanageremailinstruction'] = 'Please enter the email address for your current manager below.';
$string['conditions'] = 'Conditions';
$string['conditionsexplanation'] = 'All of these criteria must be met for the notice to be shown on the training calendar:';
$string['confirmmanageremailaddress'] = 'Confirm manager email address';
$string['confirmmanageremailaddressquestion'] = 'Is <strong>{$a}</strong> still your manager\'s email address?';
$string['confirmmanageremailinstruction1'] = 'You previously entered the following as your manager\'s email address:';
$string['confirmmanageremailinstruction2'] = 'Is this still your manager\'s email address?';
$string['costheading'] = 'Session Cost';
$string['declareinterestonlyiffull'] = 'Show "Declare Interest" link only if all events are closed';
$string['declareinterestonlyiffull_help'] = 'Only show the declare interest option if there are no events with spaces or waiting lists.';
$string['error:couldnotupdatemanageremail'] = 'Could not update manager email address.';
$string['error:emptymanageremail'] = 'Manager email address empty.';
$string['error:mismatchdatesdetected'] = 'Mismatch in dates detected. Start time and finish time should be provided for each date.';
$string['error:nodatesfound'] = 'No dates found';
$string['error:nomanageremail'] = 'You didn\'t provide an email address for your manager';
$string['error:nopositionselected'] = 'You must have a suitable position assigned to sign up for this seminar event.';
$string['error:nopositionselectedactivity'] = 'You must have a suitable position assigned to sign up for this seminar activity.';
$string['eventattendeepositionupdated'] = 'Attendee postion updated';
$string['exportheading'] = 'Export';
$string['facetoface:changesignedupjobposition'] = 'Change signed up job position';
$string['forceselectposition'] = 'Prevent signup if no position is selected or can be found';
$string['icalendarheading'] = 'iCalendar Attachments';
$string['manageradded'] = 'Your manager\'s email address has been accepted.';
$string['managerchanged'] = 'Your manager\'s email address has been changed.';
$string['manageremail'] = 'Manager\'s email';
$string['manageremailheading'] = 'Manager Emails';
$string['manageremailaddress'] = 'Manager\'s email address';
$string['manageremailformat'] = 'The email address must be of the format \'{$a}\' to be accepted.';
$string['manageremailinstruction'] = 'In order to sign-up for a training event, a confirmation email must be sent to your email address and copied to your manager\'s email address.';
$string['manageremailinstructionconfirm'] = 'Please confirm that this is your manager\'s email address:';
$string['managerupdated'] = 'Your manager\'s email address has been updated.';
$string['multiplesessionsheading'] = 'Multiple events signup settings';
$string['newmanageremailaddress'] = 'Manager\'s email address';
$string['noposition'] = 'User has no positions assigned.';
$string['nositenotices'] = '<p>No site notices are defined.</p>';
$string['noticedeleteconfirm'] = 'Delete site notice \'{$a->name}\'?<br/><blockquote>{$a->text}</blockquote>';
$string['noticetext'] = 'Notice text';
$string['position'] = 'Position';
$string['selectedpositionassignment'] = 'Position Assignment on sign up';
$string['selectedpositionname'] = 'Position Name on sign up';
$string['selectedpositiontype'] = 'Position Type on sign up';
$string['selectposition'] = 'Select a position';
$string['selectpositiononsignup'] = 'Select position on signup';
$string['setting:addchangemanageremail'] = 'Ask users for their manager\'s email address.';
$string['setting:addchangemanageremaildefault'] = 'Ask users for their manager\'s email address.';
$string['setting:addchangemanageremail_caption'] = 'Manager\'s email:';
$string['setting:bulkaddsource_caption'] = 'Bulk add field:';
$string['setting:bulkaddsource'] = 'When bulk adding attendees, match to the selected field.';
$string['setting:manageraddressformat'] = 'Suffix which must be present in the email address of the manager in order to be considered valid.';
$string['setting:manageraddressformat_caption'] = 'Required suffix:';
$string['setting:manageraddressformatdefault'] = '';
$string['setting:manageraddressformatreadable'] = 'Short description of the restrictions on a manager\'s email address.  This setting has no effect if the previous one is not set.';
$string['setting:manageraddressformatreadable_caption'] = 'Format example:';
$string['setting:manageraddressformatreadabledefault'] = 'firstname.lastname@company.com';
$string['setting:selectpositiononsignupglobal'] = 'Select position on signup';
$string['setting:selectpositiononsignupglobal_caption'] = 'When enabled a setting will appear in seminar activity settings to force users with multiple positions to choose which capacity they will be signing up on.';
$string['setting:sitenotices'] = 'Notices only appear on the Seminar Calendar found {$a}';
$string['setting:sitenoticeshere'] = 'here';
$string['sitenoticesheading'] = 'Site Notices';
$string['updateposition'] = 'Update position';
$string['userpositionheading'] = '{$a} - update selected position';
$string['waitliststatus'] = 'You have a place on the waitlist of the following session';

# Deprecated
$string['cancel'] = 'Cancel';
$string['capacity_help'] = '**Capacity** is the number of seats available in an event.

When a Seminar event reaches capacity the event details do not appear on the course page. The details will appear greyed out on the **View all events** page and the learner cannot enrol on the event.
&nbsp;';
$string['copy'] = 'Copy';
$string['datetimeknownhinttext'] = '';
$string['delete'] = 'Delete';
$string['duration_help'] = '**Duration** is the total length of the training in hours.
If the training occurs over two or more time periods, the duration is the combined total.
If the session date is known then this value is automatically recalculated when the session is saved.';
$string['edit'] = 'Edit';
$string['roommustbebookedtoexternalcalendar'] = 'Note: Please ensure that this room is available before creating this booking.';
$string['sessiondatetimeknown'] = 'Session date/time known';
$string['sessiondatetimeknown_help'] = '**If a session\'s date/time is known**

If **Yes** is entered for this setting, the session date and time will be displayed on the course page (if the session is upcoming and available), the **View all sessions page**, the session sign-up page, as well as all email notifications related to the session.

When a staff member signs up for a session with a known date and time:

* The staff member and the staff member\'s manager will be sent a confirmation email (i.e., the one formatted per the **Confirmation message** section of the Seminar activity settings).
* The staff member will be sent a reminder email message (i.e., the one formatted per the **Reminder message** section of the seminar activity settings). The reminder will be sent a number of days before the session, according to the **Days before message is sent** setting also found in the **Reminder message** section of the Seminar activity settings.

**If a session\'s date/time is not known (or wait-listed)**

If **No** is entered for this setting, the text \'wait-listed\' will be displayed on the course page, the **View all sessions page**, the session sign-up page, as well as all email notifications related to the session.

When a staff member signs up for a wait-listed session:

* The staff member will be sent a confirmation email (i.e. the one formatted per the **Wait-listed message** section of the Seminar activity settings).
* The staff member will not be sent a reminder email message.
* The staff member\'s manager will not be sent confirmation and cancellation email messages.';
$string['facetoface:editsessions'] = 'Add, edit, copy and delete face-to-face events';
$string['facetoface:overbook'] = 'Sign-up to full events.';
$string['xhxm'] = '{$a->hours}h {$a->minutes}m';
$string['missingdefaultnotifications'] = 'There are {$a} missing default notifications.';
$string['missingdefaultsfix'] = 'Click here to restore missing default notifications.';
$string['schedule_unit_3'] = '{$a} weeks';
$string['schedule_unit_3_singular'] = '1 week';
$string['scvtextfile_help'] = 'The file should be a CSV text file containing a heading row and one or more data rows. If a row contains multiple columns they should be separated by a comma (,). Every row must have the same number of columns. Below is a list of the heading names you can use:

* **username**: The username of the user to add.
* **idnumber**: The ID number of the user to add.
* **email**: The email address of the user to add.
{$a->customfields}

The following fields must be provided:

* Either username, idnumber, or email (only one)
{$a->requiredcustomfields}';
