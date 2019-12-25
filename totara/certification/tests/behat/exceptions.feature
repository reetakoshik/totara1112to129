@totara @totara_certification
Feature: Generation of certification assignment exceptions
  In order to view a certification
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | user003  | fn_003    | ln_003   | user003@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
      | Course 2 | C2        | topics | 1                |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname                       | shortname |
      | Certification Filler           | filtest   |
      | Certification Exception Tests  | exctest   |
    And I log in as "admin"
    And I set the following administration settings values:
      | menulifetime   | 0       |
      | enableprograms | Disable |
    # Get back the removed dashboard item for now.
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"

  @javascript
  Scenario: Assigned to course via multiple certifications exceptions are generated and dismissed
    Given I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification Filler" "link"
    And I click on "Edit certification details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "2" seconds
    And I click on "addcontent_rc" "button" in the "#programcontent_rc" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "2" seconds
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "2" seconds
    Then I should see "1 learner(s) assigned: 1 active, 0 exception(s)"

    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification Exception Tests" "link"
    And I click on "Edit certification details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "2" seconds
    And I click on "addcontent_rc" "button" in the "#programcontent_rc" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "2" seconds
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_002 ln_002 (user002@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "2" seconds
    Then I should see "2 learner(s) assigned: 1 active, 1 exception(s)"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Certification Filler" in the "#program-content" "css_element"
    And I should not see "Certification Exception Tests" in the "#program-content" "css_element"

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Certification Exception Tests" in the "#program-content" "css_element"

    When I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I click on "Miscellaneous" "link"
    And I click on "Certification Exception Tests" "link"
    And I click on "Edit certification details" "button"
    And I click on "Exception Report (1)" "link"
    Then I should see "fn_001 ln_001"
    And I should see "Duplicate course in different certifications" in the "fn_001 ln_001" "table_row"

    When I set the field "selectiontype" to "Duplicate course in different certifications"
    And I set the field "selectionaction" to "Do not assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "No exceptions"
    And I should see "2 learner(s) assigned: 1 active, 0 exception(s)"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_003 ln_003 (user003@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "2" seconds
    Then I should see "3 learner(s) assigned: 2 active, 0 exception(s)"
