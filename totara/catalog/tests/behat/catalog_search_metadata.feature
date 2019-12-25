@totara @totara_catalog @javascript
Feature: Catalog metadata search
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | C101     | c101      | 0        |
      | C102     | c102      | 0        |
    And I am on a totara site
    And I log in as "admin"
    And I run the scheduled task "\totara_catalog\task\refresh_catalog_data"


  Scenario: Create a course with search terms and search for the term
    Given I click on "Find Learning" in the totara menu
    And I click on "div.tw-catalogManageBtns__btn" "css_element"
    And I click on "Course" "link" in the "li.tw-catalogManageBtns__group_options_item" "css_element"
    And I follow "Expand all"
    And I set the following fields to these values:
      | Course full name  | C103 |
      | Course short name | c103 |
    And I set the field "Search terms (single words, space-separated)" to "hello world"
    And I click on "Save and display" "button"
    And I click on "Find Learning" in the totara menu
    And I set the field with xpath "//*[@id='catalog_fts_input']" to "hello"
    When I click on "Search" "button" in the "#region-main" "css_element"
    Then I should see "C103"

  Scenario: Create a program with search terms and search for the term
    Given I navigate to "Programs > Manage programs" in site administration
    And I click on "Add a new program" "button"
    And I follow "Expand all"
    And I set the field "Search terms (single words, space-separated)" to "hello world"
    And I click on "Save changes" "button"
    And I click on "Find Learning" in the totara menu
    And I set the field with xpath "//*[@id='catalog_fts_input']" to "world"
    When I click on "Search" "button" in the "#region-main" "css_element"
    Then I should see "Program fullname 101"

  Scenario: Create a certification with search terms and search for the term
    Given I navigate to "Certifications > Manage certifications" in site administration
    And I click on "Add new certification" "button"
    And I follow "Expand all"
    And I set the field "Search terms (single words, space-separated)" to "hello world"
    And I click on "Save changes" "button"
    And I click on "Find Learning" in the totara menu
    And I set the field with xpath "//*[@id='catalog_fts_input']" to "hello"
    When I click on "Search" "button" in the "#region-main" "css_element"
    Then I should see "Certification program fullname 101"