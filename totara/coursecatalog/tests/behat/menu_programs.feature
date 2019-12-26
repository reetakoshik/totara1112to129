@totara @totara_program @totara_core_menu
Feature: Test Programs menu item
  In order to use Programs menu item
  As an admin
  I must be able to configure it

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |

  Scenario: Make sure Programs is available in totara menu
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Programs" in the "#totaramenutable" "css_element"
    And I should see "Programs" in the totara menu

  Scenario: Make sure Programs is not in totara menu but is still in the editor if feature disabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable Programs" to "Disable"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Programs" in the "#totaramenutable" "css_element"
    And I should see "Feature disabled" in the "Programs" "table_row"
    And I should not see "Programs" in the totara menu
