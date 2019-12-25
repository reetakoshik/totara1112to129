@totara @totara_customfield
Feature: Administrators can add a checkbox custom field to complete during course creation
  In order for the custom field to appear during course creation
  As admin
  I need to select the checkbox custom field and add the relevant settings

  @javascript
  Scenario: Create a custom checkbox
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Checkbox"
    Then I should see "Editing custom field: Checkbox"

    When I set the following fields to these values:
         | fullname  | Custom Checkbox Field |
         | shortname | custom_checkbox       |
    And I press "Save changes"
    Then I should see "Custom Checkbox Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I expand all fieldsets
    Then I should see "Custom Checkbox Field"
    And "customfield_customcheckbox" "checkbox" should exist

    When I set the following fields to these values:
         | fullname  | Course One |
         | shortname | course1    |
    And I set the field "customfield_customcheckbox" to "1"
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the field "customfield_customcheckbox" matches value "1"

    When I set the field "customfield_customcheckbox" to "0"
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the field "customfield_customcheckbox" matches value "0"
