@totara @totara_appraisal @javascript
Feature: Create a large appraisal
  In order to ensure that appraisals with lots of questions still work
  As an admin
  I need to create an appraisal with lots of questions

  Background:
    # Set up the data we need for appraisals.
    And the following "users" exist:
      | username   | firstname  | lastname   | email                  |
      | learner1   | firstname1 | lastname1  | learner1@example.com   |
      | learner2   | firstname2 | lastname2  | learner2@example.com   |
      | manager1   | manager1   | manager1   | manager1@example.com   |
      | appraiser1 | appraiser1 | appraiser1 | appraiser1@example.com |
    And the following job assignments exist:
      | user     | fullname         | idnumber | manager  | appraiser  |
      | learner1 | learner1 Day Job | l1ja     | manager1 | appraiser1 |
      | learner2 | learner2 Day Job | l2ja     | manager1 | appraiser1 |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | A1     |
      | learner2 | A1     |

      # Set up an appraisal using the data generator.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal 1 |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name    |
      | Appraisal 1 | Stage 1 |
      | Appraisal 1 | Stage 2 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage   | name   |
      | Appraisal 1 | Stage 1 | Page 1 |
      | Appraisal 1 | Stage 1 | Page 2 |
      | Appraisal 1 | Stage 1 | Page 3 |
      | Appraisal 1 | Stage 1 | Page 4 |
      | Appraisal 1 | Stage 1 | Page 5 |
      | Appraisal 1 | Stage 2 | Page 6 |

  Scenario: Create Large Appraisal
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I create "50" appraisal questions on the page "Page 1"
    And I create "50" appraisal questions on the page "Page 2"
    And I create "50" appraisal questions on the page "Page 3"
    And I create "50" appraisal questions on the page "Page 4"
    And I create "50" appraisal questions on the page "Page 5"
    And I create "50" appraisal questions on the page "Page 6"
    And I click on "Appraisal 1" "link" in the ".appraisallist" "css_element"
    And I click on "Assignments" "link"
    And I click on "Audience" "option" in the "#menugroupselector" "css_element"
    And I click on "Audience 1 (A1)" "link" in the "assigngrouptreeviewdialog" "totaradialogue"
    And I click on "Save" "button" in the "assigngrouptreeviewdialog" "totaradialogue"
    And I click on "Activate now" "link"
    And I should see "Do you really want to activate this appraisal?"
    And I click on "Activate" "button"
    Then I should see "Appraisal Appraisal 1 activated"

    When I log out
    And I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I should see "Appraisal 1"
    And I click on "Appraisal 1" "link"
    # This step will take some time, and may time out when the web host is under load.
    # The wait is to ensure it has the time it needs.
    And I click on "Save PDF Snapshot" "button"
    And I wait "9" seconds
    And I should see "A snapshot of your appraisal has been saved."
    And I click on "Cancel" "button" in the "savepdf" "totaradialogue"
    And I click on "All Appraisals" in the totara menu
    And I should see "Snapshot"

  Scenario: Show MySQL limit warning for large appraisal
    Given database family used is one of the following:
      | mysql |
    And I am on a totara site
    And I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I create "50" "text" appraisal questions on the page "Page 1"
    And I create "50" "text" appraisal questions on the page "Page 2"
    And I create "50" "text" appraisal questions on the page "Page 3"
    And I create "36" "text" appraisal questions on the page "Page 4"
    And I click on "Appraisal 1" "link" in the ".appraisallist" "css_element"
    And I switch to "Content" tab
    Then I should not see "The large amount of questions in this appraisal may lead to activation failure."
    # One more text question and we should be over the warning threshold
    When I set the following fields to these values:
      | datatype | Short text |
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question    | Your favourite colour |
      | roles[1][2] | 1                     |
      | roles[2][2] | 1                     |
      | roles[1][6] | 1                     |
      | roles[2][1] | 1                     |
    And I press "Save changes"
    Then I should see "The large number of questions in this appraisal may prevent your appraisal from being activated."
    # Also check that message disappears when we remove one question.
    When I click on "#appraisal-quest-list .action-icon.delete" "css_element"
    And I press "Yes"
    Then I should not see "The large number of questions in this appraisal may prevent your appraisal from being activated."

  Scenario: Display appropriate error message when trying to activate very large appraisal for MySQL
    Given database family used is one of the following:
      | mysql |
    And I am on a totara site
    And I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    # 198 text questions are enough to cause a "Row size too large" error for MySQL.
    And I create "33" "text" appraisal questions on the page "Page 1"
    And I create "33" "text" appraisal questions on the page "Page 2"
    And I create "33" "text" appraisal questions on the page "Page 3"
    And I create "33" "text" appraisal questions on the page "Page 4"
    And I create "33" "text" appraisal questions on the page "Page 5"
    And I create "33" "text" appraisal questions on the page "Page 6"
    And I click on "Appraisal 1" "link" in the ".appraisallist" "css_element"
    And I switch to "Content" tab
    When I follow "Activate now"
    And I click on "Activate" "button"
    Then I should see "Appraisal not ready for activation"
    And I should see "This appraisal contains too many questions. Please remove questions to make activation possible."
