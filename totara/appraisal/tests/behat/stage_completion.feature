@totara @totara_appraisal
Feature: Test appraisal stage completion with missing roles
  In order to progress through appraisal stages
  As a learner
  I should be able to complete an appraisal stage when I don't have a manager assigned

  Background:
    # Set up the deta we need for appraisals.
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname  | lastname  | email                  |
      | learner1   | learner1   | lastname  | learner1@example.com   |
      | learner2   | learner2   | lastname  | learner2@example.com   |
      | manager    | manager    | lastname  | manager@example.com    |
      | teamlead   | teamlead   | lastname  | teamlead@example.com   |
      | appraiser  | appraiser  | lastname  | appraiser@example.com  |
    And the following job assignments exist:
      | user       | fullname | idnumber | manager   | appraiser  |
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

    # Set up an appraisal using the data generator.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal1  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name       | timedue                 |
      | Appraisal1  | App1_Stage | 1 January 2020 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage      | name      |
      | Appraisal1  | App1_Stage | App1_Page |
     And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal   | stage      | page      | name     | type          | default | roles   | ExtraInfo |
      | Appraisal1  | App1_Stage | App1_Page | App1-Q1  | text          | 2       | manager |           |

    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id     |
      | Appraisal1  | audience | AppAud |

  @javascript
  Scenario: Verify stage not completed for learner with a manager
    Given I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "Appraisal1"
    And I should see "2 (0 completed)" in the "Appraisal1" "table_row"
    And I should see " Activate" in the "Appraisal1" "table_row"
    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    Then I should see " Close" in the "Appraisal1" "table_row"
    And I log out

    # Learner completes the stage
    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Appraisal1"

    When I follow "Appraisal1"
    Then I should see "Learner: learner1 lastname" in the ".appraisal-participants" "css_element"
    And I should see "Manager: manager lastname" in the ".appraisal-participants" "css_element"
    And I should see "App1_Stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Incomplete" in the ".appraisal-stagelist" "css_element"
    And "Start" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"

    When I press "Start"
    And "//input[@value='Complete stage']" "xpath_element" should exist
    And I click on "Complete stage" "button"
    Then "View" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You have completed this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"

    # Learner can view the stage and change the answer
    When I press "View"
    Then I should see "Incomplete" in the ".appraisal-stage" "css_element"
    And "//input[@value='Save progress' and @disabled]" "xpath_element" should exist
    And "//input[@value='Complete stage' and @disabled]" "xpath_element" should exist
    And "//input[@value='Save changes']" "xpath_element" should exist
    And I log out

    # Manager can now answer
    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Appraisal1" in the "learner1 lastname" "table_row"

    When I follow "Appraisal1"
    Then I should see "Incomplete" in the ".appraisal-stagelist" "css_element"
    And "Start" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "learner1 lastname has completed this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "You must complete this stage" in the ".appraisal-stagelist" "css_element"
    And "Start" "button" should exist in the ".appraisal-stagelist" "css_element"

  @javascript
  Scenario: Verify stage completed for learner without a manager after learner completed the stage
    Given I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "Appraisal1"
    And I should see "2 (0 completed)" in the "Appraisal1" "table_row"
    And I should see " Activate" in the "Appraisal1" "table_row"
    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    Then I should see " Close" in the "Appraisal1" "table_row"
    And I log out

    When I log in as "learner2"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Appraisal1"

    # Learner completes the stage
    When I follow "Appraisal1"
    Then I should see "Learner: learner2 lastname" in the ".appraisal-participants" "css_element"
    And I should see "Manager: Role currently empty" in the ".appraisal-participants" "css_element"
    And I should see "App1_Stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Incomplete" in the ".appraisal-stagelist" "css_element"
    And "Start" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I should not see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"

    When I press "Start"
    And "//input[@value='Complete stage']" "xpath_element" should exist
    And I set the field "Your answer" to "My answer"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the ".appraisal-stagelist" "css_element"
    And "View" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You have completed this stage" in the ".appraisal-stagelist" "css_element"

    # Learner can view the stage but not change the answer
    When I press "View"
    Then I should see "Completed" in the ".appraisal-stageinfo" "css_element"
    And "//input[@value='Save progress' and @disabled]" "xpath_element" should exist
    And "//input[@value='Complete stage' and @disabled]" "xpath_element" should exist
    And "//input[@value='Save changes']" "xpath_element" should not exist
    And "My answer" "text" should exist

  @javascript
  Scenario: Verify Manager is not required to complete the appraisal if he was assigned after the appraisal was completed
    Given I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "Appraisal1"
    And I should see "2 (0 completed)" in the "Appraisal1" "table_row"
    And I should see " Activate" in the "Appraisal1" "table_row"
    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    Then I should see " Close" in the "Appraisal1" "table_row"
    And I log out

    When I log in as "learner2"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Appraisal1"

    # Learner completes the stage
    When I follow "Appraisal1"
    Then I should see "Learner: learner2 lastname" in the ".appraisal-participants" "css_element"
    And I should see "Manager: Role currently empty" in the ".appraisal-participants" "css_element"
    And I should see "App1_Stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Incomplete" in the ".appraisal-stagelist" "css_element"
    And "Start" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I should not see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"

    When I press "Start"
    And "//input[@value='Complete stage']" "xpath_element" should exist
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the ".appraisal-stagelist" "css_element"
    And "View" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You have completed this stage" in the ".appraisal-stagelist" "css_element"
    And I log out

    # Now assign a manager to learner2
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "learner2 lastname"
    And I follow "Learner2 Job"
    And I press "Choose manager"
    And I click on "manager lastname (manager@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "Manager Job" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    Then I should see "manager lastname (manager@example.com) - Manager Job" in the "#managertitle" "css_element"
    And I press "Update job assignment"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I log out

    When I log in as "learner2"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Completed" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    Then I should see "Learner: learner2 lastname" in the ".appraisal-participants" "css_element"
    And I should see "Manager: manager lastname" in the ".appraisal-participants" "css_element"
    And I should see "App1_Stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Completed" in the ".appraisal-stagelist" "css_element"
    And "View" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You have completed this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Manager not assigned at the time of completion. No action required"

    When I press "View"
    Then I should see "Completed" in the ".appraisal-stageinfo" "css_element"
    And I should see "Manager not assigned at the time of completion. No action required"
    And "//input[@value='Save progress' and @disabled]" "xpath_element" should exist
    And "//input[@value='Complete stage' and @disabled]" "xpath_element" should exist
    And "//input[@value='Save changes']" "xpath_element" should not exist
    And I log out

    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu

  @javascript
  Scenario: Verify that the appraisal is completed if the manager is removed after the learner has completed all stages
    Given I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "Appraisal1"
    And I should see "2 (0 completed)" in the "Appraisal1" "table_row"
    And I should see " Activate" in the "Appraisal1" "table_row"

    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    Then I should see " Close" in the "Appraisal1" "table_row"
    And I log out

    # Learner completes the stage
    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Appraisal1"

    When I follow "Appraisal1"
    Then I should see "Learner: learner1 lastname" in the ".appraisal-participants" "css_element"
    And I should see "Manager: manager lastname" in the ".appraisal-participants" "css_element"
    And I should see "App1_Stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Incomplete" in the ".appraisal-stagelist" "css_element"
    And "Start" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"

    When I press "Start"
    And "//input[@value='Complete stage']" "xpath_element" should exist
    And I click on "Complete stage" "button"
    Then "View" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You have completed this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I log out

    # Now remove the manager
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "learner1 lastname"
    And I follow "Learner1 Job"
    And I click on "Delete" "link" in the "#managertitle" "css_element"
    And I click on "Update job assignment" "button"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I log out

    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I follow "Appraisal1"
    Then I should see "Completed" in the ".appraisal-stagelist" "css_element"
    And "View" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You have completed this stage" in the ".appraisal-stagelist" "css_element"
    And I should not see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"
    # Learner can view the stage but not change the answer
    When I press "View"
    Then I should see "Completed" in the ".appraisal-stageinfo" "css_element"
    And "//input[@value='Save progress' and @disabled]" "xpath_element" should exist
    And "//input[@value='Complete stage' and @disabled]" "xpath_element" should exist
    And "//input[@value='Save changes']" "xpath_element" should not exist

  @javascript
  Scenario: Verify that the activestageid progresses when the learner completed the first stage before the manager is removedstages
    Given I log in as "admin"

    # Add another stage
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "Appraisal1"
    And I follow "Appraisal1"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I press "Add stage"
    And I set the following fields to these values:
      | Name                  | App1 Stage2             |
      | Description           | App1 Stage2 Description |
      | timedue[enabled]      | 1                       |
      | timedue[day]          | 31                      |
      | timedue[month]        | 12                      |
      | timedue[year]         | 2020                    |
      | Page names (optional) | App1 Page2              |
    And I click on "Add stage" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "App1 Stage2" in the ".appraisal-stages" "css_element"

    When I click on "App1 Stage2" "link" in the ".appraisal-stages" "css_element"
    And I click on "App1 Page2" "link" in the ".appraisal-page-list" "css_element"
    And I set the field "id_datatype" to "Rating (numeric scale)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question     | Rating question  |
      | From         | 1                |
      | To           | 10               |
      | list         | Text input field |
      | id_roles_1_2 | 1                |
      | id_roles_1_1 | 1                |
      | id_roles_2_2 | 1                |
      | id_roles_2_1 | 1                |
    And I press "Save changes"
    Then I should see "Rating question"
    And I should see "Activate now"

    When I follow "Activate now"
    And I press "Activate"
    Then I should see " Close" in the "Appraisal1" "table_row"
    And I log out

    # Learner completes the stage
    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Appraisal1"

    When I follow "Appraisal1"
    Then I should see "Learner: learner1 lastname" in the ".appraisal-participants" "css_element"
    And I should see "Manager: manager lastname" in the ".appraisal-participants" "css_element"
    And I should see "App1_Stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Incomplete" in the ".appraisal-stagelist" "css_element"
    And "Start" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"

    When I press "Start"
    And "//input[@value='Complete stage']" "xpath_element" should exist
    And I click on "Complete stage" "button"
    Then "View" "button" should exist in the ".appraisal-stagelist" "css_element"
    And I should see "You have completed this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Your Manager must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I log out

    # Now remove the manager
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "learner1 lastname"
    And I follow "Learner1 Job"
    And I click on "Delete" "link" in the "#managertitle" "css_element"
    And I click on "Update job assignment" "button"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I log out

    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I follow "Appraisal1"
    Then "//div[contains(@class,'appraisal-stage-completed') and contains(., 'App1_Stage') and contains(., 'Completed')]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-inprogress') and contains(., 'App1 Stage2') and contains(., 'Incomplete')]" "xpath_element" should exist
