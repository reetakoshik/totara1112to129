@javascript @mod_facetoface @totara @totara_reportbuilder @totara_customfield
Feature: All customfields column generator
  In order to always have all custom fields in report
  As an admin
  I need to be able to add "All ... custom fields" column

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Sign-up" "link"

    # Add Checkbox
    And I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | CF Checkbox |
      | Short name (must be unique) | cfcheckbox  |
    And I press "Save changes"

    # Add Date/time
    And I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | CF Date-time |
      | Short name (must be unique) | cfdatetime   |
    And I press "Save changes"

    # Add File
    And I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | CF File |
      | Short name (must be unique) | cffile  |
    And I press "Save changes"

    # Add Menu of choices
    And I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | CF Menu of choices |
      | Short name (must be unique) | cfmenuofchoices    |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Choice 1
      Choice 2
      Choice 3
      """
    And I press "Save changes"

    # Add Multi-select
    And I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | CF Multi select  |
      | Short name (must be unique) | cfmultiselect    |
      | multiselectitem[0][option]  | Option 1         |
      | multiselectitem[1][option]  | Option 2         |
      | multiselectitem[2][option]  | Option 3         |
    And I press "Save changes"

    # Add Text area
    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | CF Text area |
      | Short name (must be unique) | cftextarea   |
    And I press "Save changes"

    # Add URL
    And I set the field "Create a new custom field" to "URL"
    And I set the following fields to these values:
      | Full name                   | CF URL |
      | Short name (must be unique) | cfurl  |
    And I press "Save changes"

    # Add Location
    And I set the field "Create a new custom field" to "Location"
    And I set the following fields to these values:
      | Full name                   | CF Location                                |
      | Short name (must be unique) | cflocation                                 |
      | Default Address             | 150 Willis Street, Wellington, New Zealand |
    And I click on "#id_size_medium" "css_element"
    And I click on "#id_view_satellite" "css_element"
    And I click on "#id_display_map" "css_element"
    And I press "Save changes"

  Scenario: Enable all customfields column and check that report displays all customfields and their values correctly
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2030 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2030 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 10    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I set the following fields to these values:
      | Requests for session organiser | My note                                 |
      | CF Checkbox                    | 1                                       |
      | customfield_cfdatetime[enabled]| 1                                       |
      | customfield_cfdatetime[day]    | 5                                       |
      | customfield_cfdatetime[month]  | 6                                       |
      | customfield_cfdatetime[year]   | 2031                                    |
      | CF Menu of choices             | Choice 2                                |
      | CF Text area                   | My area                                 |
      | customfield_cfurl[url]         | http://example.com/                     |
      | customfield_cfurl[text]        | Example site                            |
      | Address                        | Molesworth St, Pipitea, Wellington 6011 |
    And I upload "totara/reportbuilder/tests/fixtures/test.txt" file to "CF File" filemanager
    And I click on "Option 2" "text"
    And I press "Confirm"

    # Check that all columns are exist and have correct data.
    Then I should see "Booked" in the "Sam1 Student1" "table_row"
    And I should see "My note" in the "Sam1 Student1" "table_row"
    And I should see "Yes" in the "Sam1 Student1" "table_row"
    And I should see "5 Jun 2031" in the "Sam1 Student1" "table_row"
    And I should see "test.txt" in the "Sam1 Student1" "table_row"
    And I should see "Choice 2" in the "Sam1 Student1" "table_row"
    And I should see "My area" in the "Sam1 Student1" "table_row"
    And I should see "Example site" in the "Sam1 Student1" "table_row"
    And I should see "Molesworth St, Pipitea, Wellington 6011" in the "Sam1 Student1" "table_row"
    And following "test.txt" should download between "10" and "100" bytes
    And I should see "Option 2" in the "Sam1 Student1" "table_row"
