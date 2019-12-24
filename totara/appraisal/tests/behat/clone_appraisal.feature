@totara @totara_appraisal @javascript
Feature: Clone appraisals
  In order to check if an appraisal is correctly cloned
  As admin I should be able to clone it and
  As learner and manager I should be able to complete it

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | A1     |
    And the following job assignments exist:
      | user     | fullname         | idnumber | manager  |
      | learner1 | Learner1 One     | l1ja     | manager1 |
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name            |
      |  Appraisal Test |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal       | name   | timedue                 |
      |  Appraisal Test | Stage1 | 1 January 2020 23:59:59 |
      |  Appraisal Test | Stage2 | 1 January 2030 23:59:59 |
      |  Appraisal Test | Stage3 | 1 January 2040 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal       | stage  | name                    |
      |  Appraisal Test | Stage1 | Stage1-Text             |
      |  Appraisal Test | Stage2 | Stage2-Ratings          |
      |  Appraisal Test | Stage3 | Stage3-Aggregates       |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal       | stage  | page                    | name              | type          | default | ExtraInfo                          |
      |  Appraisal Test | Stage1 | Stage1-Text             | S1-shorttext      | text          |         |                                    |
      |  Appraisal Test | Stage2 | Stage2-Ratings          | S2-Rating_Numeric | ratingnumeric | 5       | Range:1-10,Display:slider          |
      |  Appraisal Test | Stage2 | Stage2-Ratings          | S2-Rating_Custom  | ratingcustom  | choice1 |                                    |
      |  Appraisal Test | Stage3 | Stage3-Aggregates       | S3-Aggregate      | aggregate     |         | S2-Rating_Numeric,S2-Rating_Custom |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal       | type     | id     |
      |  Appraisal Test | audience | A1     |
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "copy" "link" in the "Appraisal Test" "table_row"
    And I set the field "name" to "Cloned Appraisal Test"
    And I press "Save changes"
    And I click on "Activate now" "link"
    And I press "Activate"
    And I log out

  Scenario: Check learner and manager can complete the cloned appraisal without errors.
    #    Complete Stage 1
    Given I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I set the field "Your answer" to "Learner answer"
    And I press "Complete stage"
    And I log out

    And I log in as "manager1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Cloned Appraisal Test" "link"
    And I press "Start"
    And I set the field "Your answer" to "Manager answer"
    And I press "Complete stage"
    And I log out

    #    Complete Stage 2
    And I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I click on "choice3" "radio"
    And I press "Complete stage"
    And I log out

    And I log in as "manager1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Cloned Appraisal Test" "link"
    And I press "Start"
    And I click on "choice4" "radio"
    And I press "Complete stage"
    And I log out

     #    Complete Stage 3
    When I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    Then I should see "Average score"
    And I press "Complete stage"
    And I log out

    When I log in as "manager1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Cloned Appraisal Test" "link"
    And I press "Start"
    Then I should see "Average score"
    And I press "Complete stage"
    And I log out
