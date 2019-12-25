@totara @totara_hierarchy @totara_generator
Feature: Behat generators for hierarchies work
  In order to use behat generators
  As a behat writer
  I need to be able to create hierarchies via behat generator

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |

  @javascript
  Scenario: Verify the hierarchy generators work
    Given the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Organisation Framework | oframe   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber | org_framework |
      | Organisation One | org1     | oframe        |
      | Organisation Two | org2     | oframe        |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber |
      | Position Framework | pframe   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber | pos_framework |
      | Position One | pos1     | pframe        |
      | Position Two | pos2     | pframe        |
    And the following job assignments exist:
      | user    | fullname       | idnumber | manager | position | organisation |
      | user001 | jobassignment1 | 1        | admin   | pos1     | org1         |
      | user002 | jobassignment1 | 1        | user001 | pos2     | org2         |
      | admin   | administrator  | 1        |         |          |              |

    When I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    Then I should see "Position Framework"

    When I click on "Position Framework" "link" in the "#frameworkstable" "css_element"
    Then I should see "Position One"
    And I should see "Position Two"

    When I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
    Then I should see "Organisation Framework"

    When I click on "Organisation Framework" "link" in the "#frameworkstable" "css_element"
    Then I should see "Organisation One"
    And I should see "Organisation Two"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "fn_001 ln_001" "link"
    And I click on "jobassignment1" "link"
    Then I should see "Position One" in the "#region-main" "css_element"
    And I should see "Organisation One" in the "#region-main" "css_element"
    And I should see "Admin User" in the "#region-main" "css_element"

  @javascript
  Scenario: Verify the user interface works the same as hierarchy generators
    Given I log in as "admin"

    When I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I click on "Add new position framework" "button"
    And I set the following fields to these values:
        | fullname | Position Framework |
        | idnumber | pframe             |
    And I press "Save changes"
    Then I should see "Position Framework"

    When I click on "Position Framework" "link" in the "#frameworkstable" "css_element"
    And I click on "Add new position" "button"
    And I set the following fields to these values:
        | fullname | Position One |
        | idnumber | pos1         |
    And I press "Save changes"
    And I press "Return to position framework"
    And I click on "Add new position" "button"
    And I set the following fields to these values:
        | fullname | Position Two |
        | idnumber | pos2         |
    And I press "Save changes"
    And I press "Return to position framework"
    Then I should see "Position One"
    And I should see "Position Two"

    When I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
    And I click on "Add new organisation framework" "button"
    And I set the following fields to these values:
        | fullname | Organisation Framework |
        | idnumber | oframe                 |
    And I press "Save changes"
    Then I should see "Organisation Framework"

    When I click on "Organisation Framework" "link" in the "#frameworkstable" "css_element"
    And I click on "Add new organisation" "button"
    And I set the following fields to these values:
        | fullname | Organisation One |
        | idnumber | org1             |
    And I press "Save changes"
    And I press "Return to organisation framework"
    And I click on "Add new organisation" "button"
    And I set the following fields to these values:
        | fullname | Organisation Two |
        | idnumber | org2             |
    And I press "Save changes"
    And I press "Return to organisation framework"
    Then I should see "Organisation One"
    And I should see "Organisation Two"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "fn_001 ln_001" "link"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | jobassignment1 |
      | ID Number | 1              |
    And I click on "Choose position" "button"
    And I click on "Position One" "link" in the "position" "totaradialogue"
    And I click on "OK" "button" in the "position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation One" "link" in the "organisation" "totaradialogue"
    And I click on "OK" "button" in the "organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "Admin User (moodle@example.com) - create empty job assignment" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "manager" "totaradialogue"
    And I press "Add job assignment"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "fn_001 ln_001" "link"
    And I click on "jobassignment1" "link"
    Then I should see "Position One" in the "#region-main" "css_element"
    And I should see "Organisation One" in the "#region-main" "css_element"
    And I should see "Admin User (moodle@example.com) - Unnamed job assignment (ID: 1)" in the "#region-main" "css_element"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "fn_002 ln_002" "link"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | jobassignment2 |
      | ID Number | 1              |
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "position" "totaradialogue"
    And I click on "OK" "button" in the "position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "organisation" "totaradialogue"
    And I click on "OK" "button" in the "organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "fn_001 ln_001" "link" in the "Choose manager" "totaradialogue"
    And I click on "jobassignment1" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "manager" "totaradialogue"
    And I press "Add job assignment"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "fn_002 ln_002" "link"
    And I click on "jobassignment2" "link"
    Then I should see "Position Two" in the "#region-main" "css_element"
    And I should see "Organisation Two" in the "#region-main" "css_element"
    And I should see "fn_001 ln_001 (user001@example.com) - jobassignment1" in the "#region-main" "css_element"

  @javascript
  Scenario: Verify the goal generators work
    Given the following "goal frameworks" exist in "totara_hierarchy" plugin:
      | fullname       | idnumber |
      | Goal Framework | gframe   |
    And the following "goals" exist in "totara_hierarchy" plugin:
      | fullname | idnumber | goal_framework |
      | Goal One | goal1    | gframe         |
      | Goal Two | goal2    | gframe         |

    When I log in as "admin"
    And I navigate to "Manage goals" node in "Site administration > Hierarchies > Goals"
    Then I should see "Goal Framework"

    When I click on "Goal Framework" "link" in the "#frameworkstable" "css_element"
    Then I should see "Goal One"
    And I should see "Goal Two"
