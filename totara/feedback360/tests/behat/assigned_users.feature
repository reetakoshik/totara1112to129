@totara @totara_feedback360 @javascript
Feature: Feedback360 assigned users
  In order to determine which users are assigned to a 360 feedback
  As an admin
  I am able to see which users are assigned

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
      | user5    | User      | Five     | user5@example.com |
      | user6    | User      | Six      | user6@example.com |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
      | user3 | CH1    |
    And I log in as "admin"
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    And I press "Create Feedback"
    And I set the following fields to these values:
      | Name               | Test feedback                                 |
      | Description        | This is a simple feedback request for testing |
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
    Then I should see "User One" in the "#assignedusers" "css_element"
    And I should see "User Two" in the "#assignedusers" "css_element"
    And I should see "User Three" in the "#assignedusers" "css_element"
    And I log out

  Scenario: Assigned users in Draft Feedback360
    Given I log in as "admin"
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    Then I should see "Assigned to 3 draft user(s)" in the "Test feedback" "table_row"

    When I follow "Test feedback"
    And I switch to "Assignments" tab
    Then I should see "Assigned Groups"
    And I should see "3" in the "Cohort 1" "table_row"
    And I should see "Assigned Users"
    And the following should exist in the "datatable" table:
      | Learner     | Assigned Via      |
      | User One    | Audience Cohort 1 |
      | User Two    | Audience Cohort 1 |
      | User Three  | Audience Cohort 1 |

    # Removing and adding users to the audience should be reflected in the feedback
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Cohort 1"
    And I follow "Edit members"
    And I set the field "Current users" to "User One (user1@example.com)"
    And I press "Remove"
    And I set the field "Potential users" to "User Five (user5@example.com)"
    And I press "Add"
    And I set the field "Potential users" to "User Six (user6@example.com)"
    And I press "Add"
    And I press "Back to audiences"
    Then the following should exist in the "cohort_admin" table:
      | Audience Name    | Id   | No. of Members | Type     |
      | Cohort 1         | CH1  | 4              | Set      |

    When I am on homepage
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    Then I should see "Assigned to 4 draft user(s)" in the "Test feedback" "table_row"

    When I follow "Test feedback"
    And I switch to "Assignments" tab
    Then I should see "Assigned Groups"
    And I should see "4" in the "Cohort 1" "table_row"
    And I should see "Assigned Users"
    And the following should exist in the "datatable" table:
      | Learner    | Assigned Via      |
      | User Two   | Audience Cohort 1 |
      | User Three | Audience Cohort 1 |
      | User Five  | Audience Cohort 1 |
      | User Six   | Audience Cohort 1 |

  Scenario: Assigned users in Active Feedback360
    Given I log in as "admin"
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    Then I should see "Assigned to 3 draft user(s)" in the "Test feedback" "table_row"

    When I follow "Activate"
    And I press "Continue"
    Then I should see "Assigned to 3 user(s)" in the "Test feedback" "table_row"

    When I follow "Test feedback"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should see "This 360 Feedback is active, no changes can be made to learner assignments"
    And I should see "Assigned Users"
    And the following should exist in the "datatable" table:
      | Learner     |
      | User One    |
      | User Two    |
      | User Three  |

    # Removing and adding users to the audience should have no impact on the assigned users
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Cohort 1"
    And I follow "Edit members"
    And I set the field "Current users" to "User One (user1@example.com)"
    And I press "Remove"
    And I set the field "Potential users" to "User Five (user5@example.com)"
    And I press "Add"
    And I set the field "Potential users" to "User Six (user6@example.com)"
    And I press "Add"
    And I press "Back to audiences"
    Then the following should exist in the "cohort_admin" table:
      | Audience Name    | Id   | No. of Members | Type     |
      | Cohort 1         | CH1  | 4              | Set      |

    When I am on homepage
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    Then I should see "Assigned to 3 user(s)" in the "Test feedback" "table_row"

    When I follow "Test feedback"
    And I switch to "Assignments" tab
    Then I should see "Assigned Users"
    And the following should exist in the "datatable" table:
      | Learner     |
      | User One    |
      | User Two    |
      | User Three  |

  Scenario: Assigned users in Closed Feedback360
    Given I log in as "admin"
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    Then I should see "Assigned to 3 draft user(s)" in the "Test feedback" "table_row"

    When I follow "Activate"
    And I press "Continue"
    Then I should see "Assigned to 3 user(s)" in the "Test feedback" "table_row"
    When I click on "Close" "link" in the ".cell.lastcol" "css_element"
    And I press "Continue"
    Then I should see "Assigned to 3 user(s)" in the "Test feedback" "table_row"

    When I follow "Test feedback"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should see "This 360 Feedback is closed, no changes can be made to learner assignments"
    And I should see "Assigned Users"
    And the following should exist in the "datatable" table:
      | Learner     |
      | User One    |
      | User Two    |
      | User Three  |

    # Removing and adding users to the audience should have no impact on the assigned users
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Cohort 1"
    And I follow "Edit members"
    And I set the field "Current users" to "User One (user1@example.com)"
    And I press "Remove"
    And I set the field "Potential users" to "User Five (user5@example.com)"
    And I press "Add"
    And I set the field "Potential users" to "User Six (user6@example.com)"
    And I press "Add"
    And I press "Back to audiences"
    Then the following should exist in the "cohort_admin" table:
      | Audience Name    | Id   | No. of Members | Type     |
      | Cohort 1         | CH1  | 4              | Set      |

    When I am on homepage
    And I navigate to "Manage Feedback" node in "Site administration > Appraisals"
    Then I should see "Assigned to 3 user(s)" in the "Test feedback" "table_row"

    When I follow "Test feedback"
    And I switch to "Assignments" tab
    Then I should see "Assigned Users"
    And the following should exist in the "datatable" table:
      | Learner     |
      | User One    |
      | User Two    |
      | User Three  |
