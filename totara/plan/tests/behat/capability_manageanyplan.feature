@totara @totara_plan @javascript
Feature: Verify capability manageanyplan.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
      | manager2 | firstname2 | lastname2 | manager2@example.com |
      | manager3 | firstname3 | lastname3 | manager3@example.com |
    And the following "system role assigns" exist:
      | user     | role    |
      | manager2 | manager |
    # Assign the user a line manager so their plan can be sent for approval.
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager3 |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | Course 1  | 1                |
      | Course 2 | Course 2  | 1                |
      | Course 3 | Course 3  | 1                |
    And the following "competency" frameworks exist:
      | fullname               | idnumber | description           |
      | Competency Framework 1 | CF1      | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname     | idnumber | description            |
      | CF1       | Competency 1 | C1       | Competency description |
      | CF1       | Competency 2 | C2       | Competency description |
      | CF1       | Competency 3 | C3       | Competency description |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | P1   |
      | Program 2 | P2   |
      | Program 3 | P3   |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |
    And the following "objectives" exist in "totara_plan" plugin:
      | user     | plan                   | name        |
      | learner1 | learner1 Learning Plan | Objective 1 |
      | learner1 | learner1 Learning Plan | Objective 2 |
      | learner1 | learner1 Learning Plan | Objective 3 |

    # Login as admin and give the site manager the manageanyplan capability.
    When I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I follow "Site Manager"
    And I press "Edit"
    And I set the field "Filter" to "manageanyplan"
    And I click on "totara/plan:manageanyplan" "checkbox"
    And I press "Save changes"
    Then I log out

    # Log in as the learner and create some evidence.
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name        | My Evidence 1                  |
    And I press "Add evidence"
    Then I should see "Evidence created"

    When I press "Add evidence"
    And I set the following fields to these values:
      | Evidence name        | My Evidence 2                  |
    And I press "Add evidence"
    Then I should see "Evidence created"
    And I log out

  Scenario: Check a user can access and approve the plan with manageanyplan capability.

    # Login as the learner and navigate to the learning plan.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "learner1 Learning Plan" "link"
    # Add some courses to the plan.
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    # Check the selected courses appear in the plan.
    When I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"

    # Add some competencies to the plan.
    When I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I click on "Competency 1" "link"
    # Check the selected competency appear in the plan.
    And I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"

    # Add some programs to the plan.
    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I press "Add programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    # Check the selected program appears in the plan.
    And I click on "Save" "button" in the "Add programs" "totaradialogue"
    Then I should see "Program 1" in the ".dp-plan-component-items" "css_element"

    # Send the plan to the manager for approval.
    When I press "Send approval request"
    Then I should see "Approval request sent for plan \"learner1 Learning Plan\""
    And I should see "This plan has not yet been approved (Approval Requested)"
    And I log out

    # As the manager, access the learners plans.
    When I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plans and verify it hasn't been approved.
    And I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "This plan has not yet been approved"

    # Approve the plan.
    When I set the field "reasonfordecision" to "Nice plan!"
    And I press "Approve"
    Then I should see "You are viewing firstname1 lastname1's plan"
    And I should see "Plan \"learner1 Learning Plan\" has been approved"

  Scenario: Check a user can amend plan courses with manageanyplan capability.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plan.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"

    # Add some competencies to the plan.
    When I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I click on "Competency 1" "link"
    And I click on "Competency 2" "link"
    And I click on "Competency 3" "link"
    And I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    # Check the selected competency appear in the plan.
    Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"
    And I should see "Competency 2" in the ".dp-plan-component-items" "css_element"
    And I should see "Competency 3" in the ".dp-plan-component-items" "css_element"

    # Add some courses to the plan.
    When I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    And I click on "Course 2" "link"
    And I click on "Course 3" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    # Check the selected courses appear in the plan.
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"
    And I should see "Course 2" in the "#dp-component-update-table" "css_element"
    And I should see "Course 3" in the "#dp-component-update-table" "css_element"

    # Add some data to the first course.
    When I follow "Add RPL"
    And I set the field "Recognition of Prior Learning" to "Completed course before end of March 2014"
    And I press "Save changes"
    Then I should see "Recognition of Prior Learning updated"

    # Delete the second course to make sure we can.
    When I click on "Delete" "link" in the "Course 2" "table_row"
    And I should see "Are you sure you want to remove this item?"
    And I press "Continue"
    Then I should see "The item was successfully removed"

    # Add some linked competencies to the course.
    When I follow "Course 1"
    And I press "Add linked competencies"
    And I follow "Competency 1"
    And I follow "Competency 2"
    And I press "Save"
    Then I should see "Competency 1"
    And I should see "Competency 2"

    # Delete the second competency.
    # NOTE: There is an fault (nothing to do with capabilities) that prevents the "Remove selected links"
    # button appearing. If the page is reloaded the button appears.
    #   When I click on "input" "css_element" in the ".dp-plan-component-items" "css_element"
    #   And I click on "Remove selected links" "button" in the ".dp-plan-component-items" "css_element"
    #   Then I should see "The selected linked competencies have been removed from this course"

    # Add some evidence.
    When I press "Add linked evidence"
    And I follow "My Evidence 1"
    And I follow "My Evidence 2"
    And I click on "Save" "button" in the "assignevidence" "totaradialogue"
    Then I should see "My Evidence 1"
    And I should see "My Evidence 2"

    # Remove an evidence.
    When I click on "input" "css_element" in the "#linkedevidencelist_r0" "css_element"
    And I click on "Remove selected links" "button" in the "#dp-component-evidence-container" "css_element"
    Then I should see "The selected linked evidence have been removed from this course"

  Scenario: Check a user can amend plan competencies with manageanyplan capability.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plan.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"

    # Add some courses to the plan.
    When I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    And I click on "Course 2" "link"
    And I click on "Course 3" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    # Check the selected courses appear in the plan.
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"
    And I should see "Course 2" in the "#dp-component-update-table" "css_element"
    And I should see "Course 3" in the "#dp-component-update-table" "css_element"

    # Add some competencies to the plan.
    When I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I click on "Competency 1" "link"
    And I click on "Competency 2" "link"
    And I click on "Competency 3" "link"
    And I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    # Check the selected competency appear in the plan.
    Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"
    And I should see "Competency 2" in the ".dp-plan-component-items" "css_element"
    And I should see "Competency 3" in the ".dp-plan-component-items" "css_element"

    # Add some data to the first competency.
    When I follow "Set Status"
    And I set the field "Status" to "Competent"
    And I press "Save changes"
    # No confirmation message is displayed for the above status change
    # or the following so not a great 'then'.
    Then I set the field "compprof_competency[2]" to "Competent with supervision"
    And I wait "1" seconds
    And I set the field "priorities_competency[2]" to "Low"

    # Add some linked courses to the competency.
    When I follow "Competency 1"
    And I press "Add linked courses from plan"
    And I follow "Course 1"
    And I follow "Course 2"
    And I press "Save"
    Then I should see "Course 1"
    And I should see "Course 2"

    # Delete the second course.
    When I click on "input" "css_element" in the "#linkedcourselist_r0" "css_element"
    And I click on "Remove selected links" "button" in the "#dp-component-update" "css_element"
    Then I should see "The selected linked courses have been removed from this competency"

    # Add some evidence.
    When I press "Add linked evidence"
    And I follow "My Evidence 1"
    And I follow "My Evidence 2"
    And I click on "Save" "button" in the "assignevidence" "totaradialogue"
    Then I should see "My Evidence 1"
    And I should see "My Evidence 2"

    # Remove an evidence.
    When I click on "input" "css_element" in the "#linkedevidencelist_r0" "css_element"
    And I click on "Remove selected links" "button" in the "#dp-component-evidence-container" "css_element"
    Then I should see "The selected linked evidence have been removed from this competency"

  Scenario: Check a user can amend plan objectives with manageanyplan capability.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plan.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"

    # Add some courses to the plan.
    When I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    And I click on "Course 2" "link"
    And I click on "Course 3" "link"
    And I click on "Save" "button" in the "Add courses" "totaradialogue"
    # Check the selected courses appear in the plan.
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"
    And I should see "Course 2" in the "#dp-component-update-table" "css_element"
    And I should see "Course 3" in the "#dp-component-update-table" "css_element"

    # No need to add objectives to the plan as this has been done.
    # by the data generator, but delete the third objective.
    When I click on "Objectives" "link" in the "#dp-plan-content" "css_element"
    And I click on "Delete" "link" in the "Objective 3" "table_row"
    Then I should see "Delete objective"
    And I should see "Are you sure you want to delete this objective?"
    # Confirm the deletion.
    When I press "Continue"
    Then I should see "Objective deleted"
    And I should not see "Objective 3"

    # Set some of data on a competency. Unfortunately, there's not
    # confirmation of the data change, so not a great 'then'.
    When I set the field "proficiencies[1]" to "In Progress"
    Then I set the field "priorities_objective[1]" to "Medium"

    # Add some linked courses to the competency.
    When I follow "Objective 1"
    And I press "Add linked courses from plan"
    And I follow "Course 1"
    And I follow "Course 2"
    And I press "Save"
    Then I should see "Course 1"
    And I should see "Course 2"

    # Delete the second course.
    When I click on "input" "css_element" in the "#linkedcourselist_r0" "css_element"
    And I click on "Remove selected links" "button" in the "#dp-component-update" "css_element"
    Then I should see "The selected linked courses have been removed from this objective"

    # Add some evidence.
    When I press "Add linked evidence"
    And I follow "My Evidence 1"
    And I follow "My Evidence 2"
    And I click on "Save" "button" in the "assignevidence" "totaradialogue"
    Then I should see "My Evidence 1"
    And I should see "My Evidence 2"

    # Remove an evidence.
    When I click on "input" "css_element" in the "#linkedevidencelist_r0" "css_element"
    And I click on "Remove selected links" "button" in the "#dp-component-evidence-container" "css_element"
    Then I should see "The selected linked evidence have been removed from this objective"

  Scenario: Check a user can amend plan programs with manageanyplan capability.

    # As the manager, access the learners plans.
    Given I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".userprofile" "css_element"
    # Access the learners plan.
    When I click on "learner1 Learning Plan" "link"
    Then I should see "You are viewing firstname1 lastname1's plan"

    # Add some courses to the plan.
    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add programs" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Program 1" "link"
    And I click on "Program 2" "link"
    And I click on "Program 3" "link"
    And I click on "Save" "button" in the "Add programs" "totaradialogue"
    # Check the selected programs appear in the plan.
    Then I should see "Program 1" in the "#dp-component-update-table" "css_element"
    And I should see "Program 2" in the "#dp-component-update-table" "css_element"
    And I should see "Program 3" in the "#dp-component-update-table" "css_element"

    # Delete the third program.
    When I click on "Delete" "link" in the "Program 3" "table_row"
    And I should see "Are you sure you want to remove this item?"
    And I press "Continue"
    Then I should see "The item was successfully removed"
    And I should not see "Program 3"

    # Add some evidence.
    When I follow "Program 1"
    And I press "Add linked evidence"
    And I follow "My Evidence 1"
    And I follow "My Evidence 2"
    And I click on "Save" "button" in the "assignevidence" "totaradialogue"
    Then I should see "My Evidence 1"
    And I should see "My Evidence 2"

    # Remove an evidence.
    When I click on "input" "css_element" in the "#linkedevidencelist_r0" "css_element"
    And I click on "Remove selected links" "button" in the "#dp-component-evidence-container" "css_element"
    Then I should see "The selected linked evidence have been removed from this program"

  Scenario: manageanyplan has precedence over plan template permissions
    Given I log in as "admin"
    When I navigate to "Manage templates" node in "Site administration > Learning Plans"
    And I click on "Edit" "link" in the "Learning Plan (Default)" "table_row"
    And I follow "Workflow"
    And I click on "Custom workflow" "radio"
    And I press "Advanced workflow settings"
    And I set the field "viewmanager" to "Deny"
    And I set the field "createmanager" to "Deny"
    And I set the field "updatemanager" to "Deny"
    And I press "Save changes"
    Then I should see "Plan settings successfully updated"

    # Admin can view and create plans.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    Then I should see "You are viewing firstname1 lastname1's plans."
    And I should see "firstname1 lastname1's current and completed learning plans are shown below."
    And I should not see "You do not currently have permission to create a Learning Plan."
    When I press "Create new learning plan"
    And I set the field "Plan name" to "Learning Plan admin created"
    And I press "Create plan"
    Then I should see "Plan creation successful"
    And I log out

    # Manager 2 is not the learners manager but does have the totara/plan:manageanyplan capability so can create plans,
    # even though the plan template has deny for manager.
    When I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    Then I should see "You are viewing firstname1 lastname1's plans."
    And I should see "firstname1 lastname1's current and completed learning plans are shown below."
    And I should not see "You do not currently have permission to create a Learning Plan."
    And I should see "You can create a new learning plan by clicking \"Create a new learning plan\" to the right of the screen."
    When I press "Create new learning plan"
    And I set the field "Plan name" to "Learning Plan manager2 created"
    And I press "Create plan"
    Then I should see "Plan creation successful"
    And I log out

    # Now remove the totara/plan:manageanyplan capability.
    # Manager 2 should not be able to create a new plan as the plan template is set as deny for manager.
    When the following "permission overrides" exist:
      | capability                 | permission | role        | contextlevel | reference |
      | totara/plan:manageanyplan  | Prohibit   | manager     | System       |           |
    When I log in as "manager2"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    Then I should see "firstname1 lastname1's current and completed learning plans are shown below."
    And I should see "You do not currently have permission to create a Learning Plan."
    And I log out

    # Manager 3 is the learners manager but does not have totara/plan:manageanyplan capability.
    # They should not be able to create plans.
    # The plan template is set as deny for manager.
    When I log in as "manager3"
    And I click on "Team" in the totara menu
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    Then I should see "firstname1 lastname1's current and completed learning plans are shown below."
    And I should see "You do not currently have permission to create a Learning Plan."
    And I log out

    # Now add the totara/plan:manageanyplan capability.
    # Manager 3 should now be able to create plans.
    When the following "roles" exist:
      | shortname   |
      | planmanager |
    And the following "role assigns" exist:
      | user     | role        | contextlevel | reference |
      | manager3 | planmanager | System       |           |
    And the following "permission overrides" exist:
      | capability                 | permission | role             | contextlevel | reference |
      | totara/plan:manageanyplan  | Allow      | planmanager      | System       |           |
    And I log in as "manager3"
    And I click on "Team" in the totara menu
    And I follow "firstname1 lastname1"
    And I click on "Learning Plans" "link" in the ".profile_tree" "css_element"
    Then I should see "You are viewing firstname1 lastname1's plans."

    When I press "Create new learning plan"
    And I set the field "Plan name" to "Learning Plan manager3 created"
    And I press "Create plan"
    Then I should see "Plan creation successful"
    And I log out
