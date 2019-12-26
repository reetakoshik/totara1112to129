@totara @totara_reportbuilder @tabexport
Feature: Test that report builder can export reports
  In order to use my reportbuilder data elsewhere
  As a admin
  I need to be able to export data to file

  Background: Set up some user report
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Some User Report"
    And I set the field "Source" to "User"
    And I press "Create report"

  @javascript
  Scenario: Export report to CVS
    # NOTE: the CSV export is hacked to not force download in behat which makes it testable
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Some User Report"
    And I follow "View This Report"
    And I set the field "id_format" to "CSV"
    And I click on "Export" "button"
    And I should see "\"User's Fullname\",Username,\"User Last Login\""
    And I should see "Guest user"
    And I should see "Admin User"
