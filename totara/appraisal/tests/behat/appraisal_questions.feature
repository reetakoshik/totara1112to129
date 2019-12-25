@totara @totara_appraisal @javascript
Feature: Sanity checks on questions appearing in appraisals
  Test questions in an appraisal

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | learner   | Learner   | One      | learner@example.com   |
      | mgr       | Manager   | One      | mgr@example.com       |
      | mgr2      | Manager   | Two      | mgr2@example.com      |
      | appraiser | Appraiser | One      | appraiser@example.com |
    And the following job assignments exist:
      | user    | fullname   | idnumber | manager | managerjaidnumber | appraiser |
      | mgr2    | mgr2 ja    | m2ja     |         |                   |           |
      | mgr     | mgr ja     | mja      | mgr2    | m2ja              |           |
      | learner | learner ja | lja      | mgr     | mja               | appraiser |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user    | cohort |
      | learner | AppAud |
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name         |
      | Appraisal #1 |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal    | name   | timedue                          |
      | Appraisal #1 | Stage1 | ## tomorrow ## j F Y 23:59:59 ## |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal    | stage  | name               |
      | Appraisal #1 | Stage1 | Page 1 (learner)   |
      | Appraisal #1 | Stage1 | Page 2 (manager)   |
      | Appraisal #1 | Stage1 | Page 3 (manager2)  |
      | Appraisal #1 | Stage1 | Page 4 (appraiser) |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal    | type     | id     |
      | Appraisal #1 | audience | AppAud |

    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Appraisal #1" "link"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I click on "Stage1" "link" in the ".appraisal-stages" "css_element"

    # Page 1 (learner)
    And I set the field "id_datatype" to "Long text"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question     | student long text |
      | id_roles_1_2 | 1                 |
      | id_roles_1_6 | 1                 |
      | id_roles_1_1 | 1                 |
      | id_roles_2_2 | 0                 |
      | id_roles_2_6 | 0                 |
      | id_roles_2_1 | 1                 |
      | id_roles_4_2 | 0                 |
      | id_roles_4_6 | 0                 |
      | id_roles_4_1 | 1                 |
      | id_roles_8_2 | 0                 |
      | id_roles_8_6 | 0                 |
      | id_roles_8_1 | 1                 |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

    Given I set the field "id_datatype" to "Multiple choice (one answer)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question                   | student multisingle |
      | choice[0][option]          | ms1                 |
      | choice[1][option]          | ms2                 |
      | choice[2][option]          | ms3                 |
      | Same as preceding question | 1                   |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

    Given I set the field "id_datatype" to "Multiple choice (several answers)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question                   | student multimulti |
      | choice[0][option]          | mm1                |
      | choice[1][option]          | mm2                |
      | choice[2][option]          | mm3                |
      | Same as preceding question | 1                  |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

    # Page 2 (manager)
    Given I follow "Page 2 (manager)"
    And I set the field "id_datatype" to "Rating (custom scale)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question          | mgr custom rating |
      | choice[0][option] | cr1               |
      | choice[0][score]  | 1                 |
      | choice[1][option] | cr2               |
      | choice[1][score]  | 2                 |
      | choice[2][option] | cr3               |
      | choice[2][score]  | 3                 |
      | id_roles_1_2      | 0                 |
      | id_roles_1_6      | 0                 |
      | id_roles_1_1      | 1                 |
      | id_roles_2_2      | 1                 |
      | id_roles_2_6      | 1                 |
      | id_roles_2_1      | 1                 |
      | id_roles_4_2      | 0                 |
      | id_roles_4_6      | 0                 |
      | id_roles_4_1      | 1                 |
      | id_roles_8_2      | 0                 |
      | id_roles_8_6      | 0                 |
      | id_roles_8_1      | 1                 |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

    Given I set the field "id_datatype" to "Redisplay previous question"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Redisplay question | student long text (Long text) |
      | id_roles_1_2       | 0                             |
      | id_roles_1_1       | 0                             |
      | id_roles_2_2       | 1                             |
      | id_roles_2_1       | 1                             |
      | id_roles_4_2       | 1                             |
      | id_roles_4_1       | 1                             |
      | id_roles_8_2       | 1                             |
      | id_roles_8_1       | 1                             |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

    # Page 3 (manager2)
    Given I follow "Page 3 (manager2)"
    And I set the field "id_datatype" to "Rating (numeric scale)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question     | mgr2 rating numeric |
      | From         | 1                   |
      | To           | 3                   |
      | id_roles_1_2 | 0                   |
      | id_roles_1_6 | 0                   |
      | id_roles_1_1 | 1                   |
      | id_roles_2_2 | 0                   |
      | id_roles_2_6 | 0                   |
      | id_roles_2_1 | 1                   |
      | id_roles_4_2 | 1                   |
      | id_roles_4_6 | 1                   |
      | id_roles_4_1 | 1                   |
      | id_roles_8_2 | 0                   |
      | id_roles_8_6 | 0                   |
      | id_roles_8_1 | 1                   |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

    Given I set the field "id_datatype" to "Redisplay previous question"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Redisplay question | mgr custom rating |
      | id_roles_1_2       | 1                 |
      | id_roles_1_1       | 1                 |
      | id_roles_2_2       | 0                 |
      | id_roles_2_1       | 0                 |
      | id_roles_4_2       | 1                 |
      | id_roles_4_1       | 1                 |
      | id_roles_8_2       | 1                 |
      | id_roles_8_1       | 1                 |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

    # Page 4 (appraiser)
    Given I follow "Page 4 (appraiser)"
    And I set the field "id_datatype" to "Short text"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question     | appraiser short text |
      | id_roles_1_2 | 0                    |
      | id_roles_1_6 | 0                    |
      | id_roles_1_1 | 1                    |
      | id_roles_2_2 | 0                    |
      | id_roles_2_6 | 0                    |
      | id_roles_2_1 | 1                    |
      | id_roles_4_2 | 0                    |
      | id_roles_4_6 | 0                    |
      | id_roles_4_1 | 1                    |
      | id_roles_8_2 | 1                    |
      | id_roles_8_6 | 1                    |
      | id_roles_8_1 | 1                    |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

    Given I set the field "id_datatype" to "Redisplay previous question"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Redisplay question | mgr2 rating numeric |
      | id_roles_1_2       | 1                   |
      | id_roles_1_1       | 1                   |
      | id_roles_2_2       | 1                   |
      | id_roles_2_1       | 1                   |
      | id_roles_4_2       | 0                   |
      | id_roles_4_1       | 0                   |
      | id_roles_8_2       | 1                   |
      | id_roles_8_1       | 1                   |
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"

  Scenario: Fill in appraisal and see questions are correctly displayed in the completed appraisal
    # Fill in appraisal as various roles.
    Given I click on "Activate now" "link"
    And I press "Activate"
    And I log out
    And I log in as "learner"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I set the field "Your answer" to "student long text answer"
    And I click on "ms2" "radio"
    And I click on "mm1" "checkbox"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    Given I click on "Complete stage" "button"
    And I log out
    And I log in as "mgr"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal #1" "link" in the "Learner One" "table_row"

    When I press "Start"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "cr3" "radio"
    And I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    Given I click on "Complete stage" "button"
    And I log out
    And I log in as "mgr2"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal #1" "link" in the "Learner One" "table_row"

    When I press "Start"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    Given I click on "Complete stage" "button"
    And I log out
    And I log in as "appraiser"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal #1" "link" in the "Learner One" "table_row"

    When I press "Start"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Next" "button"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    Given I set the field "Your answer" to "appraiser short text answer"
    And I click on "Complete stage" "button"

    # View as learner
    And I log out
    And I log in as "learner"
    And I click on "Latest Appraisal" in the totara menu

    When I press "View"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 2 (manager)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 3 (manager2)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 4 (appraiser)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    # View as manager
    When I log out
    And I log in as "mgr"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal #1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 2 (manager)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 3 (manager2)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 4 (appraiser)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    # View as manager's manager
    When I log out
    And I log in as "mgr2"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal #1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 2 (manager)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 3 (manager2)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 4 (appraiser)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    # View as appraiser
    When I log out
    And I log in as "appraiser"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal #1" "link" in the "Learner One" "table_row"
    And I press "View"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 2 (manager)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 3 (manager2)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"

    When I click on "Page 4 (appraiser)" "link"
    Then I should not see "No response"
    And I should not see "User selected nothing"