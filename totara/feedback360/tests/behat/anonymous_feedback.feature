@totara @totara_feedback360 @javascript
Feature: anonymous feedback
  In order to request anonymous
  As an user
  I am able to setup and use an anonymous feedback request

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
      | Name               | Anonymous feedback                          |
      | Description        | This is a simple anonymous feedback request |
      | Anonymous feedback | 1                                           |
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
    And I follow "(Activate Now)"
    And I press "Continue"
    And I log out

    And I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    And I click on "Request Feedback" "button" in the "Anonymous feedback" "table_row"
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
    Then I should see "0 out of 5" in the "Anonymous feedback" "table_row"
    And I log out

    And I log in as "user2"
    And I click on "360° Feedback" in the totara menu
    And I click on "Respond now" "button" in the "User One" "table_row"
    And I should see "This feedback request has been sent to 5 users (including you)."
    And I set the field "How much do you like me?" to "Not at all"
    And I press "Submit feedback"
    And I log out

    And I log in as "user5"
    And I click on "360° Feedback" in the totara menu
    And I click on "Respond now" "button" in the "User One" "table_row"
    And I should see "This feedback request has been sent to 5 users (including you)."
    And I set the field "How much do you like me?" to "Quite a bit"
    And I press "Submit feedback"
    And I log out

  Scenario: Check responses are anonymous
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    Then I should see "2 out of 5" in the "Anonymous feedback" "table_row"
    When I follow "Anonymous feedback"
    Then I should not see "Two"
    And I should not see "Five"
    And I should not see "Six"
    And I should not see "Seven"
    When I click on "View Response" "link" in the "//*[@id='region-main']//tbody//tr[1]" "xpath_element"
    Then I should see "Quite a bit"
    And I follow "Back"
    When I click on "View Response" "link" in the "//*[@id='region-main']//tbody//tr[2]" "xpath_element"
    Then I should see "Not at all"

  Scenario: Check you can't see who has not responded
    Given I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    When I click on "Edit" "link" in the "Anonymous feedback" "table_row"
    Then I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "User Six"
    And I should not see "Remove" in the "#system_user_5" "css_element"
    And I should not see "Remove" in the "#system_user_4" "css_element"
    And I should not see "Remove" in the "#system_user_4" "css_element"