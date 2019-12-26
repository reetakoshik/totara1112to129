@mod @mod_facetoface @totara @totara_reportbuilder @javascript
Feature: Add seminar attendees without signup capability
  In order to test the add attendees without signup capability
  As admin
  I need to disable signup capability, upload attendees through the bulk add attendees options.

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
    And the following "permission overrides" exist:
      | capability            | permission | role    | contextlevel | reference |
      | mod/facetoface:signup | Prohibit   | student | Course       |        C1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"
    And I log out

  Scenario: Confirms that teacher still can add users with disabled signup capability.
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    When I follow "More info"
    Then I should see "You don't have permission to signup to this seminar event."
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I set the field "menuf2f-actions" to "Add users"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press "Add"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Sam1 Student1" in the "#facetoface_sessions" "css_element"
    And I should see "Sam2 Student2" in the "#facetoface_sessions" "css_element"