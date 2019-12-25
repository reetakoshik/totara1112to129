@totara @totara_program @totara_core_menu
Feature: Test Required Learning menu item
  In order to use Required Learning menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure Required learning is available in totara menu
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should see "Required Learning" in the "#totaramenutable" "css_element"
    And I should not see "Required Learning" in the totara menu

  Scenario: Make sure Required learning is not in totara menu if both prorams and certifications are disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Programs" to "Disable"
    And I set the field "Enable Certifications" to "Disable"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should not see "Required Learning" in the "#totaramenutable" "css_element"
    And I should not see "Required Learning" in the totara menu

    When I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Programs" to "Show"
    And I set the field "Enable Certifications" to "Disable"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should see "Required Learning" in the "#totaramenutable" "css_element"
    And I should not see "Required Learning" in the totara menu
