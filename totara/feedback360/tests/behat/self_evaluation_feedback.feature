@totara @totara_feedback360 @javascript
Feature: Self evaluation feedback
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

  Scenario: Add a feedback template with optional self evaluation so that the learner can choose to self evaluate.
    Given I log in as "admin"
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    And I press "Create Feedback"
    And I set the following fields to these values:
      | Name                 | Feedback 1                                     |
      | Description          | This is feedback with optional self evaluation |
      | id_selfevaluation_1  | 1                                              |
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
    When I follow "Feedback 1"
    Then I should see "Self evaluation Optional"
    And I log out

    # The feedback should be available to to the user.
    When I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    Then I should see "Feedback 1"

    # The user can make feedback requests including self evaluation.
    And "Request Feedback" "button" should exist
    And "Evaluate yourself" "button" should not exist
    When I click on "Request Feedback" "button" in the "Feedback 1" "table_row"
    And I press "Add user(s)"
    And I click on "User Two" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Three" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Four" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Five" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Six" "link" in the "Add user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add user(s)" "totaradialogue"
    And I wait "1" seconds
    And I set the field "selfevaluation" to "1"
    And I press "Request"
    Then I should see "Are you sure that you want to"
    And I should see "Include a self evaluation."
    And I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "User Six"
    When I press "Confirm"
    Then I should see "0 out of 6" in the "Feedback 1" "table_row"
    And "Request Feedback" "button" should not exist
    And "Evaluate yourself" "button" should exist

    # Check the message exists for the feedback requests.
    And the message "Request for self evaluation for Feedback 1" contains "You have been invited to complete a self evaluation feedback form. Your participation is optional." for "user1" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user2" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user3" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user4" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user5" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user6" user

    # Remove self evaluation.
    When I click on "Edit" "link" in the "Feedback 1" "table_row"
    Then I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "User Six"
    And the field "selfevaluation" matches value "1"
    And I set the field "selfevaluation" to "0"
    When I press "Update"
    Then I should see "Are you sure that you want to"
    And I should see "Opt out of self evaluation"
    When I press "Confirm"
    Then I should see "0 out of 5" in the "Feedback 1" "table_row"
    And "Request Feedback" "button" should not exist

    # Check message for self evaluation removal.
    And the message "User One Feedback request cancellation" contains "User One has cancelled their feedback request" for "user1" user

    # The user can self evaluate.
    When I click on "Edit" "link" in the "Feedback 1" "table_row"
    And I set the field "selfevaluation" to "1"
    And I press "Update"
    And I press "Confirm"
    Then I should see "0 out of 6" in the "Feedback 1" "table_row"
    When I click on "Evaluate yourself" "button" in the "Feedback 1" "table_row"
    And I should see "Self evaluation for User One"
    And I should see "This is your 360° Feedback Self Evaluation. Authorised users such as your manager may be able to review the feedback you provide."
    When I set the field "How much do you like me?" to "Not at all"
    And I press "Submit feedback"
    Then I should see "1 out of 6 1 New" in the "Feedback 1" "table_row"
    And "Evaluate yourself" "button" should not exist

    # Attempt to remove self evaluation after is has been completed.
    When I click on "Edit" "link" in the "Feedback 1" "table_row"
    Then the "Self evaluation" "field" should be disabled
    And I log out

  Scenario: Add a feedback template with required self evaluation so that the learner can not decline self evaluate.
    Given I log in as "admin"
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    And I press "Create Feedback"
    And I set the following fields to these values:
      | Name                | Feedback 1                                     |
      | Description         | This is feedback with required self evaluation |
      | id_selfevaluation_2 | 2                                              |
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
    When I follow "Feedback 1"
    Then I should see "Self evaluation Required"
    And I log out

    # The feedback should be available to to the user.
    When I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    Then I should see "Feedback 1"

    # The user can make feedback requests with self evaluation required.
    And "Request Feedback" "button" should exist
    And "Evaluate yourself" "button" should not exist
    When I click on "Request Feedback" "button" in the "Feedback 1" "table_row"
    And I press "Add user(s)"
    And I click on "User Two" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Three" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Four" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Five" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Six" "link" in the "Add user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add user(s)" "totaradialogue"
    And I wait "1" seconds
    And the "Self evaluation" "field" should be disabled
    And I press "Request"
    Then I should see "Are you sure that you want to"
    And I should not see "Include a self evaluation."
    And I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "User Six"
    When I press "Confirm"
    Then I should see "0 out of 6" in the "Feedback 1" "table_row"
    And "Request Feedback" "button" should not exist
    And "Evaluate yourself" "button" should exist

    # Check the message exists for the feedback requests.
    And the message "Request for self evaluation for Feedback 1" contains "You have been invited to complete a self evaluation feedback form. Your participation is required." for "user1" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user2" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user3" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user4" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user5" user
    And the message "Feedback request from User One" contains "User One has requested you fill in their feedback form." for "user6" user

    # The user can self evaluate.
    When I click on "Evaluate yourself" "button" in the "Feedback 1" "table_row"
    And I should see "Self evaluation for User One"
    And I should see "This is your 360° Feedback Self Evaluation. Authorised users such as your manager may be able to review the feedback you provide."
    When I set the field "How much do you like me?" to "Not at all"
    And I press "Submit feedback"
    Then I should see "1 out of 6 1 New" in the "Feedback 1" "table_row"
    And "Evaluate yourself" "button" should not exist



  Scenario: Add a feedback template with disabled self evaluation so that the learner can not self evaluate.
    Given I log in as "admin"
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    And I press "Create Feedback"
    And I set the following fields to these values:
      | Name                 | Feedback 1                                     |
      | Description          | This is feedback with self evaluation disabled |
      | id_selfevaluation_0  | 0                                              |
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
    When I follow "Feedback 1"
    Then I should see "Self evaluation Not allowed"
    And I log out

    # The feedback should be available to to the user.
    When I log in as "user1"
    And I click on "360° Feedback" in the totara menu
    Then I should see "Feedback 1"

    # The user can make feedback requests without seeing self evaluation options.
    And "Request Feedback" "button" should exist
    And "Evaluate yourself" "button" should not exist
    When I click on "Request Feedback" "button" in the "Feedback 1" "table_row"
    And I should not see "Self evaluation"
    And I press "Add user(s)"
    And I click on "User Two" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Three" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Four" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Five" "link" in the "Add user(s)" "totaradialogue"
    And I click on "User Six" "link" in the "Add user(s)" "totaradialogue"
    And I click on "Save" "button" in the "Add user(s)" "totaradialogue"
    And I wait "1" seconds
    And I press "Request"
    Then I should see "Are you sure that you want to"
    And I should not see "self evaluation."
    And I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"
    And I should see "User Six"
    When I press "Confirm"
    Then I should see "0 out of 5" in the "Feedback 1" "table_row"
    And "Request Feedback" "button" should not exist
    And "Evaluate yourself" "button" should not exist