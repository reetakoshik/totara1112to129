@mod @mod_facetoface @totara
Feature: Play waiting list lottery
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

    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the following fields to these values:
      | Everyone on waiting list | Yes  |
      | Waitlist lottery         | Yes  |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: The second student to sign up to the session should go on waiting list
    Given I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
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
    And I press "OK"
    And I set the following fields to these values:
      | capacity                       | 2    |
      | Enable waitlist                | 1    |
      | Send all bookings to the waiting list | 1    |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Join waitlist"
    And I should see "You will be added to the waiting list for this event"
    And I press "Sign-up"
    # TODO: Seems functionality bug (not behat test)
    #And I should see "You have been placed on the waitlist for this event."
    And I log out

    When I log in as "student2"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Join waitlist"
    And I should see "You will be added to the waiting list for this event"
    And I press "Sign-up"
    # TODO: Seems functionality bug (not behat test)
    #And I should see "You have been placed on the waitlist for this event."
    And I log out

    When I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I follow "Wait-list"
    Then I should see "Sam1 Student1"
    Then I should see "Sam2 Student2"

    # Behat bug: cannot push buttons in confirmation dialogs. TL-8632
    #And I set the following fields to these values:
    #  | menuf2f-actions | Play Lottery |
    #And I should see "Please select one or more users"
    #And I click on "Close" "link_or_button"
    #And I click on "All" "link"
    #And I set the following fields to these values:
    #  | menuf2f-actions | Play Lottery |
    #And I click on "OK" "link_or_button"
    #And I should see "Successfully updated attendance"
    #Then I should not see "Sam1 Student1"
    #Then I should not see "Sam2 Student2"
    #And I follow "Attendees"
    #Then I should see "Sam1 Student1"
    #Then I should see "Sam2 Student2"
