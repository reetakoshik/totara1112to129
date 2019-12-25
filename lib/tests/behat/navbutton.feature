@core @javascript
Feature: Navigation button works in low resolutions
  In order to use Totara on small devices
  As a user
  I need to be able to expand the menu

  Scenario: Navigation button expands and collapses the totara menu
    Given I am on a totara site
    And I change viewport size to "small"
    And I log in as "admin"
    Then "#totaramenu" "css_element" should not be visible

    # Expand
    When I click on "Toggle navigation" "link_or_button"
    And I wait "1" seconds
    Then "#totaramenu" "css_element" should be visible

    # Collapse
    When I click on "Toggle navigation" "link_or_button"
    And I wait "1" seconds
    Then "#totaramenu" "css_element" should not be visible
