@javascript @mod @mod_facetoface @totara
Feature: Allocate spaces in full events
  In order to test seminar allocations with full events
  As a staff manager
  I need to allocate spaces for my team

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname | lastname     | email                    | role     | context|
      | manager1     | Terry1    | Manager1     | manager1@example.com     | manager  | system |
      | teacher1     | Terry3    | Teacher      | teacher@example.com      | learner  | system |
      | student1     | Sam1      | Student1     | student1@example.com     | learner  | system |
      | student2     | Sam2      | Student2     | student2@example.com     | learner  | system |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | manager1 | C1     | student        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "system role assigns" exist:
      | user         | role         | contextlevel | reference |
      | manager1     | staffmanager | System       |           |
    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager      |
      | student2 | POS001   | manager1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | Test seminar name        |
      | Description                             | Test seminar description |
      | Allow manager reservations              | Yes                      |
      | Maximum reservations                    | 1                        |
    And I follow "View all events"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | capacity           | 1    |
    And I press "Save changes"
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I click on "Add" "button" in the ".addremove" "css_element"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I log out

  Scenario: Allocate user to a full event without waitlist should not happen
    Given I log in as "manager1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Allocate spaces for team" in row 1
    And I click on "Sam2 Student2" "option"
    When I press "Add"
    Then I should see "This event is now full. You will need to pick another time or talk to the instructor."
    And I should see "Booking full"
    And I log out
    # Confirm that user really was not added.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "Attendees" "link"
    Then I should see "Sam1 Student1"
    And I should not see "Sam2 Student2"

  Scenario: Allocate user to a full event with waitlist enabled should work
    # Enable waitlist
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Edit event" "link"
    And I set the following fields to these values:
      | Enable waitlist   | 1 |
    And I press "Save changes"
    And I log out

    And I log in as "manager1"
    And I am on "Course 1" course homepage
    And I click on the link "Allocate spaces for team" in row 1
    And I click on "Sam2 Student2" "option"
    When I press "Add"
    Then I should not see "This event is now full. You will need to pick another time or talk to the instructor."
    And I should see "Booking full"
    And I log out

    # Confirm that user was added to waitlist
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "Attendees" "link"
    Then I should see "Sam1 Student1"
    And I should not see "Sam2 Student2"
    And I switch to "Wait-list" tab
    And I should see "Sam2 Student2"

    # Confirm to overbook
    And I click on "All" "option" in the "#menuf2f-select" "css_element"
    When I select "Confirm" from the "Attendee actions" singleselect
    And I press "Yes"
    Then I should not see "Sam2 Student2"
    And I switch to "Attendees" tab
    And I should see "Sam2 Student2"
    And I should see "This event is overbooked (2 / 1)"