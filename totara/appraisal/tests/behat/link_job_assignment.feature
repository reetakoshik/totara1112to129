@totara @totara_appraisal @javascript
Feature: Link appraisal with an appraisee job assignment.
  In order to progress through an appraisal
  As an appraisee
  I must be able to link my preferred job to the appraisal

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname  | lastname   | email                  |
      | goldfinger | Auric      | Goldfinger | goldfinger@example.com |
      | oddjob     | Harold     | Sakata     | oddjob@example.com     |
      | spectre    | Spy        | Network    | spectre@example.com    |
      | bond007    | James      | Bond       | bond007@example.com    |
      | m          | Judi       | Dench      | m@example.com          |
      | moneypenny | Eve        | Moneypenny | moneypenny@example.com |
      | q          | John       | Cleese     | q@example.com          |
    And the following "position" frameworks exist:
      | fullname           | idnumber |
      | Position Framework | posfw    |
    And the following "position" hierarchy exists:
      | fullname       | idnumber | framework |
      | Normal Day Job | pos1     | posfw     |
      | Secret Job     | pos2     | posfw     |
    And the following job assignments exist:
      | user       | fullname                        | idnumber | manager    | appraiser | position |
      | oddjob     | Nondescript Butler              | 0001     | goldfinger |           | pos1     |
      | oddjob     | Lethal Henchman in a Bowler Hat | 0002     | goldfinger | spectre   | pos2     |
      | moneypenny | Comely Secretary                | 0003     | m          | q         | pos1     |
      | m          | Head Honcho                     | 0004     |            |           | pos1     |
      | q          | Tech Whizkid                    | 0005     | m          |           | pos1     |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user       | cohort |
      | oddjob     | AppAud |
      | moneypenny | AppAud |
      | bond007    | AppAud |
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name                |
      | Job assignment test |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal           | name   | timedue                 |
      | Job assignment test | Stage1 | 1 January 2020 23:59:59 |
      | Job assignment test | Stage2 | 1 January 2030 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal           | stage  | name        |
      | Job assignment test | Stage1 | Stage1 Page |
      | Job assignment test | Stage2 | Stage2 Page |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal           | stage  | page        | name    | type          | default | ExtraInfo                          |
      | Job assignment test | Stage1 | Stage1 Page | S1-desc | ratingnumeric | 5       | Range:1-10,Display:slider          |
      | Job assignment test | Stage2 | Stage2 Page | S2-desc | ratingnumeric | 5       | Range:1-10,Display:slider          |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal           | type     | id     |
      | Job assignment test | audience | AppAud |

    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Activate" "link" in the "Job assignment test" "table_row"
    And I press "Activate"
    And I log out


  Scenario: link job assignment when first viewing appraisal
    When I log in as "bond007"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Unnamed job assignment"
    And I should see "Learner: James Bond"
    And I should see "Manager: Role currently empty"
    And I should see "Appraiser: Role currently empty"
    And the "Start" "button" should be enabled

    When I log out
    And I log in as "moneypenny"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Comely Secretary"
    And I should see "Learner: Eve Moneypenny"
    And I should see "Manager: Judi Dench"
    And I should see "Appraiser: John Cleese"
    And the "Start" "button" should be enabled

    When I log out
    And I log in as "oddjob"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Select a job assignment to link to this appraisal"
    And I should see "Nondescript Butler"
    And I should see "Lethal Henchman in a Bowler Hat"
    And I should see "Learner: Harold Sakata"
    And I should see "Manager: Role currently empty"
    And I should see "Appraiser: Role currently empty"
    And "Start" "button" should not exist

    When I click on "Lethal Henchman in a Bowler Hat" "option"
    Then I should see "Lethal Henchman in a Bowler Hat"
    And I should not see "Select a job assignment to link to this appraisal"
    And I should not see "Nondescript Butler"
    And I should see "Learner: Harold Sakata"
    And I should see "Manager: Auric Goldfinger"
    And I should see "Appraiser: Spy Network"
    And the "Start" "button" should be enabled


  Scenario: changed job assignment details
    When I log in as "bond007"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Unnamed job assignment"
    And I should see "Learner: James Bond"
    And I should see "Manager: Role currently empty"
    And I should see "Appraiser: Role currently empty"
    And the "Start" "button" should be enabled

    Given I log out
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "James Bond" "link"
    And I click on "Unnamed job assignment (ID: 1)" "link"
    And I set the following fields to these values:
      | Full name    | Supposed Salesman, Universal Exports |
      | Short name   | Supposed Salesman, Universal Exports |
    And I press "Choose manager"
    And I click on "Judi Dench" "link" in the "Choose manager" "totaradialogue"
    And I click on "Head Honcho" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I press "Choose appraiser"
    And I click on "John Cleese" "link" in the "Choose appraiser" "totaradialogue"
    And I click on "OK" "button" in the "Choose appraiser" "totaradialogue"
    And I click on "Update job assignment" "button"

    When I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name    | Spy, 007 Branch |
      | Short name   | Spy, 007 Branch |
      | ID Number    | 12345           |
    And I click on "Add job assignment" "button"
    Then I should see "Supposed Salesman, Universal Exports"
    And I should see "Spy, 007 Branch"

    Given I run the "\totara_appraisal\task\update_learner_assignments_task" task
    And I am on site homepage
    And I log out
    And I log in as "bond007"

    When I click on "Latest Appraisal" in the totara menu
    Then I should see "Supposed Salesman, Universal Exports"
    And I should not see "Spy, 007 Branch"
    And I should see "Learner: James Bond"
    And I should see "Manager: Judi Dench"
    And I should see "Appraiser: John Cleese"
    And the "Start" "button" should be enabled

  Scenario: deleted job assignment details
    When I log in as "bond007"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Unnamed job assignment"
    And I should see "Learner: James Bond"
    And I should see "Manager: Role currently empty"
    And I should see "Appraiser: Role currently empty"
    And the "Start" "button" should be enabled

    Given I log out
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "James Bond" "link"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name    | Supposed Salesman, Universal Exports |
      | Short name   | Supposed Salesman, Universal Exports |
      | ID Number    | 12344                                |
    And I press "Choose manager"
    And I click on "Judi Dench" "link" in the "Choose manager" "totaradialogue"
    And I click on "Head Honcho" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I press "Choose appraiser"
    And I click on "John Cleese" "link" in the "Choose appraiser" "totaradialogue"
    And I click on "OK" "button" in the "Choose appraiser" "totaradialogue"
    And I click on "Add job assignment" "button"

    Given I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name    | Spy, 007 Branch |
      | Short name   | Spy, 007 Branch |
      | ID Number    | 12345           |
    And I click on "Add job assignment" "button"

    When I click the delete icon for the "Unnamed job assignment (ID: 1)" job assignment
    And I click on "Yes, delete" "button"
    Then I should see "Supposed Salesman, Universal Exports"
    And I should see "Spy, 007 Branch"
    And I should not see "Unnamed job assignment (ID: 1)"

    Given I run the "\totara_appraisal\task\update_learner_assignments_task" task
    And I am on site homepage
    And I log out
    And I log in as "bond007"

    When I click on "Latest Appraisal" in the totara menu
    Then I should see "Select a job assignment to link to this appraisal"
    And I should see "Supposed Salesman, Universal Exports"
    And I should see "Spy, 007 Branch"
    And I should see "Learner: James Bond"
    And I should see "Manager: Role currently empty"
    And I should see "Appraiser: Role currently empty"
    And "Start" "button" should not exist

  Scenario: replaced job assignment details
    When I log in as "bond007"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Unnamed job assignment"
    And I should see "Learner: James Bond"
    And I should see "Manager: Role currently empty"
    And I should see "Appraiser: Role currently empty"
    And the "Start" "button" should be enabled

    Given I log out
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "James Bond" "link"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name    | Supposed Salesman, Universal Exports |
      | Short name   | Supposed Salesman, Universal Exports |
      | ID Number    | 12344                                |
    And I press "Choose manager"
    And I click on "Judi Dench" "link" in the "Choose manager" "totaradialogue"
    And I click on "Head Honcho" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I press "Choose appraiser"
    And I click on "John Cleese" "link" in the "Choose appraiser" "totaradialogue"
    And I click on "OK" "button" in the "Choose appraiser" "totaradialogue"
    And I click on "Add job assignment" "button"

    When I click the delete icon for the "Unnamed job assignment (ID: 1)" job assignment
    And I click on "Yes, delete" "button"
    Then I should see "Supposed Salesman, Universal Exports"
    And I should not see "Unnamed job assignment (ID: 1)"

    Given I run the "\totara_appraisal\task\update_learner_assignments_task" task
    And I am on site homepage
    And I log out
    And I log in as "bond007"

    When I click on "Latest Appraisal" in the totara menu
    Then I should see "Supposed Salesman, Universal Exports"
    And I should not see "Unnamed job assignment (ID: 1)"
    And I should see "Learner: James Bond"
    And I should see "Manager: Judi Dench"
    And I should see "Appraiser: John Cleese"
    And the "Start" "button" should be enabled
