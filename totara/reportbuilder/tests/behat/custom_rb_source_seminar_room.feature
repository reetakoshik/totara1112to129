@totara @totara_reportbuilder
Feature: I am able to edit the custom room
  that is within the seminars rooms report

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course101 | 101       | 0        |
    And the following "global rooms" exist in "mod_facetoface" plugin:
      | name  |
      | room1 |
    And the following "custom rooms" exist in "mod_facetoface" plugin:
      | name  |
      | room2 |
      | room3 |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname | source           |
      | report1  | rp1       | facetoface_rooms |

  Scenario: Checking whether the seminar room report displays only assigned
    custom rooms
    Given I log in as "admin"
    And I click on "Reports" in the totara menu
    And I follow "report1"
    Then I should not see "room2"
    And I should not see "room3"

  @javascript
  Scenario: I add a custom room into the course and I should be able to see it
    Given I log in as "admin"
    And I am on "Course101" course homepage
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1           |
      | Description | This is description |
    And I follow "Seminar 1"
    And I follow "Add a new event"
    And I follow "Select room"
    And I follow "room2"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"
    And I click on "Reports" in the totara menu
    And I follow "report1"
    Then I should see "room2"
    And I should not see "room3"
