@totara @totara_customfield
Feature: Administrators can add a custom date/time field to complete during course creation
  In order for the custom field to appear during course creation
  As admin
  I need to select the date/time custom field and add the relevant settings

  @javascript
  Scenario: Create a custom date only field
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Date/time"
    Then I should see "Editing custom field: Date/time"

    When I set the following fields to these values:
      | fullname  | Custom Date Field |
      | shortname | custom_date       |
      | param1    | 2000              |
      | param2    | 2020              |
    And I press "Save changes"
    Then I should see "Custom Date Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I expand all fieldsets
    Then I should see "Custom Date Field"
    And "customfield_customdate[enabled]" "checkbox" should exist
    And "customfield_customdate[day]" "select" should exist
    And "customfield_customdate[month]" "select" should exist
    And "customfield_customdate[year]" "select" should exist
    And "customfield_customdate[hour]" "select" should not exist
    And "customfield_customdate[minute]" "select" should not exist
    And "1999" "option" should not exist in the "customfield_customdate[year]" "select"
    And "2000" "option" should exist in the "customfield_customdate[year]" "select"
    And "2020" "option" should exist in the "customfield_customdate[year]" "select"
    And "2021" "option" should not exist in the "customfield_customdate[year]" "select"


    When I set the following fields to these values:
      | fullname                        | Course One |
      | shortname                       | course1    |
      | customfield_customdate[enabled] | 1          |
      | customfield_customdate[day]     | 15         |
      | customfield_customdate[month]   | 10         |
      | customfield_customdate[year]    | 2005       |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | customfield_customdate[enabled] | 1          |
      | customfield_customdate[day]     | 15         |
      | customfield_customdate[month]   | 10         |
      | customfield_customdate[year]    | 2005       |

    When I set the field "customfield_customdate[enabled]" to "0"
    Then the "customfield_customdate[day]" "select" should be disabled
    And the "customfield_customdate[month]" "select" should be disabled
    And the "customfield_customdate[year]" "select" should be disabled

    When I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the "customfield_customdate[day]" "select" should be disabled
    And the "customfield_customdate[month]" "select" should be disabled
    And the "customfield_customdate[year]" "select" should be disabled

  @javascript
  Scenario: Create a custom date and time field
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Date/time"
    Then I should see "Editing custom field: Date/time"

    When I set the following fields to these values:
      | fullname  | Custom Date/Time Field |
      | shortname | custom_datetime        |
      | param1    | 2000                   |
      | param2    | 2020                   |
      | param3    | 1                      |
    And I press "Save changes"
    Then I should see "Custom Date/Time Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I expand all fieldsets
    Then I should see "Custom Date/Time Field"
    And "customfield_customdatetime[enabled]" "checkbox" should exist
    And "customfield_customdatetime[day]" "select" should exist
    And "customfield_customdatetime[month]" "select" should exist
    And "customfield_customdatetime[year]" "select" should exist
    And "customfield_customdatetime[hour]" "select" should exist
    And "customfield_customdatetime[minute]" "select" should exist

    When I set the following fields to these values:
      | fullname                            | Course One |
      | shortname                           | course1    |
      | customfield_customdatetime[enabled] | 1          |
      | customfield_customdatetime[day]     | 15         |
      | customfield_customdatetime[month]   | 10         |
      | customfield_customdatetime[year]    | 2005       |
      | customfield_customdatetime[hour]    | 02         |
      | customfield_customdatetime[minute]  | 40         |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | customfield_customdatetime[enabled] | 1          |
      | customfield_customdatetime[day]     | 15         |
      | customfield_customdatetime[month]   | 10         |
      | customfield_customdatetime[year]    | 2005       |
      | customfield_customdatetime[hour]    | 02         |
      | customfield_customdatetime[minute]  | 40         |

    When I set the field "customfield_customdatetime[enabled]" to "0"
    Then the "customfield_customdatetime[day]" "select" should be disabled
    And the "customfield_customdatetime[month]" "select" should be disabled
    And the "customfield_customdatetime[year]" "select" should be disabled
    And the "customfield_customdatetime[hour]" "select" should be disabled
    And the "customfield_customdatetime[minute]" "select" should be disabled

    When I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the "customfield_customdatetime[day]" "select" should be disabled
    And the "customfield_customdatetime[month]" "select" should be disabled
    And the "customfield_customdatetime[year]" "select" should be disabled
    And the "customfield_customdatetime[hour]" "select" should be disabled
    And the "customfield_customdatetime[minute]" "select" should be disabled
