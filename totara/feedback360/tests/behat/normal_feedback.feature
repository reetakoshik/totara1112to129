@totara @totara_feedback360 @javascript
Feature: Normal feedback
  In order to request anonymous
  As an user
  I am able to setup and use a normal feedback request

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
      | user5    | User      | Five     | user5@example.com |
      | user6    | User      | Six      | user6@example.com |
      | user7    | User      | Seven    | user7@example.com |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
      | user3 | CH1    |
      | user4 | CH1    |
      | user5 | CH1    |
      | user6 | CH1    |
    And I log in as "admin"
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    And I press "Create Feedback"
    And I set the following fields to these values:
      | Name               | Normal feedback                          |
      | Description        | This is a simple normal feedback request |
    And I press "Create Feedback"
    And I switch to "Content" tab
    And I set the field "datatype" to "Long text"
    And I press "Add"
    And I set the field "Question" to "How much do you like me?"
    And I press "Save changes"
    And I switch to "Assignments" tab
    And I set the field "groupselector" to "Audience"
    And I click on "Cohort 1 (CH1)" "link" in the "Assign Group to 360° Feedback?" "totaradialogue"
    And I click on "Save" "button" in the "Assign Group to 360° Feedback?" "totaradialogue"
    And I should see "User One" in the "#assignedusers" "css_element"
    And I should see "User Six" in the "#assignedusers" "css_element"
    And I click on "Delete" "link" in the "Cohort 1" "table_row"
    And I should not see "User  One" in the "#assignedusers" "css_element"
    And I should not see "User Six" in the "#assignedusers" "css_element"
    And I set the field "groupselector" to "Audience"
    And I click on "Cohort 1 (CH1)" "link" in the "Assign Group to 360° Feedback?" "totaradialogue"
    And I click on "Save" "button" in the "Assign Group to 360° Feedback?" "totaradialogue"
    And I should see "User One" in the "#assignedusers" "css_element"
    And I should see "User Six" in the "#assignedusers" "css_element"
    And I follow "(Activate Now)"
    And I press "Continue"
    And I log out

    And I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    And I click on "Request Feedback" "button" in the "Normal feedback" "table_row"
    And I press "Add user(s)"
    And I click on "User Two" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Three" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Four" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Five" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Six" "link" in the "Add user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add user(s)" "totaradialogue"
    And I wait "1" seconds
    When I press "Request"
    Then I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "User Six"
    When I press "Confirm"
    Then I should see "0 out of 5" in the "Normal feedback" "table_row"
    And I log out

    And I log in as "user2"
    And I click on "360° Feedback" in the totara menu
    And I click on "Respond now" "button" in the "User One" "table_row"
    # If the next line fails check the hack in totara_feedback360_renderer::display_feedback_header
    And "#feedbackhead .feedback360-save input[value='Save progress']" "css_element" should be visible
    And I set the field "How much do you like me?" to "Not at all"
    And I press "Submit feedback"
    And I log out

    And I log in as "user5"
    And I click on "360° Feedback" in the totara menu
    And I click on "Respond now" "button" in the "User One" "table_row"
    And I set the field "How much do you like me?" to "Quite a bit"
    And I press "Submit feedback"
    And I log out

  Scenario: Check responses are shown
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    Then I should see "2 out of 5" in the "Normal feedback" "table_row"
    When I follow "Normal feedback"
    Then I should see "View Response" in the "User Two" "table_row"
    And I should see "Not Completed" in the "User Three" "table_row"
    And I should not see "View Response" in the "User Three" "table_row"
    And I should see "Not Completed" in the "User Four" "table_row"
    And I should not see "View Response" in the "User Four" "table_row"
    And I should see "View Response" in the "User Five" "table_row"
    And I should not see "View Response" in the "User Six" "table_row"
    And I should see "Not Completed" in the "User Six" "table_row"
    When I click on "View Response" "link" in the "User Five" "table_row"
    Then I should see "Quite a bit"
    And I follow "Back"
    When I click on "View Response" "link" in the "User Two" "table_row"
    Then I should see "Not at all"
    And I follow "Back"

  Scenario: Check you can delete unresponded users
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    When I click on "Edit" "link" in the "Normal feedback" "table_row"
    Then I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "User Six"
    When I click on "Remove feedback request from User Three" "link"
    Then I should not see "User three"
    When I press "Update"
    Then I should see "Cancel existing requests for:"
    And I should see "User Three"
    When I press "Confirm"
    Then I should see "2 out of 4" in the "Normal feedback" "table_row"
    When I follow "Normal feedback"
    Then I should see "View Response" in the "User Two" "table_row"
    And I should see "Not Completed" in the "User Four" "table_row"
    And I should not see "View Response" in the "User Four" "table_row"
    And I should see "View Response" in the "User Five" "table_row"
    And I should not see "View Response" in the "User Six" "table_row"
    And I should see "Not Completed" in the "User Six" "table_row"
    And "User Three" "table_row" should not exist

  Scenario: Add new request via search tab when finding users
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    And I click on "Edit" "link" in the "Normal feedback" "table_row"
    And I press "Add user(s)"
    And I click on "Search" "link" in the "Add user(s)" "totaradialogue"
    And I search for "User" in the "Add user(s)" totara dialogue
    And I click on "User Seven" "link" in the "#search-tab" "css_element"
    And I click on "Save" "button" in the "Add user(s)" "totaradialogue"
    And I wait "1" seconds
    When I press "Update"
    Then I should see "User Seven"
    When I press "Confirm"
    Then I should see "2 out of 6" in the "Normal feedback" "table_row"
    When I follow "Normal feedback"
    And I should see "Not Completed" in the "User Seven" "table_row"

  Scenario: Cancel requests to feedback360 responders
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    And I click on "stop" "link" in the "Normal feedback" "table_row"
    Then I should see "Are you sure you want to cancel this feedback request?"
    When I press "Continue"
    Then I should see "Request successfully cancelled"
    And I should see "2 out of 2" in the "Normal feedback" "table_row"

  Scenario: Remind feedback360 responders
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    When I click on "remind" "link" in the "Normal feedback" "table_row"
    Then I should see "This will send a reminder message to everyone you have requested feedback from that has yet to respond:"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Six"
    And I should not see "User Two"
    And I should not see "User Five"
    When I press "Continue"
    Then I should see "Reminders sent for Normal feedback"

  Scenario: Set and update time due for feedback360 requests
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    And I click on "Edit" "link" in the "Normal feedback" "table_row"
    And I set the following fields to these values:
     | duedate[enabled] | 1     |
     | duedate[year]    | 2030  |
     | duedate[month]   | March |
     | duedate[day]     | 15    |
    And I press "Update"
    And I press "Confirm"
    Then I should see "15 March 2030" in the "Normal feedback" "table_row"
    When I click on "Edit" "link" in the "Normal feedback" "table_row"
    And I set the following fields to these values:
      | duedate[enabled] | 1     |
      | duedate[year]    | 1980  |
      | duedate[month]   | March |
      | duedate[day]     | 15    |
    And I press "Update"
    Then I should see "The due date can not be set to an earlier date, please set it to a date equal to or after the existing due date."
    And I set the following fields to these values:
      | duedate[enabled] | 1     |
      | duedate[year]    | 2031  |
      | duedate[month]   | March |
      | duedate[day]     | 15    |
    # The "Update" button is changed to "Request" after validation because it's missing a url param when reloading the form.
    And I press "Request"
    And I press "Confirm"
    Then I should see "15 March 2031" in the "Normal feedback" "table_row"
    When I click on "Edit" "link" in the "Normal feedback" "table_row"
    And I set the following fields to these values:
      | duedate[enabled] | 0     |
    And I press "Update"
    And I press "Confirm"
    Then I should not see "15 March 2031" in the "Normal feedback" "table_row"
