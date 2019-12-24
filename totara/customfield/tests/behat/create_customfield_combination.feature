@totara @totara_customfield
Feature: Administrators can add multiple custom fields of various types to complete during course creation
  In order for the custom field to appear during course creation
  As admin
  I need to add several custom fields and add their relevant settings

  @javascript
  Scenario: Create multiple custom fields
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | fullname  | Custom Checkbox Field1 |
      | shortname | custom_checkbox1       |
    And I press "Save changes"
    Then I should see "Custom Checkbox Field1"

    When I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | fullname    | Custom Checkbox Field2 |
      | shortname   | custom_checkbox2       |
      | defaultdata | 1                      |
    And I press "Save changes"
    Then I should see "Custom Checkbox Field2"

    When I set the field "datatype" to "Date/time"
    And I set the following fields to these values:
      | fullname  | Custom Date Field |
      | shortname | custom_date       |
      | param1    | 2000              |
      | param2    | 2020              |
    And I press "Save changes"
    Then I should see "Custom Date Field"

    When I set the field "datatype" to "Date/time"
    And I set the following fields to these values:
      | fullname  | Custom Date/Time Field |
      | shortname | custom_datetime        |
      | param1    | 2000                   |
      | param2    | 2020                   |
      | param3    | 1                      |
    And I press "Save changes"
    Then I should see "Custom Date/Time Field"

    When I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | fullname                    | Custom Multi-Select Field1 |
      | shortname                   | custom_multiselect1        |
      | multiselectitem[0][option]  | Option One                 |
      | multiselectitem[1][option]  | Option Two                 |
      | multiselectitem[2][option]  | Option Three               |
    And I press "Save changes"
    Then I should see "Custom Multi-Select Field1"

    When I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | fullname                    | Custom Multi-Select Field2 |
      | shortname                   | custom_multiselect2        |
      | multiselectitem[0][option]  | Option A                   |
      | multiselectitem[1][option]  | Option B                   |
    And I press "Save changes"
    Then I should see "Custom Multi-Select Field2"

    When I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname                 | Custom Text Area Field1 |
      | shortname                | textarea1               |
      | param1                   | 30                      |
      | param2                   | 10                      |
      | defaultdata_editor[text] | Some default text       |
    And I press "Save changes"
    Then I should see "Custom Text Area Field1"

    When I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname                 | Custom Text Area Field2 |
      | shortname                | textarea2               |
      | param1                   | 15                      |
      | param2                   | 4                       |
      | defaultdata_editor[text] | Other default text      |
    And I press "Save changes"
    Then I should see "Custom Text Area Field2"

    When I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname    | Custom Text Input Field1 |
      | shortname   | textinput1               |
      | defaultdata | Some text                |
      | param1      | 12                       |
      | param2      | 15                       |
    And I press "Save changes"
    Then I should see "Custom Text Input Field1"

    When I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname    | Custom Text Input Field2 |
      | shortname   | textinput2               |
      | defaultdata | Other text               |
      | param1      | 18                       |
      | param2      | 20                       |
    And I press "Save changes"
    Then I should see "Custom Text Input Field2"

    When I go to the courses management page
    And I click on "Create new course" "link"
    And I expand all fieldsets
    Then I should see "Custom Checkbox Field1"
    And I should see "Custom Checkbox Field2"
    And I should see "Custom Date Field"
    And I should see "Custom Date/Time Field"
    And I should see "Custom Multi-Select Field1"
    And I should see "Custom Multi-Select Field2"
    And I should see "Custom Text Area Field1"
    And I should see "Custom Text Area Field2"
    And I should see "Custom Text Input Field1"
    And I should see "Custom Text Input Field2"
    And the field "customfield_customcheckbox1" matches value "0"
    And the field "customfield_customcheckbox2" matches value "1"
    And "customfield_customdate[hour]" "select" should not exist
    And "customfield_customdate[minute]" "select" should not exist
    And the field "customfield_textarea1_editor[text]" matches value "Some default text"
    And the "cols" attribute of "customfield_textarea1_editor[text]" "field" should contain "30"
    And the "rows" attribute of "customfield_textarea1_editor[text]" "field" should contain "10"
    And the field "customfield_textarea2_editor[text]" matches value "Other default text"
    And the "cols" attribute of "customfield_textarea2_editor[text]" "field" should contain "15"
    And the "rows" attribute of "customfield_textarea2_editor[text]" "field" should contain "4"
    And the "size" attribute of "customfield_textinput1" "field" should contain "12"
    And the "maxlength" attribute of "customfield_textinput1" "field" should contain "15"
    And the "size" attribute of "customfield_textinput2" "field" should contain "18"
    And the "maxlength" attribute of "customfield_textinput2" "field" should contain "20"

    When I set the following fields to these values:
      | fullname                           | Course One                    |
      | shortname                          | course1                       |
      | customfield_customcheckbox1        | 1                             |
      | customfield_customcheckbox2        | 0                             |
      | customfield_customdate[enabled]    | 1                             |
      | customfield_customdate[day]        | 15                            |
      | customfield_customdate[month]      | 10                            |
      | customfield_customdate[year]       | 2005                          |
      | customfield_customdatetime[enabled]| 1                             |
      | customfield_customdatetime[day]    | 14                            |
      | customfield_customdatetime[month]  | 11                            |
      | customfield_customdatetime[year]   | 2003                          |
      | customfield_customdatetime[hour]   | 02                            |
      | customfield_customdatetime[minute] | 40                            |
      | customfield_custommultiselect1[0]  | 1                             |
      | customfield_custommultiselect1[1]  | 1                             |
      | customfield_custommultiselect1[2]  | 0                             |
      | customfield_custommultiselect2[0]  | 0                             |
      | customfield_custommultiselect2[1]  | 1                             |
      | customfield_textarea1_editor[text] | Different words in this field |
      | customfield_textarea2_editor[text] | Other words in this field     |
      | customfield_textinput1             | Other words                   |
      | customfield_textinput2             | More words                    |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | fullname                           | Course One                    |
      | shortname                          | course1                       |
      | customfield_customcheckbox1        | 1                             |
      | customfield_customcheckbox2        | 0                             |
      | customfield_customdate[day]        | 15                            |
      | customfield_customdate[month]      | 10                            |
      | customfield_customdate[year]       | 2005                          |
      | customfield_customdatetime[day]    | 14                            |
      | customfield_customdatetime[month]  | 11                            |
      | customfield_customdatetime[year]   | 2003                          |
      | customfield_customdatetime[hour]   | 02                            |
      | customfield_customdatetime[minute] | 40                            |
      | customfield_custommultiselect1[0]  | 1                             |
      | customfield_custommultiselect1[1]  | 1                             |
      | customfield_custommultiselect1[2]  | 0                             |
      | customfield_custommultiselect2[0]  | 0                             |
      | customfield_custommultiselect2[1]  | 1                             |
      | customfield_textarea1_editor[text] | Different words in this field |
      | customfield_textarea2_editor[text] | Other words in this field     |
      | customfield_textinput1             | Other words                   |
      | customfield_textinput2             | More words                    |

    When I set the following fields to these values:
      | customfield_customcheckbox1        | 0                             |
      | customfield_customcheckbox2        | 1                             |
      | customfield_customdate[enabled]    | 0                             |
      | customfield_customdatetime[day]    | 22                            |
      | customfield_customdatetime[month]  | 12                            |
      | customfield_customdatetime[year]   | 2007                          |
      | customfield_customdatetime[hour]   | 03                            |
      | customfield_customdatetime[minute] | 50                            |
      | customfield_custommultiselect1[0]  | 0                             |
      | customfield_custommultiselect1[1]  | 1                             |
      | customfield_custommultiselect1[2]  | 0                             |
      | customfield_custommultiselect2[0]  | 1                             |
      | customfield_custommultiselect2[1]  | 0                             |
      | customfield_textarea1_editor[text] | Changed words again           |
      | customfield_textarea2_editor[text] | Changed these words as well   |
      | customfield_textinput1             | abc                           |
      | customfield_textinput2             | xyz                           |
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | customfield_customcheckbox1        | 0                             |
      | customfield_customcheckbox2        | 1                             |
      | customfield_customdatetime[day]    | 22                            |
      | customfield_customdatetime[month]  | 12                            |
      | customfield_customdatetime[year]   | 2007                          |
      | customfield_customdatetime[hour]   | 03                            |
      | customfield_customdatetime[minute] | 50                            |
      | customfield_custommultiselect1[0]  | 0                             |
      | customfield_custommultiselect1[1]  | 1                             |
      | customfield_custommultiselect1[2]  | 0                             |
      | customfield_custommultiselect2[0]  | 1                             |
      | customfield_custommultiselect2[1]  | 0                             |
      | customfield_textarea1_editor[text] | Changed words again           |
      | customfield_textarea2_editor[text] | Changed these words as well   |
      | customfield_textinput1             | abc                           |
      | customfield_textinput2             | xyz                           |
    And the "customfield_customdate[day]" "select" should be disabled
    And the "customfield_customdate[month]" "select" should be disabled
    And the "customfield_customdate[year]" "select" should be disabled
    And the "customfield_customdatetime[day]" "select" should be enabled
    And the "customfield_customdatetime[month]" "select" should be enabled
    And the "customfield_customdatetime[year]" "select" should be enabled
    And the "customfield_customdatetime[hour]" "select" should be enabled
    And the "customfield_customdatetime[minute]" "select" should be enabled
