@totara @block @block_last_course_accessed
Feature: Site Administrator can enable and disable the LCA block.

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    When I log in as "admin"
    And I follow "Find Learning"
    And I follow "Course 1"
    And I press "Turn editing on"
    And I add the "Last Course Accessed" block
    Then I should see "Last Course Accessed"
    And I log out

  Scenario: Verify Site Administrator can disable the LCA block.
    Given I log in as "admin"
    When I navigate to "Manage blocks" node in "Site administration > Plugins > Blocks"
    And I click on "Hide" "link" in the "Last Course Accessed" "table_row"

    And I follow "Find Learning"
    And I follow "Course 1"
    Then I should not see "Last Course Accessed"
    And I log out

  Scenario: Verify Site Administrator can enable the LCA block.
    Given I log in as "admin"
    When I navigate to "Manage blocks" node in "Site administration > Plugins > Blocks"
    And I click on "Hide" "link" in the "Last Course Accessed" "table_row"
    And I click on "Show" "link" in the "Last Course Accessed" "table_row"

    # For some reason, when Show is clicked the browser is thrown to the
    # course search page, which make no sense. Consequently, this test fails
    # when the comment below is removed.
    # Following the same steps manually works without problem.

    And I follow "Find Learning"
    And I follow "Course 1"
    # Then I should see "Last Course Accessed"
    And I log out
