@totara @totara_reportbuilder @javascript
Feature: Show only links to member information the manager has permission to see
  In order to prevent managers receiving no permission errors
  As a manager
  I need to see only links to member information that I have permission to see

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | user1    | User      | 1        | user1@example.com    |
      | user2    | User      | 2        | user2@example.com    |
    And the following job assignments exist:
      | user     | manager  | tempmanager | tempmanagerexpirydate |
      | user1    | manager1 | user2       | 2524474800            |


  Scenario: All links are shown with default permissions
    Given I log in as "manager1"
    When I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And I should see the "Picture of User 1" image in the "User 1" "table_row"
      And "Plans" "link" should exist in the "User 1" "table_row"
      And "Profile" "link" should exist in the "User 1" "table_row"
      And "Bookings" "link" should exist in the "User 1" "table_row"
      And "Records" "link" should exist in the "User 1" "table_row"
      And "Appraisals" "link" should exist in the "User 1" "table_row"
      And "360° Feedback" "link" should exist in the "User 1" "table_row"
      And "Goals" "link" should exist in the "User 1" "table_row"
      And "Required" "link" should exist in the "User 1" "table_row"

  Scenario: Plans link is not available if learningplans feature is not visible
    Given I log in as "admin"
      And I navigate to "Advanced features" node in "Site administration"
      And I set the field "Enable Learning Plans" to "Disable"
      And I press "Save changes"
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "Plans" "link" should not exist in the "User 1" "table_row"

  Scenario: Plans link is not available if the manager may not view member plans
    Given I log in as "admin"
      And the following "system role assigns" exist:
        | user     | role         |
        | manager1 | staffmanager |
      And the following "permission overrides" exist:
        | capability                       | permission | role            | contextlevel | reference |
        | totara/plan:accessanyplan        | Prohibit   | staffmanager    | System       |           |
        | totara/plan:manageanyplan        | Prohibit   | staffmanager    | System       |           |
        | totara/plan:accessplan           | Prohibit   | staffmanager    | System       |           |
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "Plans" "link" should not exist in the "User 1" "table_row"

  Scenario: Profile links are not available if the manager can't view the member's profile
    Given I log in as "admin"
      And the following "permission overrides" exist:
        | capability                       | permission | role          | contextlevel | reference |
        | moodle/user:viewdetails          | Prohibit   | staffmanager  | User         | user1     |
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should not exist in the "team_members" "table"
      And "Profile" "link" should not exist in the "User 1" "table_row"

  Scenario: Appraisals link is not available if appraisals feature is not visible
    Given I log in as "admin"
      And I navigate to "Advanced features" node in "Site administration"
      And I set the field "Enable Appraisals" to "Disable"
      And I press "Save changes"
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "Appraisals" "link" should not exist in the "User 1" "table_row"

  Scenario: Appraisals link is not available to temporary managers. Other links are
    Given I log in as "user2"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "Appraisals" "link" should not exist in the "User 1" "table_row"
      And I should see the "Picture of User 1" image in the "User 1" "table_row"
      And "Plans" "link" should exist in the "User 1" "table_row"
      And "Profile" "link" should exist in the "User 1" "table_row"
      And "Bookings" "link" should exist in the "User 1" "table_row"
      And "Records" "link" should exist in the "User 1" "table_row"
      And "360° Feedback" "link" should exist in the "User 1" "table_row"
      And "Goals" "link" should exist in the "User 1" "table_row"
      And "Required" "link" should exist in the "User 1" "table_row"

  Scenario: 360 Feedback link is not available if feedback360 feature is not visible
    Given I log in as "admin"
      And I navigate to "Advanced features" node in "Site administration"
      And I set the field "Enable 360 Feedbacks" to "Disable"
      And I press "Save changes"
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "360° Feedback" "link" should not exist in the "User 1" "table_row"

  Scenario: 360 Feedback link is not available if manager can't view staff feedback360
    Given I log in as "admin"
      And the following "permission overrides" exist:
        | capability                                        | permission | role          | contextlevel | reference |
        | totara/feedback360:viewstaffreceivedfeedback360   | Prohibit   | staffmanager  | User         | user1     |
        | totara/feedback360:viewstaffrequestedfeedback360  | Prohibit   | staffmanager  | User         | user1     |
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "360° Feedback" "link" should not exist in the "User 1" "table_row"

  Scenario: Goals link is not available if goals feature is not visible
    Given I log in as "admin"
      And I navigate to "Advanced features" node in "Site administration"
      And I set the field "Enable Goals" to "Disable"
      And I press "Save changes"
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "Goals" "link" should not exist in the "User 1" "table_row"

  Scenario: Goals link is not available if manager can't view staff goals
    Given I log in as "admin"
      And the following "permission overrides" exist:
        | capability                              | permission | role          | contextlevel | reference |
        | totara/hierarchy:viewstaffcompanygoal   | Prohibit   | staffmanager  | User         | user1     |
        | totara/hierarchy:viewstaffpersonalgoal  | Prohibit   | staffmanager  | User         | user1     |
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "Goals" "link" should not exist in the "User 1" "table_row"

  Scenario: Required link is not available if programs and certifications features are not visible
    Given I log in as "admin"
      And I navigate to "Advanced features" node in "Site administration"
      And I set the field "Enable Programs" to "Disable"
      And I set the field "Enable Certifications" to "Disable"
      And I press "Save changes"
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "Required" "link" should not exist in the "User 1" "table_row"

  Scenario: Required link is not available if manager can't view member's required learning
    Given I log in as "admin"
      And the following "system role assigns" exist:
        | user     | role         |
        | manager1 | staffmanager |
      And the following "permission overrides" exist:
        | capability                        | permission | role          | contextlevel | reference |
        | totara/program:accessanyprogram   | Prohibit   | staffmanager  | System       |           |
        | totara/program:viewprogram        | Prohibit   | staffmanager  | System       |           |
      And I log out
    When I log in as "manager1"
      And I click on "Team" in the totara menu
    Then "User 1" "link" should exist in the "team_members" "table"
      And "Required" "link" should not exist in the "User 1" "table_row"
