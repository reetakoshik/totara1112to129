@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Users can enrol on courses that have autosignup enabled and get signed for appropriate sessions
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

  Scenario: Auto enrol using seminar direct
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

    And I am on "Course 1" course homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name                          | Test student enrolment |
      | Automatically sign users up to seminar events |                      1 |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Sign-up" "link_or_button"
    Then I should see "Your booking has been completed and you have been enrolled on 2 event(s)."

  Scenario: Auto enrol to waiting list using seminar direct and managers enabled required
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                       | Test seminar name 1        |
      | Description                | Test seminar description 1 |
      | No Approval                | 1                          |
      | Allow manager reservations | Yes                        |
    And I follow "Test seminar name 1"
    And I follow "Add a new event"
    And I click on "Delete" "link" in the "Select room" "table_row"
    And I press "Save changes"
    And I follow "Course 1"
    And I am on "Course 1" course homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name  | Test student enrolment |
      | Default assigned role | Learner                |
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Course 1"
    And I click on "Join waitlist" "link_or_button"
    And I click on "Join waitlist" "link_or_button"
    Then I should see "Your request was accepted"
    And I should see "Wait-listed"

  Scenario: Auto enrol using seminar direct with manager approval required
    Given the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager  |
      | student1 | POS001   | teacher1 |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name             | Test seminar name 1        |
      | Description      | Test seminar description 1 |
      | Manager Approval | 1                          |
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
      | Name             | Test seminar name 2        |
      | Description      | Test seminar description 2 |
      | Manager Approval | 1                          |
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

    And I am on "Course 1" course homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name                          | Test student enrolment |
      | Automatically sign users up to seminar events |                      1 |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Sign-up" "link_or_button"
    Then I should see "Your request was sent to your manager for approval."

  Scenario: Auto enrol using seminar direct with Learner accepts terms and conditions
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                 | Test seminar name 1        |
      | Description                          | Test seminar description 1 |
      | Learner accepts terms and conditions | 1                          |
    And I follow "Test seminar name 1"
    And I follow "Add a new event"
    And I press "Save changes"

    And I am on "Course 1" course homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I click on "Disable" "link" in the "Program" "table_row"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Sign-up"
    When I click on "Sign-up" "link_or_button"
    Then I should see "Self authorisation"
    And I should see "By checking this box, I confirm that I have read and agreed to the Terms and conditions (opens a new window)."

    When I click on "Sign-up" "link_or_button"
    Then I should see "Required"

    When I click on "Terms and conditions" "link"
    Then I should see "By checking the box you confirm that permission to sign up to this seminar activity has been granted by your manager."
    And I should see "Falsely claiming that approval has been granted can result in non-admittance and disciplinary action."
    When I click on "Close" "button"
    And I click on "authorisation" "checkbox"
    And I click on "Sign-up" "link_or_button"
    Then I should see "Test seminar name 1: Your request was accepted."
    And I should see "You will receive a booking confirmation email shortly."