@totara @totara_appraisal
Feature: Perform basic appraisals administration
  In order to ensure the appraisals works as expected
  As an admin
  I need to create calendar data

  @javascript
  Scenario: Create Appraisal
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I press "Create appraisal"
    And I set the following fields to these values:
      | Name | Behat Test Appraisal |
      | Description | This is the behat description|
    And I press "Create appraisal"
    And I press "Add stage"
    And I set the following fields to these values:
      | Name | Behat Appraisal stage |
      | Description | Behat stage description|
    And I press "id_submitbutton"
    And I should see "Behat Appraisal stage" in the ".appraisal-stages" "css_element"
    And I click on "Behat Appraisal stage" "link" in the ".appraisal-stages" "css_element"
    # AJAX updated
    And I click on "Add new page" "link" in the ".appraisal-page-pane" "css_element"
    # AJAX form load
    And I set the following fields to these values:
      | Name | test page |
    And I press "Add new page"

    When I set the following fields to these values:
      | datatype | Short text |
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
      | Question    | Your favourite colour |
      | roles[1][2] | 1                     |
      | roles[2][2] | 1                     |
      | roles[8][2] | 1                     |
      | roles[1][6] | 1                     |
      | roles[2][1] | 1                     |
      | roles[8][1] | 1                     |
    And I press "Save changes"
    Then I should see "Manage Behat Appraisal stage's content"
    And I should see "Your favourite colour"
    And I should see "Short text"

    # Test that if I try to add a question without selecting the type I get an error dialog.
    When I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    Then I should see "Error occurred"
    And I should see "You must select the question type you want to add."
    And I press "OK"