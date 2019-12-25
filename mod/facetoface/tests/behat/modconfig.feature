@mod @totara @mod_facetoface @javascript
Feature: Configure seminar settings
  In order to use seminar
  As a configurator
  I need to access all settings

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname    | lastname | email         |
      | configurator | Configurator | User     | c@example.com |

    And I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Users > Permissions"
    And I click on "Add a new role" "button"
    And I click on "Continue" "button"
    And I set the following fields to these values:
      | Short name            | configurator          |
      | Custom full name      | Activity configurator |
      | contextlevel10        | 1                     |
      | totara/core:modconfig | 1                     |
    And I click on "Create this role" "button"
    And the following "role assigns" exist:
      | user         | role         | contextlevel | reference |
      | configurator | configurator | System       |           |
    And I log out

  Scenario: Access all seminar activity settings with modconfig capability
    Given I log in as "configurator"

    When I navigate to "Global settings" node in "Site administration > Seminars"
    Then I should see "facetoface_fromaddress"

    When I navigate to "Activity defaults" node in "Site administration > Seminars"
    Then I should see "facetoface_multiplesessions"

    When I navigate to "Event defaults" node in "Site administration > Seminars"
    Then I should see "defaultdaysskipweekends"

    When I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Add" "button"
    Then I should see "Manager copy prefix"

    When I navigate to "Rooms" node in "Site administration > Seminars"
    And I click on "Add a new room" "button"
    Then I should see "Name"

    When I navigate to "Assets" node in "Site administration > Seminars"
    And I click on "Add a new asset" "button"
    Then I should see "Asset name"
