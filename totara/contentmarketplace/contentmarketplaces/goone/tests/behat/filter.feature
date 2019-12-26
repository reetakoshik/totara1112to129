@totara @totara_contentmarketplace @contentmarketplace_goone @javascript @_switch_window
Feature: Search for content in the Go1 content marketplace
  As an admin
  I should be able to filter the content marketplace

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Setup Content Marketplaces" node in "Site administration > Content Marketplace"
    And I should see "Enable" in the ".contentmarketplace_goone" "css_element"
    When I click on "Enable" "link" in the ".contentmarketplace_goone" "css_element"
    And I switch to "setup" window
    And the following should exist in the "state" table:
      | full_name       | Admin User         |
      | email           | moodle@example.com |
      | users_total     | 1                  |
    And I click on "Authorize Totara" "button"
    And I switch to the main window
    And I click on "Continue" "button"
    And I click on "Save and explore GO1" "button"
    And I should see "Explore Content Marketplace: GO1"
    And I should see "82,137 results"
    And "All 82,137" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 319" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 4" "radio" in the "Availability" "fieldset" should be visible


  Scenario: Filter by availability
    # Filter to list the subscribed content
    When I click on "Subscription" "radio"
    Then I should see "319 results"

    # Go back to listing of all the content
    When I click on "All" "radio"
    Then I should see "82,137 results"


  Scenario: Filter on a tag
    # Add tag "Technology" to the filter
    When I click on "tags" "field"
    And I set the field "tags" to "tech"
    And I click on "Technology" "checkbox"
    Then I should see "20,540 results"
    And I should see "Technology" in the "[data-filter-name=tags]" "css_element"
    And "All 20,540" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 123" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 0" "radio" in the "Availability" "fieldset" should be visible

    # Remove the "Technology" tag from the filter
    When I click on "Technology" "checkbox"
    Then I should see "82,137 results"
    And I should not see "Technology" in the "[data-filter-name=tags]" "css_element"
    And "All 82,137" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 319" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 4" "radio" in the "Availability" "fieldset" should be visible


  Scenario: Filter on a couple of tags
    # Add tag "Technology" to the filter
    When I click on "tags" "field"
    And I set the field "tags" to "tech"
    And I click on "Technology" "checkbox"
    Then I should see "20,540 results"
    And I should see "Technology" in the "[data-filter-name=tags]" "css_element"
    And "All 20,540" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 123" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 0" "radio" in the "Availability" "fieldset" should be visible

    # Add tag "Communication" to the filter
    When I set the field "tags" to "comm"
    And I click on "Communication" "checkbox"
    Then I should see "20,623 results"
    And I should see "Technology" in the "[data-filter-name=tags]" "css_element"
    And I should see "Communication" in the "[data-filter-name=tags]" "css_element"
    And "All 20,623" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 130" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 0" "radio" in the "Availability" "fieldset" should be visible

    # Remove the "Technology" tag from the filter
    When I click on "Technology" "checkbox"
    Then I should see "93 results"
    And I should not see "Technology" in the "[data-filter-name=tags]" "css_element"
    And I should see "Communication" in the "[data-filter-name=tags]" "css_element"
    And "All 93" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 7" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 0" "radio" in the "Availability" "fieldset" should be visible

    # Remove the "Communication" tag from the filter
    When I click on "Communication" "checkbox"
    Then I should see "82,137 results"
    And I should not see "Technology" in the "[data-filter-name=tags]" "css_element"
    And I should not see "Communication" in the "[data-filter-name=tags]" "css_element"
    And "All 82,137" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 319" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 4" "radio" in the "Availability" "fieldset" should be visible


  Scenario: Combine several filters
    # First filter on the subscribed content
    When I click on "Subscription" "radio"
    Then I should see "319 results"

    # Add the tag "Technology" to the filter
    When I click on "tags" "field"
    And I set the field "tags" to "tech"
    And I click on "Technology" "checkbox"
    Then I should see "54 results"
    And I should see "Technology" in the "[data-filter-name=tags]" "css_element"
    And "All 20,540" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 54" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 0" "radio" in the "Availability" "fieldset" should be visible

    # Add the language "Japanese" to the filter
    When I click on "language" "field"
    And I click on "Japanese" "checkbox"
    Then I should see "No results"
    And I should see "Technology" in the "[data-filter-name=tags]" "css_element"
    And I should see "Japanese" in the "[data-filter-name=language]" "css_element"
    And "All 0" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 0" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 0" "radio" in the "Availability" "fieldset" should be visible

    # Add the language "English" to the filter
    When I click on "English" "checkbox"
    Then I should see "54 results"
    And I should see "Technology" in the "[data-filter-name=tags]" "css_element"
    And I should see "Japanese" in the "[data-filter-name=language]" "css_element"
    And I should see "English" in the "[data-filter-name=language]" "css_element"
    And "All 20,540" "radio" in the "Availability" "fieldset" should be visible
    And "Subscription 54" "radio" in the "Availability" "fieldset" should be visible
    And "Custom collection 0" "radio" in the "Availability" "fieldset" should be visible
