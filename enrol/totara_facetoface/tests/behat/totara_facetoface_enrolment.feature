@enrol @javascript @totara @enrol_totara_facetoface
Feature: Users can auto-enrol themself in courses where seminar direct enrolment is allowed
  In order to participate in courses
  As a user
  I need to auto enrol me in courses

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
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
      | No Approval | 1                        |
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

  Scenario: Enrol using seminar direct enrolment
    Given I log in as "teacher1"
    And I follow "Course 1"
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I log out
    And I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Sign-up" "link" in the "1 January 2020" "table_row"
    And I set the following fields to these values:
      | Requests for session organiser | Lorem ipsum dolor sit amet |
    And I press "Sign-up"
    Then I should see "Test seminar name: Your request was accepted"
    And I log out
    # Check signup note
    And I log in as "admin"
    And I follow "Course 1"
    And I follow "Test seminar name"
    When I click on "Attendees" "link"
    Then I should see "Lorem ipsum dolor sit amet" in the "Student 1" "table_row"

  Scenario: Seminar direct enrolment disabled
    Given I log in as "student1"
    And I click on "Find Learning" in the totara menu
    When I follow "Course 1"
    Then I should see "You can not enrol yourself in this course"

  Scenario: Enrol through course catalogue
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enhanced catalog | 1 |
    And I press "Save changes"
    And I log out
    Given I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I log out
    And I log in as "student1"
    And I should see "Courses" in the "Navigation" "block"
    And I click on "Courses" "link_or_button" in the "Navigation" "block"
    And I click on "Course 1" "link"
    And I click on "Sign-up" "link" in the "1 January 2020" "table_row"
    And I press "Sign-up"
    Then I should see "Topic 1"

  Scenario: Enrol using seminar direct enrolment with customfields
    # Setup customfields
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Sign-up" "link"

    And I click on "Edit" "link" in the "Requests for session organiser" "table_row"
    And I set the following fields to these values:
      | fullname            | Signup text input |
      | shortname           | signupnote1 |
    And I press "Save changes"

    And I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname           | Signup textarea |
      | shortname          | signuptextarea |
    And I press "Save changes"
    And I log out

    Given I log in as "teacher1"
    And I follow "Course 1"
    When I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |
    And I log out
    And I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Sign-up" "link" in the "1 January 2020" "table_row"
    And I set the following fields to these values:
      | Signup text input | Lorem ipsum dolor sit amet |
      | Signup textarea   | Some other text data |
    And I press "Sign-up"
    Then I should see "Test seminar name: Your request was accepted"
    And I log out
  # Check signup note
    And I log in as "admin"
    And I follow "Course 1"
    And I follow "Test seminar name"
    When I click on "Attendees" "link"
    Then I should see "Lorem ipsum dolor sit amet" in the "Student 1" "table_row"
    And I should see "Some other text data" in the "Student 1" "table_row"