@totara @totara_question @totara_appraisal @javascript
Feature: Custom Fields on goals are editable appraisals when they should be
  The custom fields should be editable if the user is currently answering the appraisal
  and if they have permission to edit the custom field.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username        | firstname      | lastname   | email             |
      | learner         | learner        | one        | user1@example.com |
      | manager         | manager        | two        | user2@example.com |
      | managersmanager | managersmanager| three      | user3@example.com |
      | appraiser       | appraiser      | three      | user4@example.com |

    And the following job assignments exist:
      | user    | fullname     | idnumber | manager         | appraiser |
      | manager | Manager Job  | ja1      | managersmanager |           |
      | learner | Learner1 Job | ja2      | manager         | appraiser |

    And the following "cohorts" exist:
      | name         | idnumber |
      | Audience One | AUD1     |
    And the following "cohort members" exist:
      | user    | cohort |
      | learner | AUD1   |

    And I navigate to "Manage personal goal types" node in "Site administration > Goals"
    And I press "Add a new personal goal type"
    And I set the following fields to these values:
      | Type full name      | Personal Goal Type 1 |
      | Goal type ID number | PGT1                 |
    # Save the changes.
    And I press "Save changes"
    And I follow "Personal Goal Type 1"
    And I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Text input 1 |
      | Short name (must be unique) | textinput1   |
    And I press "Save changes"
    And I log out
    And I log in as "learner"
    And I follow "Performance"
    And I follow "Goals"

    And I click on "Add personal goal" "button"
    And I set the field "Name" to "My Goal"
    And I set the field "Type" to "Personal Goal Type 1"
    And I click on "Save changes" "button"

    # Set up an appraisal using the data generator.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal1  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name       | timedue                 |
      | Appraisal1  | App1_Stage | 1 January 2030 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage      | name      |
      | Appraisal1  | App1_Stage | App1_Page |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id     |
      | Appraisal1  | audience | AUD1   |

    # the following is because I couldn't get the data generator to work with a review question
    And I log out
    And I log in as "admin"
    And I navigate to "Appraisals > Manage appraisals" in site administration
    And I click on "Appraisal1" "link"
    And I switch to "Content" tab
    And I set the field "datatype" to "goals"
    And I click on "input[value='Add']" "css_element"
    And I set the field "name" to "Question"
    And I click on "#id_selection_selectpersonal_4" "css_element"
    And I set the field "includepersonal" to "1"
    # Learner can answer
    And I set the field "roles[1][2]" to "1"
    # Learner can view other people answers
    And I set the field "roles[1][1]" to "1"
    # Manager can view other people answers
    And I set the field "roles[2][1]" to "1"
    # Managers Manager can view other people answers
    And I set the field "roles[4][1]" to "1"
    # Appraiser can view other people answers
    And I set the field "roles[8][1]" to "1"

  Scenario: The custom fields on goals should only be active for people who are answering the appraisal
    Given I click on "Save changes" "button"
    And I click on "Activate now" "link"
    And I click on "Activate" "button"
    And I log out

    When I log in as "learner"
    And I follow "Performance"
    And I follow "Latest Appraisal"
    And I press "Start"

    Then the "Text input 1" "field" should not be readonly

    When I log out
    And I log in as "manager"
    And I follow "Performance"
    And I follow "All Appraisals"
    And I click on "Appraisal1" "link"
    And I press "View"

    Then the "Text input 1" "field" should be readonly

    When I log out
    And I log in as "managersmanager"
    And I follow "Performance"
    And I follow "All Appraisals"
    And I click on "Appraisal1" "link"
    And I press "View"

    Then the "Text input 1" "field" should be readonly

    When I log out
    And I log in as "appraiser"
    And I follow "Performance"
    And I follow "All Appraisals"
    And I click on "Appraisal1" "link"
    And I press "View"

    Then the "Text input 1" "field" should be readonly

  Scenario: The custom fields on goals should not be editable when the user cannot edit the custom fields
    # Manager can answer
    Given I set the field "roles[2][2]" to "1"
    # Managers Manager can answer
    And I set the field "roles[4][2]" to "1"
    # Appraiser can answer
    And I set the field "roles[8][2]" to "1"
    And I click on "Save changes" "button"
    And I click on "Activate now" "link"
    And I click on "Activate" "button"
    And I log out

    When I log in as "learner"
    And I follow "Performance"
    And I follow "Latest Appraisal"
    And I press "Start"

    Then the "Text input 1" "field" should not be readonly

    When I log out
    And I log in as "manager"
    And I follow "Performance"
    And I follow "All Appraisals"
    And I click on "Appraisal1" "link"
    And I press "Start"

    Then the "Text input 1" "field" should not be readonly

    When I log out
    And I log in as "managersmanager"
    And I follow "Performance"
    And I follow "All Appraisals"
    And I click on "Appraisal1" "link"
    And I press "Start"

    Then the "Text input 1" "field" should be readonly

    When I log out
    And I log in as "appraiser"
    And I follow "Performance"
    And I follow "All Appraisals"
    And I click on "Appraisal1" "link"
    And I press "Start"

    Then the "Text input 1" "field" should be readonly
