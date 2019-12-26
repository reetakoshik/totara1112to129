@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Sign up to a seminar
  In order to attend a seminar
  As a student
  I need to sign up to a seminar session

  # This background requires JS as such it has been added to the Feature tags.
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Terry1    | Teacher1 | teacher1@example.com |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Label" to section "1" and I fill the form with:
      | Label text | Course view page |
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I set the following fields to these values:
      | capacity              | 1    |
    And I press "Save changes"
    And I log out

  @totara_customfield
  Scenario: Language filter should work on custom fields in seminar when an admin looks at the note in the seminar attendees list popup
    Given I log in as "admin"

    # Enabling multi-language filters for headings and content.
    And I navigate to "Manage filters" node in "Site administration > Plugins > Filters"
    And I set the field with xpath "//table[@id='filterssetting']//form[@id='activemultilang']//select[@name='newstate']" to "1"
    And I set the field with xpath "//table[@id='filterssetting']//form[@id='applytomultilang']//select[@name='stringstoo']" to "1"

    # Add sign-up custom fields.
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Sign-up" "link"
    When I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname           | <span lang="de" class="multilang">German content</span><span lang="en" class="multilang">English content</span>  |
      | shortname          | sampleinput                                                                                                      |
    And I press "Save changes"
    Then I should see "English content"
    And I should not see "German"
    Then I log out

    # Signing up for an event as a student.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    When I follow "Sign-up"
    Then I should see "English content"
    And I should not see "German"
    And I set the following fields to these values:
      | customfield_signupnote              | Note               |
      | customfield_sampleinput             | Sample value       |
    And I press "Sign-up"
    And I log out

    # As the trainer confirm I can see the details of the signup.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    And "Sam1 Student1" row "English content" column of "facetoface_sessions" table should contain "Sample value"
    When I click on ".attendee-add-note" "css_element"
    And I wait "1" seconds
    Then I should not see "German"
    And I should see "English content"
    And I click on ".closebutton" "css_element"
    And I log out

  Scenario: Sign up to a session and unable to sign up to a full session from the course page
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your request was accepted"
    # Check the user is back on the course page.
    And I should see "Course view page"
    And I should not see "All events in Test seminar name"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should not see "Sign-up"

  Scenario: Sign up to a session and unable to sign up to a full session for within the activity
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Test seminar name"
    And I follow "Test seminar name"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your request was accepted"
    # Check the user is back on the all events page.
    And I should not see "Course view page"
    And I should see "All events in Test seminar name"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should not see "Sign-up"

  Scenario: Sign up with note and manage it by Editing Teacher
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I set the following fields to these values:
     | Requests for session organiser | My test |
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Attendees"
    When I click on "Edit" "link" in the "Sam1" "table_row"
    Then I should see "Sam1 Student1 - update note"

  @totara_customfield
  Scenario: Sign up with note and ensure that other reports do not have manage button
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I set the following fields to these values:
     | Requests for session organiser | My test |
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out

    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Other sign-ups   |
      | Source      | Seminar Sign-ups |
    And I press "Create report"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "All sign up custom fields"
    And I press "Add"
    And I press "Save changes"
    And I click on "Reports" in the totara menu
    When I click on "Other sign-ups" "link"
    Then I should not see "edit" in the "Sam1 Student1" "table_row"

  @totara_customfield
  Scenario: Sign up and cancellation with custom field instances
    When I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"

    # Add signup custom fields.
    And I click on "Sign-up" "link"

    # Add a checkbox
    And I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | fullname  | Signup checkbox |
      | shortname | signupcheckbox |
    And I press "Save changes"
    Then I should see "Signup checkbox"

    # Add a date/time
    When I set the field "datatype" to "Date/time"
    And I set the following fields to these values:
      | fullname  | Signup datetime |
      | shortname | signupdatetime |
    And I press "Save changes"
    Then I should see "Signup datetime"

    # Add a file.
    When I set the field "datatype" to "File"
    And I set the following fields to these values:
      | fullname  | Signup file |
      | shortname | signupfile |
    And I press "Save changes"
    Then I should see "Signup file"

    # Add a location
    When I set the field "datatype" to "Location"
    And I set the following fields to these values:
      | fullname  | Signup location |
      | shortname | signuplocation |
    And I press "Save changes"
    Then I should see "Signup location"

    # Add a menu
    When I set the field "datatype" to "Menu of choices"
    And I set the following fields to these values:
      | fullname    | Signup menu |
      | shortname   | signupmenu |
      | defaultdata | Ja         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Ja
      Nein
      """
    And I press "Save changes"
    Then I should see "Signup menu"

    # Add a multi-select
    When I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | fullname                   | Signup multi |
      | shortname                  | signupmulti |
      | multiselectitem[0][option] | Aye   |
      | multiselectitem[1][option] | Nay   |
    And I press "Save changes"
    Then I should see "Signup multi"

    # Add a textarea
    When I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname           | Signup textarea |
      | shortname          | signuptextarea |
    And I press "Save changes"
    Then I should see "Signup textarea"

    # Add a text input
    When I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname           | Signup input |
      | shortname          | signupinput |
    And I press "Save changes"
    Then I should see "Signup input"

    # Add a URL
    When I set the field "datatype" to "URL"
    And I set the following fields to these values:
      | fullname           | Signup URL |
      | shortname          | signupurl |
    And I press "Save changes"
    Then I should see "Signup URL"
    And I should see "Signup input"
    And I should see "Signup textarea"
    And I should see "Signup menu"
    And I should see "Signup location"
    And I should see "Signup file"
    And I should see "Signup datetime"
    And I should see "Signup checkbox"

    # Add signup cancellation custom fields.
    When I click on "User cancellation" "link"
    # Add a checkbox
    And I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | fullname  | User cancellation checkbox |
      | shortname | usercancellationcheckbox |
    And I press "Save changes"
    Then I should see "User cancellation checkbox"

    # Add a date/time
    When I set the field "datatype" to "Date/time"
    And I set the following fields to these values:
      | fullname  | User cancellation datetime |
      | shortname | usercancellationdatetime |
    And I press "Save changes"
    Then I should see "User cancellation datetime"

    # Add a file.
    When I set the field "datatype" to "File"
    And I set the following fields to these values:
      | fullname  | User cancellation file |
      | shortname | usercancellationfile |
    And I press "Save changes"
    Then I should see "User cancellation file"

    # Add a location
    When I set the field "datatype" to "Location"
    And I set the following fields to these values:
      | fullname  | User cancellation location |
      | shortname | usercancellationlocation |
    And I press "Save changes"
    Then I should see "User cancellation location"

    # Add a menu
    When I set the field "datatype" to "Menu of choices"
    And I set the following fields to these values:
      | fullname    | User cancellation menu |
      | shortname   | usercancellationmenu |
      | defaultdata | Ja         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Ja
      Nein
      """
    And I press "Save changes"
    Then I should see "User cancellation menu"

    # Add a multi-select
    When I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | fullname                   | User cancellation multi |
      | shortname                  | usercancellationmulti |
      | multiselectitem[0][option] | Aye   |
      | multiselectitem[1][option] | Nay   |
    And I press "Save changes"
    Then I should see "User cancellation multi"

    # Add a textarea
    When I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname           | User cancellation textarea |
      | shortname          | usercancellationtextarea |
    And I press "Save changes"
    Then I should see "User cancellation textarea"

    # Add a text input
    When I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname           | User cancellation input |
      | shortname          | usercancellationinput |
    And I press "Save changes"
    Then I should see "User cancellation input"

    # Add a URL
    When I set the field "datatype" to "URL"
    And I set the following fields to these values:
      | fullname           | User cancellation URL |
      | shortname          | usercancellationurl |
    And I press "Save changes"
    Then I should see "User cancellation URL"
    And I should see "User cancellation input"
    And I should see "User cancellation textarea"
    And I should see "User cancellation menu"
    And I should see "User cancellation location"
    And I should see "User cancellation file"
    And I should see "User cancellation datetime"
    And I should see "User cancellation checkbox"

    When I log out
    And I log in as "student1"

    # Add images to the private files block to use later
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "mod/facetoface/tests/fixtures/test.jpg" file to "Files" filemanager
    And I upload "mod/facetoface/tests/fixtures/leaves-green.png" file to "Files" filemanager
    Then I should see "test.jpg"
    And I should see "leaves-green.png"

    # As the user signup.
    When I am on "Course 1" course homepage
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I set the following fields to these values:
      | customfield_signupcheckbox          | 1                  |
      | customfield_signupdatetime[enabled] | 1                  |
      | customfield_signupdatetime[day]     | 1                  |
      | customfield_signupdatetime[month]   | December           |
      | customfield_signupdatetime[year]    | 2030               |
      | customfield_signupmenu              | Nein               |
      | customfield_signupmulti[0]          | 1                  |
      | customfield_signupmulti[1]          | 1                  |
      | customfield_signupinput             | hi                 |
      | customfield_signupurl[url]          | http://example.org |

    # Add a file to the file custom field.
    And I click on "//div[@id='fitem_id_customfield_signupfile_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "test.jpg" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Image in the textarea custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_signuptextarea_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I set the field "Describe this image for someone who cannot see it" to "Green leaves on customfield text area"
    And I click on "Save image" "button"
    And I press "Sign-up"
    Then I should see "Your request was accepted"

    # As the trainer confirm I can see the details of the signup.
    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    Then "Sam1 Student1" row "Signup URL" column of "facetoface_sessions" table should contain "http://example.org"
    And "Sam1 Student1" row "Signup checkbox" column of "facetoface_sessions" table should contain "Yes"
    And "Sam1 Student1" row "Signup file" column of "facetoface_sessions" table should contain "test.jpg"
    And "Sam1 Student1" row "Signup menu" column of "facetoface_sessions" table should contain "Nein"
    And "Sam1 Student1" row "Signup datetime" column of "facetoface_sessions" table should contain "1 Dec 2030"
    And "Sam1 Student1" row "Signup multi (text)" column of "facetoface_sessions" table should contain "Aye, Nay"
    And "Sam1 Student1" row "Signup input" column of "facetoface_sessions" table should contain "hi"
    And I should see the "Green leaves on customfield text area" image in the "//table[@id='facetoface_sessions']/tbody/tr" "xpath_element"
    And I should see image with alt text "Green leaves on customfield text area"

    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Cancel booking"
    And I set the following fields to these values:
      | User cancellation checkbox                    | 1                    |
      | customfield_usercancellationdatetime[enabled] | 1                    |
      | customfield_usercancellationdatetime[day]     | 15                   |
      | customfield_usercancellationdatetime[month]   | October              |
      | customfield_usercancellationdatetime[year]    | 2020                 |
      | User cancellation menu                        | Ja                   |
      | customfield_usercancellationmulti[1]          | 1                    |
      | User cancellation input                       | Monkey               |
      | customfield_usercancellationurl[url]          | http://totaralms.com |
    # Add a file to the file custom field.
    And I click on "//div[@id='fitem_id_customfield_usercancellationfile_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "test.jpg" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Image in the textarea custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_usercancellationtextarea_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I set the field "Describe this image for someone who cannot see it" to "Green leaves on customfield text area"
    And I click on "Save image" "button"
    And I press "Yes"
    Then I should see "Your booking has been cancelled."

    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I follow "Cancellations"
    And I follow "Show cancellation reason"
    Then I should see "15 October 2020" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "test.jpg" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "Ja" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "Nay" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "Monkey" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see "http://totaralms.com" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see the "Green leaves on customfield text area" image in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I should see image with alt text "Green leaves on customfield text area"

  Scenario: bulk adding and removing attendees saves custom field data for all users
    When I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"

    # Add a signup custom field.
    And I click on "Sign-up" "link"
    When I set the field "datatype" to "Menu of choices"
    And I set the following fields to these values:
      | fullname           | Signup input |
      | shortname          | signupinput  |
      | defaultdata        | Bananas      |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Apples
      Bananas
      Carrots
      Dates
      """
    And I press "Save changes"
    Then I should see "Signup input"

    # Add a cancellation custom fields.
    When I click on "User cancellation" "link"
    When I set the field "datatype" to "Menu of choices"
    And I set the following fields to these values:
      | fullname    | User cancellation input |
      | shortname   | cancellationmenu        |
      | defaultdata | Two                     |
    And I set the field "Menu options (one per line)" to multiline:
      """
      One
      Two
      Three
      Four
      """
    And I press "Save changes"
    Then I should see "User cancellation input"

    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I set the field "menuf2f-actions" to "Add users"
    And I wait "1" seconds
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I set the following fields to these values:
      | Signup input | Apples |
    And I press "Confirm"
    Then I should see "Sam1 Student1" in the "#facetoface_sessions" "css_element"
    And I should see "Sam2 Student2" in the "#facetoface_sessions" "css_element"
    And I should see "Sam3 Student3" in the "#facetoface_sessions" "css_element"

    When I set the field "menuf2f-actions" to "Remove users"
    And I wait "1" seconds
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press "Remove"
    And I wait "1" seconds
    And I press "Continue"
    And I set the following fields to these values:
      | User cancellation input | Three |
    And I press "Confirm"
    Then "#facetoface_sessions" "css_element" should not exist
    And I should not see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I should not see "Sam3 Student3"

    When I log out
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Seminar signup report |
      | Source      | Seminar Sign-ups      |
    And I press "Create report"
    And I switch to "Columns" tab
    And I change the "Session Start" column to "Signup input" in the report
    And I set the field "newcolumns" to "User cancellation input"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I follow "View This Report"
    Then the following should exist in the "reportbuilder-table" table:
      | User's Fullname | Course Name | Signup input | User cancellation input |
      | Sam1 Student1   | Course 1    | Apples       | Three                   |
      | Sam2 Student2   | Course 1    | Apples       | Three                   |
      | Sam3 Student3   | Course 1    | Apples       | Three                   |
