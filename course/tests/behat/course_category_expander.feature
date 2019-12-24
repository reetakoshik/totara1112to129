@core @core_course @javascript
Feature: Find learning course category management
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
    And the following "courses" exist:
        | shortname | fullname  | category | visible |
        | t1        | top1      | 0        | 0       |
        | t2        | top2      | 0        | 1       |
        | c1c1      | c1c1      | cat1     | 0       |
        | c1c2      | c1c2      | cat1     | 1       |
        | c1ac1     | c1ac1     | cat1a    | 1       |
        | c1bc1     | c1bc1     | cat1b    | 0       |
        | c1bc2     | c1bc2     | cat1b    | 1       |
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"
    And I log out

  Scenario: Test course expansion as a normal user
    Given I log in as "user1"
    When I click on "Find Learning" in the totara menu
    Then I should not see "top1"
    And I should not see "top2"
    And I should not see "c1c1"
    And I should not see "c1c2"
    And I should not see "c1ac1"
    And I should not see "c1bc1"
    And I should not see "c1bc2"

    # Some of these will be hidden
    When I click on "Expand all" "link"
    Then I should not see "top1"
    And I should see "top2"
    And I should not see "c1c1"
    And I should see "c1c2"
    And I should not see "c1ac1"
    And I should not see "c1bc1"
    And I should not see "c1bc2"

    When I click on "[data-categoryid=3] .categoryname" "css_element"
    Then I should see "c1ac1"
    And I should not see "c1bc2"