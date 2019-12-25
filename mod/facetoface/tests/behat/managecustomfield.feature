@mod @totara @mod_facetoface @javascript @totara_customfield
Feature: Configure seminar custom fields
  In order to use seminar
  As a configurator
  I need to configure custom fields

  Scenario: Access seminar activity custom fields as admin
    Given I am on a totara site
    And I log in as "admin"

    When I navigate to "Custom fields" node in "Site administration > Seminars"
    Then I should see "Create a new custom field"

    When I follow "Asset"
    Then I should see "Create a new custom field"

    When I follow "Room"
    Then I should see "Create a new custom field"

    When I follow "Sign-up"
    Then I should see "Create a new custom field"

    When I follow "User cancellation"
    Then I should see "Create a new custom field"

    When I follow "Event cancellation"
    Then I should see "Create a new custom field"

  Scenario: Access seminar activity custom fields with modconfig capability
    Given I am on a totara site
    And the following "users" exist:
      | username     | firstname    | lastname | email         |
      | configurator | Configurator | User     | c@example.com |

    And I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I click on "Add a new role" "button"
    And I click on "Continue" "button"
    And I set the following fields to these values:
      | Short name                       | configurator          |
      | Custom full name                 | Activity configurator |
      | contextlevel10                   | 1                     |
      | mod/facetoface:managecustomfield | 1                     |
      | totara/core:modconfig            | 1                     |
    And I click on "Create this role" "button"
    And the following "role assigns" exist:
      | user         | role         | contextlevel | reference |
      | configurator | configurator | System       |           |
    And I log out
    And I log in as "configurator"

    When I navigate to "Custom fields" node in "Site administration > Seminars"
    Then I should see "Create a new custom field"

    When I follow "Asset"
    Then I should see "Create a new custom field"

    When I follow "Room"
    Then I should see "Create a new custom field"

    When I follow "Sign-up"
    Then I should see "Create a new custom field"

    When I follow "User cancellation"
    Then I should see "Create a new custom field"

    When I follow "Event cancellation"
    Then I should see "Create a new custom field"

