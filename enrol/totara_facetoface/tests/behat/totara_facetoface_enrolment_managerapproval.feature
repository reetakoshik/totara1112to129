@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Users are forced to get manager approval where required

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
      | Name             | Test seminar name        |
      | Description      | Test seminar description |
      | Manager Approval | 1                        |
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

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I log out

  Scenario: Should be unable to enrol using seminar direct without a manager
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "You can not enrol yourself in this course."

  Scenario: A user with a manager can request access, withdraw request and be granted access
    Given the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager  |
      | student1 | POS001   | teacher1 |

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Request approval" "link" in the "1 January 2020" "table_row"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Attendees"
    And I follow "Approval required"
    Then I should see "Student 1"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "manager request already pending"
    And I follow "Withdraw pending request"
    And I press "Confirm"
    Then I should see "Request approval"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Attendees"
    And I follow "Cancellations"
    Then I should see "Student 1"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Request approval" "link" in the "1 January 2020" "table_row"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Attendees"
    And I follow "Approval required"
    And I click on "input[value='2']" "css_element" in the "Student 1" "table_row"
    And I press "Update requests"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Topic 1"
