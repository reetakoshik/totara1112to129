@totara @totara_customfield
Feature: Administrators can add a custom text input field to complete during course creation
  In order for the custom field to appear during course creation
  As admin
  I need to select the text input custom field and add the relevant settings

  @javascript
  Scenario: Create a custom text input
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Text input"
    Then I should see "Editing custom field: Text input"

    When I set the following fields to these values:
      | fullname    | Custom Text Input Field |
      | shortname   | textinput               |
      | defaultdata | Some text               |
      | param1      | 12                      |
      | param2      | 15                      |
    And I press "Save changes"
    Then I should see "Custom Text Input Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"
    When I expand all fieldsets
    Then I should see "Custom Text Input Field"
    And the field "customfield_textinput" matches value "Some text"
    And the "size" attribute of "customfield_textinput" "field" should contain "12"
    And the "maxlength" attribute of "customfield_textinput" "field" should contain "15"

    When I set the following fields to these values:
      | fullname              | Course One  |
      | shortname             | course1     |
      | customfield_textinput | Other words |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the field "customfield_textinput" matches value "Other words"

    When I set the field "customfield_textinput" to "oth3r%Ch@r$"
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the field "customfield_textinput" matches value "oth3r%Ch@r$"
