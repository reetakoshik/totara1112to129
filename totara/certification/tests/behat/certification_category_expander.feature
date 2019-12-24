@totara @totara_certification @javascript
Feature: Find learning certificate category management
  As any user I should be able to expand and contract categories

  Background:
    Given I am on a totara site
    And the following "users" exist:
        | username | email             |
        | user1    | user1@example.com |
    And the following "categories" exist:
        | category | idnumber | name        |
        | 0        | cat1     | Category 1  |
        | cat1     | cat1a    | Category 1a |
        | cat1     | cat1b    | Category 1b |
    Given the following "certifications" exist in "totara_program" plugin:
        | shortname | fullname  | category | visible |
        | t1        | top1      | 1        | 0       |
        | t2        | top2      | 1        | 1       |
        | c1c1      | c1c1      | 2        | 0       |
        | c1c2      | c1c2      | 2        | 1       |
        | c1ac1     | c1ac1     | 3        | 1       |
        | c1bc1     | c1bc1     | 4        | 0       |
        | c1bc2     | c1bc2     | 4        | 1       |
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"
    And I log out

  Scenario: Test certificate expansion as a normal user
    Given I log in as "user1"
    When I click on "Certifications" in the totara menu
    Then I should not see "top1"
    And I should not see "top2"
    And I should not see "c1c1"
    And I should not see "c1c2"
    And I should not see "c1ac1"
    And I should not see "c1bc1"
    And I should not see "c1bc2"

    When I click on "[data-categoryid=2] .categoryname" "css_element"
    Then I should see "c1c2"
    And I should not see "top2"
