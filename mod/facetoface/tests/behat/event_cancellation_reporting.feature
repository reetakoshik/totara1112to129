@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation reporting
  After seminar events have been cancelled
  As an admin
  I need to be able to generate reports.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar |
      | Description | Test Seminar |
    And I follow "View all events"

    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | Timezone displayed  | Pacific/Auckland |
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | Timezone displayed  | Pacific/Auckland |
      | sessiontimezone     | Pacific/Auckland |
      | timestart[hour]     | 10               |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[hour]    | 16               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I press "Save changes"

    Given I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"

    Given I log out
    And I log in as "learner3"
    And I am on "Course 1" course homepage
    And I follow "Sign-up"
    And I press "Sign-up"

    Given I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I follow "Cancel booking"
    And I press "Yes"

    Given I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And I press "Yes"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_700: viewing “seminars: event attendees report”
    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Seminars: Event attendees"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Seminars: Event attendees"
    And I follow "View This Report"
    And I follow "To view the report, first select an event from the Number of Attendees column in the next report."
    Then I should see "Test Seminar" in the "Course 1" "table_row"
    And I should see "Course 1" in the "Test Seminar" "table_row"
    And I should see "20" in the "Test Seminar" "table_row"
    When I click on "Attendees" "link" in the "Test Seminar" "table_row"

    And I click on "Cancellations" "link"
    And I should see "User cancellation" in the "Learner One" "table_row"
    And I should see "Event cancellation" in the "Learner Two" "table_row"
    And I should see "Event cancellation" in the "Learner Three" "table_row"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_701: using "seminar sign ups" source in custom report
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | fullname | Custom test event report |
      | source   | Seminar Sign-ups         |
    And I press "Create report"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "Seminar Name"
    And I press "Add"
    And I set the field "newcolumns" to "Status"
    And I press "Add"
    And I press "Save changes"

    When I follow "View This Report"
    Then I should see "Course 1" in the "Learner One" "table_row"
    And I should see date "10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "User Cancelled" in the "Learner One" "table_row"
    And I should see "Test Seminar" in the "Learner One" "table_row"

    And I should see "Course 1" in the "Learner Two" "table_row"
    And I should see date "10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Event Cancelled" in the "Learner Two" "table_row"
    And I should see "Test Seminar" in the "Learner Two" "table_row"

    And I should see "Course 1" in the "Learner Three" "table_row"
    And I should see date "10 day Pacific/Auckland" formatted "%d %B %Y"
    And I should see "Event Cancelled" in the "Learner Three" "table_row"
    And I should see "Test Seminar" in the "Learner Three" "table_row"
