@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Users can enrol themself in courses with selected position where seminar direct enrolment is allowed
  In order to run a seminar
  As a teacher
  I need to create a seminar activity

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the following fields to these values:
      | Select job assignment on signup | 1 |
    And I press "Save changes"

    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"

    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
      | FW001     | POS002   | Position2  |
    And the following job assignments exist:
      | user     | position | fullname       |
      | student1 | POS001   | jobassignment1 |
      | student1 | POS002   | jobassignment2 |

    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I press "Save changes"

    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | Select job assignment on signup | 1             |
    And I follow "View all events"
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
    And I log out

  Scenario: Add and configure a seminar activity with a single session and position asked for but not mandated then
  sign in as user with two positions and check attendee list reflects this and the selected position can be updated
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Sign-up" "link" in the "1 January 2020" "table_row"
    And I set the following fields to these values:
      | Select a job assignment | jobassignment2 (Position2) |
    And I press "Sign-up"
    Then I should see "Topic 1"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Attendees"
    And I should see "Position2"

  Scenario: Add and configure a seminar activity with a single session and position asked for but not mandated then
  sign in as user with two positions and check attendee list reflects this and the selected position can be updated
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Sign-up" "link" in the "1 January 2020" "table_row"
    And I set the following fields to these values:
      | Select a job assignment | jobassignment2 (Position2) |
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Attendees"
    And I should see "Position2"
