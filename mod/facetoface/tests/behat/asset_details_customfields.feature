@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Check asset details with all possible custom fields
  In order to test asset details page
  As a site manager
  I need to create an event and asset, add custom fields, login as admin and check asset details page

  Scenario: View asset details page and check all custom fields are properly displayed.
    Given I am on a totara site
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

    When I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Asset" "link"

    And I click on "Checkbox" "option"
    And I set the following fields to these values:
      | Full name                   | Asset checkbox |
      | Short name (must be unique) | checkbox       |
    And I press "Save changes"

    And I click on "Date/time" "option"
    And I set the following fields to these values:
      | Full name                   | Asset date/time |
      | Short name (must be unique) | datetime        |
      | Include time?               | 1               |
    And I press "Save changes"

    And I click on "File" "option"
    And I set the following fields to these values:
      | Full name                   | Asset file |
      | Short name (must be unique) | file       |
    And I press "Save changes"

    And I click on "Location" "option"
    And I set the following fields to these values:
      | Full name                   | Asset location |
      | Short name (must be unique) | location       |
    And I press "Save changes"

    And I click on "Menu of choices" "option"
    And I set the following fields to these values:
      | Full name                   | Asset menu of choices |
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
      | Full name                   | Asset multi select |
      | Short name (must be unique) | multiselect        |
      | multiselectitem[0][option]  | Tui                |
      | multiselectitem[1][option]  | Moa                |
      | multiselectitem[2][option]  | Tuatara            |
    And I press "Save changes"

    And I click on "Text area" "option"
    And I set the following fields to these values:
      | Full name                   | Asset text area |
      | Short name (must be unique) | textarea        |
    And I press "Save changes"

    And I click on "Text input" "option"
    And I set the following fields to these values:
      | Full name                   | Asset text input |
      | Short name (must be unique) | textinput        |
    And I press "Save changes"

    And I click on "URL" "option"
    And I set the following fields to these values:
      | Full name                   | Asset address |
      | Short name (must be unique) | url           |
    And I press "Save changes"

    Then I should see "Asset checkbox"
    And I should see "Asset date/time"
    And I should see "Asset file"
    And I should see "Asset location"
    And I should see "Asset menu of choices"
    And I should see "Asset multi select"
    And I should see "Asset text area"
    And I should see "Asset text input"
    And I should see "Asset address"

    # Create an asset
    When I navigate to "Assets" node in "Site administration > Seminars"
    And I press "Add a new asset"
    # Set the basic fields.
    And I set the following fields to these values:
      | Asset name        | Asset 1         |
      | Asset checkbox    | 1               |
      | Asset menu of choices | Orange      |
      | Asset text area       | Lorem ipsum dolor sit amet, consectetur adipisicing elit |
      | Asset text input      | Duis aute irure dolor in reprehenderit in voluptate      |
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
    # Set a location.
    And I set the field "Address" to multiline:
      """
      Level 8, Totara
      Catalyst House
      150 Willis street
      Te Aro
      Wellington 6011
      """
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_both" "css_element"

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
    # Create the asset.
    And I press "Add an asset"

    # Verify that the asset was created correctly.
    When I click on "Details" "link"
    Then I should see "View asset"
    And I should see "Asset 1"
    And I should see "150 Willis street"
    # "Yes" for checkbox
    And I should see "Yes"
    And I should see "Monday, 2 March 2020, 10:30 AM"
    And I should see "test.jpg"
    And I should see "Orange"
    And I should see "Tuatara"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipisicing elit"
    And I should see "Duis aute irure dolor in reprehenderit in voluptate"
    And I should see "Totara LMS"
    And I should see the "Green leaves on customfield text area" image in the "//dd[preceding-sibling::dt[1][. = 'Asset text area']]" "xpath_element"
    And I should see image with alt text "Green leaves on customfield text area"
