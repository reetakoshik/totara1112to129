@mod_facetoface @totara @totara_customfield @core_calendar
Feature: Filter seminar events in calendar by their customfields
  In order to test the seminar filtering in calendar
  As user
  I need to search various seminar events using their customfields values

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And I log in as "admin"

    # Add customfields to events.
    When I navigate to "Custom fields" node in "Site administration > Seminars"
    And I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name  | Checkbox |
      | Short name | checkbox |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name  | Date time |
      | Short name | datetime  |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Location"
    And I set the following fields to these values:
      | Full name  | Location |
      | Short name | location |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name  | Menu of choices |
      | Short name | menuofchoices |
      | Default value | Choice 1          |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Choice 1
      Choice 2
      Choice 3
      """
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                  | Multi-select |
      | Short name                 | multiselect  |
      | multiselectitem[0][option] | Option 1     |
      | multiselectitem[1][option] | Option 2     |
      | multiselectitem[2][option] | Option 3     |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name     | Text input               |
      | Short name    | textinput                |
      | Default value | Text input default value |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name     | Text area             |
      | Short name    | textarea              |
      | Default value | Text area default value |
    And I press "Save changes"

    # Add customfields to rooms.
    And I follow "Room"

    And I should see "Location" in the "#customfields_program" "css_element"
    And I should see "Building" in the "Text input" "table_row"

    And I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name  | Checkbox |
      | Short name | checkbox |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name  | Date time |
      | Short name | datetime  |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name  | Menu of choices |
      | Short name | menuofchoices |
      | Default value | Choice 1          |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Choice 1
      Choice 2
      Choice 3
      """
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                  | Multi-select |
      | Short name                 | multiselect  |
      | multiselectitem[0][option] | Option 1     |
      | multiselectitem[1][option] | Option 2     |
      | multiselectitem[2][option] | Option 3     |
    And I click on "Make selected by default" "link" in the "#fgroup_id_multiselectitem_0" "css_element"
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name     | Text area             |
      | Short name    | textarea              |
      | Default value | Text area default value |
    And I press "Save changes"

    # Enable filtering by customfields, not using values (as they depend on id)
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Event: Checkbox" "option"
    And I click on "Event: Date time" "option"
    And I click on "Event: Location" "option"
    And I click on "Event: Menu of choices" "option"
    And I click on "Event: Multi-select" "option"
    And I click on "Event: Text input" "option"
    And I click on "Event: Text area" "option"
    And I click on "Room: Checkbox" "option"
    And I click on "Room: Date time" "option"
    And I click on "Room: Location" "option"
    And I click on "Room: Menu of choices" "option"
    And I click on "Room: Multi-select" "option"
    And I click on "Room: Building" "option"
    And I click on "Room: Text area" "option"
    And I press "Save changes"

    # Create rooms
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                          | Room 1          |
      | Address                       | 123 here street |
      | Room capacity                 | 5               |
      | Building                      | That house      |
      | Checkbox                      | 1               |
      | customfield_datetime[enabled] | 1               |
      | customfield_datetime[day]     | 15              |
      | customfield_datetime[month]   | 12              |
      | customfield_datetime[year]    | 2020            |
      | Menu of choices               | Choice 2        |
      | Text area                     | Big text        |
    # Untick default - only Option 2 selected
    And I click on "#id_customfield_multiselect_0" "css_element"
    And I click on "#id_customfield_multiselect_1" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                          | Room 2        |
      | Address                       | 123 other ave |
      | Room capacity                 | 5             |
      | Building                      | My house      |
      | Checkbox                      | 0             |
      | customfield_datetime[enabled] | 1             |
      | customfield_datetime[day]     | 13            |
      | customfield_datetime[month]   | 12            |
      | customfield_datetime[year]    | 2020          |
      | Menu of choices               | Choice 3      |
      | Text area                     | Some text     |
    # Option 1 selected by default
    And I press "Add a room"

    # Create more rooms than is needed to minimize the risk of the session and room having the same id
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                          | Room 3          |
      | Address                       | 123 new street  |
      | Room capacity                 | 15              |
      | Building                      | New house       |
      | Checkbox                      | 1               |
      | customfield_datetime[enabled] | 1               |
      | customfield_datetime[day]     | 15              |
      | customfield_datetime[month]   | 12              |
      | customfield_datetime[year]    | 2020            |
      | Menu of choices               | Choice 1        |
      | Text area                     | New text        |
    # Untick default - only Option 2 selected
    And I click on "#id_customfield_multiselect_0" "css_element"
    And I click on "#id_customfield_multiselect_1" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                          | Room 4        |
      | Address                       | 123 old ave   |
      | Room capacity                 | 15            |
      | Building                      | Old house     |
      | Checkbox                      | 0             |
      | customfield_datetime[enabled] | 1             |
      | customfield_datetime[day]     | 13            |
      | customfield_datetime[month]   | 12            |
      | customfield_datetime[year]    | 2020          |
      | Menu of choices               | Choice 3      |
      | Text area                     | Some text     |
    # Leave default - Options 1 and 2 selected
    And I click on "#id_customfield_multiselect_1" "css_element"
    And I press "Add a room"

    # Add 2 seminars using different custom fields and rooms
    And I am on "Course 1" course homepage with editing mode on

    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar one        |
      | Description | Seminar one desc   |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | timestart[timezone]  | Australia/Perth |
      | timestart[day]       | 0               |
      | timestart[month]     | 0               |
      | timestart[year]      | 0               |
      | timestart[hour]      | 0               |
      | timestart[minute]    | 0               |
      | timefinish[timezone] | Australia/Perth |
      | timefinish[day]      | 0               |
      | timefinish[month]    | 0               |
      | timefinish[year]     | 0               |
      | timefinish[hour]     | +1              |
      | timefinish[minute]   | 0               |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Room 4" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I set the following fields to these values:
      | capacity                      | 2             |
      | Address                       | 54 oak street |
      | Maximum bookings              | 5             |
      | Text input                    | short desc    |
      | Checkbox                      | 0             |
      | customfield_datetime[enabled] | 1             |
      | customfield_datetime[day]     | 13            |
      | customfield_datetime[month]   | 11            |
      | customfield_datetime[year]    | 2020          |
      | Menu of choices               | Choice 1      |
      | Text area                     | My area       |
    And I click on "#id_customfield_multiselect_0" "css_element"
    And I click on "#id_customfield_multiselect_2" "css_element"
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar two        |
      | Description | Seminar two desc   |
    And I follow "Seminar two"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | timestart[timezone]  | Australia/Perth |
      | timestart[day]       | 0               |
      | timestart[month]     | 0               |
      | timestart[year]      | 0               |
      | timestart[hour]      | 0               |
      | timestart[minute]    | 0               |
      | timefinish[timezone] | Australia/Perth |
      | timefinish[day]      | 0               |
      | timefinish[month]    | 0               |
      | timefinish[year]     | 0               |
      | timefinish[hour]     | +1              |
      | timefinish[minute]   | 0               |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select room" "link"
    And I click on "Room 3" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I set the following fields to these values:
      | capacity                      | 2            |
      | Address                       | 35 oak cres. |
      | Maximum bookings              | 5            |
      | Text input                    | text desc    |
      | Checkbox                      | 0            |
      | customfield_datetime[enabled] | 1            |
      | customfield_datetime[day]     | 15           |
      | customfield_datetime[month]   | 11           |
      | customfield_datetime[year]    | 2020         |
      | Menu of choices               | Choice 2     |
      | Text area                     | Input area   |
    And I click on "#id_customfield_multiselect_0" "css_element"
    And I click on "#id_customfield_multiselect_1" "css_element"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Check that seminar customfields can be used to filter events in calendar
    When I log in as "student1"
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Calendar" block
    And I follow "This month"
    And I should see "Sam1 Student1"
    And I should see "Seminar one"
    And I should see "Seminar two"

    # Confirm that custom fields filters are displayed
    And I should see "Event: Checkbox"
    And I should see "Event: Date time"
    And I should see "Event: Location"
    And I should see "Event: Menu of choices"
    And I should see "Event: Multi-select"
    And I should see "Event: Text input"
    And I should see "Event: Text area"
    And I should see "Room: Checkbox"
    And I should see "Room: Date time"
    And I should see "Room: Location"
    And I should see "Room: Menu of choices"
    And I should see "Room: Multi-select"
    And I should see "Room: Building"
    And I should see "Room: Text area"

    # Search all fields for one event
    And I set the following fields to these values:
      | Event: Date time:  | november        |
      | Event: Location:   | 54 oak street   |
      | Event: Text input: | short desc      |
      | Event: Text area:  | my area         |
      | Room: Location:    | 123 old ave     |
      | Room: Building:    | old house       |
      | Room: Date time:   | december        |
      | Room: Text area:   | SOME TEXT       |
    And I select "No" from the "field_sess_checkbox" singleselect
    And I select "Choice 1" from the "field_sess_menuofchoices" singleselect
    And I select "Option 3" from the "field_sess_multiselect" singleselect
    And I select "No" from the "field_room_checkbox" singleselect
    And I select "Choice 3" from the "field_room_menuofchoices" singleselect
    And I select "Option 2" from the "field_room_multiselect" singleselect
    When I press "Apply filter"
    Then I should see "Seminar one"
    And I should not see "Seminar two"

    # Search all fields common for both events.
    And I set the following fields to these values:
      | Event: Date time:  | november        |
      | Event: Location:   | oak             |
      | Event: Text input: | desc            |
      | Event: Text area:  | area            |
      | Room: Location:    | 123             |
      | Room: Building:    | house           |
      | Room: Date time:   | december        |
      | Room: Text area:   | text            |
    And I select "All" from the "field_sess_menuofchoices" singleselect
    And I select "No" from the "field_sess_checkbox" singleselect
    And I select "Option 1" from the "field_sess_multiselect" singleselect
    And I select "All" from the "field_room_checkbox" singleselect
    And I select "All" from the "field_room_menuofchoices" singleselect
    And I select "All" from the "field_room_multiselect" singleselect
    When I press "Apply filter"
    Then I should see "Seminar one"
    And I should see "Seminar two"
