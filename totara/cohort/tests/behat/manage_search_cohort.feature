@totara @totara_cohort @javascript
Feature: Manage searches in audiences
  In order to mange search in audiences
  As an admin
  I need to create audiences, add some filters to the search and save the search

  Background:
    Given the following "categories" exist:
      | name        | idnumber |
      | CategoryOne | cat1     |
      | CategoryTwo | cat2     |
    And the following "cohorts" exist:
      | name              | idnumber | description         | contextlevel | reference |
      | Category Audience | 1        | About this audience | Category     | cat1      |
      | System Audience1  | 2        | About this audience | System       | 0         |
      | Cat2 Audience     | 3        | About this audience | Category     | cat2      |
      | System Audience2  | 4        | About this audience | System       | 0         |

  Scenario: Manage searches in audience
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "All audiences"
    And I set the field "cohort-name" to "Cat"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "Category Audience"
    And I should see "Cat2 Audience"
    And I should not see "System Audience1"
    And I should not see "System Audience2"
    And I click on "Save this search" "button"
    And I set the field "name" to "Search by Cat"
    And I click on "Save changes" "button"
    And I follow "All audiences"
    And I set the field "cohort-name" to "Audience1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "System Audience1"
    And I should not see "System Audience2"
    And I should not see "Category Audience"
    And I should not see "Cat2 Audience"
    And I click on "Save this search" "button"
    And I set the field "name" to "Search by Audience1"
    And I click on "Save changes" "button"
    And I follow "All audiences"
    And "Manage searches" "button" should exist

    When I click on "Manage searches" "button"
    Then I should see "Search by Cat" in the "searchlist" "totaradialogue"
    And I should see "Search by Audience1" in the "searchlist" "totaradialogue"

    When I click on "Edit" "link" in the "Search by Cat" "table_row"
    Then I should see "Editing saved search"

    When I set the field "name" to "Search by Category"
    And I click on "Save changes" "button"
    Then I should see "Search by Category"
    And I click on "Close" "button" in the "searchlist" "totaradialogue"

    When I click on "Manage searches" "button"
    And I click on "Delete" "link" in the "Search by Category" "table_row"
    Then I should see "Are you sure you want to delete this saved search" in the "searchlist" "totaradialogue"
    And I click on "Continue" "button"
    And I should not see "Search by Category"
    And I click on "Close" "button" in the "searchlist" "totaradialogue"

    When I follow "System audiences"
    And I click on "Manage searches" "button"
    Then I should see "Search by Audience1" in the "searchlist" "totaradialogue"
