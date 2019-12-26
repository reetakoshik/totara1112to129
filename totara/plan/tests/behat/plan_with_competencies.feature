@totara @totara_plan @totara_hierarchy @totara_hierarchy_competency @javascript
Feature: Learner creates learning plan with competencies.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
      | manager1 | Dave1     | Manager1 | manager1@example.com |
    And the following "position" frameworks exist:
      | fullname             | idnumber | description           |
      | Position Framework 1 | PF1      | Framework description |
    And the following "position" hierarchy exists:
      | framework | fullname   | idnumber |
      | PF1       | Position 1 | P1       |
    And the following job assignments exist:
      | user     | position | manager  |
      | learner1 | P1       | manager1 |
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description           |
      | Competency Framework 1 | CF1      | Framework description |
      | Competency Framework 2 | CF2      | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |
      | CF1       | Competency 2 | C2       | Competency description |
      | CF2       | Competency 3 | C3       | Competency description |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name            |
      | learner1 | Learning Plan 1 |
    And the following "cohorts" exist:
      | name       | idnumber | contextlevel | reference |
      | Audience 1 | AUD1     | System       | 0         |

  Scenario: Test the learner can add and remove competencies from their learning plan prior to approval.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I follow "Learning Plans"
    And I follow "Learning Plan 1"

    # Add some competencies to the plan.
    And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I follow "Competency 1"
    And I follow "Competency 2"
    And I set the field "menu" to "Competency Framework 2"
    And I follow "Competency 3"

    # Check the selected competency appear in the plan.
    When I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    And the following should exist in the "dp-plan-component-items" table:
      | Competency Name | Courses | Evidence | Comments |
      | Competency 1    | 0       | 0        | 0        |
      | Competency 2    | 0       | 0        | 0        |
      | Competency 3    | 0       | 0        | 0        |

    # Delete a competency to make sure it's removed properly.
    When I click on "Delete" "link" in the "Competency 3" "table_row"
    Then I should see "Are you sure you want to remove this item?"
    When I press "Continue"
    Then I should not see "Competency 3" in the ".dp-plan-component-items" "css_element"

    # Send the plan to the manager for approval.
    When I press "Send approval request"
    Then I should see "Approval request sent for plan \"Learning Plan 1\""
    And I should see "This plan has not yet been approved (Approval Requested)"
    And I log out

    # As the manager, access the learners plans.
    When I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "Plans" "link" in the "Bob1 Learner1" "table_row"

    # Access the learners plans and verify it hasn't been approved.
    And I follow "Learning Plan 1"
    Then I should see "You are viewing Bob1 Learner1's plan"
    And I should see "This plan has not yet been approved"

    # Approve the plan.
    When I set the field "reasonfordecision" to "Nice plan!"
    And I press "Approve"
    Then I should see "You are viewing Bob1 Learner1's plan"
    And I should see "Plan \"Learning Plan 1\" has been approved"

    # Make sure the ajax competency update request works
    When I click on "Team" in the totara menu
    And I click on "Records" "link" in the "Bob1 Learner1" "table_row"
    And the field "competencyevidencestatus1" matches value "Not competent"
    And I set the field "competencyevidencestatus1" to "Competent"
    And I switch to "Other Evidence" tab
    And I switch to "Competencies" tab
    Then the field "competencyevidencestatus1" matches value "Competent"
    And I log out

    # Test Record of Learning: Competencies report with Global report restriction.
    And I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I click on "Edit members" "link" in the ".tabtree" "css_element"
    And I click on "Bob1 Learner1 (learner1@example.com)" "option"
    And I click on "Add" "button"
    And I click on "Dave1 Manager1 (manager1@example.com)" "option"
    And I click on "Add" "button"
    And I click on "Admin User (moodle@example.com)" "option"
    And I click on "Add" "button"

    And I set the following administration settings values:
      | Enable report restrictions | 1 |
    And I press "Save changes"

    And I navigate to "Global report restrictions" node in "Site administration > Reports"
    And I press "New restriction"
    And I set the following fields to these values:
      | Name   | 14064 restriction |
      | Active | 1                 |
    And I press "Save changes"

    And I set the field "menugroupselector" to "Audience"
    And I wait "1" seconds
    And I click on "Audience 1" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"

    And I click on "Users allowed to select restriction" "link" in the ".tabtree" "css_element"
    And I set the field "menugroupselector" to "Audience"
    And I click on "Audience 1" "link" in the "Assign a group to restriction" "totaradialogue"
    And I click on "Save" "button" in the "Assign a group to restriction" "totaradialogue"

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Record of Learning: Competencies report |
      | Source      | Record of Learning: Competencies        |
    And I click on "Create report" "button"
    And I press "Save changes"

    When I click on "View This Report" "link"
    And the following should exist in the "reportbuilder-table" table:
      | Plan            | Plan status | Competency name |
      | Learning Plan 1 | Approved    | Competency 1    |
      | Learning Plan 1 | Approved    | Competency 2    |

  Scenario: Test the competencies are hidden in the learning plan when hidden in hierarchy admin.

    Given I log in as "admin"

    # Update the learning plan template so competencies are automatically assigned by position.
    When I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "Learning Plan" "link" in the ".dp-templates" "css_element"
    And I switch to "Workflow" tab
    And I click on "Custom workflow" "radio"
    And I press "Advanced workflow settings"
    And I switch to "Competencies" tab
    And I click on "Automatically assign by position" "checkbox"
    And I press "Save changes"
    Then I should see "Competency settings successfully updated"

    # Assign Competency 1 to Position 1.
    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I follow "Position Framework 1"
    And I follow "Position 1"
    And I press "Add Competency"
    And I follow "Competency 1"
    And I follow "Competency 2"
    And I set the field "menu" to "Competency Framework 2"
    And I follow "Competency 3"
    And I click on "Save" "button" in the "Assign competencies" "totaradialogue"
    Then the following should exist in the "list-assignedcompetencies" table:
      | Type         | Name         |
      | Unclassified | Competency 1 |
      | Unclassified | Competency 2 |
      | Unclassified | Competency 3 |

    # Create a learning plan, Competency 1 should be added to the learning plan.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "Learning Plan 2"
    And I press "Create plan"
    Then I should see "Plan creation successful"
    # Check Competency 1 is added to the plan.
    When I switch to "Competencies" tab
    Then the following should exist in the "dp-plan-component-items" table:
      | Competency Name | Courses |
      | Competency 1    | 0       |
      | Competency 2    | 0       |
      | Competency 3    | 0       |

    # Hide the competency and ensure it's hidden in the plan.
    When I am on homepage
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I follow "Competency Framework 1"
    And I click on "Hide" "link" in the "Competency 1" "table_row"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    And I follow "Learning Plan 2"
    And I switch to "Competencies" tab
    Then the following should exist in the "dp-plan-component-items" table:
      | Competency Name | Courses | Evidence | Comments |
      | Competency 2    | 0       | 0        | 0        |
      | Competency 3    | 0       | 0        | 0        |
    And the following should not exist in the "dp-plan-component-items" table:
      | Competency Name | Courses | Evidence | Comments |
      | Competency 1    | 0       | 0        | 0        |

    # Hide the competency framework and make sure Competency 2 isn't added to the plan.
    When I am on homepage
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Hide" "link" in the "Competency Framework 1" "table_row"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "Learning Plan 3"
    And I press "Create plan"
    Then I should see "Plan creation successful"
    # Check Competency 1 and 2 are not added to the plan.
    When I switch to "Competencies" tab
    Then the following should exist in the "dp-plan-component-items" table:
      | Competency Name | Courses | Evidence | Comments |
      | Competency 3    | 0       | 0        | 0        |
    And the following should not exist in the "dp-plan-component-items" table:
      | Competency Name | Courses | Evidence | Comments |
      | Competency 1    | 0       | 0        | 0        |
      | Competency 2    | 0       | 0        | 0        |
