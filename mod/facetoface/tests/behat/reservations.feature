@javascript @mod @mod_facetoface @totara
Feature: Add - Remove manager reservations in Seminar
  In order to test the add/remove Seminar manager reservations
  As manager
  I need to add and remove attendees to/from a Seminar event using reservations

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
      | manager  | Max       | Manager  | manager@example.com  |
      | teamlead | Torry     | Teamlead | teamlead@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
   And the following "course enrolments" exist:
      | user | course | role           |
      | student1 | C1 | student        |
      | student2 | C1 | student        |
      | manager  | C1 | editingteacher |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager  |
      | student1 | POS001   | manager  |
      | student2 | POS001   | manager  |
      | student3 | POS001   | teamlead |

    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                       | Test Seminar name        |
      | Description                | Test Seminar description |
      | Allow manager reservations | Yes                         |
      | Maximum reservations       | 2                           |
    And I follow "Test Seminar name"
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
    And I press "OK"
    And I set the following fields to these values:
      | capacity              | 2    |
    And I press "Save changes"
    And I log out

  Scenario: Add and then remove users from Seminar using manager reservations
    Given I log in as "manager"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Test Seminar name" "link"
    And I should see "Allocate spaces for team (0/2)"
    And I should see "Reserve spaces for team (0/2)"
    And I click on "Allocate spaces for team" "link"
    And I click on "Sam1 Student1" "option"
    And I click on "Sam2 Student2" "option"
    And I press "Add"
    And I click on "Test Seminar name" "link"
    And I should see "Allocate spaces for team (2/2)"
    And I click on "Allocate spaces for team" "link"
    And I click on "Sam2 Student2" "option"
    And I press "Remove"
    And I click on "Test Seminar name" "link"
    And I should see "Allocate spaces for team (1/2)"
    And I should see "Reserve spaces for team (1/1)"

  Scenario: Confirm correct message when other manager cannot have reservations
    Given I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Test Seminar name" "link"
    And I should see "Reserve for another manager"
    And I click on "Reserve for another manager" "link"
    When I select "Torry Teamlead" from the "menumanagerid" singleselect
    And I press "Select manager"
    Then I should see "This manager does not have capabilities to reserve places in Seminar"