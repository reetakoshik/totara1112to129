@javascript @totara @totara_plan @totara_customfield
Feature: Evidence custom fields.

  Background:
    Given I am on a totara site

  Scenario: As an admin I need to add a checkbox custom field for evidence so that a manager is presented with a boolean option when adding evidence.

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | Checkbox test |
      | Short name (must be unique) | checkboxtest  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Checkbox test"

  Scenario: As an admin I need to add a date/time custom field for evidence so that a manager can select a date/time when adding evidence.

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | Date time test |
      | Short name (must be unique) | datetimetest   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Date time test"

  Scenario: As an admin I need to add a file custom field for evidence so that a manager can upload one or more files when adding an evidence record

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    Then I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | File test |
      | Short name (must be unique) | filetest  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "File test"

  Scenario: As an admin I need to add a menu of choices custom field for evidence so that a manager can choose one of several predefined options in a control when adding an evidence record

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Menu test |
      | Short name (must be unique) | menutest  |
    And I set the field "Menu options (one per line)" to multiline:
      """
      optionone
      optiontwo
      """
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Menu test"

  Scenario: As an admin I need to add a multi-select custom field for evidence so that a manager choose one or more predefined options in a control when adding an evidence record

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Multi-select test |
      | Short name (must be unique) | multiselecttest   |
      | multiselectitem[0][option]  | optionone         |
      | multiselectitem[1][option]  | optiontwo         |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Multi-select test"

  Scenario: As an admin I need to add a text custom field for evidence so that a manager can add a small amount of text to an evidence record

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Text input test |
      | Short name (must be unique) | textinputtest   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Text input test"

  Scenario: As an admin I need to add a text area custom field for evidence so that a manager can add a small amount of text to an evidence record

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Text area test |
      | Short name (must be unique) | textareatest   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Text area test"

  Scenario: As an admin I need to add a URL custom field for evidence so that a manager can add a URL to an evidence record

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "URL"
    And I set the following fields to these values:
      | Full name                   | URL test |
      | Short name (must be unique) | urltest   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "URL test"

  Scenario: As an admin I need to delete a custom field for evidence so that a manager is no longer required to enter that field's information

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Delete test |
      | Short name (must be unique) | deletetest  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Delete test"
    When I click on "Delete" "link" in the "Delete test" "table_row"
    And I press "Yes"
    Then I should see "Available Evidence Custom Fields"
    And I should not see "Delete test"

  Scenario: As an admin I need to sort the custom fields for evidence so that they can control the order in which fields are displayed when editing

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Sort test 1 |
      | Short name (must be unique) | sorttest1   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Sort test 1"
    Then I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Sort test 2 |
      | Short name (must be unique) | sorttest 2  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Sort test 2"
    When I click on "//table/tbody/tr[last()]/td/a[@title='Move up']" "xpath_element"
    Then I should see "Sort test 1" in the "//table/tbody/tr[last()]/td[1]" "xpath_element"
    When I click on "//table/tbody/tr[last() - 1]/td/a[@title='Move down']" "xpath_element"
    Then I should see "Sort test 2" in the "//table/tbody/tr[last()]/td[1]" "xpath_element"

  Scenario: As an admin I need to edit the custom fields for evidence so that I can change whether the field is required

    Given the following "users" exist:
      | username  |
      | learner1  |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Edit test |
      | Short name (must be unique) | edittest1 |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Edit test"

    When I click on "Edit" "link" in the "Edit test" "table_row"
    And I set the field "This field is required" to "Yes"
    And I press "Save changes"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the field "Evidence name" to "Test evidence"
    And I press "Add evidence"
    Then I should see "This field is required" in the "#fitem_id_customfield_edittest1" "css_element"

  Scenario: As an admin I need to edit the custom fields for evidence so that I can change the name of a custom field.

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Edit test |
      | Short name (must be unique) | edittest2 |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Edit test"
    When I click on "Edit" "link" in the "Edit test" "table_row"
    And I set the field "Full name" to "Edited name"
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Edited name"
    And I should not see "Edit test"

  Scenario: As an admin I need to edit the custom fields for evidence so that I can change the default value of a custom field.

    Given the following "users" exist:
      | username  |
      | learner1  |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Edit test |
      | Short name (must be unique) | edittest2 |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Edit test"
    When I click on "Edit" "link" in the "Edit test" "table_row"
    And I set the field "Default value" to "Test default value"
    And I press "Save changes"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    Then the field "Edit test" matches value "Test default value"

  Scenario: As an admin I need to edit the custom fields for evidence so that I can change the visibility of a field when editing or viewing.

    Given the following "users" exist:
      | username  |
      | learner1  |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Edit test 3 |
      | Short name (must be unique) | edittest3   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Edit test 3"
    When I click on "Edit" "link" in the "Edit test 3" "table_row"
    And I set the field "Hidden on the settings page?" to "Yes"
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    When I click on "Edit" "link" in the "Edit test 3" "table_row"
    When I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    Then I should not see "Edit test 3"
    When I set the field "Evidence name" to "Evidence test"
    And I press "Add evidence"
    And I follow "Evidence test"
    Then I should not see "Edit test 3"

  Scenario: As a learner I need to tick a checkbox when creating an evidence record so that I can indicate a true boolean value in the evidence record

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | Checkbox test |
      | Short name (must be unique) | checkboxtest  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Checkbox test"
    When I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name | Checkbox evidence |
    And I click on "Checkbox test" "checkbox"
    And I press "Add evidence"
    And I follow "Checkbox evidence"
    Then I should see "Checkbox test : Yes"

  Scenario: As a learner I need to set a date/time when creating an evidence record so that I can indicate a date and time in the evidence record

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | Date time test |
      | Short name (must be unique) | datetimetest   |
      | Start year                  | 1982           |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Date time test"
    Given I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Date test |
      | customfield_datetimetest[enabled] | 1         |
      | customfield_datetimetest[day]     | 15        |
      | customfield_datetimetest[month]   | 3         |
      | customfield_datetimetest[year]    | 1982      |
      | customfield_datetimetest[enabled] | 1         |
    And I press "Add evidence"
    And I follow "Date test"
    Then I should see "Date time test : 15 March 1982"

  Scenario: As a learner I need to upload a file when creating an evidence record so that I can attach file-based content

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | File test |
      | Short name (must be unique) | filetest   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "File test"
    Given I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                            | File evidence |
    And I upload "totara/plan/tests/fixtures/textfile.txt" file to "File test" filemanager
    And I press "Add evidence"
    And I follow "File evidence"
    Then I should see "Filetextfile.txt"

  Scenario: As a learner I need to select a predefined value when creating an evidence record so that I can categorise evidence

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Menu test |
      | Short name (must be unique) | menutest   |
    And I set the field "Menu options (one per line)" to multiline:
      """
      optionone
      optiontwo
      optionthree
      """
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Menu test"
    Given I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                            | Menu evidence |
    And I set the field "Menu test" to "optionthree"
    And I press "Add evidence"
    And I follow "Menu evidence"
    Then I should see "Menu test : optionthree"

  Scenario: As a learner I need to select multiple predefined values when creating an evidence record so that I can tag evidence

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Multi-select test |
      | Short name (must be unique) | multiselecttest   |
      | multiselectitem[0][option]  | optionone         |
      | multiselectitem[1][option]  | optiontwo         |
      | multiselectitem[2][option]  | optionthree       |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Multi-select test"
    Given I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                            | Multi-select evidence |
    And I set the following fields to these values:
      | customfield_multiselecttest[0] | 1 |
      | customfield_multiselecttest[1] | 1 |
    And I press "Add evidence"
    And I follow "Multi-select evidence"
    Then I should see "Multi-select test : optionone optiontwo"

  Scenario: As a learner I need to provide a short text detail when creating an evidence record so that I can provide a small amount of detail with my evidence

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Text input test |
      | Short name (must be unique) | textinputtest   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Text input test"
    Given I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name | Text input evidence |
    And I set the following fields to these values:
      | Text input test | This is my test text |
    And I press "Add evidence"
    And I follow "Text input evidence"
    Then I should see "Text input test : This is my test text"

  Scenario: As a learner I need to provide a paragraph of text when creating an evidence record so that I can provide more detail with my evidence

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Text area test |
      | Short name (must be unique) | textareatest   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Text area test"
    When I log out
    And I log in as "learner1"

        # Add images to the private files block to use later
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "totara/plan/tests/fixtures/pic1.png" file to "Files" filemanager
    Then I should see "pic1.png"

    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name | Text area evidence |
    And I set the field "Text area test" to "This is a text area!"
    # Image in the custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_textareatest_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "pic1.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "My image"
    And I click on "Save image" "button"

    And I press "Add evidence"
    And I follow "Text area evidence"
    Then I should see "Text area test : This is a text area!"
    And I should see the "My image" image in the "//*[@id='dp-plan-content']" "xpath_element"
    And I should see image with alt text "My image"

  Scenario: As a learner I need to provide a URL when creating an evidence record so that I can link to online documents and pages within Totara

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    When I set the field "Create a new custom field" to "URL"
    And I set the following fields to these values:
      | Full name                   | URL test |
      | Short name (must be unique) | urltest  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "URL test"
    When I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name            | URL evidence |
      | customfield_urltest[url] | /my/index.php      |
    And I set the field "customfield_urltest[url]" to "/my/index.php"
    And I press "Add evidence"
    And I follow "URL evidence"
    Then I should see "URL test : /my/index.php"
    And I should see "/my/index.php" in the "//p/a[@href='/my/index.php']" "xpath_element"

  Scenario: As a learner I need to edit an evidence record so that I can update the title and description

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"

    When I set the following fields to these values:
      | Evidence name | Edit typo evidence              |
      | Description   | This is some test evidence typo |
    And I press "Add evidence"
    And I follow "Edit typo evidence"
    Then I should see "Edit typo evidence"
    And I should see "Description : This is some test evidence typo"

    When I press "Edit details"
    And I set the following fields to these values:
      | Evidence name | Edit evidence              |
      | Description   | This is some test evidence |
    And I press "Update evidence"
    Then I should see "Evidence updated"
    And I should see "Edit evidence"
    And I should see "Description : This is some test evidence"

  Scenario: As a learner I need to delete an evidence record so that I can remove a evidence from the system

    Given the following "users" exist:
      | username |
      | learner1 |
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    When I set the following fields to these values:
      | Evidence name | Evidence delete test |
    And I press "Add evidence"
    Then I should see "Evidence delete test"

    When I click on "Delete" "link" in the "Evidence delete test" "table_row"
    And I press "Continue"
    Then I should see "Evidence deleted"
    And I should see "There are no records in this report"

  Scenario: As an admin I need to add a custom field for evidence where it's value is unique.

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"

    # Create a text area custom field.
    When I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Unique textarea test |
      | Short name (must be unique) | textareatest         |
      | Should the data be unique?  | Yes                  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Unique textarea test"

    # Create a text input custom field.
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Unique input test |
      | Short name (must be unique) | textinputtest     |
      | Should the data be unique?  | Yes               |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Unique input test"

    # Create a checkbox custom field.
    When I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | Unique checkbox test |
      | Short name (must be unique) | checkboxtest         |
      | Should the data be unique?  | Yes                  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Unique checkbox test"

    # Create a date/time custom field.
    When I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | Unique date/time test |
      | Short name (must be unique) | datetimetest          |
      | Should the data be unique?  | Yes                   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Unique date/time test"

    # Create a menu of choices custom field.
    When I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Unique menu of choices test |
      | Short name (must be unique) | menutest                    |
      | Should the data be unique?  | Yes                         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      optionone
      optiontwo
      optionthree
      """
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Unique menu of choices test"

    # Create a multi-select custom field.
    When I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Unique multi-select test |
      | Short name (must be unique) | multiselecttest          |
      | Should the data be unique?  | Yes                      |
      | multiselectitem[0][option]  | optionone                |
      | multiselectitem[1][option]  | optiontwo                |
      | multiselectitem[2][option]  | optionthree              |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Unique multi-select test"

    # Create a location custom field.
    When I set the field "Create a new custom field" to "Location"
    And I set the following fields to these values:
      | Full name                   | Unique location test  |
      | Short name (must be unique) | locationtest          |
      | Should the data be unique?  | Yes                   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Unique location test"

    # Create a piece of evidence using the custom fields.
    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Unique input test 1 |
      | Unique textarea test              | Hello               |
      | Unique input test                 | Test 1              |
      | Unique checkbox test              | Yes                 |
      | customfield_datetimetest[enabled] | 1                   |
      | customfield_datetimetest[day]     | 19                  |
      | customfield_datetimetest[month]   | 7                   |
      | customfield_datetimetest[year]    | 2027                |
      | Unique menu of choices test       | optiontwo           |
      | customfield_multiselecttest[1]    | 1                   |
      | id_customfield_locationtestaddress| SW1                 |
    And I press "Add evidence"
    Then I should see "Unique input test 1"

    # Create another piece of evidence using the same custom field values, setting it as not unique.
    When I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Unique input test 2 |
      | Unique textarea test              | Hello               |
      | Unique input test                 | Test 1              |
      | Unique checkbox test              | Yes                 |
      | customfield_datetimetest[enabled] | 1                   |
      | customfield_datetimetest[day]     | 19                  |
      | customfield_datetimetest[month]   | 7                   |
      | customfield_datetimetest[year]    | 2027                |
      | Unique menu of choices test       | optiontwo           |
      | customfield_multiselecttest[1]    | 1                   |
      | id_customfield_locationtestaddress| SW1                 |
    And I press "Add evidence"
    And I should see the form validation error "This value has already been used." for the "textareatest" custom field
    Then I should see the form validation error "This value has already been used." for the "textinputtest" custom field
    And I should see the form validation error "This value has already been used." for the "checkboxtest" custom field
    And I should see the form validation error "The 'datetimetest' date/time custom field contains a non-unique date" for the "datetimetest" custom field
    And I should see the form validation error "This value has already been used." for the "menutest" custom field
    # TODO: Add support for multiselect and location custom fields.
    # And I should see the form .phpvalidation error "This value has already been used." for the "multiselecttest" custom field
    # And I should see the form .phpvalidation error "This value has already been used." for the "locationtest" custom field

    # Update the custom field values to be unique.
    When I set the following fields to these values:
      | Unique textarea test              | Goodbye   |
      | Unique input test                 | Test 2    |
      | Unique checkbox test              | 0         |
      | customfield_datetimetest[day]     | 20        |
      | customfield_datetimetest[month]   | 8         |
      | customfield_datetimetest[year]    | 2028      |
      | Unique menu of choices test       | optionone |
      | customfield_multiselecttest[1]    | 0         |
      | customfield_multiselecttest[2]    | 1         |
      | id_customfield_locationtestaddress| SW2       |
    And I press "Add evidence"
    Then I should not see "This value has already been used."
    And I should not see "The 'datetimetest' date/time custom field contains a non-unique date"

  Scenario: As an admin I need to add a custom field for evidence where it's value is locked.

    Given I log in as "admin"
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"

    # Create a text input custom field.
    When I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Locked input test |
      | Short name (must be unique) | textinputtest     |
      | Is this field locked?       | Yes               |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Locked input test"

    # Create a checkbox custom field.
    When I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | Locked checkbox test |
      | Short name (must be unique) | checkboxtest         |
      | Is this field locked?       | Yes                  |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Locked checkbox test"

    # Create a date/time custom field.
    When I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | Locked date/time test |
      | Short name (must be unique) | datetimetest          |
      | Is this field locked?       | Yes                   |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Locked date/time test"

    # Create a menu of choices custom field.
    When I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Locked menu 1 |
      | Short name (must be unique) | menutest1     |
      | Is this field locked?       | Yes           |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Option 1
      Option 2
      Option 3
      """
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Locked menu 1"

    # Create a menu of choices custom field.
    When I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Locked menu 2 |
      | Short name (must be unique) | menutest2     |
      | Is this field locked?       | Yes           |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Option 1
      Option 2
      Option 3
      """
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Locked menu 2"

    # Create a multi-select custom field.
    When I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Locked multi-select test |
      | Short name (must be unique) | multiselecttest          |
      | Is this field locked?       | Yes                      |
      | multiselectitem[0][option]  | optionone                |
      | multiselectitem[1][option]  | optiontwo                |
      | multiselectitem[2][option]  | optionthree              |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Locked multi-select test"

    # Create a textarea custom field.
    When I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Locked Textarea 1 |
      | Short name (must be unique) | textarea1         |
      | Is this field locked?       | Yes               |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Locked Textarea 1"

    # Create a textarea custom field.
    When I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Locked Textarea 2 |
      | Short name (must be unique) | textarea2         |
      | Is this field locked?       | Yes               |
    And I press "Save changes"
    Then I should see "Available Evidence Custom Fields"
    And I should see "Locked Textarea 2"

    # Create a piece of evidence to check the fields are locked after first input.
    When I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name                     | Locked input test 1 |
      | Locked input test                 | Test 1              |
      | Locked checkbox test              | Yes                 |
      | customfield_datetimetest[enabled] | 1                   |
      | customfield_datetimetest[day]     | 19                  |
      | customfield_datetimetest[month]   | 7                   |
      | customfield_datetimetest[year]    | 2027                |
      | Locked menu 1                     | Option 2            |
      | Locked menu 2                     |                     |
      | customfield_multiselecttest[1]    | 1                   |
      | Locked Textarea 1                 | Locked text area!   |
      | Locked Textarea 2                 |                     |
    And I press "Add evidence"
    Then I should see "Locked input test 1"
    When I follow "Locked input test 1"
    And I click on "Edit details" "button"

    Then the "Locked input test" "field" should be readonly
    And the "Locked checkbox test" "checkbox" should be disabled
    And I should see the "menutest1" custom field is locked and contains "Option 2"
    And I should see the "menutest2" custom field is locked and empty
    And "customfield_datetimetest[day]" "select" should not exist
    And the "id_customfield_multiselecttest_0" "checkbox" should be disabled
    And the "id_customfield_multiselecttest_1" "checkbox" should be disabled
    And the "id_customfield_multiselecttest_2" "checkbox" should be disabled
    And I should see the "textarea1" custom field is locked and contains "Locked text area!"
    And I should see the "textarea2" custom field is locked and empty
