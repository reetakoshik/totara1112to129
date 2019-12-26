@totara @totara_coursecatelogue @totara_core_menu
Feature: Test Certifications menu item
  In order to use Certifications menu item
  As an admin
  I must be able to configure it

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |

  Scenario: Make sure Certifications is available in totara menu
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Certifications" in the "#totaramenutable" "css_element"
    And I should see "Certifications" in the totara menu

  Scenario: Make sure Certifications is not in totara menu if feature disabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable Certifications" to "Disable"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Certifications" in the "#totaramenutable" "css_element"
    And I should see "Feature disabled" in the "Certifications" "table_row"
