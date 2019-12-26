@totara @totara_appraisal @javascript
Feature: Appraisal progression halts at stage without any role assigned
  In order to have control of appraisal progression
  As an appraisal admin
  I can add a stage with an empty role assignment that doesn't get auto-completed

  Background:
    # Set up user data.
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | learner1  | Learner   | One      | learner1@example.com  |
      | manager   | Manager   | Man      | manager@example.com   |
    And the following job assignments exist:
      | user      | fullname      | idnumber | manager  | appraiser |
      | manager   | Manager Job   | mja      |          |           |
      | learner1  | Learner1 Job  | l1ja     | manager  |           |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | AppAud |

    # Set up appraisal data.
    # The last stage is set up to be answered by unfilled appraiser role only.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal1  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal  | name           | timedue                 |
      | Appraisal1 | App1_Stage1    | 1 January 2030 23:59:59 |
      | Appraisal1 | App1_Stage2    | 1 January 2031 23:59:59 |
      | Appraisal1 | App1_Stage3    | 1 January 2032 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal  | stage       | name       |
      | Appraisal1 | App1_Stage1 | App1_Page1 |
      | Appraisal1 | App1_Stage2 | App1_Page2 |
      | Appraisal1 | App1_Stage3 | App1_Page3 |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal  | stage       | page       | name    | type | default | roles     |
      | Appraisal1 | App1_Stage1 | App1_Page1 | App1-Q1 | text | 1       | learner   |
      | Appraisal1 | App1_Stage2 | App1_Page2 | App1-Q2 | text | 1       | manager   |
      | Appraisal1 | App1_Stage3 | App1_Page3 | App1-Q2 | text | 1       | appraiser |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id     |
      | Appraisal1  | audience | AppAud |

    # Activate the appraisal.
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    And I log out

  Scenario: Subsequent stage without any role completion should not be completed automatically
    # Learner One completes stage one.
    When I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    And I log out

    # Manager completes stage two.
    And I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "You have completed this stage"
    # The last stage should not be completed automatically, even if the only role that can answer it is empty.
    And I should not see "This appraisal was completed"
