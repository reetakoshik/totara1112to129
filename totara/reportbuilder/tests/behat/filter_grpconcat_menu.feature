@totara @totara_reportbuilder @javascript
Feature: Verify grpconcat_menu custom field filter works in the reports

  Background:
    Given I am on a totara site
    And I log in as "admin"

    # Create custom field.
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name | Organisation type 1 |
    And I press "Save changes"

    And I follow "Organisation type 1"
    And I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Org menu      |
      | Short name (must be unique) | menuofchoices |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Option 1
      Option 2
      Option 3
      """
    And I press "Save changes"

    # Create organisations with the custom field.
    And I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I press "Add new organisation framework"
    And I set the following fields to these values:
      | Name | My organisation frameworkd 1 |
    And I press "Save changes"
    And I follow "My organisation frameworkd 1"
    And I press "Add new organisation"
    And I set the following fields to these values:
      | Name | Organisation 1      |
      | Type | Organisation type 1 |
    And I press "Save changes"
    And I press "Return to organisation framework"
    And I click on "Edit" "link" in the "Organisation 1" "table_row"
    And I set the field "Org menu" to "Option 3"
    And I press "Save changes"

    # Create some users.
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |

    # Give one user a job.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Job 1 |
      | ID Number | 1     |
    And I click on "Choose organisation" "button"
    And I click on "Organisation 1" "link" in the "organisation" "totaradialogue"
    And I click on "OK" "button" in the "organisation" "totaradialogue"
    And I click on "Add job assignment" "button"

    # Create 'users' custom report.
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Test user report |
      | Source      | User             |
    And I press "Create report"
    And I follow "Filters"
    And I set the field "newstandardfilter" to "Org menu"
    And I press "Add"
    And I press "Save changes"

  Scenario: Test changing grpconcat_menu filter to various options is working
    When I follow "View This Report"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"

    When I set the field "Org menu" to "Option 3"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "User One"
    And I should see "User Two"
    And I should not see "User Three"

    When I set the field "Org menu" to "any value"
    Then the field "Org menu" matches value "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"

    When I set the field "Org menu" to "Option 2"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "User One"
    And I should not see "User Two"
    And I should not see "User Three"

    When I click on "Clear" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "Org menu" matches value "any value"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
