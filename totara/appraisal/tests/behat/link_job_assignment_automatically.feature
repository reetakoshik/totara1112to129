@totara @totara_appraisal @javascript
Feature: Automatically link an appraisee's job assignment on appraisal activation.
  In order to be informed about appraisees
  As a manager
  Job assignments should be auto-linked to an appraisal assignment when conditions are met.

  Scenario: Manager can see learner's appraisal assignment after activation without learner opening the appraisal first.
    # Set up the data we need for appraisals.
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname  | lastname  | email                  |
      | learner1   | learner1   | lastname  | learner1@example.com   |
      | learner2   | learner2   | lastname  | learner2@example.com   |
      | learner3   | learner3   | lastname  | learner3@example.com   |
      | manager    | manager    | lastname  | manager@example.com    |
    And the following job assignments exist:
      | user       | fullname       | idnumber | manager   |
      | manager    | Manager Job    | ja1      |           |
      | learner1   | Learner1 Job   | ja2      | manager   |
      | learner2   | Learner2 Job 1 | ja3      | manager   |
      | learner2   | Learner2 Job 2 | ja4      |           |
      | learner3   | Learner3 Job   | ja5      | manager   |
    And the following "cohorts" exist:
      | name                  | idnumber  | description             | contextlevel | reference |
      | Appraisals Audience 1 | AppAud1   | Appraisals Assignments1 | System       | 0         |
      | Appraisals Audience 2 | AppAud2   | Appraisals Assignments2 | System       | 0         |
    And the following "cohort members" exist:
      | user     | cohort  |
      | learner1 | AppAud1 |
      | learner2 | AppAud1 |
      | learner3 | AppAud2 |
      | manager  | AppAud1 |
      | manager  | AppAud2 |

    # Set up an appraisal using the data generator.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal1  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name       | timedue                 |
      | Appraisal1  | App1_Stage | 1 January 2022 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage      | name      |
      | Appraisal1  | App1_Stage | App1_Page |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal   | stage      | page      | name     | type          | default | roles   | ExtraInfo |
      | Appraisal1  | App1_Stage | App1_Page | App1-Q1  | text          | 2       | manager |           |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id      |
      | Appraisal1  | audience | AppAud1 |

    # Set necessary configuration.
    When I log in as "admin"
    And I set the following administration settings values:
      | totara_job_allowmultiplejobs | 0 |

    # Activate appraisal.
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I press "Activate"
    And I log out

    # Verify that manager can see.
    When I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I follow "As Manager"
    Then I should see "learner1 lastname"
    # No auto-linking of job assignment for learner2 because he was set up with 2 job assignments.
    And I should not see "learner2 lastname"
    And I should not see "learner3 lastname"
    When I follow "Appraisal1"
    Then I should see "You are viewing learner1 lastname's appraisal."
    And I should see "Learner1 Job"
    And I log out

    # Check that adding another audience to an active appraisal also auto-links job assignments.
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I follow "Appraisal1"
    And I follow "Assignments"
    And I select "Audience" from the "groupselector" singleselect
    And I follow "Appraisals Audience 2"
    And I press "Save"
    And I press "Update"
    Then I should see "Appraisals Audience 2" in the "learner3 lastname" "table_row"
    When I log out

    # Verify again that manager can see the appraisal in his tab.
    And I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I follow "As Manager"
    Then I should see "learner3 lastname"
    And I click on "Appraisal1" "link" in the "learner3 lastname" "table_row"
    Then I should see "You are viewing learner3 lastname's appraisal."
    And I should see "Learner3 Job"
