@totara @totara_question @totara_appraisal @javascript
Feature: Completion of short text totara questions
  In order to include a short text field
  As admin I need to add a text field
  As learner I need to complete a text field

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "cohorts" exist:
      | name         | idnumber |
      | Audience One | AUD1     |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | AUD1   |
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    And I click on "Create appraisal" "button"
    And I set the field "Name" to "Test Appraisal"
    And I click on "Create appraisal" "button"
    And I press "Add stage"
    And I expand all fieldsets
    And I set the following fields to these values:
     | Name                  | Stage One |
     | timedue[enabled]      | 1         |
     | timedue[day]          | 15        |
     | timedue[month]        | January   |
     | timedue[year]         | 2030      |
     | Page names (optional) | Page One  |
    And I click on "submitbutton" "button"
    And I set the field "datatype" to "Short text"
    And I click on "Add" "button" in the "#fgroup_id_addquestgroup" "css_element"
    And I set the following fields to these values:
     | Question    | Text field 1 |
     | roles[1][2] | 1            |
    And I press "Save changes"
    And I wait "1" seconds
    And I switch to "Assignments" tab
    And I select "Audience" from the "groupselector" singleselect
    And I wait "1" seconds
    And I click on "Audience One" "link" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I click on "Save" "button" in the "Assign Learner Group To Appraisal" "totaradialogue"
    And I wait "1" seconds
    Then I should see "User One" in the "#assignedusers" "css_element"
    And I click on "Activate now" "link"
    And I press "Activate"
    Then I should see "Test Appraisal activated"
    And I log out

  Scenario: A value can be submitted for a short text question field in an appraisal
    Given I log in as "user1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    Then I should see "Text field 1"
    When I set the following fields to these values:
      | Your answer | Some short text |
    And I press "Complete stage"
    Then I should see "You have completed this stage"
    When I press "View"
    Then I should see "Some short text"

  Scenario: Validation prevents submission with values greater than 255 characters
    Given I log in as "user1"
    And I click on "Latest Appraisal" in the totara menu
    And I press "Start"
    Then I should see "Text field 1"
    # Each block like charnum010 is 10 characters long. The total is 256 characters.
    When I set the following fields to these values:
      | Your answer | charnum010charnum020charnum030charnum040charnum050charnum060charnum070charnum080charnum090charnum100charnum110charnum120charnum130charnum140charnum150charnum160charnum170charnum180charnum190charnum200charnum210charnum220charnum230charnum240charnum250and456 |
    And I press "Complete stage"
    Then I should see "Maximum of 255 characters"
    # Now set Text field 1 to a string with length of 255 characters.
    When I set the following fields to these values:
      | Your answer | charnum010charnum020charnum030charnum040charnum050charnum060charnum070charnum080charnum090charnum100charnum110charnum120charnum130charnum140charnum150charnum160charnum170charnum180charnum190charnum200charnum210charnum220charnum230charnum240charnum250and45 |
    And I press "Complete stage"
    Then I should see "You have completed this stage"
    When I press "View"
    Then I should see "charnum010charnum020charnum030charnum040charnum050charnum060charnum070charnum080charnum090charnum100charnum110charnum120charnum130charnum140charnum150charnum160charnum170charnum180charnum190charnum200charnum210charnum220charnum230charnum240charnum250and45"
