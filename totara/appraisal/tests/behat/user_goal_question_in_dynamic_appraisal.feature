@totara @totara_appraisal @javascript
Feature: Access rights to user goal questions in dynamic appraisals
  User goal questions in dynamic appraisals should be accessible
  By the current manager for all appraisals
  And not by the manager on record in the appraisal

  Background:
    Given I am on a totara site
    And the following config values are set as admin:
      | dynamicappraisals | 1 |
    And the following "users" exist:
      | username     | firstname | lastname   | email                    |
      | learner1     | Learner   | One        | learner1@example.com     |
      | learner2     | Learner   | Two        | learner2@example.com     |
      | learner3     | Learner   | Three      | learner3@example.com     |
      | learner4     | Learner   | Four       | learner4@example.com     |
      | oldmgr       | Initial   | Manager    | oldmgr@example.com       |
      | newmgr       | New       | Manager    | newmgr@example.com       |
      | oldteamlead  | Old       | Teamleader | oldteamlead@example.com  |
      | newteamlead  | New       | Teamleader | newteamlead@example.com  |
      | oldappraiser | Old       | Appraiser  | oldappraiser@example.com |
      | newappraiser | New       | Appraiser  | newappraiser@example.com |

    # Set up cohorts.
    Given the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | AppAud |
      | learner2 | AppAud |
      | learner3 | AppAud |
      | learner4 | AppAud |

    # Set up hierarchies.
    Given the following "position" frameworks exist:
      | fullname           | idnumber |
      | Position Framework | posfw    |
    And the following "position" hierarchy exists:
      | fullname   | idnumber | framework |
      | Learners   | pos1     | posfw     |
      | Managers   | pos2     | posfw     |
      | Aporaisers | pos3     | posfw     |
    And the following job assignments exist:
      | user         | fullname         | idnumber         | manager     | managerjaidnumber | appraiser    | position |
      | oldappraiser | Old appraiser JA | Old appraiser JA |             |                   |              | pos3     |
      | newappraiser | New appraiser JA | New appraiser JA |             |                   |              | pos3     |
      | oldteamlead  | Old teamlead JA  | Old teamlead JA  |             |                   |              | pos2     |
      | newteamlead  | New teamlead JA  | New teamlead JA  |             |                   |              | pos2     |
      | oldmgr       | Old manager JA   | Old manager JA   | oldteamlead | Old teamlead JA   |              | pos2     |
      | newmgr       | New manager JA   | New manager JA   | newteamlead | New teamlead JA   |              | pos2     |
      | learner1     | Learner1 JA      | Learner1 JA      | oldmgr      | Old manager JA    | oldappraiser | pos1     |
      | learner2     | Learner2 JA      | Learner2 JA      | oldmgr      | Old manager JA    | oldappraiser | pos1     |
      | learner3     | Learner3 JA      | Learner3 JA      | oldmgr      | Old manager JA    | oldappraiser | pos1     |
      | learner4     | Learner4 JA      | Learner4 JA      | oldmgr      | Old manager JA    | oldappraiser | pos1     |

    # Set up a test appraisal.
    Given the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal1  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name       | timedue                 |
      | Appraisal1  | App1_Stage | 1 January 2050 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage      | name      |
      | Appraisal1  | App1_Stage | App1_Page |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal  | stage      | page      | name     | type  | default | roles             | ExtraInfo |
      | Appraisal1 | App1_Stage | App1_Page | App1-Q1  | goals | 2       | manager,appraiser |           |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id     |
      | Appraisal1  | audience | AppAud |

    # Add a personal goal review item to the appraisal.
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Appraisal1" "link"
    And I switch to "Content" tab
    And I set the field "datatype" to "Goals"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the field "Question" to "Please review your personal goals"
    And I set the field "id_selection_selectpersonal_4" to "1"
    And I set the following fields to these values:
      | Question     | Goals question   |
      | id_roles_1_2 | 1                |
      | id_roles_1_1 | 1                |
      | id_roles_2_2 | 1                |
      | id_roles_2_1 | 1                |
      | id_roles_4_2 | 1                |
      | id_roles_4_1 | 1                |
      | id_roles_8_2 | 1                |
      | id_roles_8_1 | 1                |
    And I press "Save changes"
    And I click on "Activate now" "link"
    And I press "Activate"
    And I log out

    # Create personal goals for each learner.
    Given I log in as "learner1"
    And I click on "Goals" in the totara menu
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name | Personal Goal Learner One |
    And I press "Save changes"
    And I press "Add personal goal"

    Given I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Learner One goal answer |
    And I press "Complete stage"
    And I log out

    Given I log in as "learner2"
    And I click on "Goals" in the totara menu
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name | Personal Goal Learner Two |
    And I press "Save changes"
    And I press "Add personal goal"

    Given I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Learner Two goal answer |
    And I press "Complete stage"
    And I log out

    Given I log in as "learner3"
    And I click on "Goals" in the totara menu
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name | Personal Goal Learner Three |
    And I press "Save changes"
    And I press "Add personal goal"

    Given I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Learner Three goal answer |
    And I press "Complete stage"
    And I log out

    Given I log in as "learner4"
    And I click on "Goals" in the totara menu
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name | Personal Goal Learner Four |
    And I press "Save changes"
    And I press "Add personal goal"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Learner Four goal answer |
    And I press "Complete stage"
    And I log out

    # Immediate manager does appraisals for 2 learners.
    Given I log in as "oldmgr"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Old Manager Learner One goal answer |
    And I press "Complete stage"

    Given I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Old Manager Learner Four goal answer |
    And I press "Complete stage"
    And I log out

    # Team leader does appraisals for 2 learners.
    Given I log in as "oldteamlead"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Old Teamleader Learner One goal answer |
    And I press "Complete stage"

    Given I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Old Teamleader Learner Four goal answer |
    And I press "Complete stage"
    And I log out

    # Appraiser does appraisals for 2 learners.
    Given I log in as "oldappraiser"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Old Appraiser Learner One goal answer |
    And I press "Complete stage"

    Given I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | Old Appraiser Learner Four goal answer |
    And I press "Complete stage"
    And I log out

  # ----------------------------------------------------------------------------
  Scenario: Change immediate manager after completing dynamic appraisal
    # New manager should not see any appraisals yet.
    When I log in as "newmgr"
    Then I should not see "All Appraisals" in the totara menu

    # Confirm that old manager has viewing rights to the goal question for all 4
    # learners.
    When I log out
    And I log in as "oldmgr"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Completed" in the "Learner One" "table_row"
    And I should see "Active" in the "Learner Two" "table_row"
    And I should see "Active" in the "Learner Three" "table_row"
    And I should see "Completed" in the "Learner Four" "table_row"

    When I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner One goal answer"
    And I should see "Old Teamleader Learner One goal answer"
    And I should see "Old Appraiser Learner One goal answer"
    And I should see "Personal Goal Learner One"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Two" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Two goal answer"
    And I should not see "Old Teamleader Learner Two goal answer"
    And I should not see "Old Appraiser Learner Two goal answer"
    And I should see "Personal Goal Learner Two"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Three" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Three goal answer"
    And I should not see "Old Teamleader Learner Three goal answer"
    And I should not see "Old Appraiser Learner Three goal answer"
    And I should see "Personal Goal Learner Three"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner Four goal answer"
    And I should see "Old Teamleader Learner Four goal answer"
    And I should see "Old Appraiser Learner Four goal answer"
    And I should see "Personal Goal Learner Four"

    # Confirm that old teamlead has viewing rights to the goal question for all
    # 4 learners.
    When I log out
    And I log in as "oldteamlead"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Completed" in the "Learner One" "table_row"
    And I should see "Active" in the "Learner Two" "table_row"
    And I should see "Active" in the "Learner Three" "table_row"
    And I should see "Completed" in the "Learner Four" "table_row"

    When I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner One goal answer"
    And I should see "Old Teamleader Learner One goal answer"
    And I should see "Old Appraiser Learner One goal answer"
    And I should see "Personal Goal Learner One"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Two" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Two goal answer"
    And I should not see "Old Teamleader Learner Two goal answer"
    And I should not see "Old Appraiser Learner Two goal answer"
    And I should see "Personal Goal Learner Two"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Three" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Three goal answer"
    And I should not see "Old Teamleader Learner Three goal answer"
    And I should not see "Old Appraiser Learner Three goal answer"
    And I should see "Personal Goal Learner Three"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner Four goal answer"
    And I should see "Old Teamleader Learner Four goal answer"
    And I should see "Old Appraiser Learner Four goal answer"
    And I should see "Personal Goal Learner Four"

    # Change learner reporting hierarchy for 2 learners.
    When I log out
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "Learner1 JA"
    And I press "Choose manager"
    And I click on "New Manager (newmgr@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "New manager JA" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    Then I should see "New Manager"

    When I press "Update job assignment"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner Two"
    And I follow "Learner2 JA"
    And I press "Choose manager"
    And I click on "New Manager (newmgr@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "New manager JA" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    Then I should see "New Manager"

    Given I press "Update job assignment"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I log out

    # Now new manager can see Learner 1 and 2's appraisals. Old manager cannot
    # see either of these appraisals.
    When I log in as "newmgr"
    And I click on "All Appraisals" in the totara menu
    And I should not see "Learner Three"
    And I should not see "Learner Four"
    And I should see "Completed" in the "Learner One" "table_row"
    And I should see "Active" in the "Learner Two" "table_row"

    When I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner One goal answer"
    And I should see "Old Teamleader Learner One goal answer"
    And I should see "Old Appraiser Learner One goal answer"
    And I should see "Personal Goal Learner One"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Two" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Two goal answer"
    And I should not see "Old Teamleader Learner Two goal answer"
    And I should not see "Old Appraiser Learner Two goal answer"
    And I should see "Personal Goal Learner Two"

    # The old manager cannot see Learner 1 and 2's appraisal. But he can see the
    # rest.
    When I log out
    And I log in as "oldmgr"
    And I click on "All Appraisals" in the totara menu
    Then I should not see "Learner One"
    Then I should not see "Learner Two"
    And I should see "Active" in the "Learner Three" "table_row"
    And I should see "Completed" in the "Learner Four" "table_row"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Three" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Three goal answer"
    And I should not see "Old Teamleader Learner Three goal answer"
    And I should not see "Old Appraiser Learner Three goal answer"
    And I should see "Personal Goal Learner Three"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner Four goal answer"
    And I should see "Old Teamleader Learner Four goal answer"
    And I should see "Old Appraiser Learner Four goal answer"
    And I should see "Personal Goal Learner Four"

    # The old teamlead cannot see Learner 1 and 2's appraisal.
    When I log out
    And I log in as "oldteamlead"
    And I click on "All Appraisals" in the totara menu
    Then I should not see "Learner One"
    Then I should not see "Learner Two"
    And I should see "Active" in the "Learner Three" "table_row"
    And I should see "Completed" in the "Learner Four" "table_row"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Three" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Three goal answer"
    And I should not see "Old Teamleader Learner Three goal answer"
    And I should not see "Old Appraiser Learner Three goal answer"
    And I should see "Personal Goal Learner Three"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner Four goal answer"
    And I should see "Old Teamleader Learner Four goal answer"
    And I should see "Old Appraiser Learner Four goal answer"
    And I should see "Personal Goal Learner Four"

    # The old appraiser can see all the appraisals.
    When I log out
    And I log in as "oldappraiser"
    And I click on "All Appraisals" in the totara menu
    And I should see "Completed" in the "Learner One" "table_row"
    And I should see "Active" in the "Learner Two" "table_row"
    And I should see "Active" in the "Learner Three" "table_row"
    And I should see "Completed" in the "Learner Four" "table_row"

    When I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner One goal answer"
    And I should see "Old Teamleader Learner One goal answer"
    And I should see "Old Appraiser Learner One goal answer"
    And I should see "Personal Goal Learner One"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Two" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Two goal answer"
    And I should not see "Old Teamleader Learner Two goal answer"
    And I should not see "Old Appraiser Learner Two goal answer"
    And I should see "Personal Goal Learner Two"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Three" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Three goal answer"
    And I should not see "Old Teamleader Learner Three goal answer"
    And I should not see "Old Appraiser Learner Three goal answer"
    And I should see "Personal Goal Learner Three"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner Four goal answer"
    And I should see "Old Teamleader Learner Four goal answer"
    And I should see "Old Appraiser Learner Four goal answer"
    And I should see "Personal Goal Learner Four"
    And I log out

  # ----------------------------------------------------------------------------
  Scenario: Change appraiser after completing dynamic appraisal
    # New appraiser should not see any appraisals yet.
    When I log in as "newappraiser"
    Then I should not see "All Appraisals" in the totara menu

    # Confirm that old appraiser has viewing rights to the goal question for all
    # 4 learners.
    When I log out
    And I log in as "oldappraiser"
    And I click on "All Appraisals" in the totara menu
    Then I should see "Completed" in the "Learner One" "table_row"
    And I should see "Active" in the "Learner Two" "table_row"
    And I should see "Active" in the "Learner Three" "table_row"
    And I should see "Completed" in the "Learner Four" "table_row"

    When I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner One goal answer"
    And I should see "Old Teamleader Learner One goal answer"
    And I should see "Old Appraiser Learner One goal answer"
    And I should see "Personal Goal Learner One"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Two" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Two goal answer"
    And I should not see "Old Teamleader Learner Two goal answer"
    And I should not see "Old Appraiser Learner Two goal answer"
    And I should see "Personal Goal Learner Two"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Three" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Three goal answer"
    And I should not see "Old Teamleader Learner Three goal answer"
    And I should not see "Old Appraiser Learner Three goal answer"
    And I should see "Personal Goal Learner Three"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner Four goal answer"
    And I should see "Old Teamleader Learner Four goal answer"
    And I should see "Old Appraiser Learner Four goal answer"
    And I should see "Personal Goal Learner Four"

    # Change learner reporting hierarchy for 2 learners.
    When I log out
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I follow "Learner1 JA"
    And I press "Choose appraiser"
    And I click on "New Appraiser (newappraiser@example.com)" "link" in the "Choose appraiser" "totaradialogue"
    And I click on "OK" "button" in the "Choose appraiser" "totaradialogue"
    Then I should see "New Appraiser"

    When I press "Update job assignment"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner Two"
    And I follow "Learner2 JA"
    And I press "Choose appraiser"
    And I click on "New Appraiser (newappraiser@example.com)" "link" in the "Choose appraiser" "totaradialogue"
    And I click on "OK" "button" in the "Choose appraiser" "totaradialogue"
    Then I should see "New Appraiser"

    Given I press "Update job assignment"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I log out

    # New appraiser should only see Learner 1 and 2's appraisals.
    When I log in as "newappraiser"
    And I click on "All Appraisals" in the totara menu
    And I should not see "Learner Three"
    And I should not see "Learner Four"
    And I should see "Completed" in the "Learner One" "table_row"
    And I should see "Active" in the "Learner Two" "table_row"

    When I click on "Appraisal1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner One goal answer"
    And I should see "Old Teamleader Learner One goal answer"
    And I should see "Old Appraiser Learner One goal answer"
    And I should see "Personal Goal Learner One"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Two" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Two goal answer"
    And I should not see "Old Teamleader Learner Two goal answer"
    And I should not see "Old Appraiser Learner Two goal answer"
    And I should see "Personal Goal Learner Two"

    # The old appraiser cannot see Learner 1 and 2's appraisal. But he can see
    # the rest.
    When I log out
    And I log in as "oldappraiser"
    And I click on "All Appraisals" in the totara menu
    Then I should not see "Learner One"
    Then I should not see "Learner Two"
    And I should see "Active" in the "Learner Three" "table_row"
    And I should see "Completed" in the "Learner Four" "table_row"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Three" "table_row"
    And I press "Start"
    Then I should not see "Old Manager Learner Three goal answer"
    And I should not see "Old Teamleader Learner Three goal answer"
    And I should not see "Old Appraiser Learner Three goal answer"
    And I should see "Personal Goal Learner Three"

    When I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Learner Four" "table_row"
    And I press "View"
    Then I should see "Old Manager Learner Four goal answer"
    And I should see "Old Teamleader Learner Four goal answer"
    And I should see "Old Appraiser Learner Four goal answer"
    And I should see "Personal Goal Learner Four"
    And I log out
