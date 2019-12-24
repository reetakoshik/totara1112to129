@totara @totara_dashboard
Feature: Test Dashboard for cohort users

@javascript
  Scenario: Test Dashboard is assigned to users
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | student1 | Student   | One      | student.one@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort 1 | CH1 |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |

    And I log in as "admin"
    And I navigate to "Front page settings" node in "Site administration > Front page"
      # Behat does not recognize field name in this case "Front page summary"
    And I set the following fields to these values:
      | summary | I'm a label on the frontpage |
    And I press "Save changes"
    And I navigate to "Dashboards" node in "Site administration > Appearance"
      # Add a dashboard.
    And I press "Create dashboard"
    And I set the following fields to these values:
      | Name | My first dashboard |
    And I click on "Available only to the following audiences" "radio"
    And I press "Assign new audiences"
    And I click on "Cohort 1" "link"
    And I press "OK"
    And I wait "1" seconds
    And I press "Create dashboard"
      # Add a second dashboard.
    And I press "Create dashboard"
    And I set the following fields to these values:
      | Name | My second dashboard |
    And I click on "Available only to the following audiences" "radio"
    And I press "Assign new audiences"
    And I click on "Cohort 1" "link"
    And I press "OK"
    And I wait "1" seconds
    And I press "Create dashboard"
      # Add content to the first dashboard.
    And I click on "My first dashboard" "link"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the field "Block title" to "First dashboard block header"
    And I set the field "Content" to "First dashboard block content"
    And I press "Save changes"
      # Add content to the second dashboard.
    And I navigate to "Dashboards" node in "Site administration > Appearance"
    And I click on "My second dashboard" "link"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the field "Block title" to "Second dashboard block header"
    And I set the field "Content" to "Second dashboard block content"
    And I press "Save changes"

      # Dasboard shows in the Totara menu
    And I set the following administration settings values:
      | enabletotaradashboard | Show |
    And I log out

    When I log in as "student1"
    Then I should see "Dashboard" in the totara menu
    And I log out

      # Dasboard does not show in the Totara menu
    And I log in as "admin"
    And I set the following administration settings values:
      | enabletotaradashboard | Disable |
    And I log out

    When I log in as "student1"
    Then I should not see "Dashboard" in the totara menu
    And I log out
