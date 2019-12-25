@totara @totara_program @javascript
Feature: Find learning program category management
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
    Given the following "programs" exist in "totara_program" plugin:
        | shortname | fullname  | category | visible |
        | t1        | top1      | 0        | 0       |
        | t2        | top2      | 0        | 1       |
        | c1p1      | c1p1      | cat1     | 0       |
        | c1p2      | c1p2      | cat1     | 1       |
        | c1ap1     | c1ap1     | cat1a    | 1       |
        | c1bp1     | c1bp1     | cat1b    | 0       |
        | c1bp2     | c1bp2     | cat1b    | 1       |
    And I log in as "admin"
    And I set the following administration settings values:
        | Enhanced catalog | 0 |
    And I press "Save changes"
    And I log out

  Scenario: Test program expansion as a normal user
    Given I log in as "user1"
    When I click on "Programs" in the totara menu
    Then I should not see "top1"
    And I should not see "top2"
    And I should not see "c1p1"
    And I should not see "c1p2"
    And I should not see "c1ap1"
    And I should not see "c1bp1"
    And I should not see "c1bp2"

    # Some of these will be hidden
    When I click on "Expand all" "link"
    Then I should not see "top1"
    And I should see "top2"
    And I should not see "c1p1"
    And I should see "c1p2"
    And I should not see "c1ap1"
    And I should not see "c1bp1"
    And I should not see "c1bp2"

    When I click on "[data-categoryid=3] .categoryname" "css_element"
    Then I should see "c1ap1"
    And I should not see "c1bp2"
