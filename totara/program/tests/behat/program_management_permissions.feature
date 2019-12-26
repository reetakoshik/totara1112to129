@totara @totara_program @javascript
Feature: Specific permissions allow users to manage programs
  As a user with the permissions for managing a program
  I should be able to use the program management tabs
  With permissions in the program context

  Background:
    Given I am on a totara site
    And the following "programs" exist in "totara_program" plugin:
      | fullname    | shortname | idnumber |
      | Program One | prog1     | prog1    |
      | Program Two | prog2     | prog2    |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname          | shortname | idnumber |
      | Certification One | cert1     | cert1    |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
    And the following "users" exist:
      | username | firstname     | lastname | email                |
      | authuser | Authenticated | User     | authuser@example.com |
      | progman  | Program       | Manager  | progman@example.com  |
      | john     | John          | Smith    | john@example.com     |
      | mary     | Mary          | Jones    | mary@example.com     |
    And the following "roles" exist:
      | shortname   |
      | progmanager |
    And the following "role assigns" exist:
      | user    | role        | contextlevel  | reference |
      | progman | progmanager | Program       | prog1     |
    And I log in as "admin"
    # Enable completion editor by default so we know the Completion tab isn't showing up when capabilities aren't there.
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    # Add some users that will have exceptions so we know the Exceptions tab won't be there without the right capabilities.
    And I am on "Program One" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Authenticated User (authuser@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "John Smith (john@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Authenticated User" "table_row"
    And I set the following fields to these values:
      | eventtype  | Course completion |
    And I wait "1" seconds
    And I click on "Miscellaneous" "link" in the "Choose item" "totaradialogue"
    And I click on "Course One" "link" in the "Choose item" "totaradialogue"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Assignment 'Authenticated User' updated"
    And "Exception Report (1)" "link" should be visible
    And I log out

  Scenario: An authenticated user without any program management permissions can not edit program details
    Given I log in as "authuser"
    When I am on "Program One" program homepage
    Then "Edit program details" "button" should not exist
    When I am on "Program Two" program homepage
    Then "Edit program details" "button" should not exist

  Scenario: totara/program:configuredetails allows a user to edit program details
    Given the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:configuredetails  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    Then "Edit program details" "button" should be visible
    When I press "Edit program details"
    Then I should not see "Edit program content"
    And I should not see "Edit program assignments"
    And I should not see "Edit program messages"
    And "Exception Report (1)" "link" should not exist
    And "Completion" "link" should not exist
    When I press "Edit program details"
    And I set the following fields to these values:
      | Full name | Program One New Name |
    And I press "Save changes"
    Then I should see "Program details saved successfully"
    And I should see "Program One New Name"
    When I am on "Program Two" program homepage
    Then "Edit program details" "button" should not exist

  Scenario: totara/program:configurecontent allows a user to edit program content
    Given the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:configurecontent  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    And I press "Edit program details"
    Then "Edit program details" "button" should not exist
    And "Edit program assignments" "button" should not exist
    And "Edit program messages" "button" should not exist
    And "Exception Report (1)" "link" should not exist
    And "Completion" "link" should not exist
    When I press "Edit program content"
    And I press "Add"
    And I click on "Miscellaneous" "link" in the "Add course set" "totaradialogue"
    And I click on "Course One" "link" in the "Add course set" "totaradialogue"
    And I click on "Ok" "button" in the "Add course set" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Program content saved successfully"
    And I should see "Course One"

  Scenario: totara/program:configuremessages allows a user to edit program messages
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configuremessages     | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    And I press "Edit program details"
    Then "Edit program details" "button" should not exist
    And "Edit program assignments" "button" should not exist
    And "Edit program content" "button" should not exist
    And "Exception Report (1)" "link" should not exist
    And "Completion" "link" should not exist
    When I press "Edit program messages"
    And I set the following fields to these values:
      | Subject | New subject line for Program One |
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Program messages saved"
    And the following fields match these values:
      | Subject | New subject line for Program One |

  Scenario: totara/program:configureassignments allows a user to edit program assignments
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configureassignments  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    And I press "Edit program details"
    Then "Edit program details" "button" should not exist
    And "Edit program content" "button" should not exist
    And "Edit program messages" "button" should not exist
    And "Exception Report (1)" "link" should not exist
    And "Completion" "link" should not exist
    When I press "Edit program assignments"
    And I set the field "Add a new" to "Individuals"
    And I click on "Mary Jones" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    Then I should see "'Mary Jones' has been added to the program"
    And I should see "Mary Jones"

  Scenario: totara/program:configureassignments allows a user to set completion time based on course completion
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configureassignments  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    And I press "Edit program details"
    When I press "Edit program assignments"
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | eventtype | Course completion |
    And I click on "Miscellaneous" "link" in the "Choose item" "totaradialogue"
    And I click on "Course One" "link" in the "Choose item" "totaradialogue"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Assignment 'John Smith' updated"
    And I should see "Complete within 1 Day(s) of completion of course 'Course One'" in the "John Smith" "table_row"

  Scenario: totara/program:handleexceptions allows a user to manage exceptions
    Given the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:handleexceptions  | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    And I press "Edit program details"
    Then "Edit program details" "button" should not exist
    And "Edit program content" "button" should not exist
    And "Edit program messages" "button" should not exist
    And "Edit program assignments" "button" should not exist
    And "Completion" "link" should not exist
    When I switch to "Exception Report (1)" tab
    And I set the following fields to these values:
      | selectiontype   | All learners                      |
      | selectionaction | Set realistic due date and assign |
    And I press "Proceed with this action"
    And I click on "OK" "button" in the "Confirm issue resolution" "totaradialogue"
    And I should see "Successfully resolved exceptions"

  Scenario: totara/certification:configurecertification allows a user to manage certification-specific details
    Given the following "role assigns" exist:
      | user    | role        | contextlevel  | reference |
      | progman | progmanager | Program       | cert1     |
    And the following "permission overrides" exist:
      | capability                                  | permission | role          | contextlevel | reference |
      | totara/certification:configurecertification | Allow      | progmanager   | Program      | cert1     |
    And I log in as "progman"
    And I am on "Certification One" certification homepage
    And I press "Edit certification details"
    Then "Edit program details" "button" should not exist
    And "Edit program content" "button" should not exist
    And "Edit program messages" "button" should not exist
    And "Edit program assignments" "button" should not exist
    # We haven't set up an exception for the certification, but if the above buttons aren't showing, we've tested this enough.
    And "Completion" "link" should not exist
    When I press "Edit certification"
    And I press "Save changes"
    Then I should see "Certification details saved"
    # The buttons above say 'program' details etc. If they get changed to something for certs, we need to know
    # or this test isn't making the right checks, so make sure the buttons we looked for do exist, when all permissions are there.
    When I log out
    And I log in as "admin"
    And I am on "Certification One" certification homepage
    And I press "Edit certification details"
    Then "Edit program details" "button" should be visible
    And "Edit program content" "button" should be visible
    And "Edit program messages" "button" should be visible
    And "Edit program assignments" "button" should be visible
    And "Completion" "link" should be visible

  Scenario: totara/certification:configurecertification does not allow a user view any management pages for a program (ie not a cert)
    Given the following "permission overrides" exist:
      | capability                                  | permission | role          | contextlevel | reference |
      | totara/certification:configurecertification | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    Then "Edit program details" "button" should not exist
    # Enable another capability so that they can get to the overview page and we can check there is no certification link.
    When the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:configuredetails  | Allow      | progmanager   | Program      | prog1     |
    And I am on "Program One" program homepage
    And I press "Edit program details"
    Then "Edit program content" "button" should not exist
    And "Edit program messages" "button" should not exist
    And "Edit program assignments" "button" should not exist
    And "Exception Report (1)" "link" should not exist
    And "Completion" "link" should not exist
    And "Edit certification" "button" should not exist

  Scenario: totara/program:editcompletion allows a user to access the completion editor when the editor is enabled
    Given the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:editcompletion    | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    And I press "Edit program details"
    Then "Edit program details" "button" should not exist
    And "Edit program content" "button" should not exist
    And "Edit program messages" "button" should not exist
    And "Edit program assignments" "button" should not exist
    And "Exception Report (1)" "link" should not exist
    When I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "John Smith" "table_row"
    And I press "Save changes"
    Then I should see "Completion changes have been saved"

  Scenario: totara/program:editcompletion does not allow a user to access the completion editor when the editor is disabled
    Given the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:editcompletion    | Allow      | progmanager   | Program      | prog1     |
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 0 |
    And I log out
    And I log in as "progman"
    And I am on "Program One" program homepage
    Then "Edit program details" "button" should not exist
    # Enable another capability so that they can get to the overview page and we can check there is no completion link.
    When the following "permission overrides" exist:
      | capability                       | permission | role          | contextlevel | reference |
      | totara/program:configuredetails  | Allow      | progmanager   | Program      | prog1     |
    And I am on "Program One" program homepage
    And I press "Edit program details"
    Then "Edit program content" "button" should not exist
    And "Edit program messages" "button" should not exist
    And "Edit program assignments" "button" should not exist
    And "Exception Report (1)" "link" should not exist
    And "Completion" "link" should not exist
