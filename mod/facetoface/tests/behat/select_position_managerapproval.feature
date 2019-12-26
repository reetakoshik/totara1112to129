@mod @totara @mod_facetoface
Feature: Seminar Select position with Manager approval
  In order to control seminar attendance
  As a manager
  I need to authorise seminar signups

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | teacher2 | Terry2    | Teacher2 | teacher2@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
      | student1 | C1     | student        |

    And I log in as "admin"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Enrolments" node
    And I follow "Manage enrol plugins"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the following fields to these values:
      | Select job assignment on signup | 1 |
    And I press "Save changes"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                      | Test seminar name        |
      | Description               | Test seminar description |
      | Manager Approval          | 1                        |
      | Select job assignment on signup | 1                  |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]       | 1    |
      | timestart[month]     | 1    |
      | timestart[year]      | 2020 |
      | timestart[hour]      | 11   |
      | timestart[minute]    | 0    |
      | timefinish[day]      | 1    |
      | timefinish[month]    | 1    |
      | timefinish[year]     | 2020 |
      | timefinish[hour]     | 12   |
      | timefinish[minute]   | 0    |
    And I press "OK"
    And I set the following fields to these values:
      | capacity              | 1    |
    And I press "Save changes"
    And I log out

    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
      | FW001     | POS002   | Position2  |
    And the following job assignments exist:
      | user     | position | manager  |
      | student1 | POS001   | teacher1 |
      | student1 | POS002   | teacher2 |

  @javascript
  Scenario: Student signs up with two managers assigned
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I set the following fields to these values:
      | Select a job assignment | Unnamed job assignment (ID: 2) (Position2) |
    And I press "Request approval"
    And I should see "Your request was sent to your manager for approval."
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I should not see "Approval required" in the ".tabtree" "css_element"
    And I log out
    And I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I switch to "Approval required" tab
    And I click on "input[value='2']" "css_element" in the "Sam1 Student1" "table_row"
    And I press "Update requests"
