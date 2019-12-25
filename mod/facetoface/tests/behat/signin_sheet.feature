@mod @mod_facetoface @totara
Feature: Download a seminar signin sheet
  In order to take attendance
  As a teacher
  I need to download a signin sheet

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
      | learner4 | Learner   | Four     | learner4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |

  @javascript
  Scenario: An editing trainer can download the signin sheet when one session date present
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 10   |
      | timestart[month]   | 2    |
      | timestart[year]    | 2030 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 10   |
      | timefinish[month]  | 2    |
      | timefinish[year]   | 2030 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I press "Save changes"
    And I click on the link "Attendees" in row 1
    And I should not see "Download sign-in sheet"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I press "Continue"
    And I press "Confirm"
    When I press "Download sign-in sheet"
    Then I should see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Learner Four"
    And I should not see "Teacher One"

  @javascript
  Scenario: An editing trainer can download the signin sheet after selecting first date
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 10   |
      | timestart[month]   | 2    |
      | timestart[year]    | 2030 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 10   |
      | timefinish[month]  | 2    |
      | timefinish[year]   | 2030 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I press "Add a new session"
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 10" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 9    |
      | timestart[month]   | 2    |
      | timestart[year]    | 2030 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 9    |
      | timefinish[month]  | 2    |
      | timefinish[year]   | 2030 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I press "Save changes"
    And I click on the link "Attendees" in row 1
    And I should not see "Download sign-in sheet"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I press "Continue"
    And I press "Confirm"
    And I set the field "sessiondateid" to "9 February 2030, 11:00 AM Australia/Perth"
    When I press "Download sign-in sheet"
    Then I should see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Learner Four"
    And I should not see "Teacher One"

  @javascript
  Scenario: An editing trainer can download the signin sheet after selecting second date
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 10   |
      | timestart[month]   | 2    |
      | timestart[year]    | 2030 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 10   |
      | timefinish[month]  | 2    |
      | timefinish[year]   | 2030 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I press "Add a new session"
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 10" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 9    |
      | timestart[month]   | 2    |
      | timestart[year]    | 2030 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 9    |
      | timefinish[month]  | 2    |
      | timefinish[year]   | 2030 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I press "Save changes"
    And I click on the link "Attendees" in row 1
    And I should not see "Download sign-in sheet"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I press "Continue"
    And I press "Confirm"
    And I set the field "sessiondateid" to "10 February 2030, 11:00 AM Australia/Perth"
    When I press "Download sign-in sheet"
    Then I should see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Learner Four"
    And I should not see "Teacher One"

  @javascript
  Scenario: An editing trainer cannot download the signin sheet without sesion dates
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Delete" "link" in the "Select room" "table_row"
    And I press "Save changes"
    And I click on the link "Attendees" in row 1
    And I should not see "Download sign-in sheet"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I click on "Learner Two, learner2@example.com" "option"
    And I press exact "add"
    And I press "Continue"
    When I press "Confirm"
    Then I should not see "Download sign-in sheet"
