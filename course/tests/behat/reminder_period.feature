@totara @totara_course @course_reminders
Feature: Verify course reminder periods.
  In order to set course reminder periods of 60 and 90 days
  As an admin
  I need to see up to 90 days in the "Period" drop down menus of the Course Reminders form

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And I log in as "admin"
    And I navigate to "Manage activities" node in "Site administration > Plugins > Activity modules"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Feedback" to section "1" and I fill the form with:
      | Name        | Test Feedback             |
      | Description | Test Feedback description |
    And I log out

  Scenario: Verify an admin user can set Reminder periods of 60 and 90 days.
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Reminders" node in "Course administration"
    Then the "Period" select box should contain "60"
    And the "Period" select box should contain "90"
    And I set the following fields to these values:
      | Title               | Test 1        |
      | Requirement         | Test Feedback |
      | id_invitationperiod | 90            |
      | id_reminderperiod   | 90            |
      | id_escalationperiod | 90            |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Reminders" node in "Course administration"
    And I set the following fields to these values:
      | Title               | Test 1        |
      | Requirement         | Test Feedback |
      | id_invitationperiod | 60            |
      | id_reminderperiod   | 60            |
      | id_escalationperiod | 60            |
    And I press "Save changes"
    And I log out
