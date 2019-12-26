@totara @totara_customfield @totara_completion_upload @javascript @_file_upload
Feature: Verify course completion data with custom fields can be successfully uploaded.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Bob1       | Learner1  | learner1@example.com |

    When I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name          | Checkbox 1 |
      | Short name         | checkbox1  |
      | Checked by default | Yes        |
    And I press "Save changes"
    Then I should see "Checkbox 1"

    When I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name  | Datetime 1 |
      | Short name | datetime1  |
    And I press "Save changes"
    Then I should see "Datetime 1"

    When I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name     | Menu of Choices 1 |
      | Short name    | menuofchoices1    |
      | Default value | Choice 1          |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Choice 1
      Choice 2
      Choice 3
      """
    And I press "Save changes"
    Then I should see "Menu of Choices 1"

    When I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                  | Multi-select 1 |
      | Short name                 | multiselect1   |
      | multiselectitem[0][option] | Option 1       |
      | multiselectitem[1][option] | Option 2       |
      | multiselectitem[2][option] | Option 3       |

    And I click on "Make selected by default" "link" in the "#fgroup_id_multiselectitem_0" "css_element"
    And I press "Save changes"
    Then I should see "Multi-select 1"

    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name     | Text input 1             |
      | Short name    | textinput1               |
      | Default value | Text input default value |
    And I press "Save changes"
    Then I should see "Text input 1"

    When I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name     | Text area 1             |
      | Short name    | textarea1               |
      | Default value | Text area default value |
    And I press "Save changes"
    Then I should see "Text area 1"

    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "URL"
    And I set the following fields to these values:
      | Full name   | URL 1                   |
      | Short name  | url1                    |
      | Default URL | http://www.starwars.com |
    And I press "Save changes"
    Then I should see "URL 1"
    And I log out

  Scenario: Verify a successful course evidence upload expecting default custom field values to be used and visible in the ROL.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_evidence_custom_fields_1.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "1 Records created as evidence"
    And I should see "1 Records in total"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I follow "Completed course : thisisevidence"
    Then I should see "Date completed : 1 January 2015"
    And I should see "Checkbox 1 : Yes"
    And I should see "Menu of Choices 1 : Choice 1"
    And I should see "Text input 1 : Text input default value"
    And I should see "Text area 1 : Text area default value"
    And I should see "URL 1 : http://www.starwars.com"

  Scenario: Verify a successful course evidence upload expecting default custom field values to be used and visible in the import report.
    Given I log in as "admin"
    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "Settings" "link" in the "Completion import: Course status" "table_row"
    And I follow "Columns"
    And I set the field "newcolumns" to "Date completed"
    And I press "Add"
    And I set the field "newcolumns" to "Checkbox 1"
    And I press "Add"
    And I set the field "newcolumns" to "Datetime 1"
    And I press "Add"
    And I set the field "newcolumns" to "Menu of Choices 1"
    And I press "Add"
    And I set the field "newcolumns" to "Multi-select 1 (text)"
    And I press "Add"
    And I set the field "newcolumns" to "Text input 1"
    And I press "Add"
    And I set the field "newcolumns" to "Text area 1"
    And I press "Add"
    And I set the field "newcolumns" to "URL 1"
    And I press "Add"
    And I press "Save changes"

    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_evidence_custom_fields_1.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "1 Records created as evidence"
    And I should see "1 Records in total"

    When I follow "Course import report"
    Then I should see "1 Jan 2015" in the "learner1" "table_row"
    And I should see "Yes" in the "learner1" "table_row"
    And I should see "Choice 1" in the "learner1" "table_row"
    #And I should see "Option 1" in the "learner1" "table_row"
    And I should see "Text input default value" in the "learner1" "table_row"
    And I should see "Text area default value" in the "learner1" "table_row"
    And I should see "http://www.starwars.com" in the "learner1" "table_row"

  Scenario: Verify a successful course evidence upload overriding default values and visible in the ROL.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_evidence_custom_fields_2.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "1 Records created as evidence"
    And I should see "1 Records in total"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I follow "Completed course : thisisevidence"
    Then I should see "Date completed : 1 January 2015"
    And I should see "Checkbox 1 : No"
    And I should see "Datetime 1 : 1 January 2016"
    And I should see "Menu of Choices 1 : Choice 2"
    And I should see "Text input 1 : A short text input"
    And I should see "Text area 1 : A looooooooooooooooooooooooooong text area"
    And I should see "URL 1 : http://totaralms.com"

  Scenario: Verify a course evidence upload fails with an invalid field.
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_evidence_custom_fields_3.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "Unknown column 'customfield_invalidfield"
    Then I should see "No records were imported"
