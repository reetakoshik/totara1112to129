@totara @totara_reportbuilder @javascript
Feature: Test role access restrictions in Reportbuilder
  In order to control user access to reports
  As a admin
  I need to be able to configure role access restrictions

  #NOTE: The javascript tag is required because the form filling is seriously broken in Moodle form stuff for Goute driver!!!

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email                   |
      | usermanager | User      | Manager  | usermanager@example.com |
      | usercreator | User      | Creator  | usercreator@example.com |
      | userstudent | User      | Student  | userstudent@example.com |
      | usernobody  | User      | Nobody   | usernobody@example.com  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user        | course | role    |
      | userstudent | C1     | student |
    And the following "role assigns" exist:
      | user        | role          | contextlevel | reference |
      | usercreator | coursecreator | System       |           |
      | usermanager | manager       | System       |           |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Some User Report"
    And I set the field "Source" to "User"
    And I press "Create report"

  Scenario: Verify role access defaults in Reportbuilder
    Given I switch to "Access" tab
    Then the field "Only certain users can view this report (see below)" matches value "1"
    And the field "Context" matches value "site"
    And the field "Site Manager" matches value "1"
    And the field "Course creator" matches value "0"
    And the field "Editing Trainer" matches value "0"
    And the field "Trainer" matches value "0"
    And the field "Learner" matches value "0"
    And the field "Guest" matches value "0"
    And the field "Authenticated user" matches value "0"
    And the field "Authenticated user on frontpage" matches value "0"
    And the field "Staff Manager" matches value "0"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usermanager"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usercreator"
    Then I should not see "Reports" in the totara menu
    And I log out

    When I log in as "userstudent"
    Then I should not see "Reports" in the totara menu
    And I log out

    When I log in as "usernobody"
    Then I should not see "Reports" in the totara menu

  Scenario: Set Reportbuilder role access restriction for role in any context
    Given I switch to "Access" tab
    And I set the field "Context" to "any"
    And I set the field "Site Manager" to ""
    And I set the field "Learner" to "1"
    And I press "Save changes"
    And the field "Context" matches value "any"
    And the field "Site Manager" matches value "0"
    And the field "Learner" matches value "1"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usermanager"
    Then I should not see "Reports" in the totara menu
    And I log out

    When I log in as "usercreator"
    Then I should not see "Reports" in the totara menu
    And I log out

    When I log in as "userstudent"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usernobody"
    Then I should not see "Reports" in the totara menu

  Scenario: Set Reportbuilder role access restriction for role in system context
    Given I switch to "Access" tab
    And I set the field "Context" to "site"
    And I set the field "Site Manager" to "0"
    And I set the field "Learner" to "1"
    And I press "Save changes"
    And the field "Context" matches value "site"
    And the field "Site Manager" matches value "0"
    And the field "Learner" matches value "1"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usermanager"
    Then I should not see "Reports" in the totara menu
    And I log out

    When I log in as "usercreator"
    Then I should not see "Reports" in the totara menu
    And I log out

    When I log in as "userstudent"
    Then I should not see "Reports" in the totara menu
    And I log out

    When I log in as "usernobody"
    Then I should not see "Reports" in the totara menu

  Scenario: Set Reportbuilder role access restriction for authenticated user
    Given I switch to "Access" tab
    And I set the field "Context" to "site"
    And I set the field "Site Manager" to "0"
    And I set the field "Authenticated user" to "1"
    And I press "Save changes"
    And the field "Context" matches value "site"
    And the field "Site Manager" matches value "0"
    And the field "Authenticated user" matches value "1"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usermanager"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usercreator"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "userstudent"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usernobody"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"

  Scenario: Set Reportbuilder role access restriction for multiple roles
    Given I switch to "Access" tab
    And I set the field "Context" to "site"
    And I set the field "Site Manager" to "1"
    And I set the field "Course creator" to "1"
    And I set the field "Learner" to "1"
    And I press "Save changes"
    And the field "Context" matches value "site"
    And the field "Site Manager" matches value "1"
    And the field "Course creator" matches value "1"
    And the field "Learner" matches value "1"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usermanager"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usercreator"
    And I click on "Reports" in the totara menu
    And I follow "Some User Report"
    And I should see "usernobody"
    Then I should see "Some User Report"
    And I log out

    When I log in as "userstudent"
    Then I should not see "Reports" in the totara menu
    And I log out

    When I log in as "usernobody"
    Then I should not see "Reports" in the totara menu

  Scenario: Disable Reportbuilder role access restrictions
    Given I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usermanager"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usercreator"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "userstudent"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"
    And I log out

    When I log in as "usernobody"
    And I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"

  Scenario: Set Reportbuilder role access restriction for authenticated user in any context
    # This test is here because the authenticated user role is not automatically
    # given to a system user. If the the report builder code does not explicitly
    # add the role, then this test will fail for the "any context" option.

    Given I switch to "Access" tab
    And I set the field "Context" to "any"
    And I set the field "Site Manager" to "0"
    And I set the field "Authenticated user" to "1"
    And I press "Save changes"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"

    When I log out
    And I log in as "usermanager"
    Then I should see "Reports"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"

    When I log out
    And I log in as "usercreator"
    Then I should see "Reports"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"

    When I log out
    And I log in as "userstudent"
    Then I should see "Reports"

    When  I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"

    When I log out
    And I log in as "usernobody"
    Then I should see "Reports"

    When I click on "Reports" in the totara menu
    Then I should see "Some User Report"
    And I follow "Some User Report"
    And I should see "usernobody"

    Given I log out
    And I log in as "admin"
    And I click on "Reports" in the totara menu
    And I follow "Some User Report"
    And I press "Edit this report"
    And I click on "Access" "link" in the ".tabtree" "css_element"
    And I set the field "Context" to "any"
    And I set the field "Authenticated user" to "0"
    And I press "Save changes"

    When I log out
    And I log in as "usermanager"
    Then I should not see "Reports"

    When I log out
    And I log in as "usercreator"
    Then I should not see "Reports"

    When I log out
    And I log in as "userstudent"
    Then I should not see "Reports"

    When I log out
    And I log in as "usernobody"
    Then I should not see "Reports"


