@javascript @totara @totara_appraisal @totara_reportbuilder @totara_core_menu
Feature: Test appraisal detailed report with numeric question
  In order to ensure the appraisals works as expected
  As an admin
  I need to create calendar data

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email       | auth   | confirmed |
      | user1    | User      | One      | one@example.invalid | manual | 1         |
      | user2    | User      | Two      | two@example.invalid | manual | 1         |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
    And the following job assignments exist:
      | user  | fullname      | idnumber | manager |
      | user1 | user1 Day Job | u1ja     | admin   |
      | user2 | user2 Day Job | u2ja     | admin   |
    And I log in as "admin"


  Scenario: Create Appraisal with assigned audience and check detailed report
    # Create appraisal with stage and page
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I press "Create appraisal"
    And I set the following fields to these values:
      | Name        | Behat Test Appraisal          |
      | Description | This is the behat description |
    And I press "Create appraisal"
    And I press "Add stage"
    And I set the following fields to these values:
      | Name                  | Behat Appraisal stage   |
      | Description           | Behat stage description |
      | timedue[enabled]      | 1                       |
      | timedue[day]          | 31                      |
      | timedue[month]        | 12                      |
      | timedue[year]         | 2037                    |
    And I set the field "Page names (optional)" to multiline:
      """
      Page1.1
      Page1.2
      """
    And I click on "Add stage" "button" in the ".fitem_actionbuttons" "css_element"
    And I should see "Behat Appraisal stage" in the ".appraisal-stages" "css_element"
    And I click on "Behat Appraisal stage" "link" in the ".appraisal-stages" "css_element"

    # Create appraisal numeric rating question
    And I click on "Page1.1" "link" in the ".appraisal-page-list" "css_element"
    And I set the field "id_datatype" to "Rating (numeric scale)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question     | Rating question  |
      | From         | 1                |
      | To           | 10               |
      | list         | Text input field |
      | id_roles_1_2 | 1                |
      | id_roles_1_1 | 1                |
      | id_roles_2_2 | 1                |
      | id_roles_2_1 | 1                |
    # Display settings doesn't visually change to "Text input field" radio. So nail it.
    And I click on "#id_list_2" "css_element"
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"
    And I wait "1" seconds

    # Create multi choice several answer question
    And I click on "Page1.2" "link" in the ".appraisal-page-list" "css_element"
    And I set the field "id_datatype" to "Multiple choice (several answers)"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question          | Multi-choice question  |
      | choice[0][option] | One                    |
      | choice[1][option] | Two                    |
      | choice[2][option] | Three                  |
      | id_roles_1_2      | 1                      |
      | id_roles_1_1      | 1                      |
      | id_roles_2_2      | 1                      |
      | id_roles_2_1      | 1                      |
    # Display settings doesn't visually change to "Text input field" radio. So nail it.
    And I click on "#id_listtype_list_2" "css_element"
    And I click on "//button[text()='Save changes']" "xpath_element" in the "div.moodle-dialogue-focused div.moodle-dialogue-ft" "css_element"
    And I wait "1" seconds

    # Set up users.
    And I click on "Assignments" "link"
    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "Cohort 1" "link" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I click on "Save" "button" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I wait "1" seconds

    # Activate appraisal.
    And I click on "Activate now" "link"
    And I press "Activate"

    # Edit report to add "All roles"
    When I navigate to "Reports" node in "Site administration > Appraisals"
    And I click on "Detail report" "link" in the "Behat Test Appraisal" "table_row"
    And I press "Edit this report"
    And I click on "Columns" "link"
    And I set the field "id_newcolumns" to "All Roles' Score"
    And I press "Save changes"
    And I log out

    # Add data for User One
    When I log in as "user1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    And I set the following fields to these values:
      | Your answer | 3 |
    And I click on "Next" "button" in the "#fitem_id_submitbutton" "css_element"
    And I set the following fields to these values:
      | Your answer | One |
    And I click on "Complete stage" "button" in the "#fitem_id_submitbutton" "css_element"
    And I log out

    # Load report with new column
    When I log in as "admin"
    And I navigate to "Reports" node in "Site administration > Appraisals"
    And I click on "Detail report" "link" in the "Behat Test Appraisal" "table_row"
    And I should see "3" in the "User One" "table_row"
    And I should not see "3" in the "User Two" "table_row"
    And I should see "One" in the "User One" "table_row"
    And I should not see "One" in the "User Two" "table_row"

    # Save search test
    When I press "Save this search"
    And I set the following fields to these values:
      | Search Name | My search |
    And I press "Save changes"
    Then I should see "Behat Test Appraisal detail report" in the "#region-main h2" "css_element"

    # Export parametric report test
    And I press "Export"
    And I should see "On Target"
