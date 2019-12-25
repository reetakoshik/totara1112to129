@mod @mod_facetoface @totara
Feature: User with permission remove seminar's message recipients is able to perform
  the action correctly

  Background:
    Given the following "courses" exist:
      | fullname  | shortname |
      | Course101 | C101      |

    And the following "users" exist:
      | username  | firstname | lastname |
      | kianbomba | bomba     | kian     |
      | bombakian | kian      | bomba    |
      | bolobala  | bolo      | bala     |
    And the following "course enrolments" exist:
      | user      | course    | role    |
      | kianbomba | C101      | student |
      | bombakian | C101      | teacher |
      | bolobala  | C101      | student |
    And the following "activities" exist:
      | activity    | name     | course | idnumber |
      | facetoface | Seminar1  | C101   | 1080     |
    And I am on a totara site
    And I log in as "admin"
    And I am on "Course101" course homepage
    And I follow "Seminar1"
    And I follow "Add a new event"
    And I press "Save changes"
    And I follow "Attendees"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "4 potential users" to "kianbomba"
    And I press "Add"
    And I set the field "4 potential users" to "bolobala"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

  @javascript
  Scenario: The trainer is able to remove the recipients of seminar's message
    with the remove recipients permission
    Given I am on a totara site
    And I log out
    And I log in as "bombakian"
    And I am on "Course101" course homepage
    And I follow "Seminar1"
    And I follow "Attendees"
    And I follow "Message users"
    And I set the field "Booked - 2 user(s)" to "1"
    And I follow "Recipients"
    And I press "Edit recipients individually"
    And I set the field "Existing recipients" to "bolo bala"
    And I press "Remove"
    When I press "Update"
    Then I should not see "bolo bala"
