@totara @totara_feedback360 @javascript
Feature: Select feedback360 for requests
  If I have been assigned to multiple feedback360 forms
  As a user
  I need to select which form to request feedback on

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
    And I log in as "admin"

    When I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    And I press "Create Feedback"
    And I set the following fields to these values:
      | Name               | Feedback One    |
      | Description        | Simple feedback |
    And I press "Create Feedback"
    And I switch to "Content" tab
    And I set the field "datatype" to "Long text"
    And I press "Add"
    And I set the field "Question" to "Feedback One Question"
    And I press "Save changes"
    And I switch to "Assignments" tab
    And I set the field "groupselector" to "Audience"
    And I click on "Cohort 1 (CH1)" "link" in the "Assign Group to 360° Feedback?" "totaradialogue"
    And I click on "Save" "button" in the "Assign Group to 360° Feedback?" "totaradialogue"
    Then I should see "User One" in the "#assignedusers" "css_element"
    And I follow "(Activate Now)"
    And I press "Continue"

    When I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    And I press "Create Feedback"
    And I set the following fields to these values:
      | Name               | Feedback Two   |
      | Description        | Simple feeback |
    And I press "Create Feedback"
    And I switch to "Content" tab
    And I set the field "datatype" to "Long text"
    And I press "Add"
    And I set the field "Question" to "Feedback Two Question"
    And I press "Save changes"
    And I switch to "Assignments" tab
    And I set the field "groupselector" to "Audience"
    And I click on "Cohort 1 (CH1)" "link" in the "Assign Group to 360° Feedback?" "totaradialogue"
    And I click on "Save" "button" in the "Assign Group to 360° Feedback?" "totaradialogue"
    Then I should see "User One" in the "#assignedusers" "css_element"
    And I follow "(Activate Now)"
    And I press "Continue"
    And I log out

  Scenario: Select which feedback360 form to request feedback for
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    And I click on "Request Feedback" "button" in the "Feedback Two" "table_row"
    And I press "Add user(s)"
    And I click on "User Two" "link" in the "Add user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add user(s)" "totaradialogue"
    And I wait "1" seconds
    When I press "Request"
    When I press "Confirm"
    Then I should see "0 out of 1" in the "Feedback Two" "table_row"

    When I click on "Request Feedback" "button" in the "Feedback One" "table_row"
    And I press "Add user(s)"
    And I click on "User Three" "link" in the "Add user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add user(s)" "totaradialogue"
    And I wait "1" seconds
    When I press "Request"
    When I press "Confirm"
    Then I should see "0 out of 1" in the "Feedback One" "table_row"

    When I log out
    And I log in as "user3"
    And I click on "360° Feedback" in the totara menu
    And I click on "Respond now" "button" in the "User One" "table_row"
    Then I should see "Feedback One Question"

    When I log out
    And I log in as "user2"
    And I click on "360° Feedback" in the totara menu
    And I click on "Respond now" "button" in the "User One" "table_row"
    Then I should see "Feedback Two Question"
