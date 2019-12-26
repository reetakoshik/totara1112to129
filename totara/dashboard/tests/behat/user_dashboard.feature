@javascript @totara @totara_dashboard
Feature: Perform basic dashboard user changes
  In order to ensure that dashboard work as expected
  As a user
  I need to change dashboards

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username |
      | learner1 |
      | learner2 |
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort 1 | CH1 |
    And the following totara_dashboards exist:
      | name | locked | published | cohorts |
      | First dashboard | 1 | 1 | CH1 |
      | Dashboard locked published | 1 | 1 | CH1 |
      | Dashboard unlocked published | 0 | 1 | CH1 |
      | Dashboard unpublished | 1 | 0 | CH1 |
      | Dashboard unassigned | 1 | 1 | |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | CH1    |
    And I log in as "admin"
    And I set the following administration settings values:
      | defaulthomepage | Totara dashboard |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner2 | CH1    |
    And I log out

  Scenario: Add block to personal version of second dashboard and then reset
    And I log in as "learner1"
    And I follow "Dashboard unlocked published"

    # Add block.
    When I press "Customise this page"
    And I add the "Latest announcements" block
    Then "Latest announcements" "block" should exist
    And I press "Stop customising this page"
    And "Latest announcements" "block" should exist
    And I log out

    # Check that other users unaffected.
    When I log in as "learner2"
    And I follow "Dashboard unlocked published"
    Then "Latest announcements" "block" should not exist
    And I log out

    # Reset dashboard to master version.
    When I log in as "learner1"
    And I follow "Dashboard unlocked published"
    And "Latest announcements" "block" should exist
    And "Customise this page" "button" should exist
    And I press "Customise this page"
    And I press "Reset dashboard to default"
    Then "Latest announcements" "block" should not exist

  Scenario: Confirm that dashboard blocks positions maintained when customised by users
    Given I log in as "admin"
    And I follow "Dashboard"
    And I press "Manage dashboards"
    And I click on "Dashboard unlocked published" "link"
    And I press "Blocks editing on"
    And I add the "Latest announcements" block
    And I add the "Navigation" block if not present

    # Move blocks around
    And I click on "span.moodle-core-dragdrop-draghandle" "css_element" in the "Latest announcements" "block"
    And I click on "//a[contains(., 'To item \"Navigation\"')]" "xpath_element"
    And I click on "span.moodle-core-dragdrop-draghandle" "css_element" in the "Navigation" "block"
    And I click on "//a[contains(., 'To item \"Dashboards\"')]" "xpath_element"
    And I log out

    And I log in as "learner1"
    And I follow "Dashboard unlocked published"
    And I should see "Navigation" in the "#region-main" "css_element"
    And I should not see "Navigation" in the "#block-region-side-pre" "css_element"
    And I should see "Latest announcements" in the "#block-region-side-pre" "css_element"
    And I should not see "Latest announcements" in the "#region-main" "css_element"

    When I press "Customise this page"
    Then I should see "Navigation" in the "#region-main" "css_element"
    And I should not see "Navigation" in the "#block-region-side-pre" "css_element"
    And I should see "Latest announcements" in the "#block-region-side-pre" "css_element"
    And I should not see "Latest announcements" in the "#region-main" "css_element"

  Scenario: Cannot change locked dashboard
    When I log in as "learner1"
    And I follow "Dashboard locked published"
    Then "Customise this page" "button" should not exist

  Scenario: Cannot see dashboard that is unpublished/unassigned
    When I log in as "learner1"
    Then I should not see "Dashboard unassigned"
    And I should not see "Dashboard unpublished"
    And I should see "Dashboard locked published"
