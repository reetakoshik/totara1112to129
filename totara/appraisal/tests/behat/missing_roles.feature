@totara @totara_appraisal
Feature: Admin page that lists missing roles for one appraisal
  In order to maintain the appraisal process
  As an admin
  I should be able to see job assignments that have missing roles required for a particular appraisal

  Background:
    # Set up user data
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Learner    | One       | learner1@example.com |
      | learner2 | Learner    | Two       | learner2@example.com |
      | learner3 | Learner    | Three     | learner3@example.com |
      | manager1 | Manager    | One       | manager1@example.com |
    And the following job assignments exist:
      | user     | fullname       | idnumber | manager  |
      | learner1 | Learner1 Job1  | l1j1     | manager1 |
      | learner1 | Learner1 Job2  | l1j2     |          |
      | learner2 | Learner2 Job1  | l2j1     |          |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | AppAud |
      | learner2 | AppAud |
      | learner3 | AppAud |
    # Set up appraisal data
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name       |
      | Appraisal1 |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal  | name       | timedue                 |
      | Appraisal1 | App1_Stage | 1 January 2020 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal  | stage      | name      |
      | Appraisal1 | App1_Stage | App1_Page |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal  | stage      | page      | name     | type | default | roles   | ExtraInfo |
      | Appraisal1 | App1_Stage | App1_Page | App1-Q1  | text | 2       | manager |           |
      | Appraisal1 | App1_Stage | App1_Page | App1-Q2  | text | 2       | manager |           |
      | Appraisal1 | App1_Stage | App1_Page | App1-Q3  | text | 2       | manager |           |
      | Appraisal1 | App1_Stage | App1_Page | App1-Q4  | text | 2       | manager |           |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal  | type     | id     |
      | Appraisal1 | audience | AppAud |

  @javascript
  Scenario: Admin opens missing roles page for an appraisal
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    And the following "appraisal_job_assignments" exist in "totara_appraisal" plugin:
      | appraisal  | jobassignment |
      | Appraisal1 | l2j1          |
    And I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "Some assigned users are missing important role assignments or have not yet selected a job assignment for this appraisal."

    When I follow "View full list of missing roles"
    Then I should see "Learner Learner One has not selected a job assignment yet."
    And I should see "Learner Learner Two is missing their Manager."
    And I should see "Learner Learner Three has not selected a job assignment yet."

  @javascript
  Scenario: Deleting managers answers are kept
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Settings" "link" in the "Appraisal1" "table_row"
    And I switch to "Content" tab
    And I click on "Settings" "link" in the "//li[.//*[contains(text(), 'App1-Q1')]]" "xpath_element"
    And I set the following fields to these values:
      | roles[1][2] | 0 |
      | roles[2][2] | 1 |
      | roles[2][6] | 1 |
      | roles[1][1] | 1 |
      | roles[2][1] | 1 |
    And I click on "Save changes" "button"
    And I click on "Settings" "link" in the "//li[.//*[contains(text(), 'App1-Q2')]]" "xpath_element"
    And I set the following fields to these values:
      | roles[1][2] | 0 |
      | roles[2][2] | 1 |
      | roles[2][6] | 1 |
      | roles[1][1] | 1 |
      | roles[2][1] | 1 |
    And I click on "Save changes" "button"
    And I click on "Settings" "link" in the "//li[.//*[contains(text(), 'App1-Q3')]]" "xpath_element"
    And I set the following fields to these values:
      | roles[1][2] | 0 |
      | roles[2][2] | 1 |
      | roles[2][6] | 1 |
      | roles[1][1] | 1 |
      | roles[2][1] | 1 |
    And I click on "Save changes" "button"
    And I click on "Settings" "link" in the "//li[.//*[contains(text(), 'App1-Q4')]]" "xpath_element"
    And I set the following fields to these values:
      | roles[1][2] | 0 |
      | roles[2][2] | 1 |
      | roles[2][6] | 1 |
      | roles[1][1] | 1 |
      | roles[2][1] | 1 |
    And I click on "Save changes" "button"
    And I follow "Activate now"
    And I press "Activate"
    And I log out

    And I log in as "learner1"
    And I follow "Performance"
    And I follow "Latest Appraisal"
    And I set the field with xpath "//div[contains(concat(' ', @class, ' '), ' singleselect ')]//select" to "2"
    And I click on "View" "button"

    And I log out
    And I log in as "manager1"
    And I follow "Performance"
    And I follow "All Appraisals"
    And I follow "Appraisal1"
    And I click on "Start" "button"
    And I set the field with xpath "//fieldset[.//legend//a[text()='App1-Q1']]//input" to "Manager answer1"
    And I set the field with xpath "//fieldset[.//legend//a[text()='App1-Q2']]//input" to "Manager answer2"
    And I set the field with xpath "//fieldset[.//legend//a[text()='App1-Q4']]//input" to "Manager answer4"
    And I click on "Save progress" "button"

    And I log out
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Delete" "link" in the "manager1" "table_row"
    And I click on "Delete" "button"

    And I log out
    When I log in as "learner1"
    And I follow "Performance"
    And I follow "Latest Appraisal"
    And I follow "Appraisal1"
    And I click on "View" "button"

    Then I should see "Manager answer1"
    And I should see "Manager answer2"
    And I should see "Manager answer4"
    And I should see "Manager's answer"
    And I should see "No response"
