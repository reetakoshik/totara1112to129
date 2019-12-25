@javascript @totara @totara_reportbuilder
Feature: Disabled components disappear from reportbuilder filter options
  As an admin
  I should be able to disable components and do not see them in filter options

  Background:
    Given I am on a totara site

  Scenario Outline: Disable of competencies or learning plan advanced features will hide them from filter values
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "Alerts" "table_row"
    And I should see "<name>"
    When I set the following administration settings values:
      | <setting>     | Disable |
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "Alerts" "table_row"
    Then I should not see "<name>"
    Examples:
      | setting               | name          |
      | Enable Competencies   | Competency    |
      | Enable Learning Plans | Learning plan |

  Scenario: Disable of both programs and certifications advanced feature will hide program from filter values
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "Alerts" "table_row"
    And I should see "Program"
    # Disable one
    When I set the following administration settings values:
      | Enable Programs   | Disable |
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "Alerts" "table_row"
    Then I should see "Program"
    # Disable both
    When I set the following administration settings values:
      | Enable Certifications   | Disable |
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I click on "View" "link" in the "Alerts" "table_row"
    Then I should not see "Program"
