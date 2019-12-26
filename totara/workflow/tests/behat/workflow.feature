@totara_workflow
Feature: Visit a workflow and experience the different possible behaviours

  # We must set up content marketplace so we have a second create course workflow to test with
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I navigate to "Setup Content Marketplaces" node in "Site administration > Content Marketplace"
    And I should see "What is Content Marketplace?"
    And I should see "Enable" in the ".contentmarketplace_goone" "css_element"
    When I click on "Enable" "link" in the ".contentmarketplace_goone" "css_element"
    And I switch to "setup" window
    And I should see "Allow Totara to access GO1"
    And the following should exist in the "state" table:
      | full_name       | Admin User         |
      | email           | moodle@example.com |
      | users_total     | 1                  |
    And I click on "Authorize Totara" "button"
    And I switch to the main window
    Then I should see "Subscription details"
    And I should see "testing.mygo1.com"
    And I click on "Continue" "button"
    And I should see "All content (82,137)"
    And I click on "Save and explore GO1" "button"
    And I should see "Explore Content Marketplace: GO1"
    And I should see "82,137 results"
    And I am on site homepage
    And I navigate to "Manage Content Marketplaces" node in "Site administration > Content Marketplace"
    And I should not see "What is Content Marketplace?"

  @javascript @_switch_window
  Scenario: Pass through a workflow with multiple available options
    Given I am on a totara site
    And I click on "Courses" in the totara menu
    And I click on "Create Course" "button"
    Then I should see "Add a new course"
    And I should see "Create a multi-activity course"
    And I should see image with alt text "Create a multi-activity course"
    And I should see "Add courses from the GO1 content marketplace"
    And I should see image with alt text "Add courses from the GO1 content marketplace"
    When I click on "Create a multi-activity course" "link"
    Then I should see "Add a new course"
    And I should see "Course full name"
    And I should see "Courses and categories"

  @javascript @_switch_window
  Scenario: Pass through a workflow with multiple available options but only one enabled
    Given I am on a totara site
    And I navigate to "Manage workflows" node in "Site administration > Navigation"
    And I click on "Disable" "link" in the "Add courses from the GO1 content marketplace" "table_row"
    And I click on "Courses" in the totara menu
    And I click on "Create Course" "button"
    Then I should not see "Create a multi-activity course"
    And I should not see "Add courses from the GO1 content marketplace"
    And I should see "Add a new course"
    And I should see "Course full name"
    And I should see "Courses and categories"

  @javascript @_switch_window
  Scenario: Attempt to use a workflow when none of the available options are enabled
    Given I am on a totara site
    And I navigate to "Manage workflows" node in "Site administration > Navigation"
    And I click on "Disable" "link" in the "Add courses from the GO1 content marketplace" "table_row"
    And I click on "Disable" "link" in the "Create a multi-activity course" "table_row"
    And I click on "Courses" in the totara menu
    Then "//input[@value='Create Course' and @type='submit']" "xpath_element" should not exist
