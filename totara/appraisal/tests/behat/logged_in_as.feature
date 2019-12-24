@totara @totara_appraisal
Feature: Make sure the real user is recorded in appraisals when using login-as

  Background:
    Given I am on a totara site

    # Users
    And the following "users" exist:
      | username   | firstname  | lastname  | email                  |
      | learner1   | learner1   | lastname  | learner1@example.com   |
      | manager    | manager    | lastname  | manager@example.com    |
    And the following job assignments exist:
      | user       | fullname       | idnumber | manager   |
      | manager    | Manager Job    | ja       |           |
      | learner1   | Learner1 Job   | ja       | manager   |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | AppAud |

    # Appraisal
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

    # Activate appraisal.
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"

  @javascript
  Scenario: Complete an appraisal stage as a 'logged-in as' user
    # Select the job assignment
    When I log out
    And I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu

    # Admin logs in as the manager
    And I log out
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "manager lastname" "link"
    And I click on "Log in as" "link"
    Then I should see "You are logged in as manager lastname"

    # Mark the appraisal complete
    When I click on "Performance" in the totara menu
    And I follow "Appraisal1"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "learner1 lastname must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "You have completed this stage (Admin User on behalf of manager lastname)" in the ".appraisal-stagelist" "css_element"

    # View the appraisal as the learner
    When I log out
    And I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "You must complete this stage" in the ".appraisal-stagelist" "css_element"
    And I should see "Your Manager has completed this stage (Admin User on behalf of manager lastname)" in the ".appraisal-stagelist" "css_element"
