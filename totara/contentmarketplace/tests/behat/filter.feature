@totara @totara_contentmarketplace
Feature: Totara content marketplace filter tests
  In order to test the filter elements
  As an admin
  I use the test filters to confirm behaviour

  Background:
    Given I am on a totara site


  @javascript
  Scenario: Search within a filter listing
    When I navigate to the content marketplace test filters
    And I should not see "One" in the "Tags" "fieldset"
    And I should not see "Two" in the "Tags" "fieldset"
    And I click on "tags" "field"
    Then I should see "One" in the "Tags" "fieldset"
    And I should see "Two" in the "Tags" "fieldset"

    When I set the field "tags" to "one"
    Then I should see "One" in the "Tags" "fieldset"
    And I should not see "Two" in the "Tags" "fieldset"

    When I set the field "tags" to "t"
    Then I should not see "One" in the "Tags" "fieldset"
    And I should see "Two" in the "Tags" "fieldset"

    When I set the field "tags" to ""
    Then I should see "One" in the "Tags" "fieldset"
    And I should see "Two" in the "Tags" "fieldset"


  @javascript
  Scenario: Select an element of a filter
    When I navigate to the content marketplace test filters
    And I should not see "One" in the "Tags" "fieldset"
    And I should not see "Two" in the "Tags" "fieldset"
    And I click on "tags" "field"
    Then I should see "One" in the "Tags" "fieldset"

    # Click to tag "One" to add it to the filter
    When I click on "One" "checkbox"
    Then "[data-filter-name=tags] .tcm-search-filter-selection input[checked]" "css_element" should exist
    And I should see "One" in the "Tags" "fieldset"
    And I should not see "One" in the "[data-filter-name=tags] .tcm-search-filter-results" "css_element"

    # Click to tag "One" again to remove it from the filter
    When I click on "One" "checkbox"
    Then "[data-filter-name=tags] .tcm-search-filter-selection input[checked]" "css_element" should not exist
    And I should not see "One" in the "Tags" "fieldset"
    And I should not see "One" in the "[data-filter-name=tags] .tcm-search-filter-results" "css_element"

    # The tag "One" is available for selection again
    When I click on "tags" "field"
    Then I should see "One" in the "Tags" "fieldset"


  @javascript
  Scenario: Filter search results will close if it's corresponding field loses focus
    When I navigate to the content marketplace test filters
    And "[data-filter-name=tags] .tcm-search-filter-results" "css_element" should not be visible
    And I click on "tags" "field"
    Then "[data-filter-name=tags] .tcm-search-filter-results" "css_element" should be visible

    When I click on "language" "field"
    Then "[data-filter-name=tags] .tcm-search-filter-results" "css_element" should not be visible


  @javascript
  Scenario: Tab to focus a searchable filter
    When I navigate to the content marketplace test filters
    And I click on "All" "radio"
    And "[data-filter-name=language] .tcm-search-filter-results" "css_element" should not be visible
    And I press tab
    Then "[data-filter-name=language] .tcm-search-filter-results" "css_element" should be visible

    # Lose focus by tabbing back to the arability filter
    When I press shift tab
    Then "[data-filter-name=language] .tcm-search-filter-results" "css_element" should not be visible
