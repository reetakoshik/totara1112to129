@totara @totara_core @javascript
Feature: Test manageprofilefields capability
    In order to ensure that manageprofilefields capability allows
    Site Managers to manage user profile custom fields
    I need to allow this capability to Site Manager and check that
    they can create new custom field

Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | manager1 | Manager | 1 | manager1@example.com |

Scenario: Test that user with right capability can manage user profile custom fields
    Given I log in as "admin"
    And I set the following system permissions of "Site Manager" role:
      | capability | permission |
      | totara/core:manageprofilefields | Allow |
    And I navigate to "Assign system roles" node in "Site administration > Users > Permissions"
    And I follow "Site Manager"
    And I set the field "Potential users" to "Manager 1 (manager1@example.com)"
    And I press "Add"
    And I log out
    Then I log in as "manager1"
    And I navigate to "User profile fields" node in "Site administration > Users > Accounts"
    # Field doesn't have a label
    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
        | Short name (must be unique) |  MyText |
        | Name | mytext |
    And I press "Save changes"
    Then I should see "mytext" in the "table.profilefield tr td.cell" "css_element"

    # Remove this field
    When I click on "Delete" "link" in the "mytext" "table_row"
    Then I should see "Deleted"

