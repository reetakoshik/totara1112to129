@totara @totara_appraisal @javascript
Feature: View and navigate stages in appraisals
  Users should have buttons to allow them to navigate an appraisal

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following "position" frameworks exist:
      | fullname           | idnumber |
      | Position Framework | posfw    |
    And the following "position" hierarchy exists:
      | fullname     | idnumber | framework |
      | Position One | pos1     | posfw     |
    And the following job assignments exist:
      | user     | fullname         | idnumber | manager  | position |
      | learner1 | Learner1 Day Job | l1ja     | manager1 | pos1     |
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I press "Create appraisal"
    And I set the following fields to these values:
      | Name        | Appraisal view test           |
      | Description | This is the behat description |
    And I press "Create appraisal"

    When I press "Add stage"
    And I set the following fields to these values:
      | Name                  | Behat Appraisal stage 1 |
      | Description           | Behat stage description |
      | timedue[enabled]      | 1                       |
      | timedue[day]          | 1                       |
      | timedue[month]        | 1                       |
      | timedue[year]         | 2037                    |
      | Page names (optional) | Page 1                  |
    And I click on "Add stage" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "Behat Appraisal stage 1" "link" in the ".appraisal-stages" "css_element"
    And I click on "Page 1" "link" in the ".appraisal-page-list" "css_element"
    And I set the field "id_datatype" to "Short text"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question     | Question 1 |
      | id_roles_2_2 | 1          |
      | id_roles_2_1 | 1          |
    And I press "Save changes"
    And I wait "1" seconds
    Then I should see "Question 1"

    When I press "Add stage"
    And I set the following fields to these values:
      | Name                  | Behat Appraisal stage 2 |
      | Description           | Behat stage description |
      | timedue[enabled]      | 1                       |
      | timedue[day]          | 1                       |
      | timedue[month]        | 2                       |
      | timedue[year]         | 2037                    |
      | Page names (optional) | Page 2                  |
    And I click on "Add stage" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "Behat Appraisal stage 2" "link" in the ".appraisal-stages" "css_element"
    And I click on "Page 2" "link" in the ".appraisal-page-list" "css_element"
    And I set the field "id_datatype" to "Short text"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question     | Question 2 |
      | id_roles_1_2 | 1          |
      | id_roles_1_1 | 1          |
      | id_roles_2_2 | 1          |
      | id_roles_2_1 | 1          |
    And I press "Save changes"
    And I wait "1" seconds
    Then I should see "Question 2"

    When I press "Add stage"
    And I set the following fields to these values:
      | Name                  | Behat Appraisal stage 3 |
      | Description           | Behat stage description |
      | timedue[enabled]      | 1                       |
      | timedue[day]          | 1                       |
      | timedue[month]        | 3                       |
      | timedue[year]         | 2037                    |
      | Page names (optional) | Page 3                  |
    And I click on "Add stage" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "Behat Appraisal stage 3" "link" in the ".appraisal-stages" "css_element"
    And I click on "Page 3" "link" in the ".appraisal-page-list" "css_element"
    And I set the field "id_datatype" to "Short text"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question     | Question 3 |
      | id_roles_2_2 | 1          |
      | id_roles_2_1 | 1          |
    And I press "Save changes"
    And I wait "1" seconds
    Then I should see "Question 3"

    When I switch to "Assignments" tab
    And I select "Position" from the "groupselector" singleselect
    And I click on "Position One" "link" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I click on "Save" "button" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Learner One" in the "#assignedusers" "css_element"
    When I click on "Activate now" "link"
    And I press "Activate"
    Then I should see "Appraisal view test activated"
    And I log out

  Scenario: Users see View button only when they can participate in an appraisal stage
    # Learner can't see stage 1.
    When I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "Job assignment linked to this appraisal"
    And I should see "Learner1 Day Job (Position One)"
    And "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    And "Start" "button" should not exist
    And "View" "button" should not exist
    And I log out

    # Manager can see and complete stage 1.
    When I log in as "manager1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal view test" "link"
    Then "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    When I click on "Start" "button" in the ".appraisal-stage-inprogress" "css_element"
    And I set the field "Your answer" to "Manager's answer 1"
    And I press "Complete stage"
    Then "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element"
    And "Start" "button" should exist in the "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element"
    And "Start" "button" should not exist in the "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element"
    And "View" "button" should not exist in the "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element"

    # Manager can see and compete stage 2.
    When I click on "Start" "button" in the ".appraisal-stage-inprogress" "css_element"
    And I set the field "Your answer" to "Manager's answer 2"
    And I press "Complete stage"
    Then "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    And "Start" "button" should not exist
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element"
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element"
    And "View" "button" should not exist in the "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element"
    And I log out

    # Learner can see and complete stage 2.
    When I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    Then "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    And "View" "button" should not exist
    And "Start" "button" should not exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element"
    And "Start" "button" should exist in the "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element"
    And "Start" "button" should not exist in the "//div[contains(@class,'appraisal-stage-locked') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element"
    When I click on "Start" "button" in the ".appraisal-stage-inprogress" "css_element"
    And I set the field "Your answer" to "Learner's answer 2"
    And I press "Complete stage"
    Then "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    And "Start" "button" should not exist
    And "View" "button" should not exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element"
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element"
    And "View" "button" should not exist in the "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element"
    And I log out

    # Manager can see and complete stage 3 and see the appraisal is complete.
    When I log in as "manager1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal view test" "link"
    Then "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element"
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element"
    And "Start" "button" should exist in the "//div[contains(@class,'appraisal-stage-inprogress') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element"
    When I click on "Start" "button" in the ".appraisal-stage-inprogress" "css_element"
    And I set the field "Your answer" to "Manager's answer 3"
    And I press "Complete stage"
    Then I should see "This appraisal was completed on"
    And "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element"
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element"
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element"
    And I log out

    # Learner can see the appraisal is complete.
    When I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    Then I should see "This appraisal was completed on"
    And "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element" should exist
    And "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element" should exist
    And "Start" "button" should not exist
    And "View" "button" should not exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 1')]]" "xpath_element"
    And "View" "button" should exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 2')]]" "xpath_element"
    And "View" "button" should not exist in the "//div[contains(@class,'appraisal-stage-completed') and .//h3[contains(.,'Behat Appraisal stage 3')]]" "xpath_element"
