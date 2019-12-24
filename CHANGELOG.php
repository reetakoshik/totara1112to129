<?php
/*

Totara Learn Changelog

 Release 11.12 (14th February 2019):
===================================


Important:

    TL-20156       Omitted environment tests have been reintroduced

                   It was discovered that several environment tests within Totara core had not
                   been running for sites installing or upgrading to Totara 11 or 12. The
                   following tests have been reintroduced to ensure that during installation
                   and upgrade the following criteria are met:

                   * Linear upgrades - Enforces linear upgrades; a site must upgrade to a
                     higher version of Totara that was released on or after the current version
                     they are running. For instance if you are running Totara 11.11 then you can
                     only upgrade to Totara 12.2 or higher.
                   * XML External entities are not present - Checks to make sure that there
                     are no XML files within libraries that are loading external entities by
                     default.
                   * MySQL engine - Checks that if MySQL is being used that either InnoDB or
                     XtraDB are being used. Other engines are known to cause problems in
                     Totara.
                   * MSSQL required permissions - Ensures that during installation and upgrade
                     on MSSQL the database user has sufficient permissions to complete the
                     operation.
                   * MSSQL read committed snapshots - Ensures that the MSSQL setting "Read
                     committed snapshots" is turned on for the database Totara is using.

API changes:

    TL-20109       Added a default value for $activeusers3mth when calling core_admin_renderer::admin_notifications_page()

                   TL-18789 introduced an additional parameter to
                   core_admin_renderer::admin_notifications_page() which was not indicated and
                   will cause issues with themes that override this function (which
                   bootstrapbase did in Totara 9). This issue adds a default value for this
                   function and also fixes the PHP error when using themes derived off
                   bootstrap base in Totara 9.

Performance improvements:

    TL-19810       Removed unnecessary caching from the URL sanitisation in page redirection code

                   Prior to this fix several functions within Totara, including the redirect
                   function, were using either clean_text() or purify_html() to clean and
                   sanitise URL's that were going to be output. Both functions were designed
                   for larger bodies of text, and as such cached the result after cleaning in
                   order to improve performance. The uses of these functions were leading to
                   cache bloat, that on a large site could be have a noticeable impact upon
                   performance.

                   After this fix, places that were previously using clean_text() or
                   purify_html() to clean URL's now use purify_uri() instead. This function
                   does not cache the result, and is optimised specifically for its purpose.

    TL-20026       Removed an unused index on the 'element' column in the 'scorm_scoes_track' table
    TL-20053       Improved handling of the ignored report sources and ignored embedded reports in Report Builder

                   The Report Builder API has been changed to allow checking whether a report
                   should be ignored without initialising the report. This change is fully
                   backwards compatible, but to benefit from the performance improvement it
                   will require the updating of any custom report sources and embedded reports
                   that override is_ignored() method.

                   For more technical information, please refer to the Report Builder
                   upgrade.txt file.

Improvements:

    TL-19824       Added ability to unlock closed appraisal stages

                   It is now possible to let one or more users in a learner's appraisal move
                   back to an earlier stage, allowing them to make changes to answers on
                   stages that may have become locked. An 'Edit current stage' button has been
                   added to the list of assigned learners in the appraisal administration
                   interface. To see this button, users must be granted the new capability
                   'totara/appraisal:unlockstages' (given to site managers by default), and
                   must have permission to view the Assignments tab in appraisal
                   administration (requires 'totara/appraisal:manageappraisals' and
                   'totara/appraisal:viewassignedusers').

    TL-20132       Menu type dynamic audience rules now allow horizontal scrolling of long content when required

                   When options for a menu dynamic audience rule are sufficiently long enough,
                   the dialog containing them will scroll horizontally to display them.

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance.

    TL-20152       Fixed content width restrictions when selecting badge criteria

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance.

Bug fixes:

    TL-19494       The render_tabobject now respects the linkedwhenselected parameter in the learning plans tab
    TL-19838       SCORM AICC suspend data is now correctly stored

                   This was a regression introduced in Totara 10.0 and it affected all later
                   versions. Suspend data on the affected versions was not correctly recorded,
                   resulting in users returning to an in-progress attempt not being returned
                   to their last location within the activity. This has now been fixed and
                   suspend data is correctly stored and returned.

    TL-19895       Added notification message communicating the outcome when performing a seminar approval via task block

                   Previously, when a manager performed a seminar approval via the task block,
                   there was no feedback to the manager as to whether or not it had been
                   successful.

                   An example of where this could have been problematic was when a seminar
                   event required manager approval and the signup period had closed: the task
                   would be dismissed after the manager had completed the approval process,
                   but they would not be informed that approval had not in fact taken place
                   (due to the signup period being closed).

                   With this patch, a message will now be displayed to the user after
                   attempting to perform an approval, communicating whether the approval was
                   successful or not.

    TL-19916       MySQL Derived merge has been turned off for all versions 5.7.20 / 8.0.4 and lower

                   The derived merge optimisation for MySQL is now forcibly turned off when
                   connecting to MySQL, if the version of MySQL that is running is 5.7.20 /
                   8.0.4 or lower. This was done to work around a known bug  in MySQL which
                   could lead to the wrong results being returned for queries that were using
                   a LEFT join to eliminate rows, this issue was fixed in versions 5.7.21 /
                   8.0.4 of MySQL and above and can be found in their changelogs as issue #26627181:
                    * https://dev.mysql.com/doc/relnotes/mysql/5.7/en/news-5-7-21.html
                    * https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-4.html

                   In some cases this can affect performance, so we strongly recommend all
                   sites running MySQL 5.7.20 / 8.0.4 or lower upgrade both Totara, and their
                   version of MySQL.

    TL-19936       Fixed text display for yes/no options in multiple choice questions

                   Originally, when defining a yes/no multiple choice type question, the page
                   showed 'selected by default' and 'unselect' for each allowed option. This
                   text now only appears when a default option has been selected.

    TL-19938       Fixed database deadlocking issues in job assignments sync

                   Refactored how HR Import processes unchanged job assignment records. Prior
                   to this fix if processing a large number of job assignments through HR
                   Import, the action of removing unchanged records from the process queue
                   could lead to a deadlock situation in the database.

                   The code in question has now been refactored to avoid this deadlock
                   situation, and to greatly improve performance when running an import with
                   hundreds of thousands of job assignments.

    TL-19996       Updated and renamed the 'Progress' column in the 'Record of Learning: Courses' Report Builder report source

                   The 'Progress' column displays progress for a course within a Learning
                   Plan. As this column is related to Learning plans, the 'type' of the column
                   has been moved from 'course_completion' to 'plan' and renamed from
                   'Progress' to 'Course progress'.

                   Please note that if a Learning plan has multiple courses assigned to it,
                   multiple rows will be displayed for the Learning Plan within the 'Record of
                   Learning: Courses' report if there are any 'plan' type columns included.

    TL-19997       Added limit to individual assignment dialog in program assignments
    TL-20008       Allowed users with page editing permissions to add blocks on 'My goals' page

                   Previously the 'Turn editing on' button was not available on the 'My goals'
                   page, preventing users from adding blocks to the page. This has now been
                   fixed.

    TL-20018       Removed exception modal when version tracking script fails to contact community
    TL-20019       Fixed a bug that prevented cancelling a seminar booking when one of a learner's job assignments was deleted
    TL-20092       Fixed bug which prevented custom seminar asset deletion from the seminar assets report
    TL-20102       Fixed certificates not rendering text in RTL languages.
    TL-20113       Fixed the filtering of menu custom fields within report builder reports

                   This is a regression from TL-19739 which was introduced in 11.11.

    TL-20128       Fixed 'missing parameter' error in column sorting for the Seminar notification table
    TL-20141       Fixed 'Date started' and 'Date assigned' filters in the program completion report

                   Previously the 'Date assigned' filter was mis-labelled and filtered records
                   based on the 'Date started' column. This filter has now been renamed to
                   'Date started' to correctly reflect the column name. A new 'Date assigned'
                   filter has been added to filter based on the 'Date assigned' column.

    TL-20155       Ensured that site policy content format was only ever set once during upgrade

                   Prior to this fix if the site policy editor upgrade was run multiple times
                   it could lead to site policy text format being incorrectly force to plain
                   text. Multiple upgrades should not be possible, and this issue lead to the
                   discovery of TL-20156.

                   Anyone affected by this will need to edit and reformat their site policy.


Release 11.11 (24th January 2019):
==================================


Security issues:

    TL-19900       Applied fixes for Bootstrap XSS issues

                   Bootstrap recently included security fixes in their latest set of releases.
                   To avoid affecting functionality using the current versions of Bootstrap,
                   only the security fixes have been applied rather than upgrading the version
                   of Bootstrap used.

                   It is expected that there was no exploit that could be carried out in
                   Totara due to this vulnerability, as the necessary user input does not go
                   into the affected attributes when using Bootstrap components. However we
                   have applied these fixes to minimise the risk of becoming vulnerable in the
                   future.

                   The Bootstrap library is used by the Roots theme.

    TL-19965       Corrected the encoding applied to long text feedback answers

                   Answers to long text questions for the feedback module may not have been
                   correctly encoded in some previous versions of Totara. The correct encoding
                   is applied where necessary on upgrade and is now also applied when a user
                   submits their answer.

Improvements:

    TL-18759       Improved the display of user's enrolment status

                   Added clarification to the Status field on the course enrolments page. If
                   editing a user's enrolment while the corresponding enrolment module is
                   disabled, the status will now be displayed as 'Effectively suspended'.

    TL-19666       Extended functionality for the 'Allow user's conflict' option on seminar event attendees

                   Prior to this patch, the 'Allow user's conflict' option was only applied on
                   the seminar event roles to bypass the conflict check. However it was not
                   applied to the attendees of the seminar event. With this patch the
                   functionality is now applied for attendees as well.

    TL-19721       Made help text for uploading seminar attendees from file more intuitive

                   The help text displayed when adding users to a seminar event via file
                   upload was worded in a way that made it difficult to understand. There was
                   also a formatting issue causing additional fields in the bulleted list to
                   be indented too far.

                   The string 'scvtextfile_help' was deprecated, and replaced by a new string,
                   'csvtextfile_help', to make it clear that only one of the three possible
                   user-identifying fields (username, idnumber, or email) should be used and
                   that all columns must be present in the file.

                   Additionally, the code that renders the upload form was modified so that
                   all listed fields have the same list indent level.

    TL-19823       Updated appraisal summaries to show the actual user who completed a stage

                   The actual user who completes an appraisal stage is now recorded and shown
                   when viewing the appraisal summary. This shows when a user was 'logged in
                   as' another user and completed the stage on their behalf. This also
                   continues to show the original user who participated in the appraisal, even
                   after a job assignment change results in a change to which users fulfill
                   those participant roles at the time the appraisal summary is viewed.

    TL-19825       Added 'login as' real name column to the logstore report source
    TL-19848       Upgraded PHPUnit to version 7.5

                   This patch upgrades the PHPUnit version to 7.5. Two major versions lie in
                   between the last version and this upgrade.

                   The following backwards compatibility issues have to be addressed in custom
                   code:
                   1) All PHPUnit classes are now namespaced, i.e. 'PHPUnit_Framework_TestCase' is now 'PHPUnit\Framework\TestCase'
                   2) The following previously deprecated methods got removed:
                      * getMock(),
                      * getMockWithoutInvokingTheOriginalConstructor(),
                      * setExpectedException(),
                      * setExpectedExceptionRegExp(),
                      * hasPerformedExpectationsOnOutput()
                   3) The risky check for useless tests is now active by default.

                   The phpunit.xml configuration 'beStrictAboutTestsThatDoNotTestAnything' was
                   set to 'false' to keep the previous behaviour to not show risky tests by
                   default.

                   To make the transition easier all methods removed in PHPUnit were added in
                   the base_testcase class and the functionality is proxied to new methods of
                   PHPUnit. These methods now trigger a debugging message to help developers
                   to migrate their tests to the new methods.

                   Old class names were added to renamedclasses.php to support migration to
                   new namespaced classes.

                   More information about the upgrade to 7.5:
                    * [https://phpunit.de/announcements/phpunit-6.html]
                    * [https://phpunit.de/announcements/phpunit-7.html]

    TL-19852       Fixed the wording of the 'Try another question like this one' button in the quiz module

                   The "Try another question like this one" button has been renamed into "Redo
                   question". Help text for the "Allow redo within an attempt" quiz setting
                   has been updated to clarify its behaviour.

    TL-19896       The maximum width of Select HTML elements within a Totara dialogue is now limited by the size of the dialogue
    TL-19904       Added appraisal page and stage completion events for logging
    TL-19909       Removed limit on the number of options available when creating a dynamic audience rule based on a User profile field

                   When creating a dynamic audience rule by choosing one or more values of a
                   text input User profile field, there was a limit of 2500 options to choose
                   from.

                   This was an arbitrary limit, and has been removed.

                   Note that very large numbers of options (more than ~50,000) may have an
                   effect on browser performance during the selection process. Selecting a
                   large number of options (more than ~10,000 selections) may cause the
                   receiving script to run out of memory.

Bug fixes:

    TL-18732       Changed enrolment message sending for programs to be more consistent

                   If a program (or certification) is created with required course sets (all
                   optional) the program is marked as complete straight away for any assigned
                   users. Previously the enrolment message would not be sent to users in this
                   case. We now send the enrolment message to users even if the program is
                   complete.

    TL-19471       Fixed unavailable programs not showing in user's Record of Learning items when the user had started the program
    TL-19691       Expired images now have the expired badge stamped on top

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance.

    TL-19728       Fixed the sending of duplicate emails on appraisal activation
    TL-19739       Fixed select filters when page switching on embedded reports with default values set

                   Previously if an embedded report had defined a default value for a select
                   filter, then changing that filter to 'is any value'  and hitting search
                   would correctly show all results, however if the report has multiple pages
                   then switching to any other page in the report would revert back to the
                   default value. The filter change in now maintained across pages.

    TL-19782       Fixed javascript regression in audience's 'visible learning'

                   Prior to this patch: the AJAX request was being sent twice to the server
                   when deleting a course from an audience's 'visible learning'. It caused the
                   second request to be regarded as an invalid request, because the first one
                   had already been processed and the record successfully deleted.

                   After this patch: the event will be triggered once in audience's 'visible
                   learning', and it will send only one AJAX request.

    TL-19791       Fixed an issue with audiences in course access restrictions

                   Previously the audience restrictions did not work when searching for
                   audience names which contained non-alphanumeric characters.

    TL-19797       Fixed minimum bookings notification being sent for cancelled events
    TL-19804       Fixed an issue where overridden grades were not reset during completion archiving
    TL-19811       Fixed a seminar's custom room not appearing in search results from a different seminar

                   Prior to this patch: A custom room (unpublished room) that had been used in
                   a seminar's event would appear in a search result from a query of a
                   different seminar.

                   With this patch: The custom room (unpublished room) will not appear in the
                   search result of a different seminar.

    TL-19819       Fixed incorrectly populated Seminar custom notification options

                   Data values were storing correctly to database, but were not being used
                   correctly to set the form values when the edit form was reloaded.

    TL-19828       Fixed sanity check for external mssql database that checks that the specified table exists
    TL-19839       Fixed behaviour of null customfield values being imported from database sources in HR Import

                   Null values being imported for custom fields were incorrectly erasing data
                   in Totara. The correct behaviour of ignoring null value and keeping the
                   existing value is now used.

    TL-19841       Fixed PHPUnit test failure in mod_facetoface_lib_testcase::test_sync_assets
    TL-19856       Fixed missing data attributes bug affecting search functionality for seminar rooms and assets
    TL-19864       Prevented bind password from being auto filled by Chrome when configuring LDAP authenticaion

                   The bind password field was being automatically filled when editing the
                   LDAP authentication settings. This lead to a chance that the bind password
                   was set to an incorrect/invalid value without the user being aware it had
                   happened. This only affected users of Chrome.

    TL-19865       Fixed sort order for question scale values in user data export for review questions
    TL-19866       Fixed date assigned shown on the program detail page

                   When a user is assigned to a program that they would have completed in the
                   past due to the courses in that program being complete, the date they were
                   assigned to the program was incorrectly displayed. Previously this date was
                   the date they completed the program (in the past). This now displays as the
                   actual date they were assigned, which is consistent with the 'Date
                   assigned' column in the Program record of learning report.

    TL-19873       Fixed PHP error in the report with a 'course (multi line)' filter in the saved search where selected course has been deleted
    TL-19877       Fixed bug where multi-framework rules were flagged as deleted in Audiences dynamic rules
    TL-19894       Added batch processing of users when being assigned to a Program
    TL-19903       Fixed removing value of hierarchy multi select custom fields using HR Import

                   When syncing Positions or Organisations and changing the value of a
                   multi-select custom field, if a field was left blank then it would
                   incorrectly be ignored instead of removing the value (adhering to the empty
                   field behaviour setting). Empty fields for this type of custom field now
                   remove the existing value as expected.

    TL-19908       Fixed a debug notice being generated when adding deferred Program assignments
    TL-19922       Enabled Rooms/Assets 'Manage searches' buttons

                   When managing rooms or assets, it is possible to save a search for
                   rooms/assets by name and/or availability, and to share those searches with
                   other managers. In order to edit or delete saved searches, the manager
                   clicks on a "Manage searches" button.

                   Prior to this patch, clicking the button did nothing. The button now works
                   correctly, opening the Manage searches dialogue.

    TL-19923       Fixed due date format in "Competency update" emails

                   When a manager changes the due date of a competency in a learner's Learning
                   plan, the email sent to the learner now contains the correct dates.

    TL-19947       Increased the limit on number of choices available in autocomplete menu when restricting an activity by audience
    TL-19953       Fixed missing icon for appraisal previews

                   This was supposed to be fixed in TL-19780 but it still failed in IE11
                   because of the way IE behaves with missing icons compared to other
                   browsers.

                   This has now been fixed so that IE also displays the preview correctly.

    TL-20007       Fixed an error with audience rules relying on a removed user-defined field value

                   This affected the 'choose' type of audience rules on text input user custom
                   fields. If a user-defined input value was used in the rule definition, and
                   that value was then subsequently removed as a field input, a fatal error
                   was thrown when viewing the audience. This is now handled gracefully,
                   rather than displaying an object being used as an array error the missing
                   value can now be removed from the rule.


Release 11.10 (19th December 2018):
===================================


Security issues:

    TL-19593       Improved handling of seminar attendee export fields

                   Validation was improved for fields that are set by a site admin to be
                   included when exporting seminar attendance, making user information that
                   can be exported consistent with other parts of the application.

                   Permissions checks are now also made to ensure that the user exporting has
                   permission to access the information of each user in the report.

Improvements:

    TL-19442       Enable course completion via RPL in Programs when the course is not visible to the learner

                   Previously when a course was not visible to the learner it could not be
                   marked as complete in the required learning UI. Now users with permission
                   to mark courses as complete can grant RPL even if the course is not
                   available to the learner.

Bug fixes:

    TL-18858       Fixed mismatching date format patterns in the Excel writer

                   Previously when exporting report builder reports to Excel, any dates that
                   were not otherwise explicitly formatted would be displayed in the mm/dd/yy
                   format, regardless of the user's locale. These dates are now formatted to a
                   default state so that they are displayed as per the user's operating system
                   locale when opening the Excel file.

    TL-18892       Fixed problem with redisplayed goal question in appraisals

                   Formerly, a redisplayed goal question would display the goal status as a
                   drop-down list - whether or not the user had rights to change/answer the
                   question. However, when the goal was changed, it was ignored. This patch
                   changes the drop-down into a text string when necessary so that it cannot
                   be changed.

    TL-19303       Removed "User status already changed to cancelled" unnecessary debug message.
    TL-19373       Added two new seminar date columns which support export

                   The new columns are "Local Session Start Date/Time" and "Local Session
                   Finish Date/Time" and they support exporting to Excel and Open Document
                   formats.

    TL-19481       Fixed the course restoration process for seminar event multi-select customfields

                   Previously during course restoration, the seminar event multi-select
                   customfield was losing the value(s) if there was more than one value
                   selected.

    TL-19485       Made tables scrollable when on iOS

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance.

    TL-19507       Expand and collapse icons in the current learning block are now displayed correctly in IE11

                   Previously when someone using IE11 was viewing the current learning block
                   with a program inside it, the expand and collapse icons were not displayed
                   if there was more than one course in the program.

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance

    TL-19579       Enabled multi-language support on the maintenance mode message
    TL-19615       Fixed a permission error when a user tried to edit a seminar calendar event
    TL-19679       Removed remaining references to cohorts changing to audiences
    TL-19690       Fixed bug on Seminar Cancellations tab that caused Time Signed Up to be 1 January 1970 for some users

                   When a Seminar event that required manager approval was cancelled,
                   attendees awaiting approval would show 1 January 1970 in the Time Signed Up
                   column of the Attendees View Cancellations tab.

                   The Time Signed Up for attendees awaiting approval when the event was
                   cancelled is now the date and time that attendance was requested.

    TL-19692       Fixed a naming error for an undefined user profile datatype in the observer class unit tests
    TL-19693       Role names now wrap when assigning them to a user inside a course

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance.

    TL-19694       Fixed a capability notification for the launch of SCORM content

                   This fixed a small regression from TL-19014 where a notification about
                   requiring the 'mod/scorm:launch' capability was being displayed when it
                   should not have been.

    TL-19696       Fixed the handling of calendar events when editing the calendar display settings of a seminar with multiple sessions

                   Previously with Seminar *Calendar display settings = None* and if the
                   seminar with multiple events was updated, the user calendar seminar dates
                   were hidden and the user couldn't see the seminar event in the calendar.

    TL-19698       Fixed appraisal preview regression from TL-16015

                   TL-16015 caused a regression in which previewing the questions in an
                   appraisal displayed the text "Not yet answered". This patch fixes this and
                   now the actual UI control appears; e.g. for a file question, it is a file
                   picker, and for a date question, it is a date entry field.

                   Note that although values can be "entered" into the UI controls, nothing is
                   saved when closing the preview window.

    TL-19726       Fixed the string identifier that has been declared incorrectly for facetoface's notification scheduling
    TL-19733       Fixed export of the "Previous completions" column in the Record of Learning report
    TL-19760       Fixed multi-language support for custom headings in Report Builder
    TL-19778       Fixed an error in seminar report filters when generating SQL for a search

                   Prior to this patch: the columns relating to the filters could not be added
                   because these columns were in the wrong place, they would only be added if
                   the GLOBAL setting (facetoface_hidecost) of the seminar was set to FALSE.
                   Therefore it was causing the sql error due to the columns and sql joins not
                   being found.

                   With this patch: the columns are now put in the correct place, and these
                   columns will no longer be affected by the GLOBAL setting
                   facetoface_hidecost.

    TL-19779       Fixed an error when signing up to a seminar event that requires approval with no job assignment and temporary managers disabled
    TL-19790       Fixed coursename_list Report Builder display to use correct id and name values

Contributions:

    * Ghada El-Zoghbi at Catalyst AU - TL-19692
    * Learning Pool - TL-19779


Release 11.9 (4th December 2018):
=================================


Security issues:

    TL-19669       Backported MDL-64222 security fix for badges
    TL-19365       CSRF protection was added to the login page, and HTML blocks on user pages now prevent self-XSS

                   Cross-site request forgery is now prevented on the login page. This means
                   that alternate login pages cannot be supported anymore and as such this
                   feature was deprecated. The change may also interfere with incorrectly
                   designed custom authentication plugins.

                   Previously configured alternate login pages would not work after upgrade;
                   if attempting to log in on the alternate page, users would be directed to
                   the regular login page and presented with an error message asking them to
                   retry log in, where it will be successful. To keep using vulnerable
                   alternate login pages, the administrator would need to disable CSRF
                   protection on the login page in config.php.

    TL-19028       SCORM package download protection is now on by default

                   Previously this setting was off by default.
                   Turning it on ensures that sites are more secure by default.

New features:

    TL-18859       Add Totara content marketplace and GO1 marketplace

                   Totara content marketplace provides support for browsing and importing
                   external content from content providers directly into your site.

                   Content providers can implement a new "marketplace" plugin type to
                   integrate their content into Totara Learn. The release includes a
                   marketplace plugin for GO1 ([https://totara.go1.com/]), which provides
                   direct access to search and include GO1 aggregated content.

                   When first installed the content marketplace plugin will send an internal
                   notification to site administrators and site managers on the next cron run,
                   letting them know that content marketplaces are available. To prevent this
                   notification and completely disable marketplaces add
                   $CFG->enablecontentmarketplaces = false; in your site's
                   config.php *before* you upgrade your site.

Improvements:

    TL-19145       Improved terminology for non-graded assignment strings
    TL-18963       Improved the help text for the 'Enable messaging system' setting on the advanced settings page

Bug fixes:

    TL-19623       Fixed layout on Assignment's Grade to not collapse each other.
    TL-19599       Fixed deletion of filters and columns in the "All User's Job Assignments" section
    TL-19598       Fixed SQL error when updating dynamic audience which includes Job Assignments Manager rule

                   When a dynamic audience which included the job assignments Managers rule
                   was updated an SQL error would be generated if any of the selected Managers
                   had multiple job assignments. This would lead to the dynamic audience
                   members not being updated when the scheduled task was run.

    TL-19512       In-page confirmation boxes no longer display above menu's

                   When deleting a block from a page, the confirmation box previously
                   displayed on top the menus. The menu now displays on top of the
                   confirmation box.

                   This will require themes using less inheritance to re-compile their CSS.

    TL-19508       Removed duplicated options in the 'Show with backdrop' selector on the add new step form in user tours

                   Within a user tour, a moodle form can get 2 selected "default" items. This
                   causes the last item ("No") in chrome to be selected (whereas the first
                   option should be selected).

                   Replication steps (done on Chrome):
                    # Ensure user tours are enabled
                    # Set up a tour with "show backdrop" set to "Yes"
                    # Go to the add step screen
                    # Expand the "Options" step
                    # Inspect element on the "Show with backdrop" select

                   Currently there are 2 options with the selected attribute set - there
                   should only be one.

    TL-19495       Ensured the course shortname and category fields export correctly on the 'Program overview' Report Builder source
    TL-19472       Fixed temporary manager expiry checkbox not being unchecked when temporary manager removed
    TL-19439       Fixed select all checkbox not working in comments report in IE11/Edge
    TL-19374       Removed a trailing space on the output of the certif_status Report Builder display
    TL-19297       Fixed errors when changing course format to different format on course's editing page
    TL-19256       Ensured enrolment messages are send correctly after user assignment exceptions have been resolved
    TL-19250       Fixed Totara forms file manager element with disabled subdirectories bug when uploading one file only
    TL-19249       Fixed cancel button not working in switch role form in course

                   Previously the cancel button had the same functionality as the 'Save
                   changes' button, changing the users role.

                   With this patch, the cancel button now just redirects back to the course
                   view page.

    TL-19248       Report builder filters supply the report id when changing

                   Previously there were some filters that did not supply the report id when
                   changing the filter. This issue ensures the access checks are done
                   correctly for the report

    TL-19247       Fixed race condition when adding programs to the program completion block
    TL-19215       Improved handling of text in autocomplete forms

                   Previously when adding HTML tags to an autocomplete field, they would be
                   interpreted by the browser. This issue ensures that they are displayed as
                   plain text, with offending content being removed when the form being
                   reloaded.

                   This is not a security fix as the only person who could be affected is the
                   person who is entering the data, when they are first entering the data (and
                   not on subsequent visits).

    TL-19196       Backported TL-15368 to fix user tours initialisation on the front page
    TL-19190       Fixed duplicate rows in the Program Completion report when "Is user assigned?" column is included
    TL-19160       Clarified date filter label that 'today' means 'start of today'
    TL-19158       Fixed 'Hide/Show' actions on the course/program custom fields page
    TL-19155       Fixed Google maps Ok button failure in Behat tests
    TL-19149       Made sure completion editor form is submitted correctly when the site is running non-English language
    TL-19124       Internal implementation and performance of organisation and position based report restrictions

                   This is a backport of TL-19086, which was included in October evergreen
                   release.

    TL-19122       Fixed an issue in the recurring courses where after the course restarts the enrolment date remained the date from the original course
    TL-19000       Changed Seminar event approver notification type from alert to task so that dashboard task block is created
    TL-18932       Added an ability to detect the broken audience rules when scheduled task starts running to update the audience's members

                   Prior to this patch, when the scheduled task
                   (\totara_cohort\task\update_cohort_task) was running, there was no way that
                   it could detect whether the rules were still referencing to the invalid
                   instance records or not (for example: course, program, user's position, and
                   so on). Therefore, if the rule had a reference to an invalid instance
                   record, audience will not be able update its members correctly.

                   With this patch, it will start checking whether the referenced instance
                   records are valid or not before the process of updating members. If there
                   are any invalid instance records, then the system will send an email out to
                   notify the site administrator.

    TL-18895       Added warning text to the audience's rules if there are any rules that are referencing a deleted item

                   Prior to the patch: when an item (for example: program, course, position
                   and so on) that was referenced in an audience rule got deleted, there were
                   no obvious way to tell the user that this item had been deleted.

                   With this patch: there will be a warning text, when user is viewing the
                   rule that is still referencing a deleted item.

    TL-18821       Fixed the rendering of course's topic restriction when using the 'Restriction Set'
    TL-18806       Prevented prog_write_completion from being used with certification data
    TL-18558       Fixed display activity restrictions for editing teachers.

                   Editing teachers can see activity restrictions whether they match them or
                   not.

    TL-17804       Fixed certification expiry date not being updated when a user is granted an extension

                   Additional changes include:
                    * new baseline expiry field in the completion editor which is used to calculate subsequent expiry dates
                    * preventing users from requesting extension after the certification expiry

    TL-16788       Fixed audience visible learning report's javascript

                   Prior to this patch, with a report using source 'Audience: visible
                   learning', when changing the visibility of an audience, the system would
                   update nothing. This happened because the javascript for the report was
                   looking into the wrong elements and it would not trigger any update to the
                   server side when event triggered.

                   With this patch, given the same scenario, audience visibility of
                   course/program will be updated.

    TL-16529       Fixed Global Search to accept the parameter type of either 'string' or 'array'

                   Prior to this patch: when user was trying to perform global search, the
                   system would throw an error. It happened because the query from request was
                   a string instead of an array and the global search handler was expecting
                   array data type only.

                   After this patch: the issue has been resolved, global search handler is now
                   accepting either 'string' or 'array' parameter.


Release 11.8 (25th October 2018):
=================================


Security issues:

    TL-18957       Fixed permission checks for learning plans

                   Prior to this patch all plan templates were being checked to see if a user
                   had a permission (e.g. update plan). Now only the template that the plan is
                   based off is checked for the permission.

New features:

    TL-19014       Implemented new capabilities for controlling the access to SCORM content

                   Previously all users who could enter a course were able to launch SCORM
                   activities.
                   The only way to limit access was to make the activity hidden and then to
                   use the moodle/course:viewhiddenactivities capability to grant access.

                   Two new capabilities have been added to allow better control of access to
                   SCORM activities.
                    * mod/scorm:view
                    * mod/scorm:launch

Improvements:

    TL-17586       Greatly improved the performance of the update competencies scheduled task

                   The scheduled task to reaggregate the competencies
                   "\totara_hierarchy\task\update_competencies_task" was refactored to fix a
                   memory leak. The scheduled task now loops through the users and loads and
                   reaggregates items per user and not in one huge query as before. This
                   minimises impact on memory but increases number of queries and runtime.

    TL-18565       Improved the wording around the 'Override user conflicts' settings page in seminars

                   The 'Override user scheduling conflicts' setting was initially intended for
                   use with new events where the assigned roles resulted in conflicts with
                   existing events. It was not originally designed to work with existing
                   events.
                   We changed the event configuration flow by moving the 'override' action out
                   of the settings page and into the 'save' modal dialog where it belongs.
                   So in essence you will be able override conflicts upon creation and edit.

    TL-18757       Send notifications to new appraisees for an already activated appraisal

                   Previously the appraisals module only sent out notifications to learners
                   when the appraisal was activated. If new learners are added to the
                   appraisal after activation, they did not receive any notification.

                   With this patch, notifications are sent out when new learners are added to
                   the appraisal after activation.
                   If you need the original behaviour (ie no notification for new appraisees),
                   add this line to config.php:
                   $CFG->legacy_appraisal_activation_message_behavior = true;

    TL-18770       Disabled the site policy translation interface language selector when only a single language is available
    TL-18852       Database table prefix is now required for all new installations

                   Previously MySQL did not require database prefix to be set in config.php,
                   since MySQL 8.0 the prefix is however required. To prevent problems in
                   future upgrades Totara now requires table prefix for all databases.

    TL-18909       Fixed compatibility issues with PHP 7.3RC1
    TL-18983       Added workaround for missing support for PDF embedding on iOS devices

                   Web browsers on iOS devices have very limited support for embedding PDF
                   files – for example, only the first page is displayed and users cannot
                   scroll to next page. A new workaround was added to PDF embedding in File
                   resource to allow iPhone and iPad users to open a PDF in full-screen mode
                   after clicking on an embedded PDF.

    TL-18998       Improved performance of language pack installation by changing to gzip

                   Language pack installation and updates now utilise gzip instead of zip.
                   Extract of gzip files is much quicker than zip files within Totara.
                   Manual installation and updates using zip files are still supported and
                   will continue to operate.
                   All online installations and updates will now use tgz files exclusively.

    TL-19084       Enrolment type column in course completion report source is now using subqueries to improve compatibility of other general columns in the same report

Bug fixes:

    TL-14204       Updated the inline helper text for course completion tracking

                   Prior to this patch, there was a misleading inline helper text on the
                   course view page next to 'Your progress'.
                   With this patch, the inline helper text is updated to reflect with the
                   change of the completion icon.

    TL-16539       Fixed capacity reporting when viewing Seminar event information on the course page

                   Previously a wait-list seminar event with 1 booked user and 1 wait-listed
                   user reported the capacity wrongly as '2 wait-listed'.
                   With this patch, the capacity is now reported correctly.

    TL-17584       Fixed the default heading location for Featured links block gallery tiles

                   Heading location for Gallery tiles now defaults to 'Top' like the default
                   tile. Any tiles created without setting the heading location will be set to
                   'Top'.

    TL-17629       Fixed failures in the Seminar send_notification_task when performed under high load

                   Some sites with large number of Seminar activities (100 000+) experienced
                   'out of memory' failures during execution of the scheduled task
                   (send_notifications_task). This task has now been optimised to use less
                   memory.

    TL-17658       MSSQL 2016 and below now correctly sort aggregated course columns in the program overview report

                   The program overview report was using SQL group_concat to ensure
                   concatenated columns such as course name, and course status were ordered
                   correctly and consistently.
                   However the group_concat functionality in MSSQL 2016 and below does not
                   support sorting, and there is no alternative.
                   The fix for this was to shift sorting from the database to Totara if the
                   site is running on MSSQL 2016 or below.
                   This will have a small impact on performance, but will ensure for those
                   sites that the columns are correctly and consistently sorted.
                   Our recommendation is to upgrade MSSQL 2017 is possible.

    TL-17773       Fixed the rendering of visibility controls within the course management interface for hidden categories

                   The issue happened within 'Manage courses and categories' page alongside
                   the enabled setting 'Audience visibility'.

                   When rendering the page, the course checks for the setting 'Audience
                   visible' (global) before 'Visible' (module) setting, to determine whether
                   the 'Eye Icon' should be marked as hidden or not.

                   Previously, when the 'Course category' was marked as hidden, all the
                   courses within that category were also marked as hidden. However, after
                   reloading the page, these courses were not marked as hidden. This was due
                   to the fact that the same behaviour of rendering the page was not applied
                   to AJAX interface.

                   With this the patch, the behaviour applied when rendering the page is now
                   also applied to the AJAX interface and therefore results in the same
                   behaviour.

    TL-18776       Fixed a bug causing the Atto editor to lose track of the user's selection in IE11 and Edge

                   Prior this change if heavily editing content in the Atto editor will
                   occasionally result in the wrong content being formatted.
                   This occurred only when formatting selected text, and occurred because the
                   browser would lose track of the user's selection.
                   This only affected IE11 and Edge.

    TL-18790       Fixed the Organisation content restriction within the 'Record of Learning: Certifications' report source

                   Before: within a report using 'Record of Learning: Certifications' source
                   and content restriction as 'Staff at or below any of the user's assigned
                   organisations', the User's Organisation(s) filter had an issue with its SQL
                   query.

                   After the patch: this issue is now fixed, the Organisations will display,
                   if there are any.

    TL-18802       Changed the date format of Session Date related columns within Seminar Sign-ups report source

                   Previously the report columns 'Session Start' and 'Session Finish' were
                   formatted differently than the 'Session Start (linked to activity)' column.
                   These columns are now formatted consistently.

    TL-18839       The 'Blocks editing on' button has been put back onto the 'Browse list of users' report page

                   Prior to this page being converted to an embedded report it had a button to
                   turn editing on.
                   That button was unintentionally removed during the conversion.
                   It has now been put back.

    TL-18846       User's preference 'email bounce count' is reset when user requests to change their email address

                   Prior to this patch, when the user requested to change their email address
                   and the user's email bounce count preference reached the threshold, the
                   confirmation email could not be sent to the user.

                   With this patch, given the same scenario, the email will be sent to the
                   user for the confirmation of change request.

    TL-18864       Fixed the population of the template field value when editing a Seminar's notification

                   Prior to this patch, when adding a new notification using a custom
                   notification template the user was not able to see the Template field
                   populated when editing it. Now, the field Template will be populated with
                   the right value used for the notification.

    TL-18866       Fixed the way the add-on list is displayed on the Totara registration page

                   Prior to this patch, on the Totara registration page, all the add-on
                   components were rendered without spaces separating them, preventing the
                   text from wrapping and forcing the need for horizontal scrolling in the
                   browser.

                   After the patch, there is a word wrap in place to make the text fit on the
                   screen.

    TL-18867       Fixed exported status of cancelled events in the Seminar attendance report
    TL-18869       Fixed error message display after LDAP authentication plugin settings form is submitted
    TL-18880       Fixed Seminar 'Job assignment on sign up' column to exclude html text when exporting to other format
    TL-18887       Fixed resetting of course type when uploading courses using a CSV file that does not contain the column

                   This is a regression from TL-17920 which added Course type as a supported
                   column when uploading courses via CSV.
                   Totara 10.12, 10.13, 11.6, and 11.7 are affected.

    TL-18897       Added a link on Appraisal stage interfaces for navigation back to the Appraisal
    TL-18908       Fixed window resize functionality when viewing the grader report
    TL-18922       Fixed the overlapping text within Select Assets dialog box
    TL-18941       Changed z-index of Totara dialogs to match Bootstrap 3 modal levels

                   Previously the modal had a z-index of 1 (and the backdrop 0) which caused
                   some content to be displayed above them. This sets the level to 1050 (with
                   the backdrop at 1040).

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance.

    TL-18942       Fixed the email subject line format for emails sent from the Certificate module

                   Prior to this change the subject lines used for emails sent by the
                   Certificate module were being formatted for HTML, not for emails.
                   This led to characters being escaped and converted to entities.
                   As of this fix the subject is formatted correctly, and should no longer end
                   up with HTML entities within it.

    TL-18959       Fixed the double escaping of Report names when viewing the list of my reports

                   The report name was being passed once through format_string() and once
                   through s() when displaying the list of reports to the user on the My
                   reports page.
                   The fix for this change involved modifying templates, see
                   totara/core/upgrade.txt for technical details.

    TL-18965       Changed the embedded URL of User's name within Seminar's event Cancellation
    TL-18981       Fixed aggregation of a learner's course progress after use of completion editor

                   When the completion editor is used to mark a course complete for a specific
                   learner, the progress bar in the Record of learning is now updated to
                   indicate that the learner has completed the course.

    TL-19009       Fixed incorrectly deprecated filter language strings on "Bulk user actions" page

                   Strings datelabelisafter, datelabelisbefore, datelabelisbetween were
                   deprecated in Totara 10.0 while still being used on the "Bulk user
                   actions" page. This has now been corrected and these strings were removed
                   from the deprecated list.

    TL-19010       Removed incorrectly deprecated function message_page_type_list() from the deprecatedlib.php
    TL-19017       Added styles to display Appraisal content correctly on mobile devices

                   Added styles for correctly displaying an individual Appraisal selected from
                   the 'My appraisals' page on mobile devices. Previously content had layout
                   issues and was incorrectly cropped.

    TL-19018       Fixed problems with forced redirect for new required custom profile fields
    TL-19030       Fixed the duplicate submit request for the course page when enroling

                   Double clicking the Enrol button on courses with self enrolment enabled no
                   longer submits duplicate requests.

    TL-19046       Course completion's cache is now being cleared after user deletes the course completion

                   Prior to this patch, when a manager with the
                   'totara/program:markstaffcoursecomplete' capability, marked a course as a
                   incomplete on a user's 'Record of Learning > Programs' page, the cache
                   would not be fully cleared. Causing the page to still render with the
                   course marked as complete.
                   With this patch, given the same scenario, the course completion cache of a
                   user will be reset when the manager removes the course completion for that
                   course. This will result in the completion being rendered correctly.

    TL-19072       Fixed wait-listed attendees not being automatically added to the Seminar's attendees list after a reservation is deleted

API changes:

    TL-18845       Removed a superfluous unique index on the job_assignment.id column
    TL-18927       Totara form load deferred object now resolves after the form initialise JavaScript is called

                   Previously, the Totara form initialise code was run after the load deferred
                   object had been resolved. This meant that calls to getFormInstance(formid)
                   would return null on load.done(), and not the form that was requested.

    TL-18985       Unit tests may now override lang strings


Release 11.7 (19th September 2018):
===================================


Important:

    TL-14270       Added additional information about plugins usage to registration system
    TL-18788       Added data about installed language packs into registration system
    TL-18789       Added data about number of active users in last 3 months to registration system

Improvements:

    TL-11243       Removed ambiguity from the confirmation messages for Seminar booking requests
    TL-17130       Added consent statement filter for the Site policies report

                   This patch adds support for a consent statement filter for the Site
                   policies report as well as a few minor improvements to the site policy
                   filters including:
                    * Removing the filter Current Version (Primary Policy)
                    * Replacing plain text version filter to a smart dropdown menu, which
                   includes now the list of available versions as well as the option to select
                   current version of the policy
                    * Adding policy filter which allows you to filter only by policy
                    * Making user consent statement a simple filter
                    * Added custom help for consent statement filter
                    * Added custom help for policy version filter

                   Now to select the current version of the policy it is a matter of using 2
                   filters:
                    * Policy filter to select appropriate policy
                    * Version filter to select current version

                   Please note, that this patch will also remove Current Version (Primary
                   Policy) filter from any saved search using it.

    TL-18596       Added a filter for the Number of Job Assignments for a user

                   A filter has been added for the Number of Job Assignments column and is
                   available in all report sources that include the Job Assignments filters.
                   This filter adds a way to filter users that have no Job Assignments.

    TL-18639       Added support for custom help tooltips for Report Builder filters

                   When a report source is defined it is now possible to define a custom
                   filter option to override the default help tooltip for the given filter.

    TL-18700       Backported MDL-54901 to add an environment check for https

                   If the site is not running on https the environment check now shows a
                   warning that it is not enabled. Installing the site is still possible
                   without https.

    TL-18777       Allowed plugins to have custom plugininfo class instead of just type class
    TL-18793       Improved display of course details in the course and categories management page
    TL-18812       Changed the display of the Course default 'Course Visibility' setting to 'Show' and 'Hide' to be consistent with the course setting

Bug fixes:

    TL-16532       Fixed caching of OpenSesame reports
    TL-18494       Fixed 'Bulk add attendees' results in Seminar to show ID Number instead of internal user ID
    TL-18549       Fixed 'Remove users' option showing in attendee actions for users without the removeattendees capability
    TL-18571       Fixed access rights bug when viewing goal questions in completed appraisals

                   If an appraisal has a goal question and the appraisal was completed, then
                   it is the current learner's manager who can see the goal question. However,
                   there was an issue when a learner and their manager completed the appraisal
                   but then a new manager was assigned to the learner. In this case, only the
                   old manager could see the completed appraisal but they could not see the
                   goal question because they didn't have the correct access rights. The new
                   manager could not see the completed appraisal at all.

                   This applies to static appraisals.

    TL-18578       Fixed missing required parameter when viewing 'Course membership' embedded report
    TL-18588       Prevented duplicate results when searching in Seminar dialogs

                   Seminar dialogs that provide search functionality (such as the rooms and
                   assets selectors) now ensure that search results are unique.

    TL-18602       Fixed Seminar's event decline emails to not include iCalendar attachments

                   When a booking approval request with a setting of email confirmation set as
                   'Email with iCalendar appointment' gets declined, then the iCalendar
                   attachment will not be included in the email sent back to the user who made
                   the request.

    TL-18680       Fixed the resetting of event data for each recipient of email notifications for under-capacity seminars
    TL-18682       Fixed the course name not appearing below the event time in calendar

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance.

    TL-18685       Fixed the Seminar summary report visibility records when Audience-based visibility is enabled

                   When a course had audience-based visibility enabled and the course
                   visibility was set to anything other than 'All users', the seminar sessions
                   report was still displaying the course to users even when they didn't match
                   the visibility criteria. This has been corrected.

    TL-18691       Fixed course's visibility icon within course management search to reflect the course visibility settings
    TL-18707       Fixed HR Import sanity check for an Organisation or Position parent

                   If the organisation or position parent id number was set to zero, the
                   sanity check to determine if the parent exists was being skipped. Zero is a
                   valid idnumber and is now used in the sanity check.

    TL-18710       MDL-62232 / MDL-62233: Added some extra validation for portfolio

                   These changes are small extra validation added by Moodle to previous Totara
                   patches in the portfolio.

    TL-18737       Fixed issue with help icons not having an alt text associated with them
    TL-18738       Replaced hardcoded strings in environment checks with properly translated strings
    TL-18740       Updated program observer sql for course_in_progress() function to ensure first column is always unique
    TL-18742       Fixed failing unit tests in totara_job_dialog_assign_manager_testcase
    TL-18743       Fixed date conflicts validation error showing repeatedly in the event form
    TL-18758       Fixed JavaScript race condition error when adding attendees
    TL-18765       Fixed usertours not recognising parameters on some program pages
    TL-18766       Fixed changes to Site Policy primary language not being saved

                   It is now possible to change the primary language of a Site Policy after it
                   was created.

    TL-18771       Fixed the management interface for 'assigned position' access restrictions in course sections

                   Prior to this change it was possible to add assigned position as a
                   conditional access restriction on course sections. However it was not
                   possible after adding the restriction to then edit or delete it. This has
                   now been fixed and the assigned position conditional access restriction for
                   sections behaves like all other conditional access restrictions.

    TL-18774       Fixed missing $CFG variable in get_local_referer() to allow site policy to be changed without error message when logging out
    TL-18775       Added character length validation rule for appraisal multiple choice question options
    TL-18779       Fixed the visibility of the 'Availability' section on the 'Course default settings' page

                   Previously the availability section would be displayed, even when it was
                   completely empty. This patch hides the entire section when there is nothing
                   to be displayed.

    TL-18781       Fixed an incorrect condition to detect the csv source in HR Import
    TL-18792       Fixed Invalidated course progress on completion import

                   Improved cache cleaning of progress and completion data during upload of
                   course completion

    TL-18804       Fixed the management interface for 'assigned organisation' access restrictions in course sections

                   Prior to this change it was possible to add assigned organisations as a
                   conditional access restriction on course sections but subsequent editing or
                   deleting assignments was not possible. This has been fixed and the
                   assigned organisation conditional access restriction for sections behaves
                   like all other conditional access restrictions.

    TL-18811       Fixed issue with HR Import where suspended state for a user would toggle

                   When importing users using HR Import and the 'Source contains all users'
                   setting was being used, any users who were set to be suspended would be set
                   to suspended on the first execution of HR Import and then unsuspended on
                   the second execution. Subsequent runs of HR Import would toggle the
                   suspended state for the user between suspended and active. The user is now
                   only unsuspended if specified in the imported data source.

    TL-18813       Fixed Seminar event dates being incorrectly created when editing an event with no dates

                   Prior to this fix if you created a Seminar event with no dates, and then
                   went back and edited the event, a session date would be automatically
                   created and you would have to remove them again.
                   This fix ensures a default session date is only added when a new event is
                   created.

    TL-18819       Fixed missing library inclusion for Report Builder settings file

                   In some circumstances an error was being thrown when the scheduler class
                   was not found. This only occurred very rarely when the file containing the
                   scheduler class was not included by another file.

    TL-18823       Fixed displayed ordering of items in Current Learning block

                   Items were sorted by short name, but the full name was displayed in the
                   list. Where short and full name differ significantly, the displayed order
                   would then appear to be somewhat random. The items are now sorted by full
                   name, matching what is displayed, to avoid this confusion.

    TL-18856       Added character length validation rule for appraisal multiple choice question options

Contributions:

    * Artur Poninski at Webanywhere - TL-18811
    * Russell England at Kineo USA - TL-18740


Release 11.6 (24th August 2018):
================================


Security issues:

    TL-18491       Added upstream security hardening patch for Quickforms library

                   A remote code execution vulnerability was reported in the Quickforms
                   library. This applied to other software but no such vulnerability was found
                   in Totara. The changes made to fix this vulnerability have been taken to
                   reduce risks associated with this code.

Performance improvements:

    TL-17598       Enrolments for courses added to an audience's enrolled learning are now processed by an adhoc task in the background

                   Prior to this change course enrolments that were required when a course was
                   added to an audiences enrolled learning were being processed immediately.
                   This could lead to the user performing the action having to wait
                   exceptionally long times on the page while this was processed. The fix for
                   this issue was to shift this processing to a background task. Enrolments
                   will now be processed exclusively by cron when adding courses to an
                   audience's enrolled learning.

Improvements:

    TL-13987       Improved approval request messages sent to managers for Learning Plans

                   Prior to this fix if a user requested approval for a learning plan then a
                   message was sent to the user's manager with a link to approve the request,
                   regardless of whether the manager actually had permission to view or
                   approve the request. This fix sends more appropriate messages depending on
                   the view and approve settings in the learning plan template.

    TL-17780       Added a warning message about certification changes not affecting users until they re-certify
    TL-17920       Added support for the 'coursetype' field in the 'upload courses' tool

                   The 'coursetype' field will now accept either a string or an integer value
                   from the map below:
                    * 0 => elearning
                    * 1 => blended
                    * 2 => facetoface

                   Within the 'upload courses' CSV file, the value for the 'coursetype' field
                   can be either an integer value or a string value. If the value of
                   'coursetype' was not within the expected range of values (as above), then
                   the system will throw an error message when attempting to upload the
                   course(s) or while previewing the course(s).

                   If the field is missing from the CSV file or the value is empty, then the
                   'coursetype' will be set to 'E-learning' by default. This is consistent
                   with previous behaviour.

    TL-18481       Improved the help strings for the 'Minimum time required' field within a program or certification course set

                   Program and certification 'Course set due' and 'Course set overdue' message
                   help strings have also been updated to convey that the 'Minimum time
                   required' field is used to determine when a course set is due.

    TL-18597       Improved the help text for the 'Notification recipients' global seminar setting

                   The setting is located under the notifications header on the site
                   administration > seminars > global settings page, the string changed
                   was 'setting:sessionrolesnotify' within the EN language pack.
                   Full updated text is: This setting affects *minimum
                   booking* and *minimum booking cut-off* notifications. Make sure you
                   select roles that can manage seminar events. Automated warnings will be
                   sent to all users with selected role(s) in seminar activity, course,
                   category, or system level.

    TL-18640       Updated certif_completion join to use 'UNION ALL'

                   The 'certif_completion' join in the 'rb_source_dp_certification' report
                   source now uses 'UNION ALL', previously 'UNION', which will aid
                   performance.

    TL-18675       Added 'not applicable' text to visibility column names when audience visibility is enabled

                   When audience based visibility is enabled it takes priority over other
                   types of visibility. Having multiple visibility columns added to a report
                   may cause confusion as to which type of visibility is being used. '(not
                   applicable)' is now suffixed to the visibility column to clarify which type
                   of visibility is inactive, e.g. 'Program Visible (not applicable)'.

Bug fixes:

    TL-17650       Fixed error when trying to remove large audience from course or courses from Enrolled Learning
    TL-17734       Fixed OpenSesame registration
    TL-17755       Fixed user tours not working when administration block is missing on dashboard
    TL-17767       Fixed multiple blocks of the same type not being restored upon course restore
    TL-17824       Improved the reliability of Totara Connect SSO

                   There is also a new login page parameter '?nosso=1' which may be used to
                   temporarily disable Totara Connect SSO to allow logging in via local
                   authentication method.

    TL-17846       Content restrictions are now applied correctly for Report Builder filters utilising dialogs

                   Before Totara Learn 9 the organisation and position content restriction
                   rules were applied when displaying organisation and position filters in
                   reports.

                   With the introduction of multiple job assignments in Totara Learn 9,
                   organisation and position report filters now use the generic totara dialog
                   to display available organisation and position filter values.

                   This patch added the application of the missing report content restriction
                   rules when retrieving the data to display in totara dialogs used in report
                   filters.

    TL-17857       Deleting a 'featured links' block no longer leaves orphaned cohort visibility records
    TL-17882       Recipient notification preferences are now checked before sending learning plan messages

                   Previously when a new comment was added to a user's learning plan overview,
                   the system would send an email to the target user notifying them about the
                   comment. Now the user's preferences determine whether the email is sent to
                   the user or not, specifically the
                   "message_provider_moodle_competencyplancomment_loggedoff" and
                   "message_provider_moodle_competencyplancomment_loggedin" preferences.

    TL-17934       Fixed waitlisted users not being displayed in seminar reports that included session date columns

                   Previously waitlisted users would be displayed in seminar reports that did
                   not contain session dates, but would disappear if a column related to
                   session dates was added (specifically the session start, session finish,
                   event start time, event finish time columns). Now the waitlisted users will
                   always be displayed regardless of these columns, however the columns will
                   be blank or 'not specified' for these users.

    TL-17936       Report builder graphs now use the sort order from the underlying report

                   When scheduled reports were sent, the report data was correctly ordered,
                   but the graph (if included) was not being ordered correctly. The ordering
                   of the graph now matches the order in the graph table.

    TL-17938       Fixed encoding issues using Scandinavian characters in a Location custom field address

                   This issue only affected Internet Explorer 11. All other browsers handled
                   the UTF8 character natively.

    TL-17955       Progress bar and tooltips in the Current Learning block now work properly with pagination
    TL-17970       Backported MDL-62239 to fix broken drag-drop of question types on iOS 11.3
    TL-17973       Searching a report configured to use custom fields no longer fails after referenced fields have been deleted

                   Previously if a custom field was included in searchable fields for a
                   toolbar search within report builder, and that custom field was then
                   deleted, when a user attempted to search the report using the toolbar
                   search they would get an error. The toolbar search now checks that fields
                   still exists before attempting to perform a search on them.

    TL-17977       Users editing Program assignments are now only shown the option to assign audiences if they have the required capability

                   Previously if a user did not have moodle/cohort:view capability and tried
                   to assign an audience to a program an error would be thrown. The option to
                   add audiences is now hidden from users who do not have this capability.

    TL-18482       Fixed the formatting of Custom profile field data when exporting via 'Bulk user actions'

                   Some values (specifically the Dropdown Menu) were being exported as the
                   index (number) instead of the text name of the option. This is now exported
                   correctly.

    TL-18488       Fixed a regression in DB->get_in_or_equal() when searching only integer values within a character field

                   This is a regression from TL-16700, introduced in 2.6.52, 2.7.35, 2.9.27,
                   9.15, 10.4, and 11.0. A fatal error would be encountered in PostgreSQL if
                   you attempted to call get_in_or_equal() with an array of integers, and then
                   used the output to search a character field.
                   The solution is ensure that all values are handled as strings.

    TL-18498       Fixed the ability to search for custom rooms inside the room selection dialog

                   Previously when custom rooms were created within a seminar session via the
                   room selection dialog, the custom room would not be searchable on the
                   'search' tab of the dialog. Now custom rooms that are visible on the
                   'browse' tab will also be searchable on the 'search'  tab.

    TL-18499       Fixed an issue where searching in glossary definitions longer than 255 characters would return no results on MSSQL database

                   The issue manifested itself in the definitions where the search term
                   appeared in the text only after the 255th character due to incorrectly used
                   concatenation in an SQL query.

    TL-18545       Fixed the management interface for 'audience membership' access restrictions in course sections

                   Prior to this change it was possible to add audience membership as a
                   conditional access restriction on course sections. However it was not
                   possible after adding the restriction to then edit or delete it. This has
                   now been fixed and the audience membership conditional access restriction
                   for sections behaves like all other conditional access restrictions.

    TL-18546       Fixed missing string parameter when exporting report with job assignment filters
    TL-18548       Introduced new permissions for adding and removing recipients to a seminar message

                   The two new permissions were added:
                     1) "mod/facetoface:addrecipients" : This permission allows the role to
                   add any recipients to the seminar message
                     2) "mod/facetoface:removerecipients" : This permission allows the role
                   to remove any recipients from the seminar message

                   Adding or removing seminar's message recipients action would not check for
                   the permission "mod/facetoface:addattendees" or
                   "mod/facetoface:removeattendees" but checking for the new permissions added
                   instead

    TL-18566       Backported MDL-61281 to make Solr function get_response_counts compatible() with PHP 7.2
    TL-18573       Added a check for the 'Events displayed on course page' setting when viewing seminar events on the course page

                   Now both settings are taken into account: when the 'Users can sign-up to
                   multiple events setting' is enabled, the number of events displayed for
                   which a user can sign up will be restricted to the number in the ‘Events
                   displayed on course page' setting. Events to which a user is already signed
                   up will always be displayed, and do not form part of the event count.

    TL-18574       Fixed a return type issue within the Redis session management code responsible for checking if a session exists
    TL-18583       Fixed missing status string on Site Policies Report
    TL-18587       Made sure missing library in CAS config form is included
    TL-18590       Made sure that multiple jobs are not created via search dialogs if multiple jobs are disabled sitewide
    TL-18599       Fixed minor issues with site policies

                   The following minor issues with site policies were fixed:
                    * Viewing the 'Site Policy Records' embedded report while site policies
                      are not enabled now shows the report without throwing an exception.
                    * An 'Edit this report' link is now available to administrators and users
                      with the necessary capabilities when the 'Site Policy Records' embedded
                      report is viewed.
                    * After giving consent to the necessary site policies, the user is now
                      redirected back to the original url. E.g. A user receives an email with a
                      forum link. They click the link which requires them to log in and give
                      consent to the policies. Once they have given the necessary consent, they
                      are now redirected directly to the forum page which was originally
                      requested.

    TL-18618       Restoring a course now correctly ignores links to external or deleted forum discussions
    TL-18649       Improved the Auto login guest setting description

                   The auto login guest setting incorrectly sets the expectation that
                   automatic login only happens when a non-logged in user attempts to access a
                   course. In fact it happens as soon as the user is required to login,
                   regardless of what they are trying to access. The description has been
                   improved to reflect the actual behaviour.

    TL-18676       Improved the performance of 'set of courses' in program content editing

Miscellaneous Moodle fixes:

    TL-18142       MDL-60439: Enabled multi-language filter on Tags block title

Contributions:

    * Jo Jones, Kineo UK - TL-18640
    * Michael Geering, Kineo UK - TL-17973
    * Russell England, Kineo USA - TL-17977


Release 11.5 (18th July 2018):
==============================


Security issues:

    TL-17320       Fixed validation issue when checking LTI parameters

                   On a site that has published a course as an LTI tool, a user may have been
                   able to trick the validation system into validating against the wrong
                   values. This could have allowed the user to set parameters to values
                   different to those supplied by the consumer site. This vulnerability has
                   been fixed.

Improvements:

    TL-17353       Updated the description for "Minimum scheduled report frequency" in the Report Builder general settings
    TL-17720       Added 'audience visible' default course option to the upload course tool
    TL-17790       Improved the HTML of the change password page

                   Previously the "Change password" heading was in a legend, this patch moves
                   it to a proper HTML heading.

    TL-17791       Added role HTML attributes to the Totara menu
    TL-17795       Tooltips in the "Current learning" block are now displayed when focused via the tab key
    TL-17891       Changed the Change password page to use the standard page layout

                   This gives the Change password page the standard navigation and blocks

Bug fixes:

    TL-16293       Fixed user profile custom fields "Dropdown Menu" to store non-formatted data

                   This fix has several consequences:
                   1) Whenever special characters (&, <, and >) were used in user custom
                      profile field, it was not found in dynamic audiences. It was fixed
                      by storing unfiltered values on save. Existing values will not be changed.
                   2) Improved multi language support of this custom field, which will display
                      item in user's preferred language (or default language if the user's
                      language is not given in the item).
                   3) Totara "Dropdown Menu" customfield also fixed on save.

                   Existing values that were stored previously, will not be automatically
                   fixed during upgrade. To fix them either:
                   1) Edit instance that holds value (e.g. user profile or seminar event),
                      re-select the value and save.
                   2) Use a special tool that we will provide upon request. This tool can work
                      in two modes: automatic or manual. In automatic mode it will attempt to
                      search filtered values and provide a confirmation form before fixing them.
                      In manual mode it will search for all inconsistent values (values that
                      don't have a relevant menu item in dropdown menu customfield settings)
                      across all supported components and allow you to choose to update them to
                      an existing menu item. To get this tool please request it on support board.

    TL-16795       Added support for backing up and restoring featured links blocks inside of courses

                   Due to a significant improvement in the capability of Gallery tiles in
                   Totara 12, backups created in versions prior to Totara 12 that include a
                   Featured Links Block with Gallery tiles, will not restore fully in Totara
                   12.

    TL-16853       Fixed an issue with file path separators in DomPDF while generating PDF snapshots or an Appraisal
    TL-17324       Made completion imports trim leading and trailing spaces from the 'shortname' and 'idnumber' fields

                   Previously leading and trailing spaces on the 'shortname' or 'idnumber'
                   fields, were causing inconsistencies while matching upload data to existing
                   records during course and certification completion uploads. This patch now
                   trims any leading or trailing spaces from these fields while doing the
                   matching.

    TL-17385       Fixed an error when viewing the due date column in program reports that don't allow the display of the total count
    TL-17397       Fixed category level roles being unable to restrict access to category level audiences
    TL-17420       Formatted any dates in program emails based on the recipient's selected language package
    TL-17511       Made sure compound records in reports are not aggregated and added a new jobs counting column
    TL-17531       Fixed user report performance issue when joining job assignments

                   This fix improves performance for certain reports when adding columns from
                   the "All User's job assignments" section. The fix applies to the following
                   report sources:
                    * Appraisal Status
                    * Audience Members
                    * Badges Issued
                    * Competency Status
                    * Competency Status History
                    * Goal Status
                    * Learning Plans
                    * Program Completion
                    * Program Overview
                    * Record of Learning: Recurring Programs
                    * User

    TL-17631       Custom seminar rooms are now able to be viewed and edited within the report builder
    TL-17655       Fixed the prefix auto-fill functionality for Seminar notifications "Body" and "Manager copy prefix" fields

                   Previously while creating or editing seminar notifications, the drop-down
                   selector that pre-populated text fields for notifications using a chosen
                   template's data was only populating the title input. This has been fixed to
                   also pre-populate the "Body" and "Manager copy prefix" message fields.

    TL-17657       Fixed an error causing a debugging message in the facetoface_get_users_by_status() function

                   Previously when the function was called with the include reservations
                   parameter while multiple reservations were available, there were some
                   fields added to the query that were causing a debugging message to be
                   displayed.

    TL-17714       Made sure custom user profile textareas have default values set (where one is supplied) on signup page
    TL-17733       Made sure duplicate user email addresses are validated as duplicate regardless of the text case

                   Previously it was possible to sign up or update email addresses which would
                   duplicate an existing email address, but in a different text case (test vs
                   TEST). Now, we ignore the text case during sign up, email address update,
                   HR sync, and user upload by an administrator. The way user accounts are
                   validated by the authentication methods has not changed.

    TL-17789       Fixed an accessibility issue with an incorrect skip link on the login page
    TL-17818       Fixed the database error when uploading an AICC package via SCORM package activity
    TL-17834       Fixed empty JSON being returned when deleting enrolled learning from a custom report
    TL-17845       Fixed SCORM height issue when side navigation was turned on

                   In some SCORM modules the height of the player was broken when the side
                   navigation was turned on. The height of the player is now calculated
                   correctly with both side and drop down navigation.

    TL-17847       Reduced specificity of fix for TL-17744

                   The June releases of Totara included a fix for heading levels in an HTML
                   block. This increased the specificity of the CSS causing it to override
                   other CSS declarations (this included some in the featured links block).
                   This is now fixed in a different manner, maintaining the
                   existing specificity.

    TL-17858       Fixed the ability to delete blocks from admin settings pages

                   A bug was preventing the deletion of blocks on some admin pages, this
                   affected all pages with a URL in the form of
                   <site>/admin/settings.php?section=<sectionname>. Blocks on these pages are
                   now removed correctly.

    TL-17868       Fixed a bug which assumed a job must have a manager when messaging attendees of a Seminar

                   Prior to this fix due to a bug in code it was not possible to send a
                   message to Seminar attendees, cc'ing their managers, if the attendee job
                   assignments were tracked, and there was at least one attendee who had a
                   manager, and at least one attendee who had a job assignment which did not
                   have a manager. This has now been fixed.

                   When messaging attendees, having selected to cc their managers, if an
                   attendee does not have a manager the attendee will still receive the
                   message.

    TL-17869       Fixed SQL query in display function in "Pending registrations" report

                   The SQL being used in the display function caused an error in MySQL and
                   MariaDB

    TL-17881       Ensured that Learning plan component settings are also loaded for disabled items

                   When a Learning Plan template has a component that is not enabled, such as
                   courses, linked courses added to competencies, for example, caused a
                   failure in the 'Create learning plans for users in this audience' feature.
                   This was due to settings not being initialised for Learning Plan components
                   that are not enabled, this patch ensures that initialisation of components
                   occurs when they are either enabled or disabled.

    TL-17885       Display seminar assets on reports even when they are being used in an ongoing event

                   When the Asset Availability filter is being used in a report, assets that
                   are available but currently in use (by an ongoing event at the time of
                   searching) should not be excluded from the report. Assets should only be
                   excluded if they are not available between the dates/times specified in the
                   filter.

    TL-17894       Fixed the display of Seminar approval settings when they have been disabled at the system level

                   When an admin disabled an approval option on the seminar global settings
                   page, and there was an existing seminar using the approval option, the
                   approval option would then display as an empty radio selector on that
                   seminar's settings page, and none of the approval options would be
                   displayed as selected. However unless a different approval option was
                   selected the seminar would continue using the disabled option.
                   This patch fixes the display issue by making the previously empty radio
                   selector correctly display the disabled setting's name, and marking it as
                   selected. As before, the disabled approval option can still be used for
                   existing seminars until it is changed to a different setting. When the
                   setting is changed for the seminar the now disabled approval option will no
                   longer be displayed.

Contributions:

    *  Grace Ashton at Kineo.com - TL-17657


Release 11.4 (20th June 2018):
==============================


Security issues:

    TL-10268       Prevented EXCEL/ODS Macro Injection

                   The Excel and Open Document Spreadsheet export functionality allowed the
                   exporting of formulas when they were detected, which could lead to
                   incorrect rendering and security issues on different reports throughout the
                   code base. To prevent exploitation of this functionality, formula detection
                   was removed and standard string type applied instead.

                   The formula type is still in the code base and can still be used, however
                   it now needs to be called directly using the "write_formula" method.

    TL-17424       Improved the validation of the form used to edit block configuration

                   Validation on the fields in the edit block configuration form has been
                   improved, and only fields that the user is permitted to change are passed
                   through this form.
                   The result of logical operators are no longer passed through or relied
                   upon.

    TL-17785       MDL-62275: Improved validation of calculated question formulae

Performance improvements:

    TL-17615       Improved mapping of courses and certifications within the completion import tool

                   Previously all mapping was done in SQL, and was repeated any time the
                   mapping data was needed.
                   On some database engines the SQL would perform poorly when applied to a
                   large data set.
                   This change introduces two new fields to capture the mapping, which is now
                   calculated once and saved for future reference.
                   This should lower resource use on the database when running completion
                   import.

Improvements:

    TL-17288       Missing Seminar notifications can now be restored by a single bulk action

                   During Totara upgrades from earlier versions to T9 and above, existing
                   seminars are missing the new default notification templates. There is
                   existing functionality to restore them by visiting each seminar
                   notification one by one, which will take some time if there are a lot of
                   seminars. This patch introduces new functionality to restore any missing
                   templates for ALL existing seminars at once.

    TL-17414       Improved information around the 'completions archive' functionality

                   It now explicitly expresses that completion data will be permanently
                   deleted and mentions that the data that will be archived is limited to: id,
                   courseid, userid, timecompleted, and grade. It also mentions that this
                   information will be available in the learner's Record of Learning.

    TL-17517       Improved the user interface for Course Import when no courses match a search term
    TL-17611       Added a hook to the Last Course Accessed block to allow courses to be excluded from being displayed

                   This hook allows specified courses to be excluded from being displayed in
                   the Last Course Accessed block. If the most recently accessed course is
                   excluded then the next most recently accessed course is displayed.

    TL-17613       Added a hook to the Last Course Accessed block to allow extra data to be passed to template

                   This enables extra data to be passed through to the Last Course Accessed
                   block template so that the display can be more easily modified without
                   changing core code.

    TL-17626       Prevented report managers from seeing performance data without specific capabilities

                   Site managers will no longer have access to the following report columns as
                   a default:

                   Appraisal Answers: Learner's Answers, Learner's Rating Answers, Learner's
                   Score, Manager's Answers, Manager's Rating Answers, Manager's
                   Score, Manager's Manager Answers, Manager's Manager Rating Answers,
                   Manager's Manager Score, Appraiser's Answers, Appraiser's Rating Answers,
                   Appraiser's Score, All Roles' Answers, All Roles' Rating Answers, All
                   Roles' Score.

                   Goals: Goal Name, Goal Description

                   This has been implemented to ensure site managers cannot access users'
                   performance-related personal data. To give site managers access to this
                   data the role must be updated with the following permissions:
                   * totara/appraisal:viewallappraisals
                   * totara/hierarchy:viewallgoals

    TL-17738       Changed data-vocabulary.org URL in metadata to be https

                   This URL is used to provide extra information for navigation breadcrumbs to
                   search engines when your site is indexed.

Bug fixes:

    TL-16908       Made sure evidence files are being cleaned up when evidence is deleted
    TL-16967       Fixed an 'invalidrecordunknown' error when creating Learning Plans for Dynamic Audiences

                   Once the "Automatically assign by organisation" setting was set under the
                   competencies section of Learning Plan templates, and new Learning Plans
                   were created for Dynamic Audiences, a check for the first job assignment of
                   the user was made. This first job assignment must exist otherwise an error
                   was thrown for all users that did not have a job assignment. This has now
                   been fixed and a check for all of the user's job assignments is made
                   rather than just the first one.

    TL-17102       Fixed saved searches not being applied to report blocks
    TL-17289       Made message metadata usage consistent for alerts and blocks
    TL-17364       Fixed displaying profile fields data in the self-registration request report
    TL-17405       Fixed setuplib test case error when test executed separated
    TL-17416       Prevented completion report link appearing in user profile page when user does not have permission to view reports.
    TL-17486       Fixed display issue when using "Hide if there is nothing to display" setting in the report table block

                   If the setting "Hide if there is nothing to display" was set for the report
                   table block then the block would hide even if there was data. The setting
                   now works correctly and only hides the block if the report contains no
                   data.

    TL-17523       Removed the ability to create multiple job assignments via the dialog when multiple jobs is disabled
    TL-17524       Fixed exporting reports as PDF during scheduled tasks when the PHP memory limit is exceeded

                   Generating PDF files as part of a scheduled report previously caused an
                   error and aborted the entire scheduled task if a report had a large data
                   set that exceeded the PDF memory limit. With this patch, the exception is
                   still raised, but the export completes with the exception message in the
                   PDF file notifying the user that they need to change their report. The
                   scheduled task then continues on to the next report to be exported.

    TL-17541       Fixed the help text for a setting in the course completion report

                   The help text for the 'Show only active enrolments' setting in the course
                   completion report was misleading, sounding like completion records for
                   users with removed enrolments were going to be shown on the report. This
                   has now been fixed to reflect the actual behaviour of the setting, which
                   excludes records from removed enrolments.

    TL-17542       Made sure that RPL completion information remains collapsed on the course completion report until it is explicitly expanded
    TL-17590       Added missing parameters to the 'User is a member of audience' filter javascript call
    TL-17601       Made the edit and delete icons in the calendar use Flex icons so they are now Font Awesome icons

                   In Totara 9 the edit and delete buttons for events on calendars were
                   switched over to the new Flex icon API, this was mistakenly overwritten in
                   a later patch. This patch moves the edit and delete buttons back to the
                   Flex icon API as intended.

    TL-17610       Setup cron user and course before each scheduled or adhoc task

                   Before this patch we set the admin user and the course at the beginning of
                   the cron run. Any task could have overridden the user. But if the task did
                   not take care of resetting the user at the end it affected all following
                   tasks, potentially creating unwanted results. Same goes for the course. To
                   avoid any interference we now set the admin user and the default course
                   before each task to make sure all get the same environment.

    TL-17612       Added a warning by the "next page" button when using sequential navigation

                   When the quiz is using sequential navigation, learners are unaware that
                   they cannot navigate back to a question. A warning has been introduced when
                   sequential navigation is in place to make the learner aware of this.

    TL-17622       Fixed validation of custom user profile fields during self-registration
    TL-17628       Prevented access to global report restriction interface when feature is disabled
    TL-17630       Fixed Error in help text when editing seminar notifications

                   in the 'body_help' string replaced [session:room:placeholder] with
                   [session:room:cf_placeholder] as all custom field placeholders have to have
                   the cf_ prefix in the notification.

    TL-17633       Removed misleading information in the program/certification extension help text

                   Previously the help text stated "This option will appear before the due
                   date (when it is close)" which was not accurate as the option always
                   appeared during the program/certification enrollment period. This statement
                   has now been removed.

    TL-17647       Raised MySQL limitation on the amount of questions for Appraisals.

                   Due to MySQL/MariaDB row size limit there could only be about 85 questions
                   of types "text" in one appraisal. Creating appraisals with higher numbers
                   of questions caused an error on activation. Changes have been made to the
                   way the questions are stored so that now it's possible to have up to about
                   186 questions of these types when using MySQL/MariaDB.

                   On the appraisal creation page a warning message has been added that is
                   shown when the limit is about to be exceeded due to the amount of added
                   questions.

                   Also, when this error still occurs on activation, an informative error
                   message will be shown instead of the MySQL error message.

    TL-17656       Fixed notification type validation when creating a new notification

                   When creating a new seminar notification and using the default values, the
                   save process was failing because a notification type default value was
                   missed. Now the default value for the notification type is "Send now"

    TL-17662       Fixed user roles not being added on re-enrolment into course after resetting course
    TL-17702       Fixed display issue when editing forum subscribers
    TL-17711       Fixed message URL in the component alerts
    TL-17716       Fixed HR Import sanity checks for Hierarchy parents when source does not contain all records

                   When the Organisation / Position elements are set to "source does not
                   contain all records" there are sanity checks to ensure that, if an item has
                   a parent, the parent currently exists or will exist before the record is
                   imported.

                   Prior to this patch, only the source records were being used to determine
                   if the parent exists. This only works when the element is set to "source
                   contains all records".

                   This patch ensures that when the element is set to  "source does not
                   contain all records", the sanity check also includes the existing data to
                   determine if a parent exists.

    TL-17722       Fixed issue with HTML entities being stored in Feedback module responses

                   In the Feedback module, if a text area question was being used, some
                   characters were being saved into the database as HTML encoded entities.
                   This resulted in exports and some displays incorrectly showing HTML
                   entities in place of these characters.

    TL-17724       Fixed nonfunctional cleanup script for incorrectly deleted users
    TL-17729       Dialogs no longer overwrite JavaScript strings

                   In some situations it was possible for strings required in JavaScript to be
                   removed. This will no longer happen.

    TL-17730       Added 'alt' text to report cache icon
    TL-17732       Fixed a regression in the Current Learning block caused by TL-16820

                   The export_for_template() function in the course user learning item was
                   incorrectly calling get_owner() when it should have been using has_owner().

    TL-17744       Fixed header tags being the same size as all other text in the HTML block

Contributions:

    * Jo Jones at Kineo UK - TL-17524


Release 11.3 (14th May 2018):
=============================


Security issues:

    TL-17382       Mustache str, pix, and flex helpers no longer support recursive helpers

                   A serious security issue was found in the way in which the String, Pix
                   icon, and Flex icon Mustache helpers processed variable data.
                   An attacker could craft content that would use this parsing to instantiate
                   unexpected helpers and allow them to access context data they should be
                   able to access, and in some cases to allow them to get malicious JavaScript
                   into pages viewed by other users.
                   Failed attempts to get malicious JavaScript into the page could still lead
                   to parsing issues, encoding issues, and JSON encoding issues. Some of which
                   may lead to other exploits.

                   To fix this all uses of these three mustache helpers in core code have been
                   reviewed, and any uses of them that were using user data variables have
                   been updated to ensure that they are secure.

                   In this months Evergreen release and above the API for these three helpers has
                   been revised. User data variables can no longer be used in Mustache
                   template helpers.

                   We strongly recommend all users review any customisations they have that
                   make use of Mustache templates to ensure that any helpers being used don't
                   make use of context data variables coming from user input.
                   If you find helpers that are using variables containing user data we strongly
                   recommend preparing new pre-resolved context variables in PHP or JavaScript
                   and not passing that information through the helpers.

    TL-17436       Added additional validation on caller component when exporting to portfolio
    TL-17440       Added additional validation when exporting forum attachments using portfolio plugins
    TL-17445       Added additional validation when exporting assignments using portfolio plugins
    TL-17527       Seminar attendance can no longer be used to export sensitive user data

                   Previously it was possible for a site administrator to configure Seminar
                   attendance exports to contain sensitive user data, such as a user's hashed
                   password. User fields containing sensitive data can no longer be included
                   in Seminar attendance exports.

Improvements:

    TL-12620       Automated the selection of job assignments upon a users assignment to an appraisal when possible

                   When an appraisal is activated or when learners are dynamically or manually
                   added to an active appraisal, a learner's job assignment is now
                   automatically linked to their appraisal assignment. Before this change, the
                   learner had to open the appraisal for this to happen.

                   This will only come into effect if the setting "Allow multiple job
                   assignments" is turned OFF.

                   If a user has multiple job assignments, this automatic assignment will not
                   apply. If a user has no job assignment, an empty job assignment will still
                   be automatically created.

    TL-16344       Implemented user data item for the "Self-registration with approval" authentication plugin
    TL-16356       Implemented user data item for the database module
    TL-16738       Implemented user data items for grades

                   The following user data items have been introduced:
                    * Grades - This item takes care of the Gradebook records, supporting both
                      export and purge.
                    * Temp import - This item is a fail-safe cleanup for the tables which are
                      used by grade import script for temporary storage, supporting only purge.
                    * Improved Individual assignments item - This item includes feedback and
                      grades awarded via advanced grading (Guide and Rubric), supporting both
                      purge and export.

    TL-16958       Updated language strings to replace outdated references to system roles

                   This issue is a follow up to TL-16582 with further updates to language
                   strings to ensure any outdated references to systems roles are corrected
                   and consistent, in particular changing student to learner and teacher to
                   trainer.

    TL-17142       Enabled use of the HTML editor when creating site policy statements and added the ability to preview

                   An HTML editor is now used when adding and editing Site Policy statements
                   and translations. A preview function was also added. This enables the
                   policy creator to view how the policy will be rendered to users.

                   Anyone upgrading from an earlier version of Totara 11 who has previously
                   added site policies and wants to use html formatting will need to:
                    * Edit the policy text
                    * The text will still be displayed in a text editor, but you will have an
                      option to change the entered format
                    * Make sure you have a copy of the current text somewhere (copy/paste)
                    * Change the format to "HTML format"
                    * Save and re-open the policy OR Preview and click "Continue editing". The
                      policy text will be shown in the HTML editor but will most likely contain
                      no formatting
                    * Replace the current (unformatted) text by pasting back in the copy of
                      the original text
                    * Save

    TL-17383       Improved the wording and grouping of user data items

Bug fixes:

    TL-6476        Removed the weekday-textual and month-textual options from the data source selector for report builder graphs

                   The is_graphable() method was changed to return false for the
                   weekday-textual and month-textual, stopping them from being selected in the
                   data source of a graph. This will not change existing graphs that contain
                   these fields, however if they are edited then a new data source will have
                   to be chosen. You can still display the weekday or month in a data source
                   by using the numeric form.

    TL-15037       Fixed name_link display function of the "Event name" column for the site log report source

                   The Event name (linked to event source) column in the Site Logs reporting
                   source was not fully restoring the event data.

    TL-17387       Fixed managers not being able to allocate reserved spaces when an event was fully booked
    TL-17442       Ensured that the 'deleted' field is displayed correctly in the list of source fields for HR Import
    TL-17458       Fixed a PHP undefined property notice, $allow_delete within the HR Import source settings
    TL-17471       Fixed Google reCAPTCHA v2 for the "self registration with approval" authentication plugin
    TL-17485       Stopped irrelevant instructions being shown on some of the plan component detail pages

                   The plan header includes instructions about the component and adding a new
                   one. For objectives, competencies, and programs, the instructions were
                   being shown on both the main page, which lists the component items, and the
                   detail page for each item. These instructions were confusing and irrelevant
                   on the details pages so they have been removed.

    TL-17487       Fixed the completion progress bar not updating the percentage correctly in the "Record of Learning: Courses" report
    TL-17509       Fixed the time assigned column for program and certification report sources

                   The time assigned column for the program completion, program overview,
                   certification completion, and certification overview sources previously
                   displayed the data for timestarted, this patch has two main parts:

                   1) Changes the default header of the current column to "Time started" to be
                      consistent with what it displays
                   2) Adds a new column "Time assigned" to the report source that displays the
                      expected data

                   This means that any existing sites that have a report based on one of the
                   affected sources may want to edit the columns for the report and either add
                   or switch over to the new time assigned column.

    TL-17522       Fixed inconsistent styling on the "Add new objective" button in learning plans

                   The padding on the "Add new objective" button was inconsistent with the
                   same button in other components. The missing class has been added to make
                   the styling consistent.

    TL-17528       Removed some duplicated content from the audience member alert notification
    TL-17534       Stopped time being added by the Totara form utc10 date picker

                   TL-16921 introduced the date time pickers of the utc10 totara form element.
                   As an unintended consequence, the time was being added by the input element
                   that caused validation to fail. This patch stops the time being added by
                   the date picker

    TL-17535       Fixed hard-coded links to the community site that were not being redirected properly

Contributions:

    * Marcin Czarnecki at Kineo UK - TL-17387


Release 11.2 (19th April 2018):
===============================


Important:

    TL-17097       Merged patches from Moodle releases 3.2.6, 3.2.7, and 3.2.8

Improvements:

    TL-14282       Imported ADOdb library v5.20.12
    TL-15739       Imported HTMLPurifier library v4.10.0
    TL-16255       Added a "readonly" state to the Totara reserved custom fields to prevent users from changing the pre-existing seminar custom fields
    TL-16582       Updated language contextual help strings to use terminology consistent with the rest of Totara

                   This change updates the contextual help information displayed against form
                   labels. For example this includes references to System roles, such as
                   student and teacher, have been replaced with learner and trainer.

                   In addition, HTML mark-up has been removed in the affected strings and
                   replaced with Markdown.

    TL-17137       The site policy user consent report now appears in the settings block

                   A user consent report exists for the new site policy tool, however it was
                   never linked to from the current navigation. This user consent report is
                   now linked to from the Settings block, you can find it by navigating to
                   Security > Site policies > User consent report.

    TL-17354       Ordered all user data item groups alphabetically
    TL-16357       Implemented user data item for LTI submissions
    TL-16360       Implemented user data item for glossary entries, comments and ratings
    TL-16367       Implemented user data items for standard and legacy logs
    TL-16773       Implemented user data item for the Community Block
    TL-16775       Implemented user data item for RSS client
    TL-16777       Implemented user data item for the Featured links block
    TL-16840       Implemented user data item for user data export requests
    TL-17227       Implemented user data item for role assignments
    TL-17374       Implemented user data item for Course requests
    TL-16327       Implemented user data items for Report Builder

                   Added items that allow exporting and purging of user-made saved searches
                   (private and public), scheduled reports, and their participation in global
                   report restriction.

    TL-16332       Implemented user data items for Audience memberships

                   Items for exporting and purging a user's audience membership has been
                   added. This is split into two items: Set audience membership and dynamic
                   audience membership.

    TL-16334       Implemented user data items for component and plugin user preference data

                   It is now possible to export and purge user preference data being used by
                   all parts of the system.
                   These preferences store a range of information, all pertaining to the user,
                   and the state of things that they have interacted with on the site, or the
                   decisions that they have made.
                   Some examples are:
                     * What user tours the user has completed, and when.
                     * The admin bookmarks that they have saved.
                     * Their preferences for the course overview block.
                     * Whether they have docked the admin and navigation blocks.
                     * Their preferred display mode for forums.
                     * What regions within a workshop activity they have collapsed.

    TL-16345       Implemented user data item for event monitor subscriptions

                   Implemented user data item for event monitor subscriptions to allow the
                   exporting and purging of user data kept in relation to event monitoring.

    TL-16346       Implemented user data items for Feedback360

                   Feedback360 has two user data items, both implementing export and purge:
                     * The user assignments item, this covers all of a user's assignments to a
                       Feedback360 and all responses to their requests.
                     * The response assignments item, this covers all of a user's responses to
                       other user's Feedback360 requests.

                   It is worth noting that self evaluation responses will be included in both
                   user data items.

    TL-16349       Implemented user data items for Learning Plans and Evidence

                   This allows user data for Learning Plans and Evidence items to be purged
                   and exported.

    TL-16350       Implemented user data items for Appraisals

                   Added five user data items:
                     * "Appraisals" - purge all appraisal data where the user is a learner
                     * "As the learner, excluding hidden answers from other roles" - export all
                       appraisal content that the user can see as a learner
                     * "As the learner, including hidden answers from other roles" - export all
                       appraisal content, including all answers from other roles, regardless of
                       visibility settings, where the user is the learner
                     * "Participation in other users' appraisals" - export all other users'
                       appraisals that the user is currently participating in
                     * "Participation history" - export the history of participation in other
                       users' appraisals

    TL-16365       Implemented user data items for the Wiki module

                   The following user data items have been introduced:
                      * Individual wiki as a whole.
                      * Collaborative wiki files export files uploaded by the user to the collaborative wiki.
                      * Collaborative wiki comments exports\purges user's comments for collaborative wiki pages.
                      * Collaborative wiki versions exports collaborative wiki page versions
                        submitted by the user.

    TL-16736       Implemented user data items for course enrolments

                   Added two user data items that allow exporting and purging:
                     * An item for course enrolment data.
                     * An item for pending enrolments that belong to the Flat file enrolment plugin.

    TL-16739       Implemented user data items for program and certification completion

                   This includes exporting and purging of program and certification
                   assignments, completion records (including completion history and logs). It
                   also includes exceptions, program extensions and the log of program
                   messages sent to the user.

                   Users are unassigned from any program or certification regardless of the
                   assignment type. If users were assigned via audience, position or
                   organisation it's possible that they will be reassigned automatically as
                   soon as the next scheduled task for dynamic user assignment is triggered.

    TL-16877       Implemented user data items for comments and HTML blocks

                   Now it is possible to purge, export and audit the data stored in the
                   comments and HTML blocks.

                   In case of the comments block item, all comments made by users in all
                   created comment blocks are purged or exported. This affects the front page,
                   personal dashboards and courses.

                   In case of the HTML block item, all blocks created by the users in their
                   personal dashboards are purged and exported. HTML blocks in other contexts
                   (front page, courses) are not affected as they are related to the course or
                   the site and not personal to the user.

    TL-16936       Implemented user data item for Competency progress

                   The competency progress item is specifically for the comp_criteria_record table; other
                   competency tables are handled by the competency status item.

    TL-17362       Implemented user data item for portfolios

                   Implemented user data elements for portfolios. This allows the exporting
                   and purging of user data kept in relation to exporting of data to
                   portfolios.

    TL-17373       Implemented user data item for external blogs

                   This user data items takes care of the exporting and purging of external
                   blogs. It includes all external blogs created by the user, including tags
                   assigned to it, all synced posts, and all comments made on the blogs.

    TL-17378       Implemented user data item for the transaction information of the PayPal enrolment plugin

                   When the user enrols via PayPal the transaction details are sent to the IPN
                   endpoint in Totara which records the information in the enrol_paypal
                   table. The user data item takes care of purging, exporting and counting
                   this transaction information.

    TL-16848       Renamed the "Site policies" side menu item in the "Security" section

                   The Security > "Site policies" side menu item has been renamed to "Security
                   settings" to avoid confusion with the new "Site policies" item when GDPR
                   site policies are enabled.

    TL-17384       composer.json now includes PHP version and extension requirements
    TL-17390       Enabled the "Force users to log in to view user pictures" setting by default for new installations to improve privacy
    TL-17403       Removed calls to deprecated table() and cellpadding() functions within forum ratings and external blogs
    TL-10295       Added link validation for report builder rb_display functions

                   In some cases if a param value in rb_display function is empty the function
                   returns the HTML link with empty text which breaks a page's accessibility.

    TL-17024       Added detection of pending upgrades to admin settings related pages
    TL-17268       Upgraded Node.js requirements to v8 LTS
    TL-17280       Improved compatibility for browsers with disabled HTTP referrers
    TL-17170       Included hidden items while updating the sort order of Programs and Certifications
    TL-17321       Added visibility checks to the Program deletion page

                   Previously the deletion of hidden programs was being stopped by an
                   exception in the deletion code, we've fixed the exception and added an
                   explicit check to only allow deletion of programs the user can see. If you
                   have users or roles with the totara/program:deleteprogram capability you
                   might want to consider allowing totara/program:viewhiddenprograms as well.

    TL-17352       PHPUnit and Behat do not show composer suggestions any more to minimise developer confusion
    TL-17357       Unsupported symlinks are now ignored in phpunit tests


Bug fixes:

    TL-14364       Disabled the option to issue a certificate based on the time spent on the course when tracking data is not available

                   The certificate activity has an option which requires a certain amount of
                   time to be spent on a course to receive a certificate. This time is
                   calculated on user actions recorded in the standard log. When the standard
                   log is disabled, the legacy log will be used instead. If both logs are
                   disabled, the option will also be disabled.

                   Please note, if the logs are disabled, and then re-enabled, user actions in
                   the time the logs were disabled will not be recorded. Consequently, actions
                   in this period will not be counted towards time spent on the course.

    TL-16122       Added the 'Enrolments displayed on course page' setting for the Seminar direct enrolment plugin and method

                   Previously the amount of enrolments on the course page was controlled by
                   the 'Events displayed on course page' course setting. Now there are two new
                   settings, one is under "Site administration > Plugins > Enrolments >
                   Seminar direct enrolment plugin" where the admin can set a default value
                   for all courses with the Seminar direct enrolment method. The other is
                   under the Course seminar direct enrolment method where the admin can set a
                   different value. The available options are "All(default), 2, 4, 8, 16" for
                   both settings.

    TL-16461       Fixed the date offset being applied to user completion dates when restoring a course
    TL-16724       Fixed an error while backing up a course containing a deleted glossary

                   This error occurred while attempting to backup a course that contained a
                   URL pointing to a glossary activity that had been deleted in the course
                   description. Deleted glossary items are now skipped during the backup
                   process.

    TL-16821       Removed an error that was stopping redisplay questions in Appraisals from displaying the same question twice
    TL-16839       Ensured that the names of deleted users are not shown in forum ratings
    TL-16853       Fixed bug in DomPDF when using a css file without a @page tag
    TL-16894       Fixed HR import ignoring the default user email preferences when creating new users
    TL-16898       Fixed the seminar booking email with iCal invitation not containing the booking text in some email clients.

                   Some email clients only display the iCal invitation and do not show the
                   email text if the email contains a valid iCal invitation. To handle this
                   the iCal description will now include the booking email text as well as
                   Seminar and Seminar session description.

    TL-16926       Limited the maximum number of selected users in the Report builder job assignment filter

                   Added 'selectionlimit' option to manager field filters, also introduced
                   "$CFG->totara_reportbuilder_filter_selected_managers_limit" to limit the
                   number of selected managers in the report builder job assignment filter
                   dialog. The default value is 25, to make it unlimited, set it to 0.

                   This patch also removed the equals and not-equals options from the job
                   assignment filter when multiple job assignments are not enabled.

    TL-17131       Fixed the user's appraisal snapshots not being deleted when the user is deleted

                   Previously, when an appraisal belonging to a user was deleted (such as when
                   the user was deleted), any related snapshots that had been generated were
                   inadvertently being kept. While these orphaned snapshots could not be
                   accessed through the Totara front end, they could still potentially be
                   accessed through the server's file system.

                   This patch ensures that appraisal snapshots are deleted when the appraisals
                   they belong to are deleted. During upgrade, it also deletes all appraisal
                   snapshots which belong to user appraisal assignments which no longer exist.

    TL-17151       Fixed the positioning of filters and search options in the dropdown selector on the Report builder edit filters page

                   When a filter option or search column was added in an embedded report, and
                   then removed by clicking the delete button, it was not being added back
                   into the right heading within the selectbox. Instead it was added at the
                   end of the selectbox with an untranslated key as a heading, it is now
                   placed back at the end of the correct heading.

    TL-17167       Fixed the 'show blank date records' filter option remaining selected after clearing search in reports
    TL-17226       Ensure that menu items are correctly being marked as selected in the Totara menu

                   Totara menu items are not always being identified as selected when the URL
                   contains query strings. This change insures that they are by comparing
                   against the full URL.

    TL-17231       Fixed the display of RPL course completion data after being restored from the recycle bin

                   Added cache cleaning into the course completion restore step to reflect
                   database changes that were not displayed immediately after course
                   restoration.

    TL-17235       Changed the cancellation string when cancelling a Seminar session to be consistent with other occurrences
    TL-17254       Fixed a custom field error for appraisals when goal review question was set up with "Multiple fields" option
    TL-17264       Stopped a mustache template escaping Identity Providers (IDP) URLs

                   Identity Provider URLs that contained query strings were not linking
                   correctly as html entities were being introduced. Removing the escaping
                   within the mustache template fixes this.

    TL-17267       Fixed the resetting of the 'Automatically cancel reservations' checkbox when updating a Seminar
    TL-17295       Re-implemented the toggle class 'collapsed' functionality for site admin navigation
    TL-17344       Added missing closing dl tag when auditing a user's data
    TL-17351       Removed unwanted line breaks in the manage repositories table
    TL-17358       Fixed notification preference override during Totara Connect sync

                   Changes made to a user's notification preferences on a Totara Connect
                   client site will no longer be overridden during sync.

    TL-17366       Cleaned up several small bugs within the site policy tool

                   Several small bugs and coding style cleanups have been made within the site
                   policy tool. None of these affect the behaviour of the tool, but they will
                   remove a few harmless notices when working with custom translations.

    TL-17386       Fixed the syncing of the suspended flag in Totara Connect

                   When users are synced between a Totara Connect server and client, a user's
                   suspended flag is only changed on the client when a previously
                   deleted/suspended user is restored on the server and then re-synced to the
                   client with the "auth_connect/removeuser" configuration setting set to
                   "Suspend internal user"

    TL-17392       Fixed the seminar events report visibility records when Audience-based visibility is enabled

                   When a course had audience-based visibility enabled and the course
                   visibility was set to anything other than "All users", the seminar events
                   report was still displaying the course to users even when they didn't match
                   the visibility criteria. This has been corrected.

    TL-17406       Fixed the site policy page being displayed if the admin was logged in as a learner

                   Previously if "Site policy" was enabled, the admin user could log in as a
                   learner and be able to consent to the policy instead of the actual user.
                   This patch will stop the display of the site policy page if the admin user
                   logs in as a learner.

    TL-17407       Fixed the message bar disappearing for the admin user when site policy is enabled
    TL-17415       Stopped updating calendar entries for cancelled events when updating the seminar information

                   Previously the system re-created the site, course, and user calendar
                   entries when updating seminar information. This patch added validation to
                   calendar updates for cancelled events.

Miscellaneous Moodle fixes:

    TL-16994       MDL-55849: Fixed an issue where reopening a group assignment was creating additional attempts for each group member
    TL-16995       MDL-35849: Added "alert" role HTML attribute to the log in errors

                   This allows screen readers to identify when a user has not logged in
                   correctly

    TL-16996       MDL-60025: Fixed editing a book's chapter did not update timemodified returned by core_course_get_contents
    TL-16997       MDL-59808: Fixed REST simpleserver ignoring the moodlewsrestformat parameter
    TL-16999       MDL-59867: Removed a chance of autocomplete fields using duplicate IDs

                   When there were multiple uses of autocomplete fields, there was a chance
                   that the generated HTML ids were not unique.

    TL-17000       MDL-59399: Advanced settings when adding media to an assignment submission works as expected
    TL-17002       MDL-59929: Improved usability when duplicate email entered during user registration
    TL-17003       MDL-60039: Fixed messaging search areas using 'timecreated' instead of 'timeread' to index search
    TL-17006       MDL-37810: Made sure all roles are displayed in profile and courses if a user has moodle/role:assign capability

                   Users with 'moodle/role:assign' capability now see all roles in user
                   profiles, course participants list and Auto-create group.

    TL-17007       MDL-52131: Made sure question manual comment is respecting comment format in Plain text area editor
    TL-17008       MDL-60105: Fixed global search fatal error when a file in Folder activity is renamed
    TL-17009       MDL-60018: Fixed chatmethod field type in get_chats_by_courses() web services method
    TL-17012       MDL-60167: Fixed hubs registration issues
    TL-17013       MDL-54540: Added allowfullscreen attribute to LTI iFrames to ensure the full screen can be used

                   This change adds attributes to the LTI iframe allowing the content to be
                   viewed in full screen.

    TL-17015       MDL-60121: Fixed enrol plugin backup
    TL-17017       MDL-59645: Fixed Flickr integration
    TL-17019       MDL-59931: Fixed incorrect pagination on Quiz grades results report
    TL-17023       MDL-58790: Replaced a hard coded heading with a language string when editing a quiz
    TL-17025       MDL-60198: Added missing MOODLE_INTERNAL checks in the external functions
    TL-17027       MDL-57228: Fixed error when adding a questions to a quiz with section headings

                   When adding questions to a quiz that has section headings a unique key
                   violation can cause an error to occur if you have a section with a single
                   question in it.

    TL-17028       MDL-60317: Fixed errors in quiz attempts reports
    TL-17030       MDL-60346: Fixed an issue where Solr connection ignored proxy settings
    TL-17032       MDL-60276: Fixed LTI content item so it correctly populates the tool URL when using https
    TL-17033       MDL-60357: Fixed an issue where future modified times of the documents caused search indexing problems
    TL-17034       MDL-59854: Fixed creation of the duplicate forum subscriptions due to the database query race conditions
    TL-17037       MDL-60335: Fixed encoding of non-ASCII site names in blocked hosts
    TL-17038       MDL-60247: Fixed an issue where multilang was not displayed correctly in Random glossary and in HTML block titles
    TL-17040       MDL-60182: Improved location of print icon in glossary in RTL languages
    TL-17041       MDL-60233: Added the use of the s() function to ensure Assignment module web services warnings adhere to the param type PARAM_TEXT
    TL-17042       MDL-58915: Fixed an issue where Solr connection was blocked by cURL restrictions
    TL-17043       MDL-60449: Various language strings improvements in courses and administration
    TL-17044       MDL-60314: Fixed an issue with variable being overridden causing capability not found errors
    TL-17046       MDL-60123: Fixed an issue where assignment grading annotations could not be deselected
    TL-17048       MDL-52653: Fixed increment number of attempts for SCORM 2004 activity

                   Added tracking of 'cmi.completion_status' element that is sent by SCORM
                   2004 activities.

    TL-17050       MDL-60489: Content height changes when using the modal library are now smooth transitions
    TL-17053       MDL-36580: Added encryption of secrets in backup and restore functionality

                   LTI (external tool) activity secret and key are encrypted during backup and
                   decrypted during restore using aes-256-cbc encryption algorithm.
                   Encryption key is stored in the site configuration so backup made with
                   encryption will be restored with lti key and secret on the same site, and
                   without these values on different site.

    TL-17054       MDL-60538: Added new language string in the Lesson module displayed on the final wrong answer
    TL-17055       MDL-60571: Styled "Save and go to next page" as a primary button when manually grading quiz questions
    TL-17057       MDL-51892: Added a proper description of the login errors
    TL-17058       MDL-60535: Improved style of button when adding questions from a question bank to a quiz
    TL-17059       MDL-60162: Fixed an error when downloading quiz attempts reports
    TL-17065       MDL-60360: Improved the help text for the search indicating that changes to the Solr setting requires a full re-index
    TL-17067       MDL-59606: Fixed edge cases in the Quiz reports
    TL-17068       MDL-60377: Made sure text returned by web services is formatted correctly
    TL-17069       MDL-52037: Background of the tooltip for embedded question answer is now the correct size
    TL-17071       MDL-60522: Fixed duplicate tooltips in notifications and messages popovers
    TL-17074       MDL-60607: Fixed message displayed when viewing quiz attempts using separate groups setting

                   This issue occurs when a trainer who is not part of a group is viewing quiz
                   attempts and the "Group Mode" setting is set to "Separate groups". A
                   message was showing "No students enrolled in this course yet", now the
                   message shown is "Sorry, but you need to be part of a group to see this
                   activity". Being part of a group is an existing requirement when separate
                   groups is set.

    TL-17076       MDL-60007: Corrected LTI so delete action without a content type is considered valid.

                   A DELETE operation does not contain any data in the body, and so should not
                   need to have a Content-Type header as no data is sent. However the current
                   LTI Service routing stack will consider a non GET incoming request
                   incorrect if it does not contain a Content-Type. This patch corrects this
                   behaviour.

    TL-17077       MDL-53501: Fixed get_site_info in Webservices failing if usermaxuploadfilesize setting overflows PARAM_INT
    TL-17080       MDL-51945: Fixed update_users web service to stop duplicate emails being sent

                   When updating users using the core_user_update_users webservice duplicate
                   emails for users were being allowed no matter what the "Allow accounts with
                   same email" setting was set to. After this change duplicate emails are only
                   allowed if this setting is turned on.

    TL-17081       MDL-58047: Fixed an issue where sort by last modified (submission) was not sorting as expected in grading of assignments
    TL-17082       MDL-60437: Fixed multilingual HTML block title
    TL-17083       MDL-59858: After closing a modal factory modal, focus goes back to the element that triggered it.
    TL-17084       MDL-60424: Updated the web services upload to allow cross-origin requests (CORS)
    TL-17085       MDL-60671: Switched cron output to use mtrace() function
    TL-17086       MDL-57772: Chat beep doesn't make an audible sound
    TL-17087       MDL-60717: Minor language string improvements in LDAP authentication method
    TL-17088       MDL-60733: Fixed an issue with google_oauth which led to a broken Picasa repository
    TL-17089       MDL-58699: Improved the security of the quiz module while using browser security settings

                   When the "Browser Security" setting is set to "Full screen pop-up with some
                   JavaScript security", the "Attempt quiz" button is no longer visible if a
                   user has JavaScript disabled.

    TL-17092       MDL-60615: Fixed course restore in IMSCC format
    TL-17093       MDL-60550: Added more restrictions in keyword user searches
    TL-17094       MDL-52838: Fixed an undefined variable warning and improved form validation in the Workshop module assessment form
    TL-17095       MDL-60749: Fixed display issue when exporting SCORM interaction report

                   When downloading a SCORM interaction report "&nbsp;" is shown instead of
                   empty string when there is no value.

    TL-17096       MDL-60771: Typecasted scorm score to an integer to avoid debugging error in scorm reports
    TL-17326       MDL-60436: Improved the performance of block loading
    TL-17335       MDL-61269: Set composer license to GPL-3.0-or-later
    TL-17337       MDL-61392: Improved the IPN notifications handling in Paypal enrollment plugin

Contributions:

    * Andrew Davidson at Synergy Learning - TL-17344
    * James Voong from Catalyst - TL-17357
    * Martin Sandberg at Xtractor - TL-17264


Release 11.1 (23rd March 2018):
===============================


Important:

    TL-14114       Added support for Google ReCaptcha v2 (MDL-48501)

                   Google deprecated reCAPTCHA V1 in May 2016 and it will not work for newer
                   sites. reCAPTCHA v1 is no longer supported by Google and continued
                   functionality can not be guaranteed.

    TL-17228       Added description of environment requirements for Totara 12

                   Totara 12 will raise the minimum required version of PostgreSQL from 9.2 to
                   9.4

Security issues:

    TL-17225       Fixed security issues in course restore UI

Improvements:

    TL-9414        Required totara form Checkbox lists are validated in the browser (as opposed to a page reload)
    TL-12393       Added new system role filter for reports using standard user filters
    TL-15003       Improved the performance of the approval authentication queue report
    TL-16157       Improved the layout of progress bars inside the current learning block

                   This will require regeneration of the LESS for themes that use LESS
                   inheritance

    TL-16797       Standardised the use of styling in the details of activity access restrictions

                   When some new activity access restrictions were introduced in Totara 11.0,
                   the display of restriction details in the course was not in bold like
                   existing restrictions. This patch corrects the styling.

    TL-16864       Improved the template of Seminar date/time change notifications to accommodate booked and wait-listed users

                   Clarified Seminar notification messages to specifically say that it is
                   related to the session that you are booked on, or are on the wait-list for.
                   Also removed the iCal invitations/cancellations from the templates of users
                   on the wait-list so that there is no confusion, as previously users who
                   were on the wait-list when the date of a seminar was changed received an
                   email saying that the session you are booked on has changed along with an
                   iCal invitation which was misleading.

    TL-16909       Increased the limit for the defaultid column in hierarchy scale database tables

                   Previously the defaultid column in the comp_scale and goal_scale tables was
                   a smallint, however the column contained the id of a corresponding
                   <type>_scale_values record which was a bigint. It is highly unlikely anyone
                   has encountered this limit, unless there are more than 32,000 scale values
                   on your site, however the defaultid column has been updated to remove any
                   possibility of a conflict.

    TL-16914       Added contextual details to the notification about broken audience rules

                   Additional information about broken rules and rule sets are added to email
                   notifications. This information is similar to what is displayed on
                   audiences "Overview" and "Rule Sets" tabs and contains the broken audience
                   name, the rule set with broken rule, and the internal name of the broken
                   rule.

                   This will be helpful to investigate the cause of the notifications if a
                   rule was fixed before administrator visited the audience pages.

    TL-16921       Converted utc10 Totara form field to use the same date picker that the date time field uses

                   This only affects desktop browsers

    TL-17149       Fixed undefined index for the 'Audience visibility' column in Report Builder when there is no course present
    TL-17232       Made the "Self-registration with approval" authentication type use the standard notification system

                   The "Self-registration with approval" authentication plugin is now using
                   standard notifications instead of alerts, for "unconfirmed request" and
                   "confirmed request awaiting approval" messages. A new notification was also
                   added for "automatically approved request" messages when the "require
                   approval" setting is disabled.

Bug fixes:

    TL-10394       Fixed bad grammar in the contextual help for Seminars > Custom fields > text input
    TL-16549       Cancelling a multi-date session results in notifications that do not include the cancelled date

                   Changed the algorithm of iCal UID generation for seminar event dates. This
                   allows reliable dates to be sent for changed\cancelled notifications with
                   an attached iCal file that would update the existing events in the
                   calendar.

    TL-16555       Fixed email recipients not always being displayed for scheduled reports

                   Previously if you disabled a recipient option (audiences, users, emails)
                   existing items would remain on the scheduled report but not be displayed
                   making it impossible to remove them. Existing items are now displayed, but
                   new items can not be added for disabled recipient options.

    TL-16598       Fixed a problem with suspended users and the "ignore empty fields" setting in HR Import

                   When the deleted setting was set to "Suspend internal user", the "Empty
                   strings are ignored" setting was set and the suspend field in a CSV was
                   empty. It resulted in users becoming unsuspended. The suspended field is
                   now disabled and not imported when the deleted setting is "Suspend internal
                   user".

    TL-16820       Fixed the current learning block using the wrong course URL when enabling audience based visibility
    TL-16833       Added the 'Grades' link back into the 'Course Administration' menu
    TL-16838       Stopped reaggregating competencies using the ANY aggregation rule when the user is already proficient
    TL-16856       Fixed text area user profile fields when using Self-registration with approval plugin

                   Using text area user profile fields on the registration page was stopping
                   the user and site administrator from attempting to approve the account.

    TL-16858       Improved the location of the date time picker icon in the Report builder sidebar

                   This will require regeneration of the LESS for themes that use LESS
                   inheritance

    TL-16865       Fixed the length of the uniquedelimiter string used as separator for the MS SQL GROUP_CONCAT_D aggregate function

                   MS SQL Server custom GROUP_CONCAT_* aggregate functions have issues when
                   the delimeter is more than 4 characters.

                   Some report builder sources used 5 character delimiter "\.|./" which caused
                   display issues in report. To fix it, delimeter was changed to 3 characters
                   sequence: "^|:"

    TL-16878       Fixed the role attribute on notification and message icons

                   Previously the notification and message icons used an invalid "aria-role"
                   HTML attribute. This now uses the correct "role" HTML attribute

    TL-16882       Removed the "allocation of spaces" link when a seminar event is in progress
    TL-16920       Fixed the "show blank date records" option in date filters excluding null values

                   Reports that allow filtering records with blank dates were not being
                   retrieved if the date was null

    TL-16922       Fixed multiple enrolment types being displayed per course in the 'Course Completion' report source

                   The "Enrolment Types" column for the "Course Completion" report source was
                   previously displaying all the enrolment methods the user was enrolled via
                   across the whole site. For example if the user was enrolled in one course
                   via the program enrolment plugin, and in another course via manual
                   enrolment, both records in the report would say both "program" and "Manual
                   enrolment". The column now only shows the appropriate enrolment type for
                   the associated course.

    TL-16925       Fixed the calculation of SCORM display size when the Navigation panel is no longer displayed
    TL-17104       Fixed an error when disposing of left-over temporary tables in MS SQL Server
    TL-17111       Renamed some incorrectly named unit test files
    TL-17115       Fixed the time assigned column for the Record of Learning : Programs report source

                   The time assigned column was previously displaying the data for
                   timestarted, this patch has three main parts:

                   1) Changes the default header of the current column to "Time started" to be
                   consistent with what it displays
                   2) Adds a new column "Time assigned" to the report source that displays the
                   correct data
                   3) Switches the default column for the embedded report to the new "Time
                   assigned" column

                   This means any new sites will create the embedded report with the new
                   column, but any existing sites that want to display "Time assigned" instead
                   of "Time started" will have to go to Site administration > Reports > Report
                   builder > Manage embedded report and restore default settings for the
                   Record of Learning : Programs embedded report, or manually edit the columns
                   for the report.

    TL-17116       Firefox now shows the focused item in the Atto editor toolbar

                   When using Chrome, Edge and IE11, there is an indication of which toolbar
                   item is focused when using keyboard navigation in the toolbar. This issue
                   adds an indication to Firefox as well.

    TL-17207       Fixed a missing include in user/lib.php for the report table block
    TL-17221       Allowed non-standard JS files to be minified through grunt rather than manually
    TL-17229       Fixed the display of the page while modifying Site administrator role assignments

                   This page had invalid HTML causing all form controls to be in a single
                   column, instead of an add/remove 3 column

    TL-17230       Added a missing file requirement to the company goal userdata items
    TL-17234       Fixed an error while counting the userdata for a quicklinks block relating to a deleted user

                   When a user is deleted their records are removed from the context table,
                   causing the lookup being done by this function to throw a database error.

    TL-17259       Moved the previously hard-coded string 'Add tile' into a language string for Featured links templates

                   There was a hard-coded string in the main template in the Featured links
                   block, this has been shifted into the language strings file so that it can
                   now be translated and customised.

Contributions:

    * Ben Lobo at Kineo UK - TL-16549
    * Eugene Venter at Catalyst NZ - TL-16922
    * Russell England at Kineo USA - TL-17149


Release 11.0 (12th March 2018):
==============================

Key:           +   Totara 11.0 only

Important:

    TL-9352        New site registration form

                   In this release we have added a site registration page under Site
                   administration > Totara registration. Users with the 'site:config'
                   capability will be redirected to the page after upgrade until registration
                   has been completed.

                   Please ensure you have the registration code available for each site before
                   you upgrade. Partners can obtain the registration code for their customers'
                   sites via the Subscription Portal. Direct subscribers will receive their
                   registration code directly from Totara Learning.

                   For more information see the help documentation:

                   https://help.totaralearning.com/display/TLE/Totara+registration

    TL-16313       Release packages are now provided through https://subscriptions.totara.community/

                   Release packages are no longer being provided through FetchApp, and can now
                   be accessed through our new Subscription system at
                   https://subscriptions.totara.community/.

                   If you experience any problems accessing packages through this system
                   please open a support request and let us know.

                   Please note that SHA1 checksums for previous Evergreen releases will be
                   different from those provided in the changelog notes at the time of
                   release.
                   The reason for this is that we changed the name of the root directory
                   within the package archives to ensure it is consistent across all products.

    TL-17166       Added support for March 1, 2018 PostgreSQL releases

                   PostgreSQL 10.3, 9.6.8, 9.5.12, 9.4.17 and 9.3.22 which were released 1st
                   March 2018 were found to not be compatible with Totara Learn due to the way
                   in which indexes were read by the PostgreSQL driver in Learn.
                   The method for reading indexes has been updated to ensure that Totara Learn
                   is compatible with PostgreSQL.

                   If you have upgraded PostgreSQL or are planning to you will need to upgrade
                   Totara Learn at the same time.

    TL-16198       Fixed compatibility issues with MySQL 8.0.3RC
    TL-16937   +   The following entry concerns functionality, plugins, and settings that are going
                   to be deprecated or removed in Totara Learn 12.0. If you are using any of the items
                   on this list please get in touch with us now.

                     * MNET server and client functionality will be deprecated in Totara Learn 12, and removed after its release.
                     * The following authentication plugins will be removed in Totara Learn 12.
                       If anyone is using them and wishes to continue doing so they will need
                       to re-install the plugins during upgrade.
                         * FirstClass server authentication
                         * IMAP authentication
                         * None authentication
                         * PAM authentication
                     * The email signup authentication plugin will be deprecated in Totara Learn 12.
                       All sites using this plugin should start using the Approved authentication
                       plugin instead. This plugin provides the same functionality, more features
                       that can be turned on, is better performing, and is more secure.
                     * The assignment upgrade tool will be removed in Totara Learn 12.
                       Those using the old assignment module can continue to do so, however there
                       will be no migration path from the old assignment module to the new assignment
                       module after Totara Learn 11.
                     * The InnoDB migration tool will be removed in Totara Learn 12.
                       This tool is no longer necessary as all sites using MySQL are required to be
                       running InnoDB on Totara 9 and above already.
                     * The slasharguments admin setting which was disabled in Totara Learn 10 will be removed in Totara Learn 12.
                       This includes the removal of support for file URL rewriting by the webserver.
                     * The loginhttps admin setting which was disabled in Totara Learn 10 will be removed in Totara Learn 12.
                     * The trusttext admin setting and accompanying functionality will be removed in Totara Learn 12.
                       It is commonly misunderstood and its use introduces security risks that cannot be mitigated.
                     * Previously deprecated config.html files within authentication plugins will no longer be supported
                       and uses of them will be cleaned up.
                     * The Portfolio functionality and all accompanying portfolio plugins will be deprecated in Totara Learn 12
                       and removed in a future version.

                   In addition the following system requirement changes will come in to effect for Totara Learn 12.

                     * 64bit PHP will become a recommendation and will be checked during installation and upgrade.
                       Those running 32bit versions of PHP will be shown a warning.
                     * PostgreSQL 9.4 will be required for Totara Learn 12.
                       Those running earlier versions will be required to upgrade.

New features:

    TL-9004    +   New report source: scheduled reports

                   This new report source provides details about existing scheduled reports,
                   their frequencies and recipients.

                   A new capability "totara/reportbuilder:managescheduledreports" has been
                   defined to allow people to edit and delete scheduled reports (NB: the
                   scheduling and recipients, not the linked reports themselves). Note that
                   users with the new capability should also be given the
                   "moodle/cohort:view" and "moodle/user:viewdetails" capabilities so that
                   they can add audiences and individuals as recipients of scheduled reports.

    TL-16589   +   Added new progress wizard form component

                   The progress wizard is a new form grouping which allows large forms to be broken
                   down into smaller stages providing an improved user journey. Only the current
                   stage is displayed but there is a visual indication of how many stages there are
                   in total and where the current stage sits within the journey. The user can always
                   navigate back to any previously completed stage by interacting with the wizard.
                   It is also optional if the user is allowed to skip stages or if they have to
                   complete them in the set order.

    TL-16433   +   Added tool to manage terms and conditions and obtain user consent

                   In order to facilitate GDPR subscriber compliance, a new admin tool is now
                   available that allows the site administrator to create, edit,
                   review/preview and delete terms and conditions.
                   Each term and condition can have one or more consent related user
                   confirmation which may or may not be required.

                   The tool is not enabled by default, but can be enabled through
                   the "enablesitepolicies" configuration setting.

                   If enabled, users will be required to view and consent to any current terms
                   and conditions that they have not viewed and consented to before.
                   If the user doesn't accept all required terms and conditions they will be
                   logged out.

    TL-16747   +   Added the user data management plugin

                   This plugin allows users and administrators to manage users' data. A new
                   collection of links is located under "Site administration -> Users -> User
                   data management". Here you can manage global user data settings, configure
                   purge and export profiles, see logs of purges and exports that have been
                   scheduled or performed, and manage deleted users.

                   Note that deleted users are no longer listed under "Site administration ->
                   Users -> Accounts -> Browse list of users". To manage deleted users
                   (including undelete), you need to go to "Site administration -> Users ->
                   User data management -> Deleted user accounts".

                   Purge profiles can be configured by administrators, and allow them to
                   specify which data will be deleted. The purge profiles can be applied to
                   users, deleting the data. Purge profiles can be applied to users manually.
                   Users can also be configured to have a specific purge profile automatically
                   applied on the condition that they are suspended or deleted, and site
                   defaults for these actions can also be configured. Note that existing
                   behaviour when users are suspended or deleted is not affected - the data
                   listed on the delete user confirmation page will still be deleted,
                   regardless of any purge profile which might apply to the user.

                   Export profiles can be configured by administrators, and allow them to
                   specify which data can be exported. When granted
                   the "totara/userdata:exportself" capability, users will then be able to
                   run an export of their own data, which will create a downloadable file
                   containing the specified data. Export must first be enabled in "Site
                   administration -> Users -> User data management -> Settings".

                   This new feature provides sites with tools which will support them becoming
                   GDPR compliant. By configuring purge profiles and purging data, sites can
                   comply to GDPR rules which indicate what data must be removed and
                   which must be retained, given their particular circumstances. By
                   configuring export profiles and giving users the capability to perform the
                   exports, sites can comply to GDPR rules which indicate what data must be
                   made available to users, and exclude data which is inappropriate given
                   their particular circumstances.

                   This initial release of the user data plugin contains many user data items
                   (which each specify one type of data which can be deleted or exported),
                   but is not a comprehensive collection. The sample of user data items
                   shipped with this version, along with the core user data system, will
                   provide third party developers with examples to start developing their own
                   user data items. More user data items will be released in this branch over
                   the next few releases. The intention is to provide user data items to
                   allow purge and export of all user data which might be required to be
                   deleted or exported to obtain GDPR compliance, before the GDPR rules come
                   into effect.
                   For more information on the technical implementation of user data purge and
                   export see
                   https://help.totaralearning.com/display/DEV/User+data+developer+documentation

Report Builder improvements:

    TL-7553        Improved Report Builder exports to use column headers more compatible with Microsoft Excel CSV files
    TL-11305   +   Creating/modifying/deleting scheduled reports now generate events

                   New event classes are: scheduled_report_created, scheduled_report_updated
                   and scheduled_report_deleted.

                   These events are also viewable in the system logs under
                   site administration > reports > logs.

    TL-14936       Added a report setting to control the minimum allowed frequency for scheduled reports

                   The new setting "Minimum scheduled report frequency" on the Site administration > Reports >
                   Report builder > General settings page allows you to select the minimum frequency for
                   scheduled reports, the current options are:
                     * Every X minutes
                     * Every X hours
                     * Daily
                     * Weekly
                     * Monthly

                   For example if you selected "Daily" as the minimum, then users setting up or editing scheduled
                   reports would only see that option and the less frequent options (i.e. weekly and monthly).

    TL-15027   +   Added a new capability to control who can create scheduled reports

                   There is a new "totara/reportbuilder:createscheduledreports" capability
                   that allows a user to create scheduled reports. If a user does not have
                   this capability, they will not see the "Scheduled Reports" section (ie with
                   the "Create scheduled report" button) when they go to the "Reports" page
                   via the Totara menubar.

                   Note the capability is separate and NOT related to the
                   "totara/reportbuilder:managescheduledreports" capability; that capability
                   allows users to see, edit or delete all scheduled reports in the system.

    TL-15895   +   Added the 'Send to self' option to Email settings for Scheduled reports
    TL-15896   +   Added a Report Builder administration setting to control what scheduled report email options are available
    TL-15962   +   Removed disabled embedded reports from embedded reports list.

                   Some functional areas can be disabled, for example, Record of Learning. If
                   they contain embedded reports, these reports will no longer be listed in
                   the main embedded report list.
    TL-16241       Fixed breadcrumb trail when viewing a user's completion report
    TL-16494       Improved embedded reports test coverage
    TL-16624       Improved exported course progress values within two Report Builder sources

                   The 'Record of Learning: Courses' and 'Course Completion' report sources
                   have been updated to enable a user's progress towards course completion to
                   be exported as a percentage.
    TL-16653       Report builder now shows an empty graph instead of an error message when zero values are returned
    TL-16684   +   Removed database queries from rb_display functions in cohort association report sources
    TL-16690       Added a hook for cache invalidation in Report graph block
    TL-16866   +   New Report builder graph setting "remove_empty_series"

                   Note that this setting works for orientation with data series in columns
                   only. It is also not compatible with pie charts.

    TL-16910   +   Unused group_concat emulation was removed from Report Builder installation code

General improvements:

    TL-17098   +   Improved the privacy, security and usability of the course backup/restore process
    TL-1512    +   Changed Google Fusion export to open in a new window
    TL-9277        Added additional options when selecting the maximum Feedback activity reminder time
    TL-11296       Added accessible text when creating/editing profile fields and categories
    TL-12650       Removed HTML table when viewing the print book page
    TL-12805   +   Archived Seminar attendance within certifications no longer prevents future signups

                   Previously, multiple attendance needed to be turned on in Seminars
                   contained within certifications. This is no longer required, and the
                   warning has been removed. Note that users will now be able to sign up to
                   Seminars after course reset (when the recertification window opens) even if
                   the Seminar is not a requirement for course completion and multiple
                   attendance is turned off.

    TL-14745   +   The recent activity block and recent activity page now show the same activity
    TL-16551   +   Converted the activity restriction icons into flex icons
    TL-14963   +   Added an Organisation assignment restriction for conditional activity access

                   Access to an activity can now be restricted based on the Organisation that
                   a learner has been assigned to via Job Assignments.

    TL-14964   +   Added a Position assignment restriction for conditional activity access

                   Access to an activity can now be restricted based on the Position that a
                   learner has been assigned to via Job Assignments.

    TL-14965   +   Added an Audience membership restriction for conditional activity access

                   Access to an activity can now be restricted based on the Audiences that a
                   learner has membership in.

    TL-15091   +   Added a language restriction for conditional activity access

                   Access to an activity can now be restricted based on the user's language.

    TL-15044   +   Updated Menu type custom fields to display a hyphen when the field is locked and empty
    TL-8723    +   Updated text area custom fields to display a hyphen when the field is locked and empty
    TL-15061   +   Improved the styling of delete and combine tags buttons

                   Previously these were using the Bootstrap 2 CSS class names for buttons, they have now
                   been updated to the Bootstrap 3 CSS class names.

    TL-15832   +   Updated xpath when matching against HTML tables using Behat to allow non-exact matches
    TL-15835       Made some minor improvements to program and certification completion editors

                   Changes included:
                    * Improved formatting of date strings in the transaction interface and logs.
                    * Fixed some inaccurate error messages when faults might occur.
                    * The program completion editor will now correctly default to the
                      "invalid" state when there is a problem with the record.

    TL-15856   +   Improved the styling of the modal JavaScript library

                   These were previously styled similar to that of the YUI dialogues, they are
                   now styled similar to Bootstrap 3 modals.

    TL-15871   +   Force users to complete required user profile fields upon login

                   The users will be forced during the login to complete any user profile
                   fields that have been set as required and have not yet been completed for
                   that user.

    TL-15907       Improved how evidence custom field data is saved when importing completion history
    TL-15913       Improved the display of the progress bar component and improved the quality of the CSS
    TL-15920       Activities required for course completion are now shown in the progress bar popover

                   When a user clicks on the progress bar for a specific course, the activities
                   required to complete the course are now listed in a popover.

    TL-15992       A warning message is now shown when a Quiz may require more random questions than there are questions available

                   When creating or editing a quiz, warning messages are now shown when adding
                   random questions from categories that don't contain enough questions. It is
                   only a warning to highlight the risk and doesn't prevent the course
                   administrator from creating the quiz.

                   If a learner attempts to take a quiz with insufficient questions,
                   the system behaves as before.

    TL-15995   +   Improved the indexes on the Custom Fields *_info_data tables to ensure best performance and create consistency
    TL-16007   +   Converted warning messages in HR Import to use the notification API
    TL-16069       Improved the alignment of question bank table headings
    TL-16137   +   The Background image for tiles in the featured links block can now be set to fill or fit in the tile
    TL-16138   +   The Featured links block now allows the tile shape to be configured (portrait, landscape and full width)
    TL-16141   +   Added Program and Certification tiles to the Featured links block.
    TL-16142   +   Added Progress bars to Course Tiles in the Featured links block
    TL-16152   +   Improved the layout of the Recent Learning block by removing a layout table
    TL-16154       Improved the CSS of the Last Course Accessed block, increasing the width of the progress bar

                   This will require CSS to be regenerated for themes that use LESS inheritance

    TL-16170       Externally accessible badge check now uses the correct notify_warning template
    TL-16176   +   Converted the maintenance countdown timer to use the correct notification template and AMD module
    TL-16207   +   Removed support for obsolete "mssql" database driver

                   Totara Learn 11 requires PHP 7.1, the old MSSQL driver is supported in PHP
                   5.6 and below only, It is not available in PHP 7.1.
                   The official sqlsrv driver is available for PHP 7.1, and is supported on
                   all operating systems.
                   Anyone using the old MSSQL driver should upgrade to the sqlsrv driver when
                   they upgrade their server environment.

    TL-16209   +   Removed fieldset headers from 360° Feedback questions
    TL-16252   +   Added a new setting that allows persistent logins

                   When enabled then a "Remember login" option will appear on the login page.

                   Any user logging in can check this box to enable a persistent login,
                   meaning that they won't get timed-out and have to log in again.

    TL-16256       Allowed appraisal messages to be set to "0 days" before or after event

                   Some immediate appraisal messages were causing performance issues when
                   sending to a lot of users.
                   This improvement allows you to set almost immediate messages that will send
                   on the next cron run after the action was triggered to avoid any
                   performance hits. The appraisal closure messages have also been changed to
                   work this way since they don't have any scheduling options.

    TL-16260       Invalid request to force password change is automatically deleted if auth plugin does not support changing of passwords
    TL-16372       Added support for utf8mb4 collations with full Unicode support
    TL-16373       Added screen reader text to the block actions menu
    TL-16380   +   Hide Competencies in Learning plans when the Framework they are within is hidden

                   When competencies are individually hidden via the competency management page, they
                   are automatically hidden in any learning plans. This change ensures that when
                   competency frameworks are hidden, all of the competencies within the framework are
                   also hidden within learning plans.

    TL-16427       Added more information about the delay before items appear in the recycle bin

                   * A message is displayed in the deletion confirmation dialog.
                   * A message is displayed when viewing the recycle bin if there are
                   activities or resources that are yet to be processed.

    TL-16432       Course completion history records are now included in course backups and can be restored
    TL-16441   +   Fixed signup information being displayed in attendees pages
    TL-16452   +   Dashboard, course and report name fields have been increased up to 1333 characters
    TL-16478   +   Removed unnecessary CSS in Totara plan
    TL-16479       Fixed inconsistent use of terminology in Seminars
    TL-16485   +   Converted Hierarchies CSS to LESS
    TL-16383   +   Converted Dynamic audience CSS to LESS

                   This will require CSS to be regenerated for themes that use LESS
                   inheritance

    TL-16487   +   Standardised HTML in the Statistics block
    TL-16488   +   Set alert notifications for Totara Messages to enabled by default
    TL-16489   +   Standardised HTML in the Alerts block
    TL-16497   +   Updated the Quicklinks block to use standard HTML elements
    TL-16503   +   Improved the HTML markup consistency within the Tasks block
    TL-16505   +   Updated the Report table block to use LESS instead of CSS
    TL-16506   +   Updated the Report graph block to use LESS instead of CSS
    TL-16508   +   Standardised HTML and CSS in the my learning navigation block
    TL-16509   +   Dashboard block now uses LESS instead of CSS and standard HTML
    TL-16520   +   Added tags functionality to programs and certifications
    TL-16524   +   Fixed redirect after a user confirms new account creation via email-based self registration

                   If a user clicks a link to a page within Totara but is not logged in they
                   are redirected to the login page. If a user then creates a new account via
                   email-based self registration they are redirected to the home page after
                   confirming account. This patch ensures they are redirected back to the page
                   they originally requested.

    TL-16622       Mustache string helper now accepts a variable for the string key

                   Previously when using the string helper in a mustache template, the key for
                   the string needed to be known when creating the template. This improvement
                   allows the key for the string to be added as a parameter for the template.

    TL-16627       A user's current course completion record can now be deleted

                   Using the course completion editor, it is now possible to delete a user's
                   current course completion record. This is only possible if the user is no
                   longer assigned to the course.

    TL-16632       Admin categories are no longer links by default

                   If you want to change this you can do so by searching for
                   linkadmincategories in the site administration block.

    TL-16651   +   Added support for context variables in modal library
    TL-16694       All SCORM reports were altered to use recommended enrolment subquery for listing of users

                   Please note this patch may change the results of scorm reports, only
                   enrolled users with mod/scorm:savetrack capability are now displayed there.

    TL-16696   +   Added email footer string with context URL to alert messages

                   Some system alerts were missing URL to page with relevant details of the
                   event. Now they are added in the message footer (when message is displayed
                   in HTML format).

    TL-16746   +   Added support for help icons next to checkboxes options
    TL-16867   +   Added password expiration settings to accounts created via Self-registration with approval
    TL-16919   +   Added profile locking options to "Self-registration with approval" plugin

Performance improvements:

    TL-14071   +   Replaced calls to 'dirname' with '__DIR__' to improve performance
    TL-16061       Fixed a problem where duplicating a module caused the course cache to be rebuilt twice
    TL-16161       Reduced load times for the course and category management page when using audience visibility
    TL-16189       Moved audience learning plan creation from immediate execution onto adhoc task.

                   Before this change, when learning plans were created via an audience, they
                   would be created immediately. This change moves the plan creation to an
                   adhoc task that is executed on the next cron run. This reduces any risk of
                   database problems and the task failing.

    TL-16314       Wrapped the Report builder create cache query in a transaction to relax locks on tables during cache regeneration in MySQL

                   Report Builder uses CREATE TABLE SELECT query to database in order to
                   generate cache which might take long time to execute for big data sets.

                   In MySQL this query by default is executed in REPEATABLE READ isolation
                   level and might lock certain tables included in the query. This leads to
                   reduced performance, timeouts, and deadlocks of other areas that use same
                   tables.

                   To improve performance and avoid deadlocks this query is now wrapped into
                   transaction, which will set READ COMMITTED isolation level and relax locks
                   during cache generation.

                   This will have no effect in other database engines.

    TL-16437       Changed column type from text to char in block_totara_featured_links_tiles table

API changes:

    TL-15798   +   Report Builder filters can now have default values

                   Default values for a filter are now an option when defining embedded
                   reports or when defining the default reports through the
                   define_defaultfilters method.

                   The only thing that needs to be added is the defaultvalue option as an
                   array with the corresponding filter options.
                   Please note that values are saved when creating the reports which usually
                   happens at installation time.

                   For a real example please check the "rb_system_browse_users_embedded"
                   embedded report which "User Status" filter is now set to "Active users
                   only".

    TL-16217   +   Removed deprecated custom menu functionality

                   Please use Site administration > Appearance > Main menu instead

    TL-16378   +   Hub functionality has been deprecated

                   Community hub functionality has been deprecated in this release, and will
                   be removed altogether in the next major release.

                   The links to the community hub registration and the publish course page
                   have been removed. The pages can still be accessed directly
                   ('/admin/registration/index.php' and
                   '/course/publish/index.php?id=COURSEID'). The block 'Community finder' will
                   still be visible after upgrading an existing Totara Learn 11 installation.
                   On a fresh installation however the block will be deactivated by default.
                   There is the option to reactivate the block in the administration
                   interface.

    TL-16448   +   Report Builder transformation display names are now collected through a method

                   Previously Report Builder transformations were expected to have a string
                   within totara_core.
                   The string used for transformations is now fetched through a method that
                   can be overridden by the transformation.
                   This allows strings to be co-located with their translations, and no longer
                   requires non-core developers to make core changes when introducing
                   transformations.

    TL-16745   +   Imported Font Awesome 4.7.0
    TL-16525       Fixed linting errors when copying Basis to create another theme

                   Themes that were copied prior to this issue being resolved will need to
                   adjust both theme/<themename>/bootswatch/bootswatch.less
                   and theme/<themename>/bootswatch/variables.less to conform with lint rules
                   (these have been updated in basis to pass lint rules)

    TL-16677   +   Removed deprecated rb_display_* functions

Contributions:

    * Barry Oosthuizen at Learning Pool - TL-9277
    * Dmitrii Metelkin at Catalyst AU - TL-16448
    * Eugene Venter at Catalyst NZ - TL-16524, TL-16696

*/
