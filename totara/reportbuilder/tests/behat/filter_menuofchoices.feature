@totara @totara_reportbuilder @javascript
Feature: Verify menuofchoices custom field filter works in the reports

  Background:
    Given I am on a totara site
    And I log in as "admin"

    # Create custom field.
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Course menu   |
      | Short name (must be unique) | menuofchoices |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Option 1
      Option 2
      Option 3
      """
    And I press "Save changes"

    # Create courses.
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
      | Course 3 | C3        |

    # Set course custom field to some option.
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Course menu" to "Option 1"
    And I press "Save and display"

    And I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Course menu" to "Option 2"
    And I press "Save and display"

    And I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Course menu" to "Option 3"
    And I press "Save and display"

    # Create 'courses' custom report.
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Test courses report |
      | Source      | Courses             |
    And I press "Create report"
    And I follow "Filters"
    And I set the field "newstandardfilter" to "Course menu"
    And I press "Add"
    And I press "Save changes"

  Scenario: Test changing menuofchoices filter to various options is working
    When I follow "View This Report"
    Then I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I set the field "Course menu" to "Option 1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Course 1"
    And I should not see "Course 2"
    And I should not see "Course 3"

    When I set the field "Course menu" to "any value"
    Then the field "Course menu" matches value "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I set the field "Course menu" to "Option 2"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 1"
    And I should see "Course 2"
    And I should not see "Course 3"

    When I click on "Clear" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "Course menu" matches value "any value"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
