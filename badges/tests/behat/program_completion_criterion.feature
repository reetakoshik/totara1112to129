@core @core_badges @_file_upload @javascript @totara_program
Feature: Verify badge issue based on program completion criterion.

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
    And the following "activities" exist:
      | activity | name   | intro                    | course | idnumber | completion |
      | label    | label1 | Click to complete course | C1     | label1   | 1          |
      | label    | label2 | Click to complete course | C2     | label2   | 1          |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | program1  |
      | Program 2 | program2  |
    And the following "program assignments" exist in "totara_program" plugin:
      | user     | program  |
      | learner1 | program1 |
      | learner1 | program2 |

    When I log in as "admin"
    # Set up the label to complete Course 1.
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Completion requirements" to "Course is complete when ANY of the conditions are met"
    And I set the field "Label - Click to complete course" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"

    # Set up the label to complete Course 2.
    When I am on "Course 2" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Completion requirements" to "Course is complete when ANY of the conditions are met"
    And I set the field "Label - Click to complete course" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"

    # Add the Course 1 to Program 1.
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Program 1" "table_row"
    And I switch to "Content" tab
    And I press "Add"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I click on "Ok" "button" in the "Add course set" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Caution: Program is live"

    # Add the Course 2 to Program 2.
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Program 2" "table_row"
    And I switch to "Content" tab
    And I press "Add"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I click on "Ok" "button" in the "Add course set" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "Caution: Program is live"

    # Add site level badge.
    When I navigate to "Manage badges" node in "Site administration > Badges"
    And I click on "Add a new badge" "button"
    And I set the following fields to these values:
      | Name        | Program Badge             |
      | Description | Program badge description |
      | issuername  | Mr Tester                 |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    Then I should see "Criteria for this badge have not been set up yet."

    # Add badge program criteria.
    When I set the field "Add badge criteria" to "Program completion"
    # Redirects user to select program.
    And I set the field "Qualifying program(s)" to "Program 1,Program 2"
    And I press "Save"
    Then I should see "Badge criteria successfully created"

    # Enable the badge.
    When I press "Enable access"
    Then I should see "Changes in badge access"
    And I should see "This will make your badge visible to users and allow them to start earning it."
    # Confirm enabling.
    When I press "Continue"
    Then I should see "This badge is currently available to users. Disable access to make any changes."
    And I log out

  Scenario: Verify badge is issued when program is completed.

    Given I log in as "learner1"
    When I am on "Course 1" course homepage
    And I click on "Not completed: Click to complete course. Select to mark as complete." "link"
    And I follow "Profile" in the user menu
    Then I should see "Program Badge"
    And I log out

  Scenario: Verify program badge can still be enabled and issued when multiple criteria is only partially available.

    Given I log in as "admin"
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Delete" "link" in the "Program 1" "table_row"
    And I press "Continue"
    Then I should see "Successfully deleted program \"Program 1\""

    # Disable and re-enable the badge so we can check that as it's
    # got valid criteria it can still be activated and issued.
    When I navigate to "Manage badges" node in "Site administration > Badges"
    Then I should see "Warning: A program is no longer available." in the "Program Badge" "table_row"

    When I click on "Disable access" "link" in the "Program Badge" "table_row"
    Then I should see "Access to the badges was successfully disabled."

    When I click on "Enable access" "link" in the "Program Badge" "table_row"
    Then I should see "Changes in badge access"

    When I press "Continue"
    Then I should see "Available to users" in the "Program Badge" "table_row"
    And I log out

    # Login as a learner and complete the course required
    # to complete the program and receive the badge.
    When I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Not completed: Click to complete course. Select to mark as complete." "link"
    And I follow "Profile" in the user menu
    # The bagde has been issued
    Then I should see "Program Badge"
    And I log out

  Scenario: Verify program badge can't be enabled and when no criteria is available.

    # Delete the programs so that badge has no available criteria.
    Given I log in as "admin"
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Delete" "link" in the "Program 1" "table_row"
    And I press "Continue"
    Then I should see "Successfully deleted program \"Program 1\""

    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Delete" "link" in the "Program 2" "table_row"
    And I press "Continue"
    Then I should see "Successfully deleted program \"Program 2\""

    # Disable and attempt to re-enable the badge now that it's criteria is missing.
    When I navigate to "Manage badges" node in "Site administration > Badges"
    Then I should see "Warning: 2 programs are no longer available." in the "Program Badge" "table_row"

    When I click on "Disable access" "link" in the "Program Badge" "table_row"
    Then I should see "Access to the badges was successfully disabled."

    When I click on "Enable access" "link" in the "Program Badge" "table_row"
    Then I should see "Changes in badge access"

    # The badge should not enable as it's got no criteria.
    When I press "Continue"
    Then I should see "Cannot activate the badge \"Program Badge\". Invalid criteria parameters. 2 programs do not exist."

    When I press "Continue"
    Then I should see "Not available to users" in the "Program Badge" "table_row"
