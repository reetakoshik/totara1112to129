@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation attendees view
  After seminar events have been cancelled
  As an admin
  I still need to see attendee details

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

    Given I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar |
      | Description | Test Seminar |
    And I follow "View all events"

    Given I follow "Add a new event"
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
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
    And I press "Add"
    And I wait "1" seconds
    And I click on "Learner Two, learner2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"

    Given I log out
    And I log in as "learner3"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Sign-up"
    And I press "Sign-up"

    Given I log out
    And I log in as "learner1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Cancel booking"
    And I press "Yes"

    Given I log out
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And I press "Yes"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_400: attendees "cancelled" tab view.
    When I click on "Attendees" "link"
    Then I should see the "Attendees" tab is disabled
    And I should see the "Wait-list" tab is disabled
    And I should see the "Take attendance" tab is disabled
    And I should see "User cancellation" in the "Learner One" "table_row"
    And I should see "Event cancellation" in the "Learner Two" "table_row"
    And I should see "Event cancellation" in the "Learner Three" "table_row"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_401: attendees "message users" tab view.
    When I click on "Attendees" "link"
    And I click on "Message users" "link"
    And I press "Discard message"
    Then I should see "User cancellation" in the "Learner One" "table_row"
    And I should see "Event cancellation" in the "Learner Two" "table_row"
    And I should see "Event cancellation" in the "Learner Three" "table_row"

    When I click on "Message users" "link"
    And I set the following fields to these values:
      | User Cancelled - 1 user(s)  | 1                       |
      | Event Cancelled - 2 user(s) | 1                       |
      | Subject                     | It is ON again!!!!      |
      | Body                        | Read the subject line   |
    And I press "Send message"
    Then I should see "3 message(s) successfully sent to attendees"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_402: bulk export of learners in cancelled event
    # --------------------------------------------------------------------------
    # Unfortunately it is impossible to verify the contents of an exported file
    # using a generic Behat step. This is because the location to where the file
    # is downloaded depends on the the test environment eg browser and OS. So
    # the test justs checks for the existence of an "export" UI control and goes
    # no further.
    # --------------------------------------------------------------------------
    When I click on "Attendees" "link"
    Then I should see "Export in Excel format"
    And I should see "Export in ODS format"
    And I should see "Export in CSV format"
