@totara @totara_appraisal
Feature: Test unlocking an appraisal stage for a role

  Background:
    # Set up the deta we need for appraisals.
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname  | lastname  | email                |
      | learner    | learner    | lastname  | learner@example.com  |
      | manager    | manager    | lastname  | manager@example.com  |
    And the following job assignments exist:
      | user      | fullname     | idnumber | manager   |
      | manager   | Manager Job  | ja       |           |
      | learner   | Learner Job  | ja       | manager   |

    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user    | cohort |
      | learner | AppAud |

    # Set up an appraisal using the data generator.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal1  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name        | timedue                 |
      | Appraisal1  | App1_Stage1 | 1 January 2030 23:59:59 |
      | Appraisal1  | App1_Stage2 | 1 January 2031 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage       | name       |
      | Appraisal1  | App1_Stage1 | App1_Page1 |
      | Appraisal1  | App1_Stage2 | App1_Page2 |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal   | stage       | page       | name     | type          | default | roles           | ExtraInfo |
      | Appraisal1  | App1_Stage1 | App1_Page1 | App1-Q1  | text          | 2       | learner,manager |           |
      | Appraisal1  | App1_Stage2 | App1_Page2 | App1-Q2  | text          | 2       | learner,manager |           |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id     |
      | Appraisal1  | audience | AppAud |

    # Activate appraisal.
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    And I log out

    # Learner completes stage one.
    When I log in as "learner"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I click on "Complete stage" "button"
    And I log out

    # Manager completes both stages.
    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I follow "Appraisal1"
    And I press "Start"
    And I click on "Complete stage" "button"
    And I press "Start"
    And I click on "Complete stage" "button"
    And I log out

    # Learner completes stage two.
    When I log in as "learner"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "This appraisal was completed"
    And I log out

  @javascript
  Scenario: Check that an appraisal stage can be unlocked for all roles
    # Admin edits stage.
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I follow "Appraisal1"
    And I switch to "Assignments" tab
    And I click on "Edit" "link" in the "learner lastname" "table_row"
    And I set the field "Role / user to change" to "All roles"
    And I press "Apply"
    Then I should see "Editing current stage was completed"
    And I log out

    # Check Manager must complete the stage.
    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I follow "Appraisal1"
    Then I should see "learner lastname must complete this stage"
    And I should see "You must complete this stage"
    And I should not see "You have completed this stage"
    When I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "You have completed this stage"
    And I log out

    # Learner can re-complete stages.
    When I log in as "learner"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I click on "Complete stage" "button"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "You have completed this stage"
    And I log out

    # Check Manager must complete the stage.
    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I follow "Appraisal1"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "This appraisal was completed"

  @javascript
  Scenario: Check that an appraisal stage can be unlocked for a learner
    # Admin edits stage
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I follow "Appraisal1"
    And I switch to "Assignments" tab
    And I click on "Edit" "link" in the "learner lastname" "table_row"
    And I set the field "Role / user to change" to "Learner - learner lastname"
    And I press "Apply"
    Then I should see "Editing current stage was completed"
    And I log out

    # Check Manager can edit stage
    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I follow "Appraisal1"
    Then I should see "learner lastname must complete this stage"
    And I should see "You have completed this stage (manager lastname)"
    And I should not see "You must complete this stage"
    And I should not see "learner lastname has completed this stage"
    When I press "View"
    And I press "Save changes"
    Then I should see "Changes saved"
    And I log out

    # Learner can re-complete stages and manager is auto-completed and whole appraisal is completed
    When I log in as "learner"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I click on "Complete stage" "button"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "This appraisal was completed"