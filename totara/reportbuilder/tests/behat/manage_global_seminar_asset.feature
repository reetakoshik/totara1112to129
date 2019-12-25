@totara @totara_reportbuilder
Feature: As an administrator I am allowed to view the global seminar asset
  on the manage seminar asset page, but not on the custom seminar asset page.

  Background:
    Given I am on a totara site
    And the following "global assets" exist in "mod_facetoface" plugin:
      | name  |
      | Asset1 |
    And the following "custom assets" exist in "mod_facetoface" plugin:
      | name  |
      | Asset2 |

  Scenario: View the management page of global seminar assets
    Given I log in as "admin"
    And I navigate to "Seminars > Assets" in site administration
    Then I should see "Asset1"
    And I should not see "Asset2"
