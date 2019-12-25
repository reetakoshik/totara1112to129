@totara @totara_hierarchy @totara_core_menu
Feature: Test Goals menu item
  In order to use Goals menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure Goals is available in totara menu
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should see "Goals" in the "#totaramenutable" "css_element"
    And I should see "Goals" in the totara menu

  Scenario: Make sure Goals is available in totara menu even if everything else is disabled in Appraisals
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Appraisals" to "Disable"
    And I set the field "Enable 360 Feedbacks" to "Disable"
    And I press "Save changes"
    When I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should see "Goals" in the "#totaramenutable" "css_element"
    And I should see "Goals" in the totara menu

  Scenario: Make sure Goals is not in totara menu if feature disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Goals" to "Disable"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should not see "Goals" in the "#totaramenutable" "css_element"
    And I should not see "Goals" in the totara menu
