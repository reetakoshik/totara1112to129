@totara @totara_appraisal @totara_core_menu
Feature: Test Appraisals Main menu item
  In order to use Appraisals menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure Appraisals is available in totara menu
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Performance" in the "#totaramenutable" "css_element"
    And I should see "Parent" in the "Performance" "table_row"
    And I should see "Performance" in the totara menu

  Scenario: Make sure Appraisals is not in totara menu if all features disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Advanced features" node in "Site administration > System information"
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable Appraisals" to "Disable"
    And I set the field "Enable 360 Feedbacks" to "Disable"
    And I set the field "Enable Goals" to "Disable"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Performance" in the "#totaramenutable" "css_element"
    And I should see "Parent" in the "Performance" "table_row"
    And I should see "Feature disabled" in the "Latest Appraisal" "table_row"
    And I should see "Feature disabled" in the "All Appraisals" "table_row"
    And I should see "Feature disabled" in the "360° Feedback" "table_row"
    And I should see "Feature disabled" in the "Goals" "table_row"
