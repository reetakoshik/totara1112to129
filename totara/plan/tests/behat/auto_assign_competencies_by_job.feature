@totara @totara_plan @javascript
Feature: Verify competencies are automatically added to plan according to job assignment.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
      | learner2 | Bob2      | Learner2 | learner2@example.com |
      | learner3 | Bob3      | Learner3 | learner3@example.com |
      | learner4 | Bob4      | Learner4 | learner4@example.com |
      | learner5 | Bob5      | Learner5 | learner5@example.com |
      | manager1 | Dave1     | Manager1 | manager1@example.com |
      | manager2 | Dave2     | Manager2 | manager2@example.com |
      | manager3 | Dave3     | Manager3 | manager3@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
      | Course 3 | C3        | 1                |
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description                        |
      | Competency Framework 1 | CF1      | Competency Framework 1 description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |
      | CF1       | Competency 2 | C2       | Competency description |
      | CF1       | Competency 3 | C3       | Competency description |
    And the following "position" frameworks exist:
      | fullname             | idnumber | description                      |
      | Position Framework 1 | PF1      | Position Framework 1 description |
    And the following "position" hierarchy exists:
      | framework | fullname   | idnumber | description          |
      | PF1       | Position 1 | P1       | Position description |
      | PF1       | Position 2 | P2       | Position description |
      | PF1       | Position 3 | P3       | Position description |
    And the following "organisation" frameworks exist:
      | fullname                 | idnumber | description                          |
      | Organisation Framework 1 | OF1      | Organisation Framework 1 description |
    And the following "organisation" hierarchy exists:
      | framework | fullname       | idnumber | description              |
      | OF1       | Organisation 1 | O1       | Organisation description |
      | OF1       | Organisation 2 | O2       | Organisation description |
      | OF1       | Organisation 3 | O3       | Organisation description |
    And the following job assignments exist:
      | user     | fullname | manager  | position | organisation |
      | learner1 | Job 1    | manager1 | P1       |              |
      | learner1 | Job 2    | manager2 | P2       |              |
      | learner1 | Job 3    | manager3 | P3       |              |
      | learner2 | Job 1    | manager1 |          |              |
      | learner2 | Job 2    | manager2 |          |              |
      | learner2 | Job 3    | manager3 |          |              |
      | learner4 | Job 1    | manager3 |          | O1           |
      | learner4 | Job 2    | manager3 |          | O2           |
      | learner5 | Job 1    | manager2 |          | O3           |

    When I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    And I follow "Position 1"
    Then I should see "Position Framework 1 - Position 1"

    # Add Competency 1 to Position 1.
    When I press "Add Competency"
    Then I should see "Locate competency" in the "Assign competencies" "totaradialogue"

    When I follow "Competency 1"
    And I click on "Save" "button" in the "Assign competencies" "totaradialogue"
    Then I should see "Remove" in the "Competency 1" "table_row"

    # Add Competency 2 to Position 2.
    When I follow "Position Framework 1"
    And I follow "Position 2"
    Then I should see "Position Framework 1 - Position 2"

    When I press "Add Competency"
    Then I should see "Locate competency" in the "Assign competencies" "totaradialogue"

    When I follow "Competency 2"
    And I click on "Save" "button" in the "Assign competencies" "totaradialogue"
    Then I should see "Remove" in the "Competency 2" "table_row"

    # Add Competency 3 to Position 3.
    When I follow "Position Framework 1"
    And I follow "Position 3"
    Then I should see "Position Framework 1 - Position 3"

    When I press "Add Competency"
    Then I should see "Locate competency" in the "Assign competencies" "totaradialogue"

    When I follow "Competency 3"
    And I click on "Save" "button" in the "Assign competencies" "totaradialogue"
    Then I should see "Remove" in the "Competency 3" "table_row"

    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    # Add Competency 2 to Organisation 2.
    And I follow "Organisation Framework 1"
    And I follow "Organisation 2"
    Then I should see "Organisation Framework 1 - Organisation 2"

    When I press "Add Competency"
    Then I should see "Locate competency" in the "Assign competencies" "totaradialogue"

    When I follow "Competency 2"
    And I click on "Save" "button" in the "Assign competencies" "totaradialogue"
    Then I should see "Remove" in the "Competency 2" "table_row"

    # Add Competency 3 to Organisation 3.
    When I follow "Organisation Framework 1"
    And I follow "Organisation 3"
    Then I should see "Organisation Framework 1 - Organisation 3"

    When I press "Add Competency"
    Then I should see "Locate competency" in the "Assign competencies" "totaradialogue"

    When I follow "Competency 3"
    And I click on "Save" "button" in the "Assign competencies" "totaradialogue"
    Then I should see "Remove" in the "Competency 3" "table_row"

    # Make sure competencies are automatically assigned when a learning plan is created.
    When I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "Edit" "link" in the "Learning Plan (Default)" "table_row"
    And I follow "Workflow"
    And I click on "Custom workflow" "radio"
    And I press "Advanced workflow settings"
    And I switch to "Competencies" tab
    And I click on "Automatically assign by position" "checkbox"
    And I click on "Automatically assign by organisation" "checkbox"
    And I press "Save changes"
    Then I should see "Competency settings successfully updated"
    And I log out

  Scenario: Create a learning plan that pulls through the competencies based on job assignments.

    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I follow "Learning Plans"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "My Learning Plan"
    And I set the field "Plan description" to "A short but meaningful description of My Learning Plan: competencies."
    When I press "Create plan"
    Then I should see "Plan creation successful"

    # Check that the competencies have been added from each of the job assignments.
    When I switch to "Competencies" tab
    Then I should see "Competency 1"
    And I should see "Competency 2"
    And I should see "Competency 3"

  Scenario: Create a learning plan that pulls through no competencies as there's no positions assigned.

    Given I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    And I follow "Learning Plans"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "My Learning Plan"
    And I set the field "Plan description" to "A short but meaningful description of My Learning Plan: competencies."
    When I press "Create plan"
    Then I should see "Plan creation successful"

    # Check that no competencies have been added as there's no positions assigned to the jobs.
    When I follow "Competencies"
    Then I should not see "Competency 1"
    And I should not see "Competency 2"
    And I should not see "Competency 3"

  Scenario: Create a learning plan that has more than one job assignment for the same position.

    Given the following job assignments exist:
      | user     | fullname | position |
      | learner3 | Job 1    | P1       |
      | learner3 | Job 2    | P1       |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner3 | A1     |
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I click on "Edit" "link" in the "Audience 1" "table_row"
    And I switch to "Learning Plan" tab
    And I press "Save and create plans"
    Then I should see "This will create new learning plans for 1 user(s)" in the "Confirm creation of plans" "totaradialogue"
    And I should see "Any learning plans will be created for audience members by an adhoc task on the next cron run"

    When I click on "Save" "button" in the "Confirm creation of plans" "totaradialogue"
    Then I should see "Settings saved"
    And I should see "Any learning plans will be created for audience members by an adhoc task on the next cron run"

    When I trigger cron
    And I am on homepage
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Learning Plan" tab
    Then the following should exist in the "cohortplancreatehistory" table:
      | Template      | User       | Plan status | Number of affected users |
      | Learning Plan |	Admin User | Draft       | 1                        |

    # Check that learner3 has a single competency assigned in their learning plan.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob3 Learner3"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    And I follow "Competencies (1)"
    Then I should see "Competency 1"
    And I should not see "Competency 2"
    And I should not see "Competency 3"

  Scenario: Create a learning plan that pulls through the competencies based on job assignments for organisations.

    Given I log in as "learner4"
    And I click on "Dashboard" in the totara menu
    And I follow "Learning Plans"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "My Learning Plan"
    And I set the field "Plan description" to "A short but meaningful description of My Learning Plan: competencies."
    When I press "Create plan"
    Then I should see "Plan creation successful"

  # Check that the competencies have been added from each of the job assignments.
    When I follow "Competencies"
    Then I should see "Competency 2"
    And I should not see "Competency 1"
    And I should not see "Competency 3"

  Scenario: Create a learning plan that pulls through no competencies as there's no organisations assigned.

    Given I log in as "learner2"
    And I click on "Dashboard" in the totara menu
    And I follow "Learning Plans"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "My Learning Plan"
    And I set the field "Plan description" to "A short but meaningful description of My Learning Plan: competencies."
    When I press "Create plan"
    Then I should see "Plan creation successful"

    # Check that no competencies have been added as there's no organisations assigned to the jobs.
    When I follow "Competencies"
    Then I should not see "Competency 1"
    And I should not see "Competency 2"
    And I should not see "Competency 3"

  Scenario: Create a learning plan that has more than one job assignment for the same organisation.

    Given the following job assignments exist:
      | user     | fullname | organisation |
      | learner3 | Job 1    | O3           |
      | learner3 | Job 2    | O3           |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner3 | A1     |
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I click on "Edit" "link" in the "Audience 1" "table_row"
    And I switch to "Learning Plan" tab
    And I press "Save and create plans"
    Then I should see "This will create new learning plans for 1 user(s)" in the "Confirm creation of plans" "totaradialogue"
    And I should see "Any learning plans will be created for audience members by an adhoc task on the next cron run"

    When I click on "Save" "button" in the "Confirm creation of plans" "totaradialogue"
    Then I should see "Settings saved"
    And I should see "Any learning plans will be created for audience members by an adhoc task on the next cron run"

    When I trigger cron
    And I am on homepage
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I switch to "Learning Plan" tab
    Then the following should exist in the "cohortplancreatehistory" table:
      | Template      | User       | Plan status | Number of affected users |
      | Learning Plan |	Admin User | Draft       | 1                        |

    # Check that learner3 has a single competency assigned in their learning plan.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob3 Learner3"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    And I follow "Competencies (1)"
    Then I should see "Competency 3"
    And I should not see "Competency 1"
    And I should not see "Competency 2"
