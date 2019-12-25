@totara @totara_contentmarketplace @contentmarketplace_goone
Feature: Disabling a content marketplace

  Scenario: An enabled marketplace has several actions
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 1 | contentmarketplace_goone |
    And I log in as "admin"
    When I navigate to "Manage Content Marketplaces" node in "Site administration > Content Marketplace"
    Then I should see "Enabled" in the ".contentmarketplace_goone" "css_element"
    And "Settings" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Disable" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Enable" "link" should not exist in the ".contentmarketplace_goone" "css_element"
    And "Set up" "link" should exist in the ".contentmarketplace_goone" "css_element"

  Scenario: A disabled marketplace has several actions disabled
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 0 | contentmarketplace_goone |
    And I log in as "admin"
    When I navigate to "Manage Content Marketplaces" node in "Site administration > Content Marketplace"
    Then I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    And "Settings" "link" should not exist in the ".contentmarketplace_goone" "css_element"
    And "Disable" "link" should not exist in the ".contentmarketplace_goone" "css_element"
    And "Enable" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Set up" "link" should exist in the ".contentmarketplace_goone" "css_element"

  @javascript
  Scenario: An enabled marketplace can be disabled
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 1 | contentmarketplace_goone |
    And I log in as "admin"
    And I navigate to "Manage Content Marketplaces" node in "Site administration > Content Marketplace"
    And I should see "Enabled" in the ".contentmarketplace_goone" "css_element"
    When I click on "Disable" "link" in the ".contentmarketplace_goone" "css_element"
    And I should see "Are you sure?" in the ".modal" "css_element"
    And I click on "Disable GO1" "button" in the ".modal" "css_element"
    Then I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    And "Enable" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Disable" "link" should not exist in the ".contentmarketplace_goone" "css_element"

  Scenario: An enabled marketplace can be disabled
    Given I am on a totara site
    And the following config values are set as admin:
      | enabled | 0 | contentmarketplace_goone |
    And I log in as "admin"
    And I navigate to "Manage Content Marketplaces" node in "Site administration > Content Marketplace"
    And I should see "Disabled" in the ".contentmarketplace_goone" "css_element"
    When I click on "Enable" "link" in the ".contentmarketplace_goone" "css_element"
    Then I should see "Enabled" in the ".contentmarketplace_goone" "css_element"
    And "Disable" "link" should exist in the ".contentmarketplace_goone" "css_element"
    And "Enable" "link" should not exist in the ".contentmarketplace_goone" "css_element"
