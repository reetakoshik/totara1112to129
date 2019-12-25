@totara @totara_reportbuilder
Feature: As an administrator
  I am able to edit a custom asset
  that is within the seminar assets report

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course101 | 101       | 0        |
    And the following "global assets" exist in "mod_facetoface" plugin:
      | name  |
      | Asset1 |
    And the following "custom assets" exist in "mod_facetoface" plugin:
      | name  |
      | Asset2 |
      | Asset3 |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname | source           |
      | Report1  | rp1       | facetoface_asset |

  Scenario: Check whether the seminar asset report displays only assigned custom assets
    Given I log in as "admin"
    And I click on "Reports" in the totara menu
    And I follow "Report1"
    Then I should not see "Asset2"
    And I should not see "Asset3"

  @javascript
  Scenario: I add a custom asset to a course and I should be able to see it
    Given I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course101"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1           |
      | Description | This is description |
    And I follow "Seminar 1"
    And I follow "Add a new event"
    And I follow "Select asset"
    And I follow "Asset2"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I click on "Reports" in the totara menu
    And I follow "Report1"
    Then I should see "Asset2"
    And I should not see "Asset3"
