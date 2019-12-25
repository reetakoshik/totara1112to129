@totara @totara_feedback360 @totara_core_menu
Feature: Test 360 Feedback menu item
  In order to use 360 Feedback menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure 360 Feedback is available in totara menu
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should see "360° Feedback" in the "#totaramenutable" "css_element"
    And I should not see "360° Feedback" in the totara menu

  Scenario: Make sure 360 Feedback is available in totara menu even if other things disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Goals" to "Disable"
    And I set the field "Enable Appraisals" to "Disable"
    And I press "Save changes"
    When I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should see "360° Feedback" in the "#totaramenutable" "css_element"
    And I should not see "360° Feedback" in the totara menu

  Scenario: Make sure 360 Feedback is not in totara menu if feature disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable 360 Feedbacks" to "Disable"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Appearance"
    Then I should not see "360° Feedback" in the "#totaramenutable" "css_element"
    And I should not see "360° Feedback" in the totara menu
