@core @core_block @javascript
Feature: Ensure page layouts are listed correctly
  In order for blocks to show in layouts
  As a user
  I need to set regions throughout the site

  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: Ensure editing a block on the home page lists the home page layout options
    When I navigate to "Turn editing on" node in "Front page settings"
    And I add the "Navigation" block if not present
    And I configure the "Navigation" block
    And I expand all fieldsets
    Then the "Default region" select box should contain "Left"
    And the "Default region" select box should contain "Right"
    And the "Default region" select box should contain "Main"
    And the "Default region" select box should contain "Top"
    And the "Default region" select box should contain "Bottom"
    And the "Region" select box should contain "Left"
    And the "Region" select box should contain "Right"
    And the "Region" select box should contain "Top"
    And the "Region" select box should contain "Bottom"
    And the "Region" select box should contain "Main"

  Scenario: Ensure editing a block on the dashboard lists the dashboard layout options
    Given I click on "Dashboard" in the totara menu
    And I click on "Customise this page" "button"
    And I add the "Navigation" block if not present
    And I configure the "Navigation" block
    And I expand all fieldsets
    Then the "Default region" select box should contain "Left"
    And the "Default region" select box should contain "Right"
    And the "Default region" select box should contain "Main"
    And the "Default region" select box should contain "Top"
    And the "Default region" select box should contain "Bottom"
    And the "Region" select box should contain "Left"
    And the "Region" select box should contain "Right"
    And the "Region" select box should contain "Top"
    And the "Region" select box should contain "Bottom"
    And the "Region" select box should contain "Main"

  Scenario: Ensure editing a block in a course lists the course layout options
    Given the following "courses" exist:
      | fullname | shortname |
      | course 1 | c1        |
    And I am on "course 1" course homepage with editing mode on
    And I add the "Navigation" block if not present
    And I configure the "Navigation" block
    And I expand all fieldsets
    Then the "Default region" select box should contain "Left"
    And the "Default region" select box should contain "Right"
    And the "Default region" select box should contain "Main"
    And the "Default region" select box should contain "Top"
    And the "Default region" select box should contain "Bottom"
    And the "Region" select box should contain "Left"
    And the "Region" select box should contain "Right"
    And the "Region" select box should contain "Top"
    And the "Region" select box should contain "Bottom"
    And the "Region" select box should not contain "Main"

  Scenario: Ensure editing a block in an activity lists the activity layout options
    Given the following "courses" exist:
      | fullname | shortname |
      | course 1 | c1        |
    And I am on "course 1" course homepage with editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | name | My forum |
    And I click on "My forum" "link"
    And I add the "Navigation" block if not present
    And I configure the "Navigation" block
    And I expand all fieldsets
    Then the "Default region" select box should contain "Left"
    And the "Default region" select box should contain "Right"
    And the "Default region" select box should contain "Main"
    And the "Default region" select box should contain "Top"
    And the "Default region" select box should contain "Bottom"
    And the "Region" select box should contain "Left"
    And the "Region" select box should contain "Right"
    And the "Region" select box should contain "Top"
    And the "Region" select box should contain "Bottom"
    And the "Region" select box should not contain "Main"

  Scenario: Ensure editing a block on an admin page lists the admin layout options
    When I navigate to "Notifications" node in "Site administration > System information"
    And I click on "Blocks editing on" "button"
    And I add the "Navigation" block if not present
    And I configure the "Navigation" block
    And I expand all fieldsets
    Then the "Default region" select box should contain "Left"
    And the "Default region" select box should contain "Right"
    And the "Default region" select box should contain "Main"
    And the "Default region" select box should contain "Top"
    And the "Default region" select box should contain "Bottom"
    And the "Region" select box should contain "Left"
    And the "Region" select box should not contain "Right"
    And the "Region" select box should not contain "Top"
    And the "Region" select box should not contain "Bottom"
    And the "Region" select box should not contain "Main"

  Scenario: Ensure regions get a special css class in editing mode only
    When I navigate to "Turn editing on" node in "Front page settings"
    Then "#block-region-main.editing-region-border" "css_element" should exist
    And "#block-region-top.editing-region-border" "css_element" should exist
    And "#block-region-bottom.editing-region-border" "css_element" should exist
    And "#block-region-side-pre.editing-region-border" "css_element" should exist
    And "#block-region-side-post.editing-region-border" "css_element" should exist
    When I navigate to "Turn editing off" node in "Front page settings"
    # Negative check only for one region as it takes longer.
    Then "#block-region-top.editing-region-border" "css_element" should not exist

    When I click on "Dashboard" in the totara menu
    And I click on "Customise this page" "button"
    Then "#block-region-main.editing-region-border" "css_element" should exist
    And "#block-region-top.editing-region-border" "css_element" should exist
    And "#block-region-bottom.editing-region-border" "css_element" should exist
    And "#block-region-side-pre.editing-region-border" "css_element" should exist
    And "#block-region-side-post.editing-region-border" "css_element" should exist
    When I click on "Stop customising this page" "button"
    Then "#block-region-main.editing-region-border" "css_element" should not exist

    # On "Advanced features" page only the left region should have the region border displayed.
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I click on "Blocks editing on" "button"
    Then "#block-region-side-pre.editing-region-border" "css_element" should exist
    And "#block-region-main.editing-region-border" "css_element" should not exist
