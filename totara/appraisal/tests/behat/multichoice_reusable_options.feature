@totara @totara_appraisal @javascript
Feature: Create and use multichoice option collections

  Scenario: I can create and reuse multichoice option collections
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
      | user     | position | manager  |
      | learner1 | pos1     | manager1 |
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I press "Create appraisal"
    And I set the following fields to these values:
      | Name        | Behat Test Appraisal |
      | Description | This is the behat description   |
    And I press "Create appraisal"
    And I press "Add stage"
    And I set the following fields to these values:
      | Name                  | Behat Appraisal stage   |
      | Description           | Behat stage description |
      | timedue[enabled]      | 1                       |
      | timedue[day]          | 31                      |
      | timedue[month]        | 12                      |
      | timedue[year]         | 2037                    |
      | Page names (optional) | Page1                   |
    And I click on "Add stage" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "Behat Appraisal stage" in the ".appraisal-stages" "css_element"
    And I click on "Behat Appraisal stage" "link" in the ".appraisal-stages" "css_element"
    And I click on "Page1" "link" in the ".appraisal-page-list" "css_element"
    And I set the field "id_datatype" to "Multiple choice (several answers)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question                                  | Multi-choice question one |
      | choice[0][option]                         | One                       |
      | choice[1][option]                         | Two                       |
      | choice[2][option]                         | Three                     |
      | Save these options for other questions as | 1                         |
      | id_roles_1_2                              | 1                         |
      | id_roles_1_1                              | 1                         |
      | id_roles_2_2                              | 1                         |
      | id_roles_2_1                              | 1                         |
      | saveoptionsname                           | Custom choice set         |
    And I click on "#id_listtype_list_2" "css_element"
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"
    And I wait "1" seconds

    # Add a second multi choice question
    And I set the field "id_datatype" to "Multiple choice (several answers)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question                                  | Multi-choice question two |
      | selectchoices                             | Custom choice set         |
      | Same as preceding question                | 1                         |
    And I click on "#id_listtype_list_2" "css_element"
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"
    And I wait "1" seconds

    # Set up users.
    And I switch to "Assignments" tab
    And I select "Position" from the "groupselector" singleselect
    And I click on "Position One" "link" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I click on "Save" "button" in the "Assign Learner Group To Appraisal" "totaradialogue"

    # Activate appraisal.
    And I click on "Activate now" "link"
    And I press "Activate"

    # Create some data
    When I log out
    And I log in as "learner1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I click on "//legend/a[text()='Multi-choice question one']/ancestor::fieldset//select/option[text()='Two']" "xpath_element"
    And I click on "//legend/a[text()='Multi-choice question two']/ancestor::fieldset//select/option[text()='Three']" "xpath_element"
    And I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"

    # View the details report
    When I log out
    And I log in as "admin"
    And I navigate to "Reports" node in "Site administration > Appraisals"
    And I click on "Detail report" "link" in the "Behat Test Appraisal" "table_row"
    And I should see "Two" in the "Learner One" "table_row"
    And I should see "Three" in the "Learner One" "table_row"
