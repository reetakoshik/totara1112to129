@totara @totara_core @totara_core_menu
Feature: Test Team menu item
  In order to use Team menu item
  As an admin
  I must be able to cofigure it

  Scenario: Make sure Team is available by default
    Given I am on a totara site
    And I log in as "admin"

    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Team" in the "#totaramenutable" "css_element"
    And I should see "Team" in the totara menu

  Scenario: I can see Team as manager
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | user003  | fn_003    | ln_003   | user003@example.com |
      | manager  | Big       | manager  | manager@example.com |
    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | idnumber | manager |
      | user001  | POS001   | 1        | manager |
      | user002  | POS001   | 1        | manager |

    When I log in as "manager"
    And I click on "Team" in the totara menu
    Then I should see "Team Members: 2 records shown"
    And I should see "fn_001 ln_001"
    And I should see "fn_002 ln_002"

  Scenario: I should not see Team as learner
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | user003  | fn_003    | ln_003   | user003@example.com |
      | manager  | Big       | manager  | manager@example.com |
    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | idnumber | manager |
      | user001  | POS001   | 1        | manager |
      | user002  | POS001   | 1        | manager |

    When I log in as "user001"
    Then I should not see "Team" in the totara menu

  Scenario: I can disable Team for everybody
    Given I am on a totara site
    And I log in as "admin"
    And I should see "Team" in the ".totaraNav" "css_element"
    And I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable Team" to "Disable"
    And I press "Save changes"

    When I navigate to "Main menu" node in "Site administration > Navigation"
    Then I should see "Team" in the "#totaramenutable" "css_element"
    And I should see "Feature disabled" in the "Team" "table_row"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    Then I should not see "Team Members (View)"
