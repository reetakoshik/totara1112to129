@javascript @mod @mod_facetoface @totara
Feature: Reserve spaces for team in seminar
  In order to test seminar reservations
  As a site manager
  I need to reserve spaces for my team

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname | lastname     | email                    | role     | context|
      | sitemanager1 | Terry1    | Sitemanager1 | sitemanager1@example.com | manager  | system |
      | sitemanager2 | Terry2    | Sitemanager2 | sitemanager2@example.com | manager  | system |
      | teacher1     | Terry3    | Teacher      | teacher@example.com      | learner  | system |
      | student1     | Sam1      | Student1     | student1@example.com     | learner  | system |
      | student2     | Sam2      | Student2     | student2@example.com     | learner  | system |
      | student3     | Sam3      | Student3     | student3@example.com     | learner  | system |
      | student4     | Sam4      | Student4     | student2@example.com     | learner  | system |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | sitemanager1 | C1 | manager        |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "system role assigns" exist:
      | user         | role         | contextlevel | reference |
      | sitemanager1 | manager      | System       |           |
      | sitemanager2 | manager      | System       | System    |
    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager      |
      | student1 | POS001   | sitemanager1 |
      | student2 | POS001   | sitemanager1 |
      | student3 | POS001   | sitemanager2 |
      | student4 | POS001   | sitemanager2 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | Test seminar name        |
      | Description                             | Test seminar description |
      | How many times the user can sign-up?    | Unlimited                |
      | Allow manager reservations              | Yes                      |
      | Maximum reservations                    | 10                       |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 3 |
      | allowoverbook      | 1 |
    And I press "Save changes"
    And I log out

  Scenario: Wait listed users should be added to attendees list when reservations are deleted
    Given I log in as "sitemanager1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Reserve spaces for team"
    And I set the following fields to these values:
      | managerid | 3 |
    And I press "Select manager"
    And I set the following fields to these values:
      | reserve | 1 |
    And I press "Update"
    And I log out

    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Sign-up" in row 1
    And I press "Sign-up"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Sign-up" in row 1
    And I press "Sign-up"
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on the link "Join waitlist" in row 1
    And I press "Join waitlist"
    And I log out

    Given I log in as "sitemanager1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on "Attendees" "link"
    Then I should see "Sam1 Student1"
    And I should see "Sam3 Student3"
    And I should not see "Sam2 Student2"
    When I click on "Wait-list" "link"
    Then I should see "Sam2 Student2"

    When I click on "Go back" "link"
    Then I follow "Manage reservations"
    And I click on "Delete" "link"
    And I press "Continue"
    When I click on "Attendees" "link"
    Then I should see "Sam1 Student1"
    And I should see "Sam3 Student3"
    And I should see "Sam2 Student2"
    And I log out
