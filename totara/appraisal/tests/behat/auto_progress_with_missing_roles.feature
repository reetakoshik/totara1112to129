@totara @totara_appraisal @javascript
Feature: Automatic progress with missing roles
  As a leaner
  I should be able to complete an appraisal even when a required role is missing

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | learner1  | Learner   | One      | learner1@example.com  |
    And the following job assignments exist:
      | user      | fullname      | idnumber | manager | appraiser |
      | learner1  | Learner1 Job  | l1ja     |         |           |
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
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal  | stage       | name       |
      | Appraisal1 | App1_Stage1 | App1_Page1 |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal  | stage       | page       | name    | type | default |
      | Appraisal1 | App1_Stage1 | App1_Page1 | App1-Q1 | text | 1       |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id     |
      | Appraisal1  | audience | AppAud |

    # Activate the appraisal.
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"

  Scenario: Appraisal does not automatically progress when Dynamic Appraisals Automatic Progression is off
    When the following config values are set as admin:
      | dynamicappraisals             | 1 |
      | dynamicappraisalsautoprogress | 0 |
    And I log out

    When I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Job assignment linked to this appraisal"
    And I should see "Manager: Role currently empty"
    And I should see "Your Manager must complete this stage"
    When I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "You have completed this stage"
    And I should see "Incomplete"

  Scenario: Appraisal automatically progresses when Dynamic Appraisals Automatic Progression is on
    When the following config values are set as admin:
      | dynamicappraisals             | 1 |
      | dynamicappraisalsautoprogress | 1 |
    And I log out

    When I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Job assignment linked to this appraisal"
    And I should see "Manager: Role currently empty"
    And I should not see "Your Manager must complete this stage"
    When I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "You have completed this stage"
    And I should see "This appraisal was completed on"

  Scenario: Appraisal automatically progresses when Dynamic Appraisals is off
    When the following config values are set as admin:
      | dynamicappraisals             | 0 |
      | dynamicappraisalsautoprogress | 0 |
    And I log out

    When I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Job assignment linked to this appraisal"
    And I should see "Manager: Role currently empty"
    And I should not see "Your Manager must complete this stage"
    When I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "You have completed this stage"
    And I should see "This appraisal was completed on"
