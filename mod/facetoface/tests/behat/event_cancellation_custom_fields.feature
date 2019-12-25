@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Seminar event cancellation custom fields
  After seminar events have been created
  As an admin
  I need to be able to cancel them with additional details.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |
      | learner2 | C1     | student        |
      | learner3 | C1     | student        |

    Given I log in as "admin"
    Given I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"

    Given I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Event cancellation" "link"
    And I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | fullname  | cancelcheckbox |
      | shortname | cancelcheckbox |
    And I press "Save changes"

    Given I set the field "datatype" to "Date/time"
    And I set the following fields to these values:
      | fullname  | canceldatetime |
      | shortname | canceldatetime |
    And I press "Save changes"

    Given I set the field "datatype" to "File"
    And I set the following fields to these values:
      | fullname  | cancelfile |
      | shortname | cancelfile |
    And I press "Save changes"

    Given I set the field "datatype" to "Location"
    And I set the following fields to these values:
      | fullname  | cancellocation |
      | shortname | cancellocation |
    And I press "Save changes"

    Given I set the field "datatype" to "Menu of choices"
    And I set the following fields to these values:
      | fullname    | cancelmenu |
      | shortname   | cancelmenu |
      | defaultdata | Ja         |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Ja
      Nein
      """
    And I press "Save changes"

    Given I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | fullname                   | cancelmulti |
      | shortname                  | cancelmulti |
      | multiselectitem[0][option] | Aye   |
      | multiselectitem[1][option] | Nay   |
    And I press "Save changes"

    Given I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | fullname           | canceltextarea |
      | shortname          | canceltextarea |
    And I press "Save changes"

    Given I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname           | canceltextinput |
      | shortname          | canceltextinput |
    And I press "Save changes"

    Given I set the field "datatype" to "URL"
    And I set the following fields to these values:
      | fullname           | cancelURL |
      | shortname          | cancelURL |
    And I press "Save changes"

    Given I navigate to "Notification templates" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Seminar event cancellation" "table_row"

    # Note: cannot use "I set the field 'Body' to multiline"; this field has an
    # HTML editor and for some reason, Selenium will fail at this step with an
    # "illegal token" error.
    And I set the following fields to these values:
      | Body                  | CANCELLED<br/>[sessioncancel:cancelcheckbox]::[sessioncancel:canceldatetime]::[sessioncancel:cancellocation]::[sessioncancel:cancelmenu]::[sessioncancel:cancelmulti]::[sessioncancel:canceltextinput]::[sessioncancel:cancelURL] |
      | Update all activities | 1                                                         |
    And I press "Save changes"

    Given I log out
    And I log in as "teacher1"

    # Add images to the private files block to use later
    When I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "mod/facetoface/tests/fixtures/test.jpg" file to "Files" filemanager
    And I upload "mod/facetoface/tests/fixtures/leaves-green.png" file to "Files" filemanager
    Then I should see "test.jpg"
    And I should see "leaves-green.png"

    And I am on "Course 1" course homepage
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar |
      | Description | Test Seminar |
    And I follow "View all events"

    Given I follow "Add a new event"
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[day]      | 10               |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[day]     | 10               |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"

    Given I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Pacific/Auckland |
      | timestart[hour]     | 10               |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Pacific/Auckland |
      | timefinish[hour]    | 16               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Pacific/Auckland |
    And I press "OK"
    And I click on "Teacher One" "checkbox"
    And I press "Save changes"

    Given I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "10:00 AM - 4:00 PM Pacific/Auckland" "table_row"
    And I set the following fields to these values:
      | customfield_cancelcheckbox          | 1                  |
      | customfield_canceldatetime[enabled] | 1                  |
      | customfield_canceldatetime[day]     | 1                  |
      | customfield_canceldatetime[month]   | December           |
      | customfield_canceldatetime[year]    | 2030               |
      | customfield_cancellocationaddress   | Kensington         |
      | customfield_cancelmenu              | Nein               |
      | customfield_cancelmulti[0]          | 1                  |
      | customfield_cancelmulti[1]          | 1                  |
      | customfield_canceltextinput         | hi                 |
      | customfield_cancelURL[url]          | http://example.org |

    # Add a file to the file custom field.
    And I click on "//div[@id='fitem_id_customfield_cancelfile_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "test.jpg" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Image in the textarea custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_canceltextarea_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I set the field "Describe this image for someone who cannot see it" to "Green leaves on customfield text area"
    And I click on "Save image" "button"

    And I press "Yes"

  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_500: filling up custom fields when cancelling events
    When I click on "Attendees" "link"
    Then I should see "Yes" in the "//dt[contains(., 'cancelcheckbox')]//following-sibling::dd" "xpath_element"
    And I should see "1 December 2030" in the "//dt[contains(., 'canceldatetime')]//following-sibling::dd" "xpath_element"
    And I should see "test.jpg" in the "//dt[contains(., 'cancelfile')]//following-sibling::dd" "xpath_element"
    And I should see "Kensington" in the "//dt[contains(., 'cancellocation')]//following-sibling::dd" "xpath_element"
    And I should see "Nein" in the "//dt[contains(., 'cancelmenu')]//following-sibling::dd" "xpath_element"
    And I should see "Aye, Nay" in the "//dt[contains(., 'cancelmulti')]//following-sibling::dd" "xpath_element"
    And I should see "hi" in the "//dt[contains(., 'canceltextinput')]//following-sibling::dd" "xpath_element"
    And I should see "http://example.org" in the "//dt[contains(., 'cancelURL')]//following-sibling::dd" "xpath_element"
    And I should see the "Green leaves on customfield text area" image in the "//dd[preceding-sibling::dt[1][. = 'canceltextarea']]" "xpath_element"
    And I should see image with alt text "Green leaves on customfield text area"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_501: create seminar events custom report with custom cancellation fields
    Given I log out
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | fullname | Custom test event report |
      | source   | Seminar Sessions         |
    And I press "Create report"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "cancelcheckbox"
    And I press "Add"
    And I set the field "newcolumns" to "canceldatetime"
    And I press "Add"
    And I set the field "newcolumns" to "cancelfile"
    And I press "Add"
    And I set the field "newcolumns" to "cancellocation"
    And I press "Add"
    And I set the field "newcolumns" to "cancelmenu"
    And I press "Add"
    And I set the field "newcolumns" to "cancelmulti (icons)"
    And I press "Add"
    And I set the field "newcolumns" to "cancelmulti (text)"
    And I press "Add"
    And I set the field "newcolumns" to "canceltextarea"
    And I press "Add"
    And I set the field "newcolumns" to "canceltextinput"
    And I press "Add"
    And I set the field "newcolumns" to "cancelURL"
    And I press "Add"
    And I press "Save changes"

    When I follow "View This Report"
    Then I should see "Test Seminar" in the "Course 1" "table_row"
    And I should see "1 Dec 2030" in the "Test Seminar" "table_row"
    And I should see "test.jpg" in the "Test Seminar" "table_row"
    And I should see "Kensington" in the "Test Seminar" "table_row"
    And I should see "Nein" in the "Test Seminar" "table_row"
    And I should see "Aye, Nay" in the "Test Seminar" "table_row"
    And I should see "hi" in the "Test Seminar" "table_row"
    And I should see "http://example.org" in the "Test Seminar" "table_row"
    And I should see the "Green leaves on customfield text area" image in the "Test Seminar" "table_row"
    And I should see image with alt text "Green leaves on customfield text area"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_cancel_502: use cancellation custom fields in notification template
    And I run all adhoc tasks
    When I click on "Dashboard" in the totara menu
    Then I should see "Seminar event cancellation"

    When I click on "View all alerts" "link"
    And I follow "Show more..."
    And I set the field "Message Content value" to "CANCELLED"
    And I click on "input[value=Search]" "css_element"
    Then I should see "Yes::1 December 2030::Kensington::Nein::Aye, Nay::hi::http://example.org"

