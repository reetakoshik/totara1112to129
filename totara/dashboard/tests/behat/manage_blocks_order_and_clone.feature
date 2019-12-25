@totara @totara_dashboard
Feature: Create a dashboard with HTML blocks, re-order the blocks, clone the dashboard, check the blocks for the same order of a new dashboard
  In order to ensure that dashboard can be cloned with HTML blocks
  As an admin
  I create and customise a dashboard
  I then clone the dashboard with content

  @javascript
  Scenario: Cloning a dashboard copies HTML blocks in same order
    Given I am on a totara site
    And the following totara_dashboards exist:
      | name           | locked | published |
      | My dashboard   | 0      | 1         |
    # Add a block to the my dashboard
    And I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "My dashboard" "link"
    And I press "Blocks editing on"
    # Add HTML block 1
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes                                                      |
      | Block title                  | HTML block 1                                             |
      | Content                      | Lorem ipsum dolor sit amet, consectetur adipisicing elit |
      | Weight                       | 3                                                        |
    And I press "Save changes"
    # Add HTML block 2
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes                                                             |
      | Block title                  | HTML block 2                                                    |
      | Content                      | Duis aute irure dolor in reprehenderit in voluptate velit esset |
      | Weight                       | 2                                                               |
    And I press "Save changes"
    # Add HTML block 3
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes                                                           |
      | Block title                  | HTML block 3                                                  |
      | Content                      | Excepteur sint occaecat cupidatat non proident, sunt in culpa |
      | Weight                       | 1                                                             |
    And I press "Save changes"
    And I press "Blocks editing off"

    # Make sure the blocks are in the right order
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "My dashboard" "link"
    Then "Skip HTML block 3" "link" should appear before "Skip HTML block 2" "link"
    And "Skip HTML block 2" "link" should appear before "Skip HTML block 1" "link"

    # Clone original dashboard
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Clone dashboard" "link" in the "My dashboard" "table_row"
    Then I should see "Do you really want to clone dashboard My dashboard?"
    # Confirm the clone
    When I press "Continue"
    Then I should see "Dashboard 'My dashboard' successfully cloned to 'My dashboard copy 1'"
    # Check the HTML blocks for the same order of a new dashboard
    When I click on "My dashboard copy 1" "link"
    Then "Skip HTML block 3" "link" should appear before "Skip HTML block 2" "link"
    And "Skip HTML block 2" "link" should appear before "Skip HTML block 1" "link"
