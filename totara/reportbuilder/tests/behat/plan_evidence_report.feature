@totara @totara_reportbuilder @totara_customfield @javascript
Feature: Record of learning evidence report
  In order to evaluate custom field usage in plans
  As an admin
  I need control over report filtering and display

  Background:
    Given I am on a totara site
    And I log in as "admin"


  Scenario: Ensure checkbox evidence custom fields are displayed and filterable in the report.
    Given I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | Checkbox test 1 |
      | Short name (must be unique) | checkboxtest    |
    When I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Checkbox test 1"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name   | Test evidence 1 |
      | Checkbox test 1 | 1               |
    And I press "Add evidence"
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name   | Test evidence 2 |
      | Checkbox test 1 | 0               |
    And I press "Add evidence"
    And I click on "Record of Learning" in the totara menu
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I follow "Record of Learning: Evidence"
    And I follow "Columns"
    And I set the field "newcolumns" to "Checkbox test 1"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I follow "Filters"
    And I set the field "newstandardfilter" to "Checkbox test 1"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Filters updated"

    When I follow "View This Report"
    Then I should see "Yes" in the "Test evidence 1" "table_row"
    And I should see "No" in the "Test evidence 2" "table_row"

    When I set the field "Checkbox test 1" to "Yes"
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Test evidence 1"
    And I should not see "Test evidence 2"


  Scenario: Ensure date/time evidence custom fields are displayed and filterable in the report.
    Given I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | Date/time test |
      | Short name (must be unique) | datetimetest   |
      | Start year                  | 1999           |
    When I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Date/time test"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Test evidence 1 |
      | customfield_datetimetest[enabled] | 1               |
      | customfield_datetimetest[day]     | 7               |
      | customfield_datetimetest[month]   | 11              |
      | customfield_datetimetest[year]    | 2000            |
    And I press "Add evidence"
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Test evidence 2 |
      | customfield_datetimetest[enabled] | 1               |
      | customfield_datetimetest[day]     | 7               |
      | customfield_datetimetest[month]   | 10              |
      | customfield_datetimetest[year]    | 2000            |
    And I press "Add evidence"
    And I click on "Record of Learning" in the totara menu
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I follow "Record of Learning: Evidence"
    And I follow "Columns"
    And I set the field "newcolumns" to "Date/time test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"
    And I follow "Filters"
    And I set the field "newstandardfilter" to "Date/time test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Filters updated"

    When I follow "View This Report"
    Then I should see "7 Nov 2000" in the "Test evidence 1" "table_row"
    And I should see "7 Oct 2000" in the "Test evidence 2" "table_row"

    # Filter before given date.
    When I set the following fields to these values:
      | dp_plan_evidence-custom_field_4_eck        | 1    |
      | dp_plan_evidence-custom_field_4_edt[day]   | 8    |
      | dp_plan_evidence-custom_field_4_edt[month] | 10   |
      | dp_plan_evidence-custom_field_4_edt[year]  | 2000 |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Test evidence 2"
    And I should not see "Test evidence 1"

    # Filter after given date.
    When I press "Clear"
    And I set the following fields to these values:
      | id_dp_plan_evidence-custom_field_4_sck     | 1    |
      | dp_plan_evidence-custom_field_4_sdt[day]   | 8    |
      | dp_plan_evidence-custom_field_4_sdt[month] | 10   |
      | dp_plan_evidence-custom_field_4_sdt[year]  | 2000 |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Test evidence 1"
    And I should not see "Test evidence 2"

    # Filter between given dates.
    When I press "Clear"
    And I set the following fields to these values:
      | dp_plan_evidence-custom_field_4_eck        | 1    |
      | dp_plan_evidence-custom_field_4_edt[day]   | 8    |
      | dp_plan_evidence-custom_field_4_edt[month] | 10   |
      | dp_plan_evidence-custom_field_4_edt[year]  | 2000 |
    And I set the following fields to these values:
      | id_dp_plan_evidence-custom_field_4_sck     | 1    |
      | dp_plan_evidence-custom_field_4_sdt[day]   | 6    |
      | dp_plan_evidence-custom_field_4_sdt[month] | 10   |
      | dp_plan_evidence-custom_field_4_sdt[year]  | 2000 |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Test evidence 2"
    And I should not see "Test evidence 1"


  Scenario: Ensure menu of choices custom fields are displayed and filterable in the report
    Given I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "Menu of choices"
    When I set the following fields to these values:
      | Full name                   | Choices test |
      | Short name (must be unique) | choicestest  |
    And I set the field "Menu options (one per line)" to multiline:
      """
      one
      two
      three
      """
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Choices test"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Test evidence 1 |
      | Choices test                      | three           |
    And I press "Add evidence"
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Test evidence 2 |
      | Choices test                      | one             |
    And I press "Add evidence"
    And I click on "Record of Learning" in the totara menu
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I follow "Record of Learning: Evidence"
    And I follow "Columns"
    And I set the field "newcolumns" to "Choices test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I follow "Filters"
    And I set the field "newstandardfilter" to "Choices test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Filters updated"

    When I follow "View This Report"
    Then I should see "three" in the "Test evidence 1" "table_row"
    And I should see "one" in the "Test evidence 2" "table_row"

    When I set the field "Choices test" to "one"
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Test evidence 2"
    And I should not see "Test evidence 1"


  Scenario: Ensure text custom fields are displayed and filterable in the report
    Given I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "Text input"
    When I set the following fields to these values:
      | Full name                   | Text test |
      | Short name (must be unique) | texttest  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Text test"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name | Test evidence 1          |
      | Text test     | This is some test text 1 |
    And I press "Add evidence"
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name | Test evidence 2          |
      | Text test     | This is some test text 2 |
    And I press "Add evidence"
    And I click on "Record of Learning" in the totara menu
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I follow "Record of Learning: Evidence"
    And I follow "Columns"
    And I set the field "newcolumns" to "Text test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I follow "Filters"
    And I set the field "newstandardfilter" to "Text test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Filters updated"

    When I follow "View This Report"
    Then I should see "This is some test text 1" in the "Test evidence 1" "table_row"
    And I should see "This is some test text 2" in the "Test evidence 2" "table_row"

    When I set the field "dp_plan_evidence-custom_field_4" to "test text 2"
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Test evidence 2"
    And I should not see "Test evidence 1"


  Scenario: Ensure text-area custom fields are displayed and filterable in the report
    Given I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "Text area"
    When I set the following fields to these values:
      | Full name                   | Text area test |
      | Short name (must be unique) | texttest       |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Text area test"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name | Test evidence 1          |
      | Text area test     | This is some test text 1 |
    And I press "Add evidence"
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name      | Test evidence 2          |
      | Text area test     | This is some test text 2 |
    And I press "Add evidence"
    And I click on "Record of Learning" in the totara menu
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I follow "Record of Learning: Evidence"
    And I follow "Columns"
    And I set the field "newcolumns" to "Text area test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I follow "Filters"
    And I set the field "newstandardfilter" to "Text area test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Filters updated"

    When I follow "View This Report"
    Then I should see "This is some test text 1" in the "Test evidence 1" "table_row"
    And I should see "This is some test text 2" in the "Test evidence 2" "table_row"

    When I set the field "dp_plan_evidence-custom_field_4" to "test text 1"
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Test evidence 1"
    And I should not see "Test evidence 2"


  Scenario: Ensure URL custom fields are displayed and filterable in the report
    Given I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "URL"
    When I set the following fields to these values:
      | Full name                   | URL test |
      | Short name (must be unique) | urltest  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "URL test"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name               | Test evidence 1 |
      | customfield_urltest[url]    | /my/index.php   |
      | customfield_urltest[target] | 0               |
    And I press "Add evidence"
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name               | Test evidence 2      |
    And I press "Add evidence"
    And I click on "Record of Learning" in the totara menu
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I follow "Record of Learning: Evidence"
    And I follow "Columns"
    And I set the field "newcolumns" to "URL test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I follow "Filters"
    And I set the field "newstandardfilter" to "URL test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Filters updated"

    When I follow "View This Report"
    Then I should see "/my/index.php" in the "Test evidence 1" "table_row"
    And I should not see "/my/index.php" in the "Test evidence 2" "table_row"

    When I set the field "URL test" to "is not empty (NOT NULL)"
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Test evidence 1"
    And I should not see "Test evidence 2"


  @_file_upload
  Scenario: Ensure file custom fields are displayed and filterable in the report
    Given I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "File"
    When I set the following fields to these values:
      | Full name                   | File test |
      | Short name (must be unique) | filetest  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "File test"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Test evidence 1 |
    And I upload "totara/reportbuilder/tests/fixtures/test.txt" file to "File test" filemanager
    And I press "Add evidence"
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Test evidence 2 |
    And I upload "totara/reportbuilder/tests/fixtures/test2.txt" file to "File test" filemanager
    And I press "Add evidence"
    And I click on "Record of Learning" in the totara menu
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I follow "Record of Learning: Evidence"
    And I follow "Columns"
    And I set the field "newcolumns" to "File test"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I follow "View This Report"
    Then I should see "test.txt" in the "Test evidence 1" "table_row"
    And I should see "test2.txt" in the "Test evidence 2" "table_row"


  Scenario: Ensure multi-select evidence custom fields are displayed and filterable in the report.
    Given I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Multi select test |
      | Short name (must be unique) | selecttest        |
      | multiselectitem[0][option]  | One               |
      | multiselectitem[1][option]  | Two               |
      | multiselectitem[2][option]  | Three             |
    When I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Multi select test"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Test evidence 1 |
      | customfield_selecttest[1]         | 1               |
      | customfield_selecttest[2]         | 1               |
    And I press "Add evidence"
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Test evidence 2 |
      | customfield_selecttest[0]         | 1               |
      | customfield_selecttest[1]         | 1               |
    And I press "Add evidence"
    And I click on "Record of Learning" in the totara menu
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I follow "Record of Learning: Evidence"
    And I follow "Columns"
    And I set the field "newcolumns" to "Multi select test (text)"
    And I press "Add"
    And I press "Save changes"
    Then I should see "Columns updated"

    When I follow "View This Report"
    Then I should see "Two, Three" in the "Test evidence 1" "table_row"
    And I should see "One, Two" in the "Test evidence 2" "table_row"
