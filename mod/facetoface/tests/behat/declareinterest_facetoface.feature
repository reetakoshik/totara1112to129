@javascript @mod @mod_facetoface @totara
Feature: Manager approval and declare of interest
  In order to control seminar attendance
  As a manager
  I need to authorise seminar signups

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
      | student2 | Sam2      | Student2 | student2@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |

  Scenario: Student cannot declare interest where not enabled
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should not see "Declare interest"

  Scenario: Student can declare and withdraw interest where enabled
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                       | Test declareinterestfullybooked |
      | Description                | Test seminar description        |
      | Manager Approval           | 1                               |
      | Users can declare interest | Always                          |
    And I click on "View all events" "link" in the "declareinterestfullybooked" activity
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
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Declare interest"
    And I follow "Declare interest"
    And I set the following fields to these values:
      | Reason for interest: | Test reason |
    And I press "Confirm"
    And I should see "Withdraw interest"
    And I follow "Withdraw interest"
    And I press "Confirm"
    And I should see "Declare interest"

  Scenario: Student cannot declare interest until all sessions are fully booked if setting enabled.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                        | Test declareinterestfullybooked                     |
      | Description                 | Test seminar description                            |
      | Users can declare interest  | When no upcoming events are available for booking |
    And I click on "View all events" "link" in the "declareinterestfullybooked" activity
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
      | capacity           | 1    |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should not see "Declare interest"
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should see "Declare interest"

  Scenario: Student cannot declare interest if overbooking is enabled.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                       | Test declareinterestfullybooked                     |
      | Description                | Test seminar description                            |
      | Users can declare interest | When no upcoming events are available for booking |
    And I click on "View all events" "link" in the "declareinterestfullybooked" activity
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
      | Enable waitlist    | Yes  |
      | capacity           | 1    |
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should not see "Declare interest"
    And I follow "Sign-up"
    And I press "Sign-up"
    And I should see "Your request was accepted"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should not see "Declare interest"

  Scenario: Staff can view who has expressed interest
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                       | Test f2f 1                      |
      | Description                | Test seminar description        |
      | Users can declare interest | Always                          |
    And I click on "View all events" "link" in the "Test f2f 1" activity
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
    And I press "Save changes"
    And I follow "Course 1"
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                       | Test f2f 2                      |
      | Description                | Test seminar description        |
      | Users can declare interest | Always                          |
    And I click on "View all events" "link" in the "Test f2f 2" activity
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
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Declare interest" "link" in the "Test f2f 1" activity
    And I set the following fields to these values:
      | Reason for interest: | Test reason 1 |
    And I press "Confirm"
    And I click on "Declare interest" "link" in the "Test f2f 2" activity
    And I set the following fields to these values:
      | Reason for interest: | Test reason 2 |
    And I press "Confirm"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Declare interest" "link" in the "Test f2f 1" activity
    And I set the following fields to these values:
      | Reason for interest: | Test reason 3 |
    And I press "Confirm"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test f2f 1"
    And I follow "Declared interest report"
    And I should see "Test reason 1"
    And I should not see "Test reason 2"
    And I should see "Test reason 3"
    And I follow "Course 1"
    And I follow "Test f2f 2"
    And I follow "Declared interest report"
    And I should not see "Test reason 1"
    And I should see "Test reason 2"
    And I should not see "Test reason 3"

  Scenario: Student can declare interest when past sessions are not full and no upcoming sessions
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                       | Test declareinterestnotfullybookedpast              |
      | Description                | Test seminar description                            |
      | Manager Approval           | 1                                                   |
      | Users can declare interest | When no upcoming events are available for booking |
    And I click on "View all events" "link" in the "declareinterestnotfullybookedpast" activity
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | 0                |
      | timefinish[day]    | -1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | +1               |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I press "Save changes"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Declare interest"
    And I follow "Declare interest"
    And I set the following fields to these values:
      | Reason for interest: | Test reason |
    And I press "Confirm"
    And I should see "Withdraw interest"
