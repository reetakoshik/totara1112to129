@totara @totara_core @totara_customfield
Feature: Test evidence dialogue search
  In order to test the evidence dialog search
  As an admin
  I set up data and then use the evidence dialog search


  @javascript
  Scenario: I can search for evidence in the evidence dialog
    # Setup.
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | format | summary |
      | Course 1 | C1        | topics | x       |
    And I log in as "admin"
    And I click on "Record of Learning" in the totara menu
    Then I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name | Test Evidence itemid1 |
      | Description   | Test Evidence descid1 |
    And I press "Add evidence"
    Then I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name | Test Evidence itemid2 |
      | Description   | Test Evidence descid2 |
    And I press "Add evidence"
    # Create plan.
    And I click on "Manage plans" "link"
    When I press "Create new learning plan"
    And I set the field "Plan name" to "Test Learning Plan"
    When I press "Create plan"
    Then I should see "Plan creation successful"
    # Add course.
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I follow "Miscellaneous"
    And I follow "Course 1"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    And I wait "1" seconds
    # Go to the evidence dialog.
    And I follow "Course 1"
    And I press "Add linked evidence"
    And I click on "Search" "link" in the "Add linked evidence" "totaradialogue"
    # Search for both.
    And I search for "id" in the "Add linked evidence" totara dialogue
    Then I should see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
    # Search for evidence item #1 using name.
    And I search for "itemid1" in the "Add linked evidence" totara dialogue
    Then I should see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should not see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
    # Search for evidence item #2 using description.
    When I search for "descid2" in the "Add linked evidence" totara dialogue
    Then I should not see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
    # Remove the evidence description custom field.
    Then I click on "Cancel" "button" in the "Add linked evidence" "totaradialogue"
    And I wait "1" seconds
    And I navigate to "Evidence custom fields" node in "Site administration > Learning Plans"
    And I click on "Delete" "link" in the "Description" "table_row"
    And I press "Yes"
    # Go to the evidence dialog.
    And I click on "Record of Learning" in the totara menu
    And I click on "Test Learning Plan" "link"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I follow "Course 1"
    And I press "Add linked evidence"
    And I click on "Search" "link" in the "Add linked evidence" "totaradialogue"
    # Search for evidence item #1 using name.
    When I search for "itemid1" in the "Add linked evidence" totara dialogue
    Then I should see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should not see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
    # Search for any evidence using description.
    When I search for "desc" in the "Add linked evidence" totara dialogue
    Then I should not see "Test Evidence itemid1" in the "Add linked evidence" "totaradialogue"
    And I should not see "Test Evidence itemid2" in the "Add linked evidence" "totaradialogue"
