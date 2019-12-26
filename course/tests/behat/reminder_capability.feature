@totara @totara_course
Feature: Verify course reminder capability.
  Background:
    Given I am on a totara site
    And the following "users" exist:
        | username       | firstname | lastname | email                      |
        | student1       | Student1  | Student1 | student1@example.com       |
        | student2       | Student2  | Student2 | student2@example.com       |
        | manager1       | Manager1  | Manager1 | manager1@example.com       |
        | editingtrainer | Editing   | Trainer  | editingtrainer@example.com |
    And the following "courses" exist:
        | fullname | shortname | format |
        | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
        | user           | course | role           |
        | student1       | C1     | student        |
        | student2       | C1     | student        |
        | editingtrainer | C1     | editingteacher |
    And the following "system role assigns" exist:
        | user           | role           |
        | manager1       | manager        |
        | editingtrainer | editingteacher |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Feedback" to section "1" and I fill the form with:
        | Name        | Test Feedback             |
        | Description | Test Feedback description |
    And I log out

  @javascript
  Scenario: Verify an admin user can access Reminders.
    Given I log in as "admin"
    When I am on "Course 1" course homepage
    And I navigate to "Reminders" node in "Course administration"
    Then I should see "Edit course reminders"
    And I log out

  @javascript
  Scenario: Verify a Site Manager can access Reminders.
    Given I log in as "manager1"
    When I am on "Course 1" course homepage
    And I navigate to "Reminders" node in "Course administration"
    Then I should see "Edit course reminders"
    And I log out

  @javascript
  Scenario: Verify a Site Manager cannot access Reminders when access is removed.
    Given I log in as "manager1"
    When I set the following system permissions of "Site Manager" role:
      | capability                    | permission |
      | moodle/course:managereminders | Prevent    |
    And I am on "Course 1" course homepage
    Then I should not see "Reminders"
    And I log out

  @javascript
  Scenario: Verify Editing Trainer can access Reminders.
    Given I log in as "editingtrainer"
    And I am on "Course 1" course homepage
    Then I should not see "Reminders"
    And I log out

    And I log in as "admin"
    And I set the following system permissions of "Editing Trainer" role:
        | capability                    | permission |
        | moodle/course:managereminders | Allow      |
        | moodle/course:update          | Prevent    |
    And I log out

    And I log in as "editingtrainer"
    And I am on "Course 1" course homepage
    When I navigate to "Reminders" node in "Course administration"
    Then I should see "Edit course reminders"
    And I log out
