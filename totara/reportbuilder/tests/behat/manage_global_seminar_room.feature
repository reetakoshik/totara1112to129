@totara @totara_reportbuilder
Feature: I am only allowed to view the global seminar room
  within the manage seminar room page,
  but not the custom seminar room.

  Background:
    Given I am on a totara site
    And the following "global rooms" exist in "mod_facetoface" plugin:
      | name  |
      | room1 |
    And the following "custom rooms" exist in "mod_facetoface" plugin:
      | name  |
      | room2 |

  Scenario: I am viewing the management page of the global seminars rooms
    Given I log in as "admin"
    And I navigate to "Seminars > Rooms" in site administration
    Then I should see "room1"
    And I should not see "room2"
