@totara @totara_plan @totara_rol @javascript @totara_core_menu
Feature: Check Record of Learning feature visibility
  In order to control access to RoL
  As an admin
  I need to be able to enable and disable it

  Scenario: Verify Record of Learning appears in the Totara menu if enabled
    Given I am on a totara site
    And I log in as "admin"

    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Record of Learning" in the "#totaramenutable" "css_element"
    And I should see "Record of Learning" in the totara menu

  Scenario: Verify Record of Learning does not appear in the Totara menu if disabled
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable Record of Learning" to "Disable"
    And I press "Save changes"

    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Record of Learning" in the "#totaramenutable" "css_element"
    And I should see "Feature disabled" in the "Record of Learning" "table_row"
    And I should not see "Record of Learning" in the totara menu

