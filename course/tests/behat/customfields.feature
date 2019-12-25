@totara @core_course @totara_customfield
Feature: Course customfields can be created and populated
  As an admin
  I create custom fields and set them for courses
  So that I can test I can review the data I set

  @javascript
  Scenario: I can create and fill in course custom fields
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
    # Checkbox.
    And I click on "Checkbox" "option"
    And I set the following fields to these values:
      | Full name                   | Course checkbox |
      | Short name (must be unique) | checkbox       |
    And I press "Save changes"
    # Date/time
    And I click on "Date/time" "option"
    And I set the following fields to these values:
      | Full name                   | Course date/time |
      | Short name (must be unique) | datetime        |
      | Include time?               | 1               |
    And I press "Save changes"
    # File
    And I click on "File" "option"
    And I set the following fields to these values:
      | Full name                   | Course file |
      | Short name (must be unique) | file       |
    And I press "Save changes"
    # Location
    And I click on "Location" "option"
    And I set the following fields to these values:
      | Full name                   | Course location |
      | Short name (must be unique) | location       |
    And I press "Save changes"
    # Menu of choices
    And I click on "Menu of choices" "option"
    And I set the following fields to these values:
      | Full name                   | Course menu of choices |
      | Short name (must be unique) | menuofchoices         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Apple
      Orange
      Banana
      """
    And I press "Save changes"
    # Multi-select
    And I click on "Multi-select" "option"
    And I set the following fields to these values:
      | Full name                   | Course multi select |
      | Short name (must be unique) | multiselect        |
      | multiselectitem[0][option]  | Tui                |
      | multiselectitem[1][option]  | Moa                |
      | multiselectitem[2][option]  | Tuatara            |
    And I press "Save changes"
    # Text area
    And I click on "Text area" "option"
    And I set the following fields to these values:
      | Full name                   | Course text area |
      | Short name (must be unique) | textarea        |
    And I press "Save changes"
    # Text input
    And I click on "Text input" "option"
    And I set the following fields to these values:
      | Full name                   | Course text input |
      | Short name (must be unique) | textinput        |
    And I press "Save changes"
    # URL
    And I click on "URL" "option"
    And I set the following fields to these values:
      | Full name                   | Course address |
      | Short name (must be unique) | url           |
    And I press "Save changes"

    Then I should see "Course checkbox"
    And I should see "Course date/time"
    And I should see "Course file"
    And I should see "Course location"
    And I should see "Course menu of choices"
    And I should see "Course multi select"
    And I should see "Course text area"
    And I should see "Course text input"
    And I should see "Course address"

    When I click on "Courses" in the totara menu
    And I press "Add a new course"
    And I set the following fields to these values:
      | Course full name            | Test course                          |
      | Course short name           | Test prog                             |
      | Course checkbox             | 1                                     |
      | customfield_datetime[day]   | 20                                    |
      | customfield_datetime[month] | October                               |
      | customfield_datetime[year]  | 2020                                  |
      | customfield_locationaddress | 150 Willis Street, Te Aro, Wellington |
      | Course text area            | This is within an editor              |
      | Course text input           | This is an input                      |
      | customfield_url[url]        | http://totaralms.com                  |

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

    # Here we go.
    And I press "Save and return"

    # Check the form saves correctly and returns to the right page.
    Then I should see "Test course"

    # Confirm image loads successfully.
    When I click on "Test course" "link"
    And I navigate to "Edit settings" node in "Course administration"
    And I click on "Custom fields" "link"
    Then I should see image with alt text "Blue leaves"
