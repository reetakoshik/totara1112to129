@totara @totara_program @totara_customfield
Feature: Program and certification customfields can be created and populated
  As an admin
  I create custom fields and set them for programs
  So that I can test I can review the data I set

  @javascript
  Scenario: I can create and fill in program custom fields
    Given I log in as "admin"
    # Add images to the private files block to use later
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    When I upload "totara/program/tests/fixtures/leaves-blue.png" file to "Files" filemanager
    And I upload "totara/program/tests/fixtures/leaves-green.png" file to "Files" filemanager
    Then I should see "leaves-blue.png"
    And I should see "leaves-green.png"

    When I navigate to "Custom fields" node in "Site administration > Courses"
    And I follow "Programs / Certifications"
    # Checkbox.
    And I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | Program checkbox |
      | Short name (must be unique) | checkbox       |
    And I press "Save changes"
    # Date/time
    And I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | Program date/time |
      | Short name (must be unique) | datetime        |
      | Include time?               | 1               |
    And I press "Save changes"
    # File
    And I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | Program file |
      | Short name (must be unique) | file       |
    And I press "Save changes"
    # Location
    And I set the field "Create a new custom field" to "Location"
    And I set the following fields to these values:
      | Full name                   | Program location |
      | Short name (must be unique) | location       |
    And I press "Save changes"
    # Menu of choices
    And I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Program menu of choices |
      | Short name (must be unique) | menuofchoices         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Apple
      Orange
      Banana
      """
    And I press "Save changes"
    # Multi-select
    And I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Program multi select |
      | Short name (must be unique) | multiselect        |
      | multiselectitem[0][option]  | Tui                |
      | multiselectitem[1][option]  | Moa                |
      | multiselectitem[2][option]  | Tuatara            |
    And I press "Save changes"
    # Text area
    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Program text area |
      | Short name (must be unique) | textarea        |
    And I press "Save changes"
    # Text input
    And I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Program text input |
      | Short name (must be unique) | textinput        |
    And I press "Save changes"
    # URL
    And I set the field "Create a new custom field" to "URL"
    And I set the following fields to these values:
      | Full name                   | Program address |
      | Short name (must be unique) | url           |
    And I press "Save changes"

    Then I should see "Program checkbox"
    And I should see "Program date/time"
    And I should see "Program file"
    And I should see "Program location"
    And I should see "Program menu of choices"
    And I should see "Program multi select"
    And I should see "Program text area"
    And I should see "Program text input"
    And I should see "Program address"

    When I click on "Programs" in the totara menu
    And I press "Add a new program"
    And I set the following fields to these values:
      | Full name                     | Test program                          |
      | Short name                    | Test prog                             |
      | Program checkbox              | 1                                     |
      | customfield_datetime[enabled] | 1                                     |
      | customfield_datetime[day]     | 20                                    |
      | customfield_datetime[month]   | October                               |
      | customfield_datetime[year]    | 2020                                  |
      | customfield_locationaddress   | 150 Willis Street, Te Aro, Wellington |
      | Program text area             | This is within an editor              |
      | Program text input            | This is an input                      |
      | customfield_url[url]          | http://totaralms.com                  |

    # Image in the custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_textarea_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "leaves-blue.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "Blue leaves"
    And I click on "Save image" "button"

    # File in the file custom field.
    And I click on "//div[@id='fitem_id_customfield_file_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    And I press "Save changes"
    And I follow "Overview"
    Then I should see "Yes" for "Program checkbox" in the program overview
    And I should see "20 October 2020" for "Program date/time" in the program overview
    And I should see "leaves-green.png" for "Program file" in the program overview
    And I should see "150 Willis Street, Te Aro, Wellington" for "Program location" in the program overview
    And I should see "This is within an editor" for "Program text area" in the program overview
    And I should see "This is an input" for "Program text input" in the program overview
    And I should see "http://totaralms.com" for "Program address" in the program overview
    And I should see image with alt text "Blue leaves"
