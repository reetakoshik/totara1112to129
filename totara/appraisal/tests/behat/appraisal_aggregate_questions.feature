@totara @totara_appraisal @javascript
Feature: Perform basic actions for aggregate questions
  In order to view aggregate questions
  As a user
  I need to answer some ratings questions

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | appraiser | Sally     | Sal      | appraiser@example.com |
      | manager   | Terry     | Ter      | manager@example.com   |
      | jimmy     | Jimmy     | Jim      | jimmy@example.com     |
      | bobby     | Bobby     | Bob      | bobby@example.com     |
      | dobby     | Dobby     | Dob      | dobby@example.com     |
    And the following job assignments exist:
      | user  | fullname      | idnumber | manager | appraiser |
      | jimmy | jimmy Day Job | l1ja     | manager | appraiser |
      | bobby | bobby Day Job | l2ja     | manager | appraiser |
      | dobby | dobby Day Job | l3ja     | manager | appraiser |
    And the following "cohorts" exist:
      | name                | idnumber | description            | contextlevel | reference |
      | Appraisals Audience | AppAud   | Appraisals Assignments | System       | 0         |
    And the following "cohort members" exist:
      | user  | cohort |
      | jimmy | AppAud |
      | bobby | AppAud |
      | dobby | AppAud |
    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name                  |
      | Aggregate Tests       |
      | Zero Aggregate Tests  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal            | name   | timedue                 |
      | Aggregate Tests      | Stage1 | 1 January 2020 23:59:59 |
      | Aggregate Tests      | Stage2 | 1 January 2030 23:59:59 |
      | Zero Aggregate Tests | Stage0 | 1 January 2030 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal            | stage  | name              |
      | Aggregate Tests      | Stage1 | Stage1-Ratings    |
      | Aggregate Tests      | Stage1 | Stage1-Aggregates |
      | Aggregate Tests      | Stage2 | Stage2-Ratings    |
      | Aggregate Tests      | Stage2 | Stage2-Aggregates |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal            | stage  | page              | name              | type          | default | ExtraInfo                                             |
      | Aggregate Tests      | Stage1 | Stage1-Ratings    | S1-desc           | text          |         |                                                       |
      | Aggregate Tests      | Stage1 | Stage1-Ratings    | S1-Rating_Numeric | ratingnumeric | 5       | Range:1-10,Display:slider                             |
      | Aggregate Tests      | Stage1 | Stage1-Ratings    | S1-Rating_Custom  | ratingcustom  | choice1 |                                                       |
      | Aggregate Tests      | Stage1 | Stage1-Aggregates | S1-Rating_Extra   | ratingnumeric | 7       | Range:2-8,Display:slider                              |
      | Aggregate Tests      | Stage1 | Stage1-Aggregates | S1-Aggregate      | aggregate     |         | S1-Rating_Numeric,S1-Rating_Custom                    |
      | Aggregate Tests      | Stage2 | Stage2-Ratings    | S2-Rating_Numeric | ratingnumeric | 4       | Range:1-10,Display:slider                             |
      | Aggregate Tests      | Stage2 | Stage2-Ratings    | S2-Rating_Custom  | ratingcustom  | choice2 |                                                       |
      | Aggregate Tests      | Stage2 | Stage2-Aggregates | S2-Aggregate      | aggregate     |         | S2-Rating_Numeric,S2-Rating_Custom                    |
      | Aggregate Tests      | Stage2 | Stage2-Aggregates | Total Aggregate   | aggregate     |         | *                                                     |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Num1_Txt  | ratingnumeric |         | Range:0-100,Display:text                              |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Num2_Txt  | ratingnumeric | 0       | Range:0-100,Display:text                              |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Num3_Txt  | ratingnumeric | 30      | Range:0-100,Display:text                              |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Num4_Sl   | ratingnumeric |         | Range:0-100,Display:slider                            |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Num5_Sl   | ratingnumeric | 20      | Range:0-100,Display:slider                            |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Cust1     | ratingcustom  |         | Scores:0;50;100                                       |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Cust2     | ratingcustom  | choice1 | Scores:0;50;100                                       |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Cust3     | ratingcustom  | choice2 | Scores:0;50;100                                       |
      | Zero Aggregate Tests | Stage0 | Stage0-Ratings    | S0-Rate_Cust4     | ratingcustom  |         | Scores:0;50;100                                       |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates | S0-Agg_Num0       | aggregate     |         | S0-Rate_Num1_Txt,S0-Rate_Num2_Txt,S0-Rate_Num3_Txt,S0-Rate_Num4_Sl,S0-Rate_Num5_Sl                            |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates | S0-Agg_Num1       | aggregate     |         | S0-Rate_Num1_Txt,S0-Rate_Num2_Txt,S0-Rate_Num3_Txt,S0-Rate_Num4_Sl,S0-Rate_Num5_Sl;UseUnans:true              |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates | S0-Agg_Num2       | aggregate     |         | S0-Rate_Num1_Txt,S0-Rate_Num2_Txt,S0-Rate_Num3_Txt,S0-Rate_Num4_Sl,S0-Rate_Num5_Sl;UseZero:true               |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates | S0-Agg_Num3       | aggregate     |         | S0-Rate_Num1_Txt,S0-Rate_Num2_Txt,S0-Rate_Num3_Txt,S0-Rate_Num4_Sl,S0-Rate_Num5_Sl;UseUnans:true;UseZero:true |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates | S0-Agg_Cust0      | aggregate     |         | S0-Rate_Cust1,S0-Rate_Cust2,S0-Rate_Cust3,S0-Rate_Cust4;UseUnans:false;UseZero:false                          |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates | S0-Agg_Cust1      | aggregate     |         | S0-Rate_Cust1,S0-Rate_Cust2,S0-Rate_Cust3,S0-Rate_Cust4;UseUnans:true                                         |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates | S0-Agg_Cust2      | aggregate     |         | S0-Rate_Cust1,S0-Rate_Cust2,S0-Rate_Cust3,S0-Rate_Cust4;UseZero:true                                          |
      | Zero Aggregate Tests | Stage0 | Stage0-Aggregates | S0-Agg_Cust3      | aggregate     |         | S0-Rate_Cust1,S0-Rate_Cust2,S0-Rate_Cust3,S0-Rate_Cust4;UseUnans:true;UseZero:true                            |

    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal            | type     | id     |
      | Aggregate Tests      | audience | AppAud |
      | Zero Aggregate Tests | audience | AppAud |

  Scenario: Check available questions in the aggregate settings page.
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Aggregate Tests" "link"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I click on "Stage1" "link" in the ".appraisal-stages" "css_element"
    And I click on "Stage1-Aggregates" "link" in the ".appraisal-page-container" "css_element"
    And I click on "Settings" "link" in the "S1-Aggregate" "list_item"
    Then I should not see "S1-desc" in the ".aggregateselector" "css_element"
    And I should see "S1-Rating_Numeric" in the ".aggregateselector" "css_element"
    And I should see "S1-Rating_Custom" in the ".aggregateselector" "css_element"
    And I should not see "S1-Rating_Extra" in the ".aggregateselector" "css_element"
    And I should not see "S2-Rating_Numeric" in the ".aggregateselector" "css_element"
    And I should not see "S2-Rating_Custom" in the ".aggregateselector" "css_element"
    And I click on "Close" "button" in the ".moodle-dialogue-hd" "css_element"
    When I click on "Stage2" "link" in the ".appraisal-stages" "css_element"
    And I click on "Stage2-Aggregates" "link" in the ".appraisal-page-container" "css_element"
    And I click on "Settings" "link" in the "S2-Aggregate" "list_item"
    Then I should not see "S1-desc" in the ".aggregateselector" "css_element"
    And I should see "S1-Rating_Numeric" in the ".aggregateselector" "css_element"
    And I should see "S1-Rating_Custom" in the ".aggregateselector" "css_element"
    And I should see "S1-Rating_Extra" in the ".aggregateselector" "css_element"
    And I should see "S2-Rating_Numeric" in the ".aggregateselector" "css_element"
    And I should see "S2-Rating_Custom" in the ".aggregateselector" "css_element"

  Scenario: Set aggregate question to manager view only
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Aggregate Tests" "link"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I click on "Stage1-Aggregates" "link" in the ".appraisal-page-container" "css_element"
    And I click on "Settings" "link" in the "S1-Aggregate" "list_item"
    And I set the field "Question" to "S1-Aggregate-Permissions-Test"
    And I click on "roles[1][2]" "checkbox" in the "#id_perms" "css_element"
    And I click on "roles[2][1]" "checkbox" in the "#id_perms" "css_element"
    And I click on "roles[8][1]" "checkbox" in the "#id_perms" "css_element"
    And I click on "roles[8][2]" "checkbox" in the "#id_perms" "css_element"
    And I press "Save changes"
    Then I should see "S1-Aggregate-Permissions-Test" in the "#appraisal-quest-list" "css_element"
    When I click on "Settings" "link" in the "S1-Aggregate" "list_item"
    And I click on "roles[2][2]" "checkbox" in the "#id_perms" "css_element"
    And I press "Save changes"
    Then I should see "At least one role must have visibility access"
    When I press "Cancel"
    And I click on "Activate now" "link"
    And I press "Activate"
    Then I should see "Appraisal Aggregate Tests activated"

  Scenario: Answer ratings questions and view aggregate question
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Aggregate Tests" "link"
    And I click on "Activate now" "link"
    And I press "Activate"
    And I log out

    When I log in as "jimmy"
    And I click on "All Appraisals" in the totara menu
    And I click on "Aggregate Tests" "link" in the "Aggregate Tests" "table_row"
    And I press "Start"
    And I click on "choice3" "radio"
    And I click on "Next" "button"

    Then I should see "Average score: 5.5"
    And I should see "Median score: 5.5"

    When I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    And I log out
    And I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I click on "Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
    And I press "Start"
    And I click on "choice1" "radio"
    And I click on "Next" "button"

    Then I should see "Average score: 3.5"
    And I should see "Median score: 3.5"
    And I should see "Average score: 5.5"
    And I should see "Median score: 5.5"

    When I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    And I log out
    And I log in as "appraiser"
    And I click on "All Appraisals" in the totara menu
    And I click on "Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
    And I press "Start"
    And I click on "choice1" "radio"
    And I click on "Next" "button"

    Then I should see "Average score: 3.5"
    And I should see "Median score: 3.5"
    And I should see "Average score: 5.5"
    And I should see "Median score: 5.5"

    When I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    And I log out
    And I log in as "jimmy"
    And I click on "All Appraisals" in the totara menu
    And I click on "Aggregate Tests" "link" in the "Aggregate Tests" "table_row"
    And I press "Start"
    And I click on "choice4" "radio"
    And I click on "Next" "button"

    Then I should see "Average score: 6"
    And I should see "Median score: 6"

    When I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    And I log out
    And I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I click on "Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
    And I press "Start"
    And I click on "choice1" "radio"
    And I click on "Next" "button"

    Then I should see "Average score: 3"
    And I should see "Median score: 3"
    And I should see "Average score: 4"
    And I should see "Median score: 4"
    And I should see "Average score: 6"
    And I should see "Median score: 6"

    When I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    And I log out
    And I log in as "appraiser"
    And I click on "All Appraisals" in the totara menu
    And I click on "Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
    And I press "Start"
    And I click on "choice1" "radio"
    And I click on "Next" "button"

    Then I should see "Average score: 3"
    And I should see "Median score: 3"
    And I should see "Average score: 4"
    And I should see "Median score: 4"
    And I should see "Average score: 6"
    And I should see "Median score: 6"

    When I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"

    Then I should see "This appraisal was completed"


  Scenario: Answer ratings questions and view aggregate question with zero values ignoring unanswered questions
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Zero Aggregate Tests" "link"
    And I click on "Activate now" "link"
    And I press "Activate"
    And I log out

    When I log in as "jimmy"
    And I click on "All Appraisals" in the totara menu
    And I click on "Zero Aggregate Tests" "link" in the "Zero Aggregate Tests" "table_row"
    And I press "Start"
    And I set the field with xpath "//fieldset[legend[contains(.,'S0-Rate_Num1')]]//input" to "40"
    And I set the field with xpath "//fieldset[legend[contains(.,'S0-Rate_Num2')]]//input" to "60"
    And I click on "choice1" "radio" in the "S0-Rate_Cust1" "fieldset"
    And I click on "choice3" "radio" in the "S0-Rate_Cust2" "fieldset"
    And I click on "choice2" "radio" in the "S0-Rate_Cust3" "fieldset"
    And I click on "Next" "button"
    Then I should see "Average score: 37.5" in the "//fieldset[legend[contains(.,'S0-Agg_Num0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 35" in the "//fieldset[legend[contains(.,'S0-Agg_Num0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 37.5" in the "//fieldset[legend[contains(.,'S0-Agg_Num1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 35" in the "//fieldset[legend[contains(.,'S0-Agg_Num1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 75" in the "//fieldset[legend[contains(.,'S0-Agg_Cust0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 75" in the "//fieldset[legend[contains(.,'S0-Agg_Cust0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 75" in the "//fieldset[legend[contains(.,'S0-Agg_Cust1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 75" in the "//fieldset[legend[contains(.,'S0-Agg_Cust1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 37.5" in the "//fieldset[legend[contains(.,'S0-Agg_Cust3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 25" in the "//fieldset[legend[contains(.,'S0-Agg_Cust3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"

    When I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    And I log out
    And I log in as "manager"
    And I click on "All Appraisals" in the totara menu
    And I click on "Zero Aggregate Tests" "link" in the "Jimmy Jim" "table_row"
    And I press "Start"
    And I set the field with xpath "//fieldset[legend[contains(.,'S0-Rate_Num2')]]//input" to "60"
    And I click on "choice2" "radio" in the "S0-Rate_Cust1" "fieldset"
    And I click on "Next" "button"
    Then I should see "Average score: 37.5" in the "//fieldset[legend[contains(.,'S0-Agg_Num0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 35" in the "//fieldset[legend[contains(.,'S0-Agg_Num0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 37.5" in the "//fieldset[legend[contains(.,'S0-Agg_Num1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 35" in the "//fieldset[legend[contains(.,'S0-Agg_Num1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 75" in the "//fieldset[legend[contains(.,'S0-Agg_Cust0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 75" in the "//fieldset[legend[contains(.,'S0-Agg_Cust0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 75" in the "//fieldset[legend[contains(.,'S0-Agg_Cust1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 75" in the "//fieldset[legend[contains(.,'S0-Agg_Cust1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Average score: 37.5" in the "//fieldset[legend[contains(.,'S0-Agg_Cust3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"
    And I should see "Median score: 25" in the "//fieldset[legend[contains(.,'S0-Agg_Cust3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Learner')]]" "xpath_element"

    Then I should see "Average score: 36.67" in the "//fieldset[legend[contains(.,'S0-Agg_Num0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Median score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Average score: 36.67" in the "//fieldset[legend[contains(.,'S0-Agg_Num1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Median score: 30" in the "//fieldset[legend[contains(.,'S0-Agg_Num1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Average score: 27.5" in the "//fieldset[legend[contains(.,'S0-Agg_Num2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Median score: 25" in the "//fieldset[legend[contains(.,'S0-Agg_Num2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Average score: 22" in the "//fieldset[legend[contains(.,'S0-Agg_Num3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Median score: 20" in the "//fieldset[legend[contains(.,'S0-Agg_Num3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Average score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Median score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Average score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Median score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Average score: 33.33" in the "//fieldset[legend[contains(.,'S0-Agg_Cust2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Median score: 50" in the "//fieldset[legend[contains(.,'S0-Agg_Cust2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Average score: 25" in the "//fieldset[legend[contains(.,'S0-Agg_Cust3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"
    And I should see "Median score: 25" in the "//fieldset[legend[contains(.,'S0-Agg_Cust3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Manager')]]" "xpath_element"

    Then I should see "Not yet answered" in the "//fieldset[legend[contains(.,'S0-Agg_Num0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Appraiser')]]" "xpath_element"
    And I should see "Not yet answered" in the "//fieldset[legend[contains(.,'S0-Agg_Num1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Appraiser')]]" "xpath_element"
    And I should see "Not yet answered" in the "//fieldset[legend[contains(.,'S0-Agg_Num2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Appraiser')]]" "xpath_element"
    And I should see "Not yet answered" in the "//fieldset[legend[contains(.,'S0-Agg_Num3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Appraiser')]]" "xpath_element"
    And I should see "Not yet answered" in the "//fieldset[legend[contains(.,'S0-Agg_Cust0')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Appraiser')]]" "xpath_element"
    And I should see "Not yet answered" in the "//fieldset[legend[contains(.,'S0-Agg_Cust1')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Appraiser')]]" "xpath_element"
    And I should see "Not yet answered" in the "//fieldset[legend[contains(.,'S0-Agg_Cust2')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Appraiser')]]" "xpath_element"
    And I should see "Not yet answered" in the "//fieldset[legend[contains(.,'S0-Agg_Cust3')]]//div[@id='fitem_id_aggregate' and div[@class='fitemtitle' and contains(.,'Appraiser')]]" "xpath_element"
