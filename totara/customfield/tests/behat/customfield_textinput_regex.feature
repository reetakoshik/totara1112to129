@totara @totara_customfield
Feature: Administrators can add a regex to custom text input field
  In order for the custom field to appear during course creation
  As admin
  I need to select the text input custom field and force its format using regex

  Background:
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I should see "Create a new custom field"
    And I set the field "datatype" to "Text input"
    And I should see "Editing custom field: Text input"

  @javascript
  Scenario: Create a custom text input with regex and check that it works
    When I set the following fields to these values:
      | fullname    | Custom Text Input Field                    |
      | shortname   | textinput                                  |
      | defaultdata | http://www.totaralms.com/partners              |
      | param1      | 50                                         |
      | param2      | 50                                         |
      | regex       | /^https?:\/\/www\.totaralms\.com\/[^\?]*$/ |
    And I press "Save changes"
    And I should see "Custom Text Input Field"

    # Check default value is offered
    When I go to the courses management page
    And I click on "Create new course" "link"
    And I should see "Add a new course"
    And I expand all fieldsets
    And I should see "Custom Text Input Field"
    Then the field "customfield_textinput" matches value "http://www.totaralms.com/partners"

    # Check when field doesn't match pattern
    When I set the following fields to these values:
      | fullname              | Course One                |
      | shortname             | course1                   |
      | customfield_textinput | http://www.totaralms.org/ |
    And I press "Save and display"
    Then I should see "The value you have entered for Custom Text Input Field does not match the required format."

    # Check when field match pattern
    When I set the following fields to these values:
      | fullname              | Course One                                     |
      | shortname             | course1                                        |
      | customfield_textinput | https://www.totaralms.com/solutions/totara-lms |
    And I press "Save and display"
    Then I should see "Course One" in the page title
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And the field "customfield_textinput" matches value "https://www.totaralms.com/solutions/totara-lms"

  @javascript
  Scenario: Check that default value also enforced to match pattern
    When I set the following fields to these values:
      | fullname    | Custom Text Input Field |
      | shortname   | textinput               |
      | defaultdata | test                    |
      | param1      | 10                      |
      | param2      | 20                      |
      | regex       | /^[0-9]{5}$/            |
    And I press "Save changes"
    Then I should see "The value you have entered for Default value does not match the required format."

  @javascript
  Scenario: Check that insecure patterns are not allowed
    When I set the following fields to these values:
      | fullname    | Custom Text Input Field |
      | shortname   | textinput               |
      | param1      | 10                      |
      | param2      | 20                      |
      | regex       | /^.*$/ie                |
    And I press "Save changes"
    Then I should see "A delimiter or modifier was used that is not permitted."

  @javascript
  Scenario: Check that error patterns are not allowed
    When I set the following fields to these values:
      | fullname    | Custom Text Input Field |
      | shortname   | textinput               |
      | param1      | 10                      |
      | param2      | 20                      |
      | regex       | /^[0-9){5}$/            |
    And I press "Save changes"
    Then I should see "The regular expression you have entered is not valid."
