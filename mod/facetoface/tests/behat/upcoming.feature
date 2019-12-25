@javascript @mod @mod_facetoface @totara
Feature: Seminar actions in upcoming block are correct
  In order to use calendar for seminar signups
  As a learner
  I need to signup and confirm that I booked using upcoming events block

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: Signup and check that learner is booked using upcoming events block
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar             |
    And I follow "View all events"
    And I follow "Add a new event"
    And I press "Save changes"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Sign-up" in the "Booking open" "table_row"
    And I should see "Sign-up" in the "Upcoming events" "block"

    And I click on "Sign-up" "link"
    And I press "Sign-up"
    Then I should see "Booked" in the "Cancel booking" "table_row"
    And I should see "Booked" in the "Upcoming events" "block"
    And I should not see "Sign-up" in the "Upcoming events" "block"

  Scenario: Join waitlist and check that learner is joined using upcoming events block
    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the following fields to these values:
      | Everyone on waiting list | Yes |
    And I press "Save changes"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar |
    And I follow "View all events"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Enable waitlist                       | 1 |
      | Send all bookings to the waiting list | 1 |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Join waitlist" in the "Booking open" "table_row"
    And I should see "Join waitlist" in the "Upcoming events" "block"

    And I click on "Join waitlist" "link"
    And I press "Join waitlist"
    Then I should see "Wait-listed" in the "Cancel waitlist" "table_row"
    And I should see "Wait-listed" in the "Upcoming events" "block"
    And I should not see "Sign-up" in the "Upcoming events" "block"
