@totara @totara_job @mod @mod_facetoface
Feature: Assign a manager to a user via the job assignment page and send message through Seminar message users CC
  In order to assign a manager to a user
  As an admin
  I must be able the manager's job assignment, create seminar event and use Message users

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | One      | user1@example.com    |
      | user2    | User      | Two      | user2@example.com    |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following "activities" exist:
      | activity   | name          | course | idnumber |
      | facetoface | Seminar 15838 | C1     | seminar  |
    And the following job assignments exist:
      | user  | fullname               | idnumber | manager  |
      | user1 | JA15838 job assignment | JA15838  | manager1 |
      | user2 | JA15838 job assignment | JA15838  |          |

  @javascript
  Scenario: Send message to attendee and manager using CC recipient's manager
    Given I log in as "admin"

    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Seminar 15838"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | User One |
    And I press "Search"
    And I click on "User One, user1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    And I click on "Message users" "link"
    And I set the following fields to these values:
      | Booked - 1 user(s)      | 1                     |
      | CC recipient's managers | 1                     |
      | Subject                 | Lorem ipsum dolor sit amet |
      | Body                    | Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. |
    When I press "Send message"
    Then I should see "2 message(s) successfully sent to attendees"

  @javascript
  Scenario: Send message to attendee and manager for select job assignment using CC recipient's manager
    Given I log in as "admin"
    And the following config values are set as admin:
      | facetoface_selectjobassignmentonsignupglobal | 1 |

    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I follow "Seminar 15838"
    And I follow "Add a new event"
    And I press "Save changes"

    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | User |
    And I press "Search"
    And I click on "User One, user1@example.com" "option"
    And I click on "User Two, user2@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I set the Seminar signup job assignment to "JA15838 job assignment" for "User One"
    And I set the Seminar signup job assignment to "JA15838 job assignment" for "User Two"

    And I click on "Message users" "link"
    And I set the following fields to these values:
      | Booked - 2 user(s)      | 1                     |
      | CC recipient's managers | 1                     |
      | Subject                 | Lorem ipsum dolor sit amet |
      | Body                    | Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. |
    When I press "Send message"
    Then I should see "3 message(s) successfully sent to attendees"
