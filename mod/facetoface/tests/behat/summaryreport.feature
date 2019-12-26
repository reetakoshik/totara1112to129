@javascript @mod @mod_facetoface @totara @totara_reportbuilder
Feature: Seminar sessions report overview
  In order to see all required information
  As an admin
  I need to configure seminar summary report and see all required information

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | teacher1  | Terry3    | Teacher  | teacher@example.com  |
      | student1  | Sam1      | Student1 | student1@example.com |
      | student2  | Sam2      | Student2 | student2@example.com |
      | student3  | Sam3      | Student3 | student3@example.com |
      | student4  | Sam4      | Student4 | student4@example.com |
      | student5  | Sam5      | Student5 | student5@example.com |
      | student6  | Sam6      | Student6 | student6@example.com |
      | student7  | Sam7      | Student7 | student7@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | student4 | C2     | student        |
      | student5 | C2     | student        |
      | student6 | C2     | student        |
    And I log in as "admin"

    # Enable roles for student and trainer
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Learner" "checkbox" in the "#admin-facetoface_session_roles" "css_element"
    # Trainer is ambigous with Editing Trainer
    And I click on "s__facetoface_session_roles[4]" "checkbox" in the "#admin-facetoface_session_roles" "css_element"
    And I press "Save changes"

    # Prepare report
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Seminar Summary          |
      | Source      | Seminar Sessions |
    And I press "Create report"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "Number of Attendees"
    And I press "Add"
    And I set the field "newcolumns" to "Overbooking allowed"
    And I press "Add"
    And I set the field "newcolumns" to "Approval Type"
    And I press "Add"
    And I set the field "newcolumns" to "Overall status"
    And I press "Add"
    And I set the field "newcolumns" to "Booking Status"
    And I press "Add"
    And I set the field "newcolumns" to "Normal cost"
    And I press "Add"
    And I set the field "newcolumns" to "Discount cost"
    And I press "Add"
    And I set the field "newcolumns" to "Minimum bookings"
    And I press "Add"
    And I set the field "newcolumns" to "Event Learner"
    And I press "Add"
    And I set the field "newcolumns" to "Event Learner (linked to profile)"
    And I press "Add"
    And I set the field "newcolumns" to "Event Trainer"
    And I press "Add"
    And I set the field "newcolumns" to "Event Trainer (linked to profile)"
    And I press "Add"
    And I press "Save changes"

    And I click on "Filters" "link"
    And I set the field "newstandardfilter" to "Booking Status"
    And I press "Add"
    And I set the field "newstandardfilter" to "Overall status"
    And I press "Add"
    And I set the field "newstandardfilter" to "Event Learner"
    And I press "Add"
    And I set the field "newstandardfilter" to "Event Trainer"
    And I press "Add"
    And I press "Save changes"

    # 1: (1st activity of C1) Underbooked, upcoming, manager approval
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name             | Test seminar name 1      |
      | Description      | Test seminar description |
      | Manager Approval | 1                           |
    And I follow "Test seminar name 1"
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
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity              | 2    |
      | mincapacity           | 1    |
      | sendcapacityemail     | 1    |
      | cutoff[number]        | 25   |
      | normalcost            | 1.11 |
      | discountcost          | 1.00 |
    And I press "Save changes"

  Scenario: Check canceled seminar sessions summary report
    Given I click on "Cancel event" "link"
    And I press "Yes"
    And I click on "Reports" in the totara menu
    When I click on "Seminar Summary" "link"
    Then I should see "N/A" in the "1.11" "table_row"

  Scenario: Check name column links to seminar in sessions summary report.

    Given I click on "Reports" in the totara menu
    When I click on "Seminar Summary" "link"
    And I click on "Test seminar name 1" "link" in the "Course 1" "table_row"
    Then I should see "All events in Test seminar name 1"

  Scenario: Check active seminar sessions summary report
    # Prepare 4 sessions in three activities:
    # 2: (2nd activity of C1) Two dates, self approved, overbooked, 1st started, 2nd upcoming
    And I am on "Course 1" course homepage
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name 2      |
      | Description       | Test seminar description |
    And I follow "Test seminar name 2"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | 0                |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | +30              |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Add a new session"
    And I click on "Edit session" "link" in the ".f2fmanagedates .lastrow" "css_element"
    And I set the following fields to these values:
      | timestart[day]     | 1                |
      | timestart[month]   | 1                |
      | timestart[year]    | 2030             |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | 1                |
      | timefinish[month]  | 1                |
      | timefinish[year]   | 2030             |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | 30               |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity              | 1                |
      | normalcost           | 2.22              |
      | discountcost         | 2.10              |
    And I press "Save changes"
    And I click on the link "Attendees" in row 1
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press exact "add"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"

    # 3: (2nd activity of C1) Bookings available, upcoming
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity              | 2    |
      | normalcost            | 3.33 |
      | discountcost          | 1.50 |
    And I press "Save changes"
    And I click on the link "Attendees" in row 1
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    # 4: (1st activity of C2) Fully booked, ended, no one
    And I am on "Course 2" course homepage
    #And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name 3      |
      | Description       | Test seminar description |
    And I follow "Test seminar name 3"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -2               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | -1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity              | 1                |
      | normalcost            | 4.44             |
    And I click on "Sam4 Student4" "checkbox"
    And I click on "Sam5 Student5" "checkbox"
    And I press "Save changes"
    And I follow "Attendees"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam6 Student6, student6@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    # 5: (1st activity of C2) N/A, ended, no one
    And I am on "Course 2" course homepage
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name 4      |
      | Description       | Test seminar description |
    And I follow "Test seminar name 4"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -2               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | -1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity           | 2             |
      | normalcost         | 5.55          |
    And I press "Save changes"
    And I follow "Attendees"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam7 Student7, student7@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    And I click on "Reports" in the totara menu
    When I click on "Seminar Summary" "link"
    Then I should see "Course 1" in the "2.22" "table_row"
    And I should see "No" in the "1.11" "table_row"
    And I should see "Manager Approval" in the "1.11" "table_row"
    And I should see "Underbooked" in the "1.11" "table_row"
    And I should see "Upcoming" in the "1.11" "table_row"
    And I should see "1.00" in the "1.11" "table_row"

    And I should see "Course 1" in the "2.22" "table_row"
    And I should see "No" in the "2.22" "table_row"
    And I should see "No Approval" in the "2.22" "table_row"
    And I should see "Overbooked" in the "2.22" "table_row"
    And I should see "Event in progress" in the "2.22" "table_row"
    And I should see "2.10" in the "2.22" "table_row"

    And I should see "Course 1" in the "3.33" "table_row"
    And I should see "No" in the "3.33" "table_row"
    And I should see "No Approval" in the "3.33" "table_row"
    And I should see "Booking open" in the "3.33" "table_row"
    And I should see "Upcoming" in the "3.33" "table_row"
    And I should see "1.50" in the "3.33" "table_row"

    And I should see "Course 2" in the "4.44" "table_row"
    And I should see "No" in the "4.44" "table_row"
    And I should see "No Approval" in the "4.44" "table_row"
    And I should see "Booking full" in the "4.44" "table_row"
    And I should see "Event over" in the "4.44" "table_row"
    And "Sam4 Student4" "link" should exist in the "4.44" "table_row"
    And "Sam5 Student5" "link" should exist in the "4.44" "table_row"

    And I should see "Course 2" in the "5.55" "table_row"
    And I should see "No Approval" in the "5.55" "table_row"
    And I should see "N/A" in the "5.55" "table_row"
    And I should see "Event over" in the "5.55" "table_row"

    # Check filters
    When I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Underbooked"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "1.11"
    And I should not see "2.22"
    And I should not see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Overbooked"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2.22"
    And I should not see "1.11"
    And I should not see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Booking open"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "3.33"
    And I should not see "1.11"
    And I should not see "2.22"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Booking full"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "4.44"
    And I should not see "1.11"
    And I should not see "2.22"
    And I should not see "3.33"
    And I press "Clear"

    When I set the field "Booking Status field limiter" to "isn't equal to"
    And I set the field "Booking Status value" to "Booking open"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "3.33"
    And I should see "1.11"
    And I should see "2.22"
    And I should see "4.44"
    And I press "Clear"

    When I set the field "Overall status field limiter" to "is equal to"
    And I set the field "Overall status value" to "Upcoming"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "1.11"
    And I should see "2.22"
    And I should see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Overall status field limiter" to "is equal to"
    And I set the field "Overall status value" to "Event in progress"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2.22"
    And I should not see "1.11"
    And I should not see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Overall status field limiter" to "is equal to"
    And I set the field "Overall status value" to "Event over"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "4.44"
    And I should not see "1.11"
    And I should not see "2.22"
    And I should not see "3.33"

    When I set the field "Overall status field limiter" to "isn't equal to"
    And I set the field "Overall status value" to "Upcoming"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "1.11"
    And I should not see "3.33"
    And I should see "2.22"
    And I should see "4.44"
    And I press "Clear"

    When I set the field "Overall status field limiter" to "isn't equal to"
    And I set the field "Overall status value" to "Upcoming"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "1.11"
    And I should not see "3.33"
    And I should see "2.22"
    And I should see "4.44"
    And I press "Clear"


    When I set the field "Overall status field limiter" to "isn't equal to"
    And I set the field "Overall status value" to "Upcoming"
    And I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Overbooked"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2.22"
    And I should not see "1.11"
    And I should not see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Event Learner field limiter" to "contains"
    And I set the field "Event Learner value" to "Sam"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "4.44"
    And I should not see "1.11"
    And I should not see "2.22"
    And I should not see "3.33"
    And I press "Clear"

    # Disable teacher role, and ensure that column and filter disappeared
    Given "Event Trainer" "link" should exist in the ".reportbuilder-table" "css_element"
    And I click on "Home" in the totara menu
    When I navigate to "Global settings" node in "Site administration > Seminars"
    # Trainer is ambigous with Editing Trainer
    And I click on "s__facetoface_session_roles[4]" "checkbox" in the "#admin-facetoface_session_roles" "css_element"
    And I press "Save changes"
    And I click on "Reports" in the totara menu
    And I follow "Seminar Summary"
    Then "Event Learner" "link" should exist in the ".reportbuilder-table" "css_element"
    And "Event Trainer" "link" should not exist in the ".reportbuilder-table" "css_element"
    And I press "Edit this report"
    And I follow "Columns"
    And I should see "Event Learner"
    And I should not see "Event Trainer"
    And I follow "Filters"
    And I should see "Event Learner"
    And I should not see "Event Trainer"
