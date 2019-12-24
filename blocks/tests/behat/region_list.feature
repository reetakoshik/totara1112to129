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
  And I configure the "Navigation" block
  And I expand all fieldsets
  Then the "Default region" select box should contain "Left"
  And the "Default region" select box should contain "Right"
  And the "Default region" select box should contain "Main"
  And the "Region" select box should contain "Left"
  And the "Region" select box should contain "Right"
  And the "Region" select box should not contain "Main"

Scenario: Ensure editing a block on the dashboard lists the dashboard layout options
  Given I click on "Dashboard" in the totara menu
  And I click on "Customise this page" "button"
  And I configure the "Navigation" block
  And I expand all fieldsets
  Then the "Default region" select box should contain "Left"
  And the "Default region" select box should contain "Right"
  And the "Default region" select box should contain "Main"
  And the "Region" select box should contain "Left"
  And the "Region" select box should contain "Right"
  And the "Region" select box should contain "Main"

Scenario: Ensure editing a block in a course lists the course layout options
  Given the following "courses" exist:
    | name     | shortname |
    | course 1 | c1        |
  And I click on "Find Learning" in the totara menu
  And I click on "course 1" "link"
  And I click on "Turn editing on" "button"
  And I configure the "Navigation" block
  And I expand all fieldsets
  Then the "Default region" select box should contain "Left"
  And the "Default region" select box should contain "Right"
  And the "Default region" select box should contain "Main"
  And the "Region" select box should contain "Left"
  And the "Region" select box should contain "Right"
  And the "Region" select box should not contain "Main"

Scenario: Ensure editing a block in an activity lists the activity layout options
  Given the following "courses" exist:
    | name     | shortname |
    | course 1 | c1        |
  And I click on "Find Learning" in the totara menu
  And I click on "course 1" "link"
  And I click on "Turn editing on" "button"
  And I add a "Forum" to section "1" and I fill the form with:
    | name | My forum |
  And I click on "My forum" "link"
  And I configure the "Navigation" block
  And I expand all fieldsets
  Then the "Default region" select box should contain "Left"
  And the "Default region" select box should contain "Right"
  And the "Default region" select box should contain "Main"
  And the "Region" select box should contain "Left"
  And the "Region" select box should contain "Right"
  And the "Region" select box should not contain "Main"

Scenario: Ensure editing a block on an admin page lists the admin layout options
  When I navigate to "Notifications" node in "Site administration"
  And I click on "Blocks editing on" "button"
  And I configure the "Navigation" block
  And I expand all fieldsets
  Then the "Default region" select box should contain "Left"
  And the "Default region" select box should contain "Right"
  And the "Default region" select box should contain "Main"
  And the "Region" select box should contain "Left"
  And the "Region" select box should not contain "Right"
  And the "Region" select box should not contain "Main"