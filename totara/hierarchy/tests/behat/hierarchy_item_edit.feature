@totara_hierarchy @totara @totara_customfield
Feature: The generators create the expected position framework

  Background:
    Given I am on a totara site
    And the following "position type" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber   |
      | Position type 1 | POSTYPE001 |
    And the following "position" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test position framework   | FW001    | Framework description |
    And the following "position" hierarchy exists:
      | framework | fullname          | idnumber | type |
      | FW001     | First position    | POS001   | POSTYPE001 |

  Scenario: A position item can be updated
    When I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Test position framework"
    And I click on "Edit" "link" in the "First position" "table_row"
    Then the following fields match these values:
      | Name               | First position          |
      | Position ID number | POS001                  |
    And I set the following fields to these values:
      | Name               | Second position           |
      | Position ID number | POS002                    |
      | Description        | This is a second position |
    And I click on "Save changes" "button"
    And I should see "This is a second position" in the ".dl-horizontal" "css_element"

  @javascript
  Scenario: A position item custom fields can be updated and displayed
    When I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Positions"
    Then I follow "Position type 1"

    # Add Text input field.
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Custom field text input |
      | Short name (must be unique) | CF_text                 |
    Then I press "Save changes"

    # Add Textarea field.
    When I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Custom field text area |
      | Short name (must be unique) | CF_textarea            |
    Then I press "Save changes"

    # Add File field.
    When I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | Custom field file  |
      | Short name (must be unique) | CF_file            |
    Then I press "Save changes"

    # Add URL field.
    When I set the field "Create a new custom field" to "URL"
    And I set the following fields to these values:
      | Full name                   | Custom field url  |
      | Short name (must be unique) | CF_url            |
    Then I press "Save changes"

    # Add Location field.
    When I set the field "Create a new custom field" to "Location"
    And I set the following fields to these values:
      | Full name                   | Custom field location |
      | Short name (must be unique) | CF_location            |
    Then I press "Save changes"

    # Add data to the custom fields for the position.
    And I navigate to "Manage positions" node in "Site administration > Positions"
    And I click on "Test position framework" "link"
    And I click on "First position" "link"
    And I click on "Edit" "link"
    And I set the following fields to these values:
       | Custom field text input     | My text input  |
       | Custom field text area      | My text area   |
    And I set the field "customfield_CFurl[url]" to "http://www.google.com"
    And I set the field "customfield_CFlocationaddress" to "BN1 1YR"
    And I set the field "customfield_CFlocationdisplay" to "both"
    And I upload "question/type/ddmarker/tests/fixtures/mkmap.png" file to "Custom field file" filemanager
    And I click on "Save changes" "button"

    # Check all the custom field data is displayed correctly on the item/view.php page.
    Then I should see "First position"
    And I should see "Position type 1"
    And I should see "My text input"
    And I should see "My text area"
    And I should see "BN1 1YR"
    And I should see "mkmap.png" in the "//div[@id='region-main']//dd/a[@class='icon']" "xpath_element"
    And I should see "http://www.google.com" in the "//div[@id='region-main']//dd/a[@rel='noreferrer']" "xpath_element"

    # Check all the custom field data is displayed correctly on the hierarchy/index.php page.
    When I follow "Test position framework"
    Then I should see "First position"
    And I should see "Position type 1"
    And I should see "My text input"
    And I should see "My text area"
    And I should see "BN1 1YR"
    And I should see "mkmap.png" in the "//div[@id='region-main']//div/a[@class='icon']" "xpath_element"
    And I should see "http://www.google.com" in the "//div[@id='region-main']//div/a[@rel='noreferrer']" "xpath_element"
