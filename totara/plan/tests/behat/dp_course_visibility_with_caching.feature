@totara @totara_plan
Feature: See that course visibility affects Record of Learning: Courses content correctly when using report caching.
  Change the visibility settings of a course through several states and see that the course is correctly displayed in the RoL.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | mana003  | fn_003    | ln_003   | user003@example.com |
    And the following "courses" exist:
      | fullname                          | shortname   | enablecompletion |
      | RoLCourseVisibility Test Course 1 | testcourse1 | 1                |
      | RoLCourseVisibility Test Course 2 | testcourse2 | 1                |
    And the following "course enrolments" exist:
      | user    | course      | role    |
      | user001 | testcourse1 | student |
      | user002 | testcourse1 | student |
      | user002 | testcourse2 | student |
    And the following job assignments exist:
      | user    | fullname       | manager |
      | user001 | jobassignment1 | mana003 |
      | user002 | jobassignment2 | mana003 |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable report caching | 1 |

  @javascript
  Scenario: Normal visibility (default), visible (default).
    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden.
    And I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visible" to "0"
    And I press "Save and display"
    Then I should see "Topic 1"

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "Report data last updated"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal visibility (default), hidden, 2nd course assigned.
    And I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visible" to "0"
    And I press "Save and display"
    Then I should see "Topic 1"

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should be visible and contain the course for learner.
    When I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be visible and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Courses"
    And I should see "Report data last updated"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Normal vis hidden, switch to audience vis.
    And I click on "Courses" in the totara menu
    And I click on "RoLCourseVisibility Test Course 1" "link"
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the field "Visible" to "0"
    And I press "Save and display"
    Then I should see "Topic 1"

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # To start, check that RoL: Courses tab is shown and contains the course for learner.
    When I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    # Should be marked hidden.
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # Switch the site setting, course is now set to all users (default).
    When I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"
    And I log out

    # Then, check that RoL: Courses tab should is shown and contains the course for learner.
    When I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    # Should NOT be marked hidden!!!
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be visible and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, all users (default).
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users and auds.
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

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, enrolled users.
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

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should be shown and contains the course for learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, no users.
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

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should not be visible to learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    # This one is a bit of an anomaly, but not much we can do about it without a big refactor.
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    And I should not see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_001 ln_001" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    # This one is a bit of an anomaly, but not much we can do about it without a big refactor.
    Then I should see "Record of Learning for fn_001 ln_001 : All Courses"
    And I should see "Report data last updated"
    And I should not see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should not see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: Audience visibility, no users, 2nd course assigned.
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

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should be visible but not contain the course for learner.
    When I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    And I should not see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Courses"
    And I should see "Report data last updated"
    And I should not see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"

  @javascript
  Scenario: ROL Courses with caching: Audience visibility, no users, 2nd course assigned, completed then unassigned.
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

    # Some method of completion is necessary to be able to apply RPL.
    When I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Site Manager | 1 |
    And I press "Save changes"
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I complete the course via rpl for "fn_002 ln_002" with text "rpl"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "fn_002 ln_002" "table_row"
    And I press "Continue"
    Then I should not see "fn_002 ln_002"
    And I should see "fn_001 ln_001"

    # Set up report caching, after configuring the course.
    When I navigate to "Manage embedded reports" node in "Site administration > Reports > Report builder"
    And I follow "Record of Learning: Courses"
    And I switch to "Performance" tab
    And I set the following fields to these values:
      | Enable Report Caching | 1 |
      | Generate Now          | 1 |
    And I click on "Save changes" "button"
    Then I should see "Report Updated"
    And I log out

    # RoL: Courses tab should be visible but not contain the course for learner.
    When I log in as "user002"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
    And I log out

    # RoL: Courses tab should be shown and contains the course for manager.
    When I log in as "mana003"
    And I click on "Team" in the totara menu
    And I click on "fn_002 ln_002" "link"
    And I click on "Record of Learning" "link" in the ".userprofile" "css_element"
    Then I should see "Record of Learning for fn_002 ln_002 : All Courses"
    And I should see "Report data last updated"
    And I should see "RoLCourseVisibility Test Course 1" in the "#dp-plan-content" "css_element"
    And I should see "RoLCourseVisibility Test Course 2" in the "#dp-plan-content" "css_element"
