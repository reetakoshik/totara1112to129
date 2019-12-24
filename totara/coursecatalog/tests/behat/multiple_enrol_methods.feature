@javascript @totara @totara_coursecatalog @enrol
Feature: Users can auto-enrol themself in courses where self enrolment is allowed
  In order to participate in courses
  As a user
  I need to auto enrol me in courses

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

    Given I log in as "admin"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Enrolments" node
    And I follow "Manage enrol plugins"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I set the following administration settings values:
      | Enhanced catalog   | 1    |
      | Guest login button | Show |
    And I log out

    Given I log in as "teacher1"

    Given I follow "Course 1"
    When I add "Self enrolment" enrolment method with:
      | Enrolment key            | moodle_rules |
      | Use group enrolment keys | Yes          |
    And I follow "Groups"
    And I press "Create group"
    And I set the following fields to these values:
      | Group name    | Group 1             |
      | Enrolment key | Test-groupenrolkey1 |
    And I press "Save changes"

    Given I follow "Course 1"
    And I turn editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name  | Test forum name        |
      | Description | Test forum description |
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes          |
      | Password           | moodle_rules |
    And I press "Save changes"

    Given I follow "Course 1"
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name 2        |
      | Description | Test seminar description 2 |
      | No Approval | 1                          |
    And I follow "Test seminar name 2"
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
    And I press "Save changes"
    And I follow "Course 1"
    And I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    Given I log out

  Scenario: Self-enrolment through course catalog requiring a group enrolment key or guest access or seminar
    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I click on ".rb-display-expand" "css_element"
    And I set the following fields to these values:
      | Enrolment key | Test-groupenrolkey1 |
    And I press "Enrol with - Self enrolment"
    Then I should see "Topic 1"
    And I should not see "Enrolment options"
    And I should not see "Enrol me in this course"
    And I log out

    When I log in as "student2"
    And I click on "Find Learning" in the totara menu
    And I click on ".rb-display-expand" "css_element"
    Then I should see "Guest access"
    And I set the following fields to these values:
      | Password | moodle_rules |
    And I press "Enrol with - Guest access"
    And I should see "Test forum name"
    And I log out

    When I log in as "student3"
    And I should see "Courses" in the "Navigation" "block"
    And I click on "Courses" "link_or_button" in the "Navigation" "block"
    And I click on ".rb-display-expand" "css_element"
    And I click on "Sign-up" "link" in the "1 January 2020" "table_row"
    And I press "Sign-up"
    Then I should see "Topic 1"
