@totara_hierarchy @totara_hierarchy_goals @totara @javascript
Feature: Verify creation and use of company goal types and custom fields.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner1  | Learner1 | learner1@example.com |
    And the following "goal frameworks" exist in "totara_hierarchy" plugin:
      | fullname                 | idnumber |
      | Company Goal Framework 1 | CGF1     |
    And the following "goals" exist in "totara_hierarchy" plugin:
      | fullname       | idnumber | goal_framework |
      | Company Goal 1 | CG1      | CGF1           |
      | Company Goal 2 | CG2      | CGF1           |

  Scenario: Verify a goal type can be successfully created, updated and deleted.

    # Create a new Company Goal Type
    Given I log in as "admin"
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I press "Add a new company goal type"
    And I set the following fields to these values:
      | Type full name         | Company Goal Type 1             |
      | Goal type ID number    | CGT1                            |
      | Goal Type Description  | Company Goal Type 1 description |
    When I press "Save changes"
    Then I should see "The goal type \"Company Goal Type 1\" has been created"

    # Update the Company Goal Type.
    When I click on "Edit" "link" in the ".generaltable" "css_element"
    And I set the following fields to these values:
      | Type full name         | Company Goal Type 1a               |
      | Goal type ID number    | CGT1a                              |
      | Goal Type Description  | Company Goal Type 1a description   |
    And I press "Save changes"
    Then I should see "The goal type \"Company Goal Type 1a\" has been updated"

    # Delete the Company Goal Type.
    When I click on "Delete" "link" in the ".generaltable" "css_element"
    And I press "Continue"
    Then I should see "The goal type \"Company Goal Type 1a\" has been completely deleted."
    And I should see "No goal types"

  @_file_upload @totara_customfield
  Scenario: Verify custom fields can be successfully added to a company goal type and company goal.

    # Create a new Company Goal Type
    Given I log in as "admin"
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I press "Add a new company goal type"
    And I set the following fields to these values:
      | Type full name         | Company Goal Type 1  |
      | Goal type ID number    | CGT1 |
    # Save the changes.
    When I press "Save changes"
    Then I should see "The goal type \"Company Goal Type 1\" has been created"

    # Select the goal type to create custom fields for.
    When I follow "Company Goal Type 1"
    Then I should see "No fields have been defined"

    # Create a checkbox.
    When I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | Checkbox 1 |
      | Short name (must be unique) | checkbox1  |
    And I press "Save changes"
    Then I should see "Checkbox 1"

    # Create a datetime.
    When I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | Datetime 1 |
      | Short name (must be unique) | datetime1  |
    And I press "Save changes"
    Then I should see "Datetime 1"

    # Create a file upload.
    When I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | File 1 |
      | Short name (must be unique) | file1  |
    And I press "Save changes"
    Then I should see "File 1"

    # Create a menu of choices.
    When I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Menu of choices 1          |
      | Short name (must be unique) | menuofchoices1             |
    And I set the field "Menu options (one per line)" to multiline:
    """
    Choice 1
    Choice 2
    Choice 3
    """
    And I press "Save changes"
    Then I should see "Menu of choices 1"

    # Create a multi-select.
    When I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Multi-select 1 |
      | Short name (must be unique) | multiselect1   |
    And I set the field "multiselectitem[0][option]" to "Select 1"
    And I set the field "multiselectitem[1][option]" to "Select 2"
    And I set the field "multiselectitem[2][option]" to "Select 3"
    And I press "Save changes"
    Then I should see "Multi-select 1"

    # Create a text area.
    When I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Text area 1 |
      | Short name (must be unique) | textarea1   |
    And I press "Save changes"
    Then I should see "Text area 1"

    # Create a text input.
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Text input 1 |
      | Short name (must be unique) | textinput1   |
    And I press "Save changes"
    Then I should see "Text input 1"

    # Create a URL field.
    When I set the field "Create a new custom field" to "URL"
    And I set the following fields to these values:
      | Full name                   | URL 1 |
      | Short name (must be unique) | url1  |
    And I press "Save changes"
    Then I should see "URL 1"

    # Add some data to the custom fields on the company goals.
    When I navigate to "Manage goals" node in "Site administration > Goals"
    And I follow "Company Goal Framework 1"
    And I click on "Edit" "link" in the ".totaratable" "css_element"
    And I press "Change type"
    And I press "Choose"
    And I press "Reclassify items"
    And I set the following fields to these values:
      | Checkbox 1                     | 1            |
      | customfield_datetime1[enabled] | 1            |
      | customfield_datetime1[month]   | December     |
      | customfield_datetime1[day]     | 31           |
      | customfield_datetime1[year]    | 2035         |
      | customfield_menuofchoices1     | Choice 1     |
      | Select 1                       | 1            |
      | Select 2                       | 1            |
      | Select 3                       | 1            |
      | Text area 1                    | Text area 1  |
      | Text input 1                   | Text input 1 |
      | customfield_url1[url]          | https://www.totaralms.com |
      | customfield_url1[text]         | Totara LMS                |
      | customfield_url1[target]       | 1                         |
    And I upload "/totara/hierarchy/tests/behat/fixtures/logo.png" file to "File 1" filemanager
    And I press "Save changes"
    # Check that all the data has been added to the company goal.
    Then I should see "Company Goal Type 1" in the ".dl-horizontal" "css_element"
    And I should see "Yes" in the ".dl-horizontal" "css_element"
    And I should see "31 December 2035" in the ".dl-horizontal" "css_element"
    And I should see "logo.png" in the ".dl-horizontal" "css_element"
    And I should see "Choice 1" in the ".dl-horizontal" "css_element"
    And I should see "Select 1 Select 2 Select 3" in the ".dl-horizontal" "css_element"
    And I should see "Text area 1" in the ".dl-horizontal" "css_element"
    And I should see "Text input 1" in the ".dl-horizontal" "css_element"
    And I should see "URL 1" in the ".dl-horizontal" "css_element"

    # Login as a learner and create a company goal with the custom fields.
    When I log out
    And I log in as "learner1"
    And I click on "Goals" in the totara menu
    Then I should see "Company Goals"

    # Add a company goal to the learner goals.
    When I press "Add company goal"
    And I follow "Company Goal 1"
    And I press "Save"
    Then I should see "Company Goal 1"

    # Check the correct data is visible on the goal.
    And I press "Show details"
    Then I should see "Type: Company Goal Type 1"
    And I should see "Checkbox 1: Yes"
    And I should see "Datetime 1: 31 December 2035"
    And I should see "logo.png"
    And I should see "Menu of choices 1: Choice 1"
    And I should see "Multi-select 1:"
    And I should see "Select 1"
    And I should see "Select 2"
    And I should see "Select 3"
    And I should see "Text area 1: Text area 1"
    And I should see "Text input 1: Text input 1"
    And I should see "URL 1: Totara LMS"
