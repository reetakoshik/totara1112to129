@totara @totara_hierarchy @totara_hierarchy_competency @_file_upload @totara_customfield
Feature: Test use of images in competencies and competency custom fields
  I should be able to use and view images in competency descriptions
  and custom text area fields

  @javascript
  Scenario: Images in competency and custom competency fields and descriptions
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                        |
      | learner1 | Learner   | One      | learner1@example.com         |
    And the following "courses" exist:
      | fullname              | shortname | format |enablecompletion |
      | An Unexpected Journey | C1        | weeks  | 1               |
    And the following "course enrolments" exist:
      | user      | course | role    |
      | learner1  | C1     | student |
    And the following "competency" frameworks exist:
      | fullname            | idnumber    |
      | Test Comp Framework | tstcompfw   |
    And I log in as "admin"

    # Add images to the private files block to use later
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "totara/hierarchy/tests/behat/fixtures/learninglogo1.jpg" file to "Files" filemanager
    And I upload "totara/hierarchy/tests/behat/fixtures/learninglogo2.jpg" file to "Files" filemanager
    And I upload "totara/hierarchy/tests/behat/fixtures/learninglogo3.jpg" file to "Files" filemanager
    And I upload "totara/hierarchy/tests/behat/fixtures/learninglogo4.jpg" file to "Files" filemanager
    Then I should see "learninglogo1.jpg"
    And I should see "learninglogo2.jpg"
    And I should see "learninglogo3.jpg"
    And I should see "learninglogo4.jpg"

    # Create text area custom field for Competency type
    When I navigate to "Manage types" node in "Site administration > Competencies"
    And I press "Add a new type"
    And I set the following fields to these values:
    | Type full name | Competency type 1 |
    # Add the image to the description field.
    And I click on "//button[@class='atto_image_button']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo1.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo1 on competency type description"
    And I click on "Save image" "button"
    And I press "Save changes"
    Then "Competency type 1" "link" should exist
    When I follow "Competency type 1"
    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Custom text area 1  |
      | Short name (must be unique) | CTA1                |
    And I press "Save changes"
    Then I should see "Custom text area 1"

    # Add a file custom field.
    When I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | Custom file 1  |
      | Short name (must be unique) | CF1                |
    And I press "Save changes"
    Then I should see "Custom file 1"

    # Create competency using the competency type
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I follow "Test Comp Framework"
    And I press "Add new competency"
    And I set the following fields to these values:
      | Name | My competency 1    |
      | Type | Competency type 1  |

    # Image in competency description
    And I click on "//button[@class='atto_image_button']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo2.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo2 in competency description"
    And I click on "Save image" "button"
    And I press "Save changes"
    Then I should see the "logo2 in competency description" image in the "//dd[preceding-sibling::dt[1][. = 'Description']]" "xpath_element"
    And I should see image with alt text "logo2 in competency description"

    # Image in the custom field
    When I click on "Edit" "link"
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_CTA1_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo3.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo3 on customfield text area"
    And I click on "Save image" "button"

    # File in the file custom field.
    And I click on "//div[@id='fitem_id_customfield_CF1_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "learninglogo4.jpg" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Verify the outcome
    And I press "Save changes"
    Then I should see the "logo2 in competency description" image in the "//dd[preceding-sibling::dt[1][. = 'Description']]" "xpath_element"
    And I should see the "logo3 on customfield text area" image in the "//dd[preceding-sibling::dt[1][. = 'Custom text area 1']]" "xpath_element"
    And I should see "learninglogo4.jpg"
    And I should see image with alt text "logo2 in competency description"
    And I should see image with alt text "logo3 on customfield text area"

    When I press "Return to competency framework"
    Then I should see the "logo2 in competency description" image in the "My competency 1" "table_row"
    And I should see the "logo3 on customfield text area" image in the "Custom text area 1" "table_row"
    And I should see image with alt text "logo2 in competency description"
    And I should see image with alt text "logo3 on customfield text area"

    # Also check reports
    # For this we need a completed competency
    # Add a choice activity and complete the activity as a learner
    When I am on site homepage
    And I am on "An Unexpected Journey" course homepage
    And I add a "Choice" to section "1" and I fill the form with:
      | Choice name         | Help to Gandalf the Grey                          |
      | Description         | The wizard, member of the Istari order            |
      | option[0]           | Join the Dwarves                                  |
      | option[1]           | Stay home                                         |
      | id_completion       | Show activity as complete when conditions are met |
      | id_completionsubmit | 1                                                 |
    And I turn editing mode off
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Choice - Help to Gandalf the Grey" "checkbox"
    And I press "Save changes"
    And I am on site homepage
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Test Comp Framework" "link"
    And I click on "My competency 1" "link"
    And I press "Assign course completions"
    And I click on "Miscellaneous" "link"
    And I click on "An Unexpected Journey" "link"
    And I click on "Save" "button" in the ".totara-dialog[aria-describedby=evidence]" "css_element"
    And I wait "2" seconds
    And I press "Return to competency framework"
    And I log out
    And I log in as "learner1"
    And I click on "An Unexpected Journey" "link"
    And I choose "Join the Dwarves" from "Help to Gandalf the Grey" choice activity
    And I should see "Your selection: Join the Dwarves"
    And I click on "Dashboard" in the totara menu
    And I click on "Record of Learning" in the totara menu
    Then I should see "Complete"
    And I log out
    And I log in as "admin"
    And I run the "\totara_hierarchy\task\update_competencies_task" task

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Name   | Test Competency Status |
      | Source | Competency Status      |
    And I press "Create report"
    Then I should see "Edit Report 'Test Competency Status'"

    When I switch to "Columns" tab
    Then "//select[@id='id_newcolumns']/optgroup[@label='Competency custom fields']/option[.='Custom text area 1']" "xpath_element" should exist
    And the "newcolumns" select box should contain "Custom text area 1"

    When I select "Custom text area 1" from the "newcolumns" singleselect
    And I press "Add"
    And I press "Save changes"
    And I follow "View This Report"
    Then I should see the "logo3 on customfield text area" image in the "My competency 1" "table_row"

    When I follow "My competency 1"
    Then I should see the "logo2 in competency description" image in the "//dd[preceding-sibling::dt[1][. = 'Description']]" "xpath_element"
    And I should see the "logo3 on customfield text area" image in the "//dd[preceding-sibling::dt[1][. = 'Custom text area 1']]" "xpath_element"
    And I should see image with alt text "logo2 in competency description"
    And I should see image with alt text "logo3 on customfield text area"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Name   | Test Competency Status History |
      | Source | Competency Status History      |
    And I press "Create report"
    Then I should see "Edit Report 'Test Competency Status History'"

    When I switch to "Columns" tab
    Then "//select[@id='id_newcolumns']/optgroup[@label='Competency custom fields']/option[.='Custom text area 1']" "xpath_element" should exist
    And the "newcolumns" select box should contain "Custom text area 1"

    When I select "Custom text area 1" from the "newcolumns" singleselect
    And I press "Add"
    And I press "Save changes"
    And I follow "View This Report"
    Then I should see the "logo3 on customfield text area" image in the "My competency 1" "table_row"
    And I should see image with alt text "logo3 on customfield text area"
