@totara @totara_catalog @javascript
Feature: Using the sorting feature of catalog
  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname                | shortname | category | coursetype |
      | Course A Korean Drama   | course A  | 0        | 0          |
      | Course Course Bolo bala | course B  | 0        | 0          |
      | Course This is SPARTAN  | course C  | 0        | 2          |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Configure catalogue"
    And I follow "General"
    And I set the following Totara form fields to these values:
      | Featured learning | 1 |
    And I wait for pending js
    And I set the following Totara form fields to these values:
      | featured_learning_source | Course Type |
      | featured_learning_value  | Seminar     |
    And I click on "Save" "button"

  Scenario: With only one language installed, sorting can be changed manually
    When I click on "Find Learning" in the totara menu
    Then I should see "Featured"
    And "Course This is SPARTAN" "text" should appear before "Course Bolo bala" "text"
    And "Course This is SPARTAN" "text" should appear before "Course A Korean Drama" "text"
    When I follow "Featured"
    And I follow "Alphabetical"
    Then "Course A Korean Drama" "text" should appear before "Course Bolo bala" "text"
    And "Course Course Bolo bala" "text" should appear before "Course This is SPARTAN" "text"

  Scenario: With two languages installed, sorting is changed automatically
    Given I navigate to "Language packs" node in "Site administration > Localisation"
    And I set the field "Available language packs" to "fr"
    And I press "Install selected language pack(s)"
    When I click on "Find Learning" in the totara menu
    Then I should not see "Sort by"
    And "Course This is SPARTAN" "text" should appear before "Course Bolo bala" "text"
    And "Course This is SPARTAN" "text" should appear before "Course A Korean Drama" "text"
    And I set the field with xpath "//*[@id='catalog_fts_input']" to "course"
    When I click on "Search" "button" in the "#region-main" "css_element"
    Then "Course Course Bolo bala" "text" should appear before "Course A Korean Drama" "text"
    Then "Course Course Bolo bala" "text" should appear before "Course This is SPARTAN" "text"
