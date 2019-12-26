@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Users can enrol on courses that have several seminar activities and signup to several sessions
  In order to participate in courses with seminars
  As a user
  I need to sign up to seminars when enrolling on the course

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name 1        |
      | Description | Test seminar description 1 |
      | No Approval | 1                          |
    And I follow "Test seminar name 1"
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
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name 2        |
      | Description | Test seminar description 2 |
      | No Approval | 1                          |
    And I follow "Test seminar name 2"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 2    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 2    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I follow "Course 1"
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name                          | Test student enrolment |
      | Automatically sign users up to seminar events | 0                      |
    And I log out

  Scenario: Enrol using seminar direct to a multisession
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Sign-up" "link" in the "2 January 2020" "table_row"
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I am on "Course 1" course homepage
    And I click on "Sign-up" "link" in the "1 January 2020" "table_row"
    And I press "Sign-up"
    And I should see "Your request was accepted"
