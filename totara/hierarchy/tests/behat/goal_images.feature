@totara @totara_hierarchy @totara_hierarchy_goal @_file_upload @totara_customfield
Feature: Test use of images in goals and goal custom fields
  I should be able to use and view images in goal descriptions
  and custom text area fields

  @javascript
  Scenario: Images in goal and custom goal fields and descriptions
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
    And the following "goal" frameworks exist:
      | fullname       | idnumber |
      | Goal Framework | goalfw   |
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
    And I upload "totara/hierarchy/tests/behat/fixtures/learninglogo5.jpg" file to "Files" filemanager
    And I upload "totara/hierarchy/tests/behat/fixtures/learninglogo6.jpg" file to "Files" filemanager
    And I upload "totara/hierarchy/tests/behat/fixtures/leaves-green.png" file to "Files" filemanager
    Then I should see "learninglogo1.jpg"
    And I should see "learninglogo2.jpg"
    And I should see "learninglogo3.jpg"
    And I should see "learninglogo4.jpg"
    And I should see "learninglogo5.jpg"
    And I should see "learninglogo6.jpg"
    And I should see "leaves-green.png"

    # Create text area custom field for Company goal type
    When I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I press "Add a new company goal type"
    And I set the following fields to these values:
    | Type full name | Comp goal type 1 |
    # Add the image to the description field.
    And I click on "//button[@class='atto_image_button']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo1.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo1 on company goal type description"
    And I click on "Save image" "button"
    And I press "Save changes"
    Then "Comp goal type 1" "link" should exist
    When I follow "Comp goal type 1"
    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Custom comp text area 1  |
      | Short name (must be unique) | CCTA1                    |
    And I press "Save changes"
    Then I should see "Custom comp text area 1"

    # Add a file custom field for company goal types.
    When I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | Custom file 1  |
      | Short name (must be unique) | CF1                |
    And I press "Save changes"
    Then I should see "Custom file 1"

    # Create a Personal goal type
    When I navigate to "Manage personal goal types" node in "Site administration > Goals"
    And I press "Add a new personal goal type"
    And I set the following fields to these values:
    | Type full name | Pers goal type 1 |
    # Add the image to the description field.
    And I click on "//button[@class='atto_image_button']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo2.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo2 on personal goal type description"
    And I click on "Save image" "button"
    And I press "Save changes"
    Then "Pers goal type 1" "link" should exist

    # Create text area custom field for the Personal goal type
    When I follow "Pers goal type 1"
    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Custom pers text area 1  |
      | Short name (must be unique) | CPTA1                    |
    And I press "Save changes"
    Then I should see "Custom pers text area 1"

    # Add a file custom field for the Personal goal types.
    When I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | Custom file 1  |
      | Short name (must be unique) | CF1                |
    And I press "Save changes"
    Then I should see "Custom file 1"

    # Create goal using the company goal type
    When I navigate to "Manage goals" node in "Site administration > Goals"
    And I follow "Goal Framework"
    And I press "Add new goal"
    And I set the following fields to these values:
      | Name | My goal 1         |
      | Type | Comp goal type 1  |

    # Image in goal description
    And I click on "//button[@class='atto_image_button']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo3.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo3 in goal description"
    And I click on "Save image" "button"
    And I press "Save changes"
    Then I should see the "logo3 in goal description" image in the "//dd[preceding-sibling::dt[1][. = 'Description']]" "xpath_element"
    And I should see image with alt text "logo3 in goal description"

    # Image in the custom field
    When I click on "Edit" "link"
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_CCTA1_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo4.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo4 on custom My goal 1 customfield text area"
    And I click on "Save image" "button"

    # File in the file custom field.
    And I click on "//div[@id='fitem_id_customfield_CF1_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Verfiy the outcome.
    And I press "Save changes"
    Then I should see the "logo3 in goal description" image in the "//dd[preceding-sibling::dt[1][. = 'Description']]" "xpath_element"
    And I should see the "logo4 on custom My goal 1 customfield text area" image in the "//dd[preceding-sibling::dt[1][. = 'Custom comp text area 1']]" "xpath_element"
    And I should see "leaves-green.png"
    And I press "Return to goal framework"
    And I log out

    # Create goals for the learner and add images in the text areas
    When I log in as "learner1"
    # Add images to the private files block to use later
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "totara/hierarchy/tests/behat/fixtures/learninglogo5.jpg" file to "Files" filemanager
    And I upload "totara/hierarchy/tests/behat/fixtures/learninglogo6.jpg" file to "Files" filemanager
    And I upload "totara/hierarchy/tests/behat/fixtures/leaves-blue.png" file to "Files" filemanager
    And I click on "Save changes" "button"
    Then I should see "learninglogo5.jpg"
    And I should see "learninglogo6.jpg"
    And I should see "leaves-blue.png"

    # Company goal for learner with images
    When I click on "Goals" in the totara menu
    And I press "Add company goal"
    And I follow "My goal 1"
    And I press "Save"
    Then I should see "My goal 1"

    When I follow "My goal 1"
    Then I should see the "logo3 in goal description" image in the "//dd[preceding-sibling::dt[1][. = 'Description']]" "xpath_element"
    And I should see the "logo4 on custom My goal 1 customfield text area" image in the "//dd[preceding-sibling::dt[1][. = 'Custom comp text area 1']]" "xpath_element"
    And I should see image with alt text "logo3 in goal description"
    And I should see image with alt text "logo4 on custom My goal 1 customfield text area"

    When I click on "Goals" in the totara menu
    And I follow "View Goal Frameworks"
    And I follow "Goal Framework"
    Then I should see the "logo3 in goal description" image in the "Description" "table_row"
    And I should see the "logo4 on custom My goal 1 customfield text area" image in the "Custom comp text area 1" "table_row"
    And I should see image with alt text "logo3 in goal description"
    And I should see image with alt text "logo4 on custom My goal 1 customfield text area"

    # Personal goal with images in the description and customfield text area
    When I click on "Goals" in the totara menu
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name | My personal goal 1  |
      | Type | Pers goal type 1    |

    # Image in personal goal description
    And I click on "//button[@class='atto_image_button']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo5.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo5 in pers goal description"
    And I click on "Save image" "button"
    And I click on "Save changes" "button"
    Then I should see "My personal goal 1"

    When I follow "My personal goal 1"
    Then I should see the "logo5 in pers goal description" image in the "//dd[preceding-sibling::dt[1][. = 'Description']]" "xpath_element"

    # Image in the custom field
    When I click on "Edit" "link"
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_CPTA1_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "learninglogo6.jpg" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "logo6 on personal goal customfield text area"
    And I click on "Save image" "button"

    # File in the file custom field.
    And I click on "//div[@id='fitem_id_customfield_CF1_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "leaves-blue.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    And I press "Save changes"
    Then I should see "My personal goal 1"

    When I follow "My personal goal 1"
    Then I should see the "logo5 in pers goal description" image in the "//dd[preceding-sibling::dt[1][. = 'Description']]" "xpath_element"
    And I should see the "logo6 on personal goal customfield text area" image in the "//dd[preceding-sibling::dt[1][. = 'Custom pers text area 1']]" "xpath_element"
    And I should see "leaves-blue.png"
    And I should see image with alt text "logo5 in pers goal description"
    And I should see image with alt text "logo6 on personal goal customfield text area"

    # Also check reports
    When I log out
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Name   | Test Goal Custom Fields |
      | Source | Goal Custom Fields      |
    And I press "Create report"
    Then I should see "Edit Report 'Test Goal Custom Fields'"

    When I switch to "Columns" tab
    Then "//select[@id='id_newcolumns']/optgroup[@label='Company Goal Type']/option[.='Custom comp text area 1']" "xpath_element" should exist
    And "//select[@id='id_newcolumns']/optgroup[@label='Personal Goal Type']/option[.='Custom pers text area 1']" "xpath_element" should exist
    And the "newcolumns" select box should contain "Custom comp text area 1"
    And the "newcolumns" select box should contain "Custom pers text area 1"

    When I select "All personal goal custom fields" from the "newcolumns" singleselect
    And I press "Add"
    And I select "All company goal custom fields" from the "newcolumns" singleselect
    And I press "Add"
    And I press "Save changes"
    And I follow "View This Report"
    Then I should see the "logo4 on custom My goal 1 customfield text area" image in the "My goal 1" "table_row"
    And I should see the "logo6 on personal goal customfield text area" image in the "My personal goal 1" "table_row"
    And I should see image with alt text "logo4 on custom My goal 1 customfield text area"
    And I should see image with alt text "logo6 on personal goal customfield text area"

    When I am on site homepage
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Name   | Test Goal Summary |
      | Source | Goal Summary      |
    And I press "Create report"
    Then I should see "Edit Report 'Test Goal Summary'"

    When I switch to "Columns" tab
    Then "//select[@id='id_newcolumns']/optgroup[@label='Company Goal Type']/option[.='Custom comp text area 1']" "xpath_element" should exist
    And "//select[@id='id_newcolumns']/optgroup[@label='Personal Goal Type']/option[.='Custom pers text area 1']" "xpath_element" should not exist
    And the "newcolumns" select box should contain "Custom comp text area 1"
    And the "newcolumns" select box should not contain "Custom pers text area 1"

    When I select "Custom comp text area 1" from the "newcolumns" singleselect
    And I press "Add"
    And I press "Save changes"
    And I follow "View This Report"
    And I follow "Goal Framework"
    Then I should see the "logo4 on custom My goal 1 customfield text area" image in the "My goal 1" "table_row"
    And I should see image with alt text "logo4 on custom My goal 1 customfield text area"

    When I am on site homepage
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Name   | Test Goal Status |
      | Source | Goal Status      |
    And I press "Create report"
    Then I should see "Edit Report 'Test Goal Status'"

    When I switch to "Columns" tab
    Then "//select[@id='id_newcolumns']/optgroup[@label='Company Goal Type']/option[.='Custom comp text area 1']" "xpath_element" should exist
    And "//select[@id='id_newcolumns']/optgroup[@label='Personal Goal Type']/option[.='Custom pers text area 1']" "xpath_element" should not exist
    And the "newcolumns" select box should contain "Custom comp text area 1"
    And the "newcolumns" select box should not contain "Custom pers text area 1"

    When I select "Custom comp text area 1" from the "newcolumns" singleselect
    And I press "Add"
    And I press "Save changes"
    And I follow "View This Report"
    Then I should see the "logo4 on custom My goal 1 customfield text area" image in the "Learner One" "table_row"
    And I should see image with alt text "logo4 on custom My goal 1 customfield text area"

    When I am on site homepage
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Name   | Test Goal Status History |
      | Source | Goal Status History      |
    And I press "Create report"
    Then I should see "Edit Report 'Test Goal Status History'"

    When I switch to "Columns" tab
    Then "//select[@id='id_newcolumns']/optgroup[@label='Company Goal Type']/option[.='Custom comp text area 1']" "xpath_element" should not exist
    And "//select[@id='id_newcolumns']/optgroup[@label='Personal Goal Type']/option[.='Custom pers text area 1']" "xpath_element" should not exist
    And the "newcolumns" select box should not contain "Custom comp text area 1"
    And the "newcolumns" select box should not contain "Custom pers text area 1"
