@totara @totara_plan @totara_core_menu
Feature: Test Learning Plans Main menu item
  In order to use Learning Plans menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure Learning Plans is available in My Learning block
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Learning Plans" in the "#totaramenutable" "css_element"
    When I click on "Dashboard" in the totara menu
    And I should see "Learning Plans" in the "My Learning" "block"

  Scenario: Make sure Learning Plans is available in totara menu but not used
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then "Learning Plans" row "Visibility" column of "totaramenutable" table should contain "Unused"
    And I should not see "Learning Plans" in the totara menu

  Scenario: Make sure Learning Plans is not in totara menu if feature disabled
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable Learning Plans" to "Disable"
    And I press "Save changes"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    Then "Learning Plans" row "Visibility" column of "totaramenutable" table should contain "Unused"
    When I click on "Edit" "link" in the "Learning Plans" "table_row"
    And I set the following Totara form fields to these values:
      | Parent item       | Top            |
    And I press "Save changes"
    Then "Learning Plans" row "Visibility" column of "totaramenutable" table should contain "Feature disabled"
