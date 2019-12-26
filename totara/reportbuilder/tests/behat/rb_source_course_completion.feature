@totara @totara_reportbuilder @javascript
Feature: Check that course completion reports don't show multiple enrolment types per course when only one has been selected.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | trainer1 | Trainer   | One      | trainer1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | trainer1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | A1     |

  Scenario: User is enrolled in a second course using a different method
    Given I log in as "admin"
    And I follow "Course 2"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I press "Add enrolled audiences"
    And I click on "Audience 1" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I press "Save and display"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Course Completion Report |
      | Source      | Course Completion        |
    And I press "Create report"
    And I switch to "Columns" tab
    And I add the "Enrolment Types" column to the report
    And I follow "View This Report"
    Then I should see "Manual enrolments"
    And I should see "Audience sync"
    And I should not see "Audience sync, Manual enrolments"

  Scenario: User is enrolled in the same course using a different method
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I press "Add enrolled audiences"
    And I click on "Audience 1" "link"
    And I click on "OK" "button" in the "Course audiences (enrolled)" "totaradialogue"
    And I press "Save and display"
    And I run the scheduled task "\enrol_cohort\task\sync_members"
    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Course Completion Report |
      | Source      | Course Completion        |
    And I press "Create report"
    And I switch to "Columns" tab
    And I add the "Enrolment Types" column to the report
    And I follow "View This Report"
    Then I should see "Manual enrolments"
    And I should see "Audience sync"
    And I should see "Audience sync, Manual enrolments"

  Scenario: User with RPL completion is reported correctly
    Given I log in as "trainer1"
    And I am on "Course 1" course homepage
    # Set up completion
    And I follow "Course completion"
    And I expand all fieldsets
    And I set the field "Editing Trainer" to "1"
    And I press "Save changes"
    # Mark the learner as complete
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I mark "Learner One" complete by "Editing Trainer" in the course completion report
    And I mark "Learner Two" complete by RPL with "You completed it!" in the course completion report
    And I log out
    # Create the report
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Course Completion Report |
      | Source      | Course Completion        |
    And I press "Create report"
    And I switch to "Columns" tab
    And I add the "RPL note" column to the report
    When I follow "View This Report"
    Then I should see "" in the "course_completion_rplnote" report column for "Learner One"
    And I should see "You completed it!" in the "course_completion_rplnote" report column for "Learner Two"
