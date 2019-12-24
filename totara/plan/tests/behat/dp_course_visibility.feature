@totara @totara_plan
Feature: See that course visibility affects Record of Learning: Courses content correctly.
  Change the visibility settings of a course through several states and see that the course is correctly displayed in the RoL.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | mana003  | fn_003    | ln_003   | user003@example.com |
    And the following "courses" exist:
      | fullname                          | shortname   |
      | RoLCourseVisibility Test Course 1 | testcourse1 |
      | RoLCourseVisibility Test Course 2 | testcourse2 |
    And the following "course enrolments" exist:
      | user    | course      | role    |
      | user001 | testcourse1 | student |
      | user002 | testcourse1 | student |
      | user002 | testcourse2 | student |
    And the following job assignments exist:
      | user    | fullname       | manager |
      | user001 | jobassignment1 | mana003 |
      | user002 | jobassignment2 | mana003 |

  @javascript
  Scenario: Normal visibility (default), visible (default).
    # RoL: Courses tab should be shown and contains the course for learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden.
    When I log in as "admin"
    And I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visible" to "0"
    And I press "Save and display"
    Then I should see "Topic 1"

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden, 2nd course assigned.
    When I log in as "admin"
    And I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visible" to "0"
    And I press "Save and display"
    Then I should see "Topic 1"

    # RoL: Courses tab should be visible and contain the course for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be visible and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Courses"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal vis hidden, switch to audience vis.
    When I log in as "admin"
    And I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visible" to "0"
    And I press "Save and display"
    Then I should see "Topic 1"

    # To start, check that RoL: Courses tab is shown and contains the course for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # Switch the site setting, course is now set to all users (default).
    When I log out
    And I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    # Then, check that RoL: Courses tab should is shown and contains the course for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    # Should NOT be marked hidden!!!
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be visible and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, all users (default).
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users and auds.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visibility" to "Enrolled users and members of the selected audiences"
    And I press "Save and display"
    Then I should see "Topic 1"

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visibility" to "Enrolled users only"
    And I press "Save and display"
    Then I should see "Topic 1"

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, no users.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    # RoL: Courses tab should not be visible to learner.
    When I log out
    And I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    # This one is a bit of an anomaly, but not much we can do about it without a big refactor.
    Then I should see "Record of Learning : All Courses"
    And I should not see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    # This one is a bit of an anomaly, but not much we can do about it without a big refactor.
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should not see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, no users, 2nd course assigned.
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    Then I should see "Topic 1"

    # RoL: Courses tab should be visible but not contain the course for learner.
    When I log out
    And I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should not see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log out
    And I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Courses"
    And I should not see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
