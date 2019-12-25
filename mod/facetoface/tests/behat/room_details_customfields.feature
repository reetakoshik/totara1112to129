@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Check room details with all possible custom fields
  In order to test room details page
  As a site manager
  I need to create an event and room, add custom fields, login as admin and check room details page

  Scenario: Login as manager and view room details page and check all custom fields are properly displayed.
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name            | course | idnumber |
      | facetoface | Seminar TL-9134 | C1     | seminar  |
    And I log in as "admin"

    # Add images to the private files block to use later
    When I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "mod/facetoface/tests/fixtures/test.jpg" file to "Files" filemanager
    And I upload "mod/facetoface/tests/fixtures/leaves-green.png" file to "Files" filemanager
    Then I should see "test.jpg"
    And I should see "leaves-green.png"

    # Add custom fields.
    When I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Room" "link"

    And I click on "Checkbox" "option"
    And I set the following fields to these values:
      | Full name                   | Room checkbox  |
      | Short name (must be unique) | checkbox       |
    And I press "Save changes"

    And I click on "Date/time" "option"
    And I set the following fields to these values:
      | Full name                   | Room date/time  |
      | Short name (must be unique) | datetime        |
      | Include time?               | 1               |
    And I press "Save changes"

    And I click on "File" "option"
    And I set the following fields to these values:
      | Full name                   | Room file  |
      | Short name (must be unique) | file       |
    And I press "Save changes"

    And I click on "Menu of choices" "option"
    And I set the following fields to these values:
      | Full name                   | Room menu of choices  |
      | Short name (must be unique) | menuofchoices         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Apple
      Orange
      Banana
      """
    And I press "Save changes"

    And I click on "Multi-select" "option"
    And I set the following fields to these values:
      | Full name                   | Room multi select  |
      | Short name (must be unique) | multiselect        |
      | multiselectitem[0][option]  | Tui                |
      | multiselectitem[1][option]  | Moa                |
      | multiselectitem[2][option]  | Tuatara            |
    And I press "Save changes"

    And I click on "Text area" "option"
    And I set the following fields to these values:
      | Full name                   | Room text area  |
      | Short name (must be unique) | textarea        |
    And I press "Save changes"

    And I click on "Text input" "option"
    And I set the following fields to these values:
      | Full name                   | Room text input  |
      | Short name (must be unique) | textinput        |
    And I press "Save changes"

    And I click on "URL" "option"
    And I set the following fields to these values:
      | Full name                   | Room address  |
      | Short name (must be unique) | url           |
    And I press "Save changes"
    # Verify the custom fields are all there.
    Then I should see "Room checkbox"
    And I should see "Room date/time"
    And I should see "Room file"
    And I should see "Room menu of choices"
    And I should see "Room multi select"
    And I should see "Room text area"
    And I should see "Room text input"
    And I should see "Room address"

    # Create a room
    When I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                 | Room 1          |
      | Room capacity        | 10              |
      | Building             | Building 123    |
      | Address              | 123 Tory street |
      | Room checkbox        | 1               |
      | Room menu of choices | Orange          |
      | Room text area       | Lorem ipsum dolor sit amet, consectetur adipisicing elit |
      | Room text input      | Duis aute irure dolor in reprehenderit in voluptate      |
      | customfield_datetime[enabled] | 1    |
      | customfield_datetime[day]     | 2    |
      | customfield_datetime[month]   | 3    |
      | customfield_datetime[year]    | 2020 |
      | customfield_datetime[hour]    | 10   |
      | customfield_datetime[minute]  | 30   |
      | customfield_datetime[enabled] | 1    |
      | customfield_multiselect[2]    | 1    |
      | customfield_url[url]          | http://totaralearning.com |
      | customfield_url[text]         | Totara LMS                |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"

    # Add a file to the file custom field.
    And I click on "//div[@id='fitem_id_customfield_file_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "test.jpg" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Image in the textarea custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_textarea_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I set the field "Describe this image for someone who cannot see it" to "Green leaves on customfield text area"
    And I click on "Save image" "button"

    # Add the room.
    And I press "Add a room"

    # Use the room.
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    And I click on "Room 1, Building 123, 123 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I am on "Course 1" course homepage

    # View the room.
    And I click on "Room details" "link"
    And I switch to "popup" window

    # Confirm that all of the room customfields saved.
    Then I should see "View room"
    And I should see "Room 1"
    # "Yes" for checkbox
    And I should see "Yes"
    And I should see "Monday, 2 March 2020, 10:30 AM"
    And I should see "test.jpg"
    And I should see "Orange"
    And I should see "Tuatara"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipisicing elit"
    And I should see "Duis aute irure dolor in reprehenderit in voluptate"
    And I should see "Totara LMS"
    And I should see "Upcoming sessions in this room"
    And I should see "Seminar TL-9134"
    And I should see the "Green leaves on customfield text area" image in the "//dd[preceding-sibling::dt[1][. = 'Room text area']]" "xpath_element"
    And I should see image with alt text "Green leaves on customfield text area"
