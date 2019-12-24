@totara @totara_customfield
Feature: Administrators can add a custom text area field to complete during course creation
  In order for the custom field to appear during course creation
  As admin
  I need to select the text area custom field and add the relevant settings

  @javascript
  Scenario: Create a custom text area
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Text area"
    Then I should see "Editing custom field: Text area"

    When I set the following fields to these values:
      | fullname                 | Custom Text Area Field |
      | shortname                | textarea               |
      | param1                   | 15                     |
      | param2                   | 4                      |
      | defaultdata_editor[text] | Some default text      |
    And I press "Save changes"
    Then I should see "Custom Text Area Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I expand all fieldsets
    Then I should see "Custom Text Area Field"
    And the field "customfield_textarea_editor[text]" matches value "Some default text"
    And the "cols" attribute of "customfield_textarea_editor[text]" "field" should contain "15"
    And the "rows" attribute of "customfield_textarea_editor[text]" "field" should contain "4"

    When I set the following fields to these values:
      | fullname                          | Course One                    |
      | shortname                         | course1                       |
      | customfield_textarea_editor[text] | Different words in this field |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the field "customfield_textarea_editor[text]" matches value "Different words in this field"

    When I set the field "customfield_textarea_editor[text]" to "%Some 0ther: ch@racters now.,;#"
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the field "customfield_textarea_editor[text]" matches value "%Some 0ther: ch@racters now.,;#"
