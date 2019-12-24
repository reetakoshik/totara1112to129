@totara @totara_program @javascript
Feature: Request extensions in programs
  In order to request extensions for a specific program
  As user
  I need to be able to request extension if it is enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Terry     | Manager  | manager1@example.com |
      | student1 | Sam       | Student  | student1@example.com |
    And the following "programs" exist in "totara_program" plugin:
      | fullname               | shortname |
      | Program toggle request | program1  |
    And the following "program assignments" exist in "totara_program" plugin:
      | user     | program  |
      | student1 | program1 |
    And the following "position" frameworks exist:
      | fullname      | idnumber |
      | PosHierarchy1 | FW001    |
    And the following "position" hierarchy exists:
      | framework | idnumber | fullname   |
      | FW001     | POS001   | Position1  |
    And the following job assignments exist:
      | user     | position | manager  | fullname       |
      | student1 | POS001   | manager1 | jobassignment1 |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable program extension requests | 1 |
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    And I press "Edit program details"
    And I click on "Details" "link" in the "#program-overview" "css_element"
    And I set the following fields to these values:
      | Allow extension requests | 1 |
    And I press "Save changes"
    And I click on "Assignments" "link" in the "#program-overview" "css_element"
    And I click on "Set due date" "link"
    And I click on "Day(s)" "option" in the "#timeperiod" "css_element"
    And I click on "Program enrollment date" "option" in the "#eventtype" "css_element"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I log out

  Scenario: Program extension request enable (site and program level), manager assigned and program about to expire
    Given I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    Then I should see "Request an extension"
    And I log out

  Scenario: Program extension request enable in site level but not in the program level, manager assigned and program about to expire
    Given I log in as "admin"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    And I press "Edit program details"
    And I click on "Details" "link" in the "#program-overview" "css_element"
    And I set the following fields to these values:
      | Allow extension requests | 0 |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    Then I should not see "Request an extension"

  Scenario: Program extension request not enable in site level but enable in the program level, manager assigned and program about to expire
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable program extension requests | 0 |
    And I log out

    When I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    Then I should not see "Request an extension"

  Scenario: Program extension request not enable in site or program level, manager assigned and program about to expire
    Given I log in as "admin"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    And I press "Edit program details"
    And I click on "Details" "link" in the "#program-overview" "css_element"
    And I set the following fields to these values:
      | Allow extension requests | 0 |
    And I press "Save changes"
    And I set the following administration settings values:
      | Enable program extension requests | 0 |
    And I log out

    When I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    Then I should not see "Request an extension"

  Scenario: Manager assigned, program about to expire, user made a extension request and
            program extension request disable in the program level.
    Given I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    And I click on "Request an extension" "link"
    And I set the following fields to these values:
      | extensionreason | I need an extension |
      | extensiontime   | 01/01/2020          |
    And I press "Ok"
    And I log out

    When I log in as "admin"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    And I press "Edit program details"
    And I click on "Details" "link" in the "#program-overview" "css_element"
    And I set the following fields to these values:
      | Allow extension requests | 0 |
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    Then I should not see "Pending extension request"
    And I log out

  Scenario: Program extension request enable in site and program level, no manager assigned and program about to expire
    Given I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Sam Student"
    And I click on "jobassignment1" "link"
    And I click on "Delete" "link" in the "#managertitle" "css_element"
    And I click on "Update job assignment" "button"
    And I log out

    When I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    Then I should not see "Request an extension"

  Scenario: Learner makes a request in the future. Manager is able to see it and grant it.
    Given I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    And I click on "Request an extension" "link"
    And I set the following fields to these values:
      | extensionreason     | I need an extension |
      | extensiontime       | 01/01/2010          |
      | extensiontimehour   | 10                  |
      | extensiontimeminute | 15                  |
    And I press "Ok"
    Then I should see "Cannot request extension that is before current program due date"
    When I click on "Request an extension" "link"
    And I set the following fields to these values:
      | extensionreason     | I need an extension |
      | extensiontime       | 14/01/2025          |
      | extensiontimehour   | 14                  |
      | extensiontimeminute | 55                  |
    And I press "Ok"
    Then I should see "Request for program extension has been sent to your manager(s)"
    When I log out
    And I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "1" "link" in the "Sam Student" "table_row"
    Then I should see "14 January 2025, 2:55 PM"
    When I click on "Grant" "option" in the ".approval" "css_element"
    And I press "Update Extensions"
    Then I should see "All extensions successfully updated"
    When I log out
    And I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    Then I should see "Due date: 14 January 2025, 2:55 PM"

  Scenario: Learner makes a request in the future. Manager is able to see it and deny it.
    Given I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    When I click on "Request an extension" "link"
    And I set the following fields to these values:
      | extensionreason     | I need an extension |
      | extensiontime       | 14/01/2025          |
      | extensiontimehour   | 14                  |
      | extensiontimeminute | 55                  |
    And I press "Ok"
    Then I should see "Request for program extension has been sent to your manager(s)"
    When I log out
    And I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "1" "link" in the "Sam Student" "table_row"
    Then I should see "14 January 2025, 2:55 PM"
    When I click on "Deny" "option" in the ".approval" "css_element"
    And I press "Update Extensions"
    Then I should see "All extensions successfully updated"
    When I log out
    And I log in as "student1"
    And I click on "Programs" in the totara menu
    And I click on "Program toggle request" "link"
    Then I should not see "Due date: 14 January 2025, 2:55 PM"

  Scenario: Extension request setting is saved when adding a program
    Given I log in as "admin"
    And I click on "Programs" in the totara menu
    And I press "Create Program"
    And I set the following fields to these values:
      | Allow extension requests | 0 |
    And I press "Save changes"
    Then I should see "Program creation successful"
    And the following fields match these values:
      | Allow extension requests | 0 |

    When I click on "Programs" in the totara menu
    And I press "Create Program"
    And I set the following fields to these values:
      | Allow extension requests | 1 |
    And I press "Save changes"
    Then I should see "Program creation successful"
    And the following fields match these values:
      | Allow extension requests | 1 |

    When I set the following administration settings values:
      | Enable program extension requests | 0 |
    And I click on "Programs" in the totara menu
    And I press "Create Program"
    Then I should not see "Allow extension requests"
    When I press "Save changes"
    Then I should see "Program creation successful"
