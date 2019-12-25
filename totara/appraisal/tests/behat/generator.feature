@totara @totara_appraisal @totara_generator
Feature: Verify appraisal data generators.

  Background:
    # Set up the deta we need for appraisals.
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
      | learner2 | firstname2 | lastname2 | learner2@example.com |
      | learner3 | firstname3 | lastname3 | learner3@example.com |
      | learner4 | firstname4 | lastname4 | learner4@example.com |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname                 | idnumber |
      | Organisation Framework 1 | OF1      |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname       | idnumber | org_framework |
      | Organisation 1 | O1       | OF1           |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname             | idnumber |
      | Position Framework 1 | PF1      |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname   | idnumber | pos_framework |
      | Position 1 | P1       | PF1           |
    And the following job assignments exist:
      | user     | position | organisation |
      | learner1 |          | O1           |
      | learner2 | P1       |              |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner3 | A1     |

    # Set up an appraisal using the data generator.
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal 1 |
    # NOTE: all behat dates are in Perth timezone by default - 1 second before the end of day!
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name      | timedue                |
      | Appraisal 1 | Stage 1-1 | 2082729599             |
      | Appraisal 1 | Stage 1-2 | 1 January 2036 23:59:59|
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage     | name       |
      | Appraisal 1 | Stage 1-1 | Page 1-1-1 |
      | Appraisal 1 | Stage 1-1 | Page 1-1-2 |
      | Appraisal 1 | Stage 1-2 | Page 1-2-1 |
      | Appraisal 1 | Stage 1-2 | Page 1-2-2 |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal   | stage     | page       | name         |
      | Appraisal 1 | Stage 1-1 | Page 1-1-1 | Question 1-1-1-1 |
      | Appraisal 1 | Stage 1-1 | Page 1-1-1 | Question 1-1-1-2 |
      | Appraisal 1 | Stage 1-1 | Page 1-1-2 | Question 1-1-2-1 |
      | Appraisal 1 | Stage 1-1 | Page 1-1-2 | Question 1-1-2-2 |
    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type         | id |
      | Appraisal 1 | audience     | A1 |
      | Appraisal 1 | organisation | O1 |
      | Appraisal 1 | position     | P1 |
    And the following "messages" exist in "totara_appraisal" plugin:
      | appraisal   | recipients                                     | name      | stage     | event                      | delta | deltaperiod | messageto | completeby |
      | Appraisal 1 | all                                            | Message 1 |           |                            |       |             |           |            |
      | Appraisal 1 |                                                | Message 2 |           |                            |       |             |           |            |
      | Appraisal 1 | learner, manager, manager's manager, appraiser | Message 3 |           |                            |       |             |           |            |
      | Appraisal 1 | learner, manager                               | Message 4 |           |                            |       |             |           |            |
      | Appraisal 1 | learner, manager                               | Message 5 | Stage 1-1 | appraisal stage completion | 2     | weeks       | each      |            |
      | Appraisal 1 | learner, manager, appraiser                    | Message 6 | Stage 1-2 | stage_due                  | -5    | days        |           | complete   |

  @javascript
  Scenario: Verify appraisals have been created correctly.

    Given I log in as "admin"
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I follow "Appraisal 1"
    Then I should see "Appraisal 1"

    When I follow "Content"
    Then I should see "Stage 1-1"
    And I should see "31 Dec 2035"
    And I should see "1 Jan 2036"
    And I should see "Page 1-1-1"
    And I should see "Page 1-1-2"
    And I should see "Question 1-1-1-1"
    And I should see "Question 1-1-1-2"

    When I follow "Page 1-1-2"
    Then I should see "Question 1-1-2-1"
    And I should see "Question 1-1-2-2"

    When I follow "Assignments"
    Then I should see "Audience 1"
    And I should see "Organisation 1"
    And I should see "Position 1"
    And I should see "firstname1 lastname1"
    And I should see "firstname2 lastname2"
    And I should see "firstname3 lastname3"

    When I click on "Messages" "link" in the ".tabtree" "css_element"
    Then I should see "Message 1" in the "1" "table_row"
    Then I should see "Appraisal activation" in the "1" "table_row"
    Then I should see "Immediate" in the "1" "table_row"
    Then I should see "Learner" in the "1" "table_row"
    Then I should see "Manager" in the "1" "table_row"
    Then I should see "Manager's Manager" in the "1" "table_row"
    Then I should see "Appraiser" in the "1" "table_row"
    # Message 2 and 3 are the same as 1 just with different recipients.
    And I should see "Message 2"
    And I should see "Message 3"
    # Message 4 should be the same only with 2 recipients.
    And I should see "Message 4" in the "4" "table_row"
    And I should see "Learner" in the "4" "table_row"
    And I should see "Manager" in the "4" "table_row"
    And I should not see "Manager's Manager" in the "4" "table_row"
    And I should not see "Appraiser" in the "4" "table_row"
    # Message 5 is a stage based message.
    And I should see "Message 5" in the "5" "table_row"
    And I should see "Stage completion" in the "5" "table_row"
    And I should see "2 weeks after event" in the "5" "table_row"
    And I should see "Learner" in the "5" "table_row"
    And I should see "Manager" in the "5" "table_row"
    And I should not see "Manager's Manager" in the "5" "table_row"
    And I should not see "Appraiser" in the "5" "table_row"

    # Add check to verify form fields are populated correctly.
    When I follow "Message 5"
    Then the "eventid" select box should contain "Stage 1-1 Stage"
    And the "eventtype" select box should contain "Upon completion"
    And the field "Send after" matches value "1"
    And the field "delta" matches value "2"
    And the "deltaperiod" select box should contain "weeks"
    And the field "Learner" matches value "1"
    And the field "Manager" matches value "1"
    And the field "Manager's Manager" does not match value "1"
    And the field "Appraiser" does not match value "1"
    And the "messagetoall" select box should contain "Send different message for each role"
    And the field "messagetitle[1]" matches value "Message 5 for Learner"
    And the field "messagebody[1]" matches value "Message 5 body for Learner"
    And the field "messagetitle[2]" matches value "Message 5 for Manager"
    And the field "messagebody[2]" matches value "Message 5 body for Manager"
    And I press "cancel"

    When I follow "Message 6"
    Then the "eventid" select box should contain "Stage 1-2 Stage"
    And the "eventtype" select box should contain "Complete by date"
    And the field "Send before" matches value "1"
    And the field "delta" matches value "5"
    And the "deltaperiod" select box should contain "days"
    And the field "Learner" matches value "1"
    And the field "Manager" matches value "1"
    And the field "Manager's Manager" does not match value "1"
    And the field "Appraiser" matches value "1"
    And the "messagetoall" select box should contain "Send same message to all roles"
    And the field "messagetitle[0]" matches value "Message 6"
    And the field "messagebody[0]" matches value "Message 6 body"
