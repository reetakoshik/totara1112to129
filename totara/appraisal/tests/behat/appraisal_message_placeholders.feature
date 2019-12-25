@javascript @totara @totara_appraisal
Feature: Test appraisal messages

  Background:
    # Set up the data we need for appraisals.
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname  | lastname  | email                |
      | learner1   | learner1   | lastname  | learner1@local.com   |
      | learner2   | learner2   | lastname  | learner2@local.com   |
      | learner3   | learner3   | lastname  | learner3@local.com   |
      | manager    | manager    | lastname  | manager@local.com    |
      | teamlead   | teamlead   | lastname  | teamlead@local.com   |
      | appraiser  | appraiser  | lastname  | appraiser@local.com  |
    And the following job assignments exist:
      | user       | fullname       | idnumber | manager   | appraiser  |
      | appraiser  | Appraiser Job  | ja       |           |            |
      | teamlead   | Team Lead Job  | ja       |           |            |
      | manager    | Manager Job    | ja       | teamlead  |            |
      | learner1   | Learner1 Job   | ja       | manager   | appraiser  |
      | learner2   | Learner2 Job   | ja       |           |            |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | AppAud |
      | learner2 | AppAud |
      | learner3 | AppAud |

    # Set up an appraisal using the data generator.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal1  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name        | timedue                 |
      | Appraisal1  | App1_Stage1 | 1 January 2025 23:59:59 |
      | Appraisal1  | App1_Stage2 | 2 January 2025 23:59:59 |
      | Appraisal1  | App1_Stage3 | 3 January 2025 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage       | name              |
      | Appraisal1  | App1_Stage1 | App1_Stage1_Page1 |
      | Appraisal1  | App1_Stage2 | App1_Stage2_Page1 |
      | Appraisal1  | App1_Stage3 | App1_Stage3_Page1 |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal   | stage       | page              | name                  | type          | default | roles                               | ExtraInfo                          |
      | Appraisal1  | App1_Stage1 | App1_Stage1_Page1 | App1-Stage1-Page1-Q1  | ratingnumeric | 2       | learner,manager,teamlead,appraiser  | Range:1-10,Display:slider          |
      | Appraisal1  | App1_Stage2 | App1_Stage2_Page1 | App1-Stage2-Page1-Q1  | ratingnumeric | 2       | learner,manager,teamlead,appraiser  | Range:1-10,Display:slider          |
      | Appraisal1  | App1_Stage3 | App1_Stage3_Page1 | App1-Stage3-Page1-Q1  | ratingnumeric | 2       | learner,manager,teamlead,appraiser  | Range:1-10,Display:slider          |
    And the following "messages" exist in "totara_appraisal" plugin:
      | appraisal  | recipients  | name                             | stage       | event                      |
      | Appraisal1 | all         | Message 1 - Appraisal Activation |             |                            |
      | Appraisal1 | all         | Message 2 - Stage 1 Completion   | App1_Stage1 | appraisal stage completion |
      | Appraisal1 | all         | Message 3 - Stage 2 Completion   | App1_Stage2 | appraisal stage completion |
      | Appraisal1 | all         | Message 4 - Stage 3 Completion   | App1_Stage3 | appraisal stage completion |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id     |
      | Appraisal1  | audience | AppAud |

  Scenario: Test appraisal placeholders within messages
    Given I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "Appraisal1"
    And I should see "3 (0 completed)" in the "Appraisal1" "table_row"
    And I should see " Activate" in the "Appraisal1" "table_row"

    # Add all available placeholders to the following messages.
    When I follow "Appraisal1"
    And I switch to "Messages" tab
    And I should see "Message 1 - Appraisal Activation"
    And I should see "Message 2 - Stage 1 Completion"
    And I should see "Message 3 - Stage 2 Completion"
    And I should see "Message 4 - Stage 3 Completion"

    # Appraisal activation message.
    And I follow "Message 1 - Appraisal Activation"
    And I set the field "Message title" to "Message 1 - Appraisal Activation - [appraisalname]"
    And I add all appraisal message placeholders in the "Message body" field
    And I press "Save changes"
    Then I should see "Message 1 - Appraisal Activation - [appraisalname]"

    # Stage 1 completion message.
    And I follow "Message 2 - Stage 1 Completion"
    And I set the field "Message title" to "Message 2 - [appraisalname] - [currentstagename] completion"
    And I add all appraisal message placeholders in the "Message body" field
    And I press "Save changes"
    Then I should see "Message 2 - [appraisalname] - [currentstagename] completion"

    # Stage 2 completion message.
    And I follow "Message 3 - Stage 2 Completion"
    And I set the field "Message title" to "Message 3 - [appraisalname] - [currentstagename] completion"
    And I add all appraisal message placeholders in the "Message body" field
    And I press "Save changes"
    Then I should see "Message 3 - [appraisalname] - [currentstagename] completion"

    # Stage 3 completion message.
    And I follow "Message 4 - Stage 3 Completion"
    And I set the field "Message title" to "Message 4 - [appraisalname] - [currentstagename] completion"
    And I add all appraisal message placeholders in the "Message body" field
    And I press "Save changes"
    Then I should see "Message 4 - [appraisalname] - [currentstagename] completion"

    # Activate the appraisal and test all the messages.
    When I click on "Activate now" "link"
    And I press "Activate"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"
    And I log out

    And I run the scheduled task "\totara_appraisal\task\scheduled_messages"
    # Test Message 1, appraisal activation for the learner.
    And the message "Message 1 - Appraisal Activation - Appraisal1" exists for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" does not contain "[" for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" does not contain "]" for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "appraisalname: Appraisal1" for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "appraisaldescription: Test Appraisal 1 description" for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" for "learner1" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """

    # TODO: See TL-12620
    # There are issues where JA data is not set against the appraisal at a stage that would allow the data below to be available.
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "currentstagename: " for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "expectedstagecompletiondate: " for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "previousstagename: No previous stage" for "learner1" user

    # Test Message 2, stage 1 completion.
    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "appraiser"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "teamlead"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    # Check the message 2, stage 1 completion for the learner1.
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" exists for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" does not contain "[" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" does not contain "]" for "learner1" user

    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraisalname: Appraisal1" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraisaldescription: Test Appraisal 1 description" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" for "learner1" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "currentstagename: App1_Stage1" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "expectedstagecompletiondate: 1 Jan 2025" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "previousstagename: No previous stage" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userusername: learner1" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userlastname: lastname" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userfullname: learner1 lastname" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerusername: manager" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerfirstname: manager" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerfullname: manager lastname" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerusername: teamlead" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerfirstname: teamlead" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerlastname: lastname" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerfullname: teamlead lastname" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserusername: appraiser" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserfirstname: appraiser" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserlastname: lastname" for "learner1" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserfullname: appraiser lastname" for "learner1" user

    # Check the message 2, stage 1 completion for the appraiser.
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" exists for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" does not contain "[" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" does not contain "]" for "appraiser" user

    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraisalname: Appraisal1" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraisaldescription: Test Appraisal 1 description" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" for "appraiser" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "currentstagename: App1_Stage1" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "expectedstagecompletiondate: 1 Jan 2025" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "previousstagename: No previous stage" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userusername: learner1" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userlastname: lastname" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userfullname: learner1 lastname" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerusername: manager" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerfirstname: manager" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerfullname: manager lastname" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerusername: teamlead" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerfirstname: teamlead" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerlastname: lastname" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerfullname: teamlead lastname" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserusername: appraiser" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserfirstname: appraiser" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserlastname: lastname" for "appraiser" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserfullname: appraiser lastname" for "appraiser" user

    # Check the message 2, stage 1 completion for the manager.
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" exists for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" does not contain "[" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" does not contain "]" for "manager" user

    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraisalname: Appraisal1" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraisaldescription: Test Appraisal 1 description" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" for "manager" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "currentstagename: App1_Stage1" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "expectedstagecompletiondate: 1 Jan 2025" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "previousstagename: No previous stage" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userusername: learner1" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userlastname: lastname" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userfullname: learner1 lastname" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerusername: manager" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerfirstname: manager" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerfullname: manager lastname" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerusername: teamlead" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerfirstname: teamlead" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerlastname: lastname" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerfullname: teamlead lastname" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserusername: appraiser" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserfirstname: appraiser" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserlastname: lastname" for "manager" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserfullname: appraiser lastname" for "manager" user

    # Check the message 2, stage 1 completion for the teamlead.
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" exists for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" does not contain "[" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" does not contain "]" for "teamlead" user

    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraisalname: Appraisal1" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraisaldescription: Test Appraisal 1 description" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" for "teamlead" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "currentstagename: App1_Stage1" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "expectedstagecompletiondate: 1 Jan 2025" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "previousstagename: No previous stage" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userusername: learner1" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userlastname: lastname" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "userfullname: learner1 lastname" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerusername: manager" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerfirstname: manager" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managerfullname: manager lastname" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerusername: teamlead" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerfirstname: teamlead" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerlastname: lastname" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "managersmanagerfullname: teamlead lastname" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserusername: appraiser" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserfirstname: appraiser" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserlastname: lastname" for "teamlead" user
    And the message "Message 2 - Appraisal1 - App1_Stage1 completion" contains "appraiserfullname: appraiser lastname" for "teamlead" user

    # Test Message 3, stage 2 completion.
    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "appraiser"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "teamlead"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    # Check the message 3, stage 2 completion for the learner1.
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" exists for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" does not contain "[" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" does not contain "]" for "learner1" user

    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraisalname: Appraisal1" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraisaldescription: Test Appraisal 1 description" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" for "learner1" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "currentstagename: App1_Stage2" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "expectedstagecompletiondate: 2 Jan 2025" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "previousstagename: App1_Stage1" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userusername: learner1" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userlastname: lastname" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userfullname: learner1 lastname" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerusername: manager" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerfirstname: manager" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerfullname: manager lastname" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerusername: teamlead" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerfirstname: teamlead" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerlastname: lastname" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerfullname: teamlead lastname" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserusername: appraiser" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserfirstname: appraiser" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserlastname: lastname" for "learner1" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserfullname: appraiser lastname" for "learner1" user

    # Check the message 3, stage 2 completion for the appraiser.
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" exists for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" does not contain "[" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" does not contain "]" for "appraiser" user

    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraisalname: Appraisal1" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraisaldescription: Test Appraisal 1 description" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" for "appraiser" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "currentstagename: App1_Stage2" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "expectedstagecompletiondate: 2 Jan 2025" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "previousstagename: App1_Stage1" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userusername: learner1" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userlastname: lastname" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userfullname: learner1 lastname" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerusername: manager" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerfirstname: manager" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerfullname: manager lastname" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerusername: teamlead" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerfirstname: teamlead" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerlastname: lastname" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerfullname: teamlead lastname" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserusername: appraiser" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserfirstname: appraiser" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserlastname: lastname" for "appraiser" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserfullname: appraiser lastname" for "appraiser" user

    # Check the message 3, stage 2 completion for the manager.
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" exists for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" does not contain "[" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" does not contain "]" for "manager" user

    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraisalname: Appraisal1" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraisaldescription: Test Appraisal 1 description" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" for "manager" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "currentstagename: App1_Stage2" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "expectedstagecompletiondate: 2 Jan 2025" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "previousstagename: App1_Stage1" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userusername: learner1" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userlastname: lastname" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userfullname: learner1 lastname" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerusername: manager" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerfirstname: manager" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerfullname: manager lastname" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerusername: teamlead" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerfirstname: teamlead" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerlastname: lastname" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerfullname: teamlead lastname" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserusername: appraiser" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserfirstname: appraiser" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserlastname: lastname" for "manager" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserfullname: appraiser lastname" for "manager" user

    # Check the message 3, stage 2 completion for the teamlead.
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" exists for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" does not contain "[" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" does not contain "]" for "teamlead" user

    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraisalname: Appraisal1" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraisaldescription: Test Appraisal 1 description" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" for "teamlead" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "currentstagename: App1_Stage2" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "expectedstagecompletiondate: 2 Jan 2025" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "previousstagename: App1_Stage1" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userusername: learner1" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userlastname: lastname" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "userfullname: learner1 lastname" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerusername: manager" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerfirstname: manager" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managerfullname: manager lastname" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerusername: teamlead" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerfirstname: teamlead" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerlastname: lastname" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "managersmanagerfullname: teamlead lastname" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserusername: appraiser" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserfirstname: appraiser" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserlastname: lastname" for "teamlead" user
    And the message "Message 3 - Appraisal1 - App1_Stage2 completion" contains "appraiserfullname: appraiser lastname" for "teamlead" user

    # Test Message 4, stage 3 completion.
    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "appraiser"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "teamlead"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    # Check the message 4, stage 3 completion for the leaner1.
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" exists for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" does not contain "[" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" does not contain "]" for "learner1" user

    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraisalname: Appraisal1" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraisaldescription: Test Appraisal 1 description" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" for "learner1" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "currentstagename: App1_Stage3" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "expectedstagecompletiondate: 3 Jan 2025" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "previousstagename: App1_Stage2" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userusername: learner1" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userlastname: lastname" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userfullname: learner1 lastname" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerusername: manager" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerfirstname: manager" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerfullname: manager lastname" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerusername: teamlead" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerfirstname: teamlead" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerlastname: lastname" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerfullname: teamlead lastname" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserusername: appraiser" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserfirstname: appraiser" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserlastname: lastname" for "learner1" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserfullname: appraiser lastname" for "learner1" user

    # Check the message 4, stage 3 completion for the appraiser.
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" exists for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" does not contain "[" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" does not contain "]" for "appraiser" user

    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraisalname: Appraisal1" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraisaldescription: Test Appraisal 1 description" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" for "appraiser" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "currentstagename: App1_Stage3" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "expectedstagecompletiondate: 3 Jan 2025" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "previousstagename: App1_Stage2" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userusername: learner1" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userlastname: lastname" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userfullname: learner1 lastname" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerusername: manager" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerfirstname: manager" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerfullname: manager lastname" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerusername: teamlead" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerfirstname: teamlead" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerlastname: lastname" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerfullname: teamlead lastname" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserusername: appraiser" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserfirstname: appraiser" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserlastname: lastname" for "appraiser" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserfullname: appraiser lastname" for "appraiser" user

    # Check the message 4, stage 3 completion for the manager.
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" exists for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" does not contain "[" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" does not contain "]" for "manager" user

    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraisalname: Appraisal1" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraisaldescription: Test Appraisal 1 description" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "expectedappraisalcompletiondate: 3 Jan 2025" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" for "manager" user contains multiline
    """
listofstagenames: * App1_Stage1
* App1_Stage2
* App1_Stage3
    """
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "currentstagename: App1_Stage3" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "expectedstagecompletiondate: 3 Jan 2025" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "previousstagename: App1_Stage2" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userusername: learner1" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userlastname: lastname" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "userfullname: learner1 lastname" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerusername: manager" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerfirstname: manager" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managerfullname: manager lastname" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerusername: teamlead" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerfirstname: teamlead" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerlastname: lastname" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "managersmanagerfullname: teamlead lastname" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserusername: appraiser" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserfirstname: appraiser" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserlastname: lastname" for "manager" user
    And the message "Message 4 - Appraisal1 - App1_Stage3 completion" contains "appraiserfullname: appraiser lastname" for "manager" user

  Scenario: Test appraisal placeholders default values within messages
    Given I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "Appraisal1"
    And I should see "3 (0 completed)" in the "Appraisal1" "table_row"
    And I should see " Activate" in the "Appraisal1" "table_row"

    # add all available placeholders to the following messages.
    When I follow "Appraisal1"
    And I switch to "Messages" tab

    # Appraisal activation message.
    And I follow "Message 1 - Appraisal Activation"
    And I set the field "Message title" to "Message 1 - Appraisal Activation - [appraisalname]"
    And I add all appraisal message placeholders in the "Message body" field
    And I press "Save changes"
    Then I should see "Message 1 - Appraisal Activation - [appraisalname]"

    # Activate the appraisal and test all the messages.
    When I click on "Activate now" "link"
    And I press "Activate"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"
    And I log out

    And I run the scheduled task "\totara_appraisal\task\scheduled_messages"
    # Test Message 1, appraisal activation for default values.
    And the message "Message 1 - Appraisal Activation - Appraisal1" exists for "learner3" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" does not contain "[" for "learner3" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" does not contain "]" for "learner3" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "previousstagename: No previous stage" for "learner3" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "managerfullname: Manager not known" for "learner1" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "managersmanagerfullname: Manager's manager not known" for "learner3" user
    And the message "Message 1 - Appraisal Activation - Appraisal1" contains "appraiserfullname: Appraiser not known" for "learner3" user
