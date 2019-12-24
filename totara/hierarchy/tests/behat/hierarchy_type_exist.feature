@totara @totara_hierarchy
Feature: Test hierarchy generator.

  @javascript
  Scenario: Test position hierarchy type is created.
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Position Framework 001 | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname | idnumber |
      | PFW001        | Position | POS001   |
    And the following "position type" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber   |
      | Position type 1 | POSTYPE001 |
    Given I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Hierarchies > Positions"
    And I should see "Position type 1"
    # Test position hierarchy type is added to position hierarchy.
    And the following "textinput field for hierarchy type" exist in "totara_hierarchy" plugin:
      | hierarchy | typeidnumber | value |
      | position  | POSTYPE001   | Apple |
    # Test text profile field is added for position hierarchy type.
    And the following "hierarchy type assignments" exist in "totara_hierarchy" plugin:
      | hierarchy | field | typeidnumber | idnumber | value |
      | position  | text  | POSTYPE001   | POS001   | Apple |
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I follow "Position Framework 001"
    And I should see "Position"
    And I should see "Type: Position type 1"
    And I should see "Position type text: Apple"

  @javascript
  Scenario: Test organisation hierarchy type is created.
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname                   | idnumber |
      | Organisation Framework 001 | OFW001   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname        | idnumber |
      | OFW001        | Organisation    | ORG001   |
    And the following "organisation type" exist in "totara_hierarchy" plugin:
      | fullname            | idnumber   |
      | Organisation type 1 | ORGTYPE001 |
    Given I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Hierarchies > Organisations"
    And I should see "Organisation type 1"
    # Test organisation hierarchy type is added to organisation hierarchy.
    And the following "textinput field for hierarchy type" exist in "totara_hierarchy" plugin:
      | hierarchy    | typeidnumber | value |
      | organisation | ORGTYPE001   | Apple |
    And the following "hierarchy type assignments" exist in "totara_hierarchy" plugin:
      | hierarchy    | field | typeidnumber | idnumber | value |
      | organisation | text  | ORGTYPE001   | ORG001   | Apple |
    And I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
    And I follow "Organisation Framework 001"
    And I should see "Organisation"
    And I should see "Type: Organisation type 1"
    And I should see "Organisation type text: Apple"

  @javascript
  Scenario: Test competency hierarchy type is created.
    And the following "competency frameworks" exist in "totara_hierarchy" plugin:
      | fullname                 | idnumber |
      | Competency Framework 001 | CFW001   |
    And the following "competencies" exist in "totara_hierarchy" plugin:
      | comp_framework | fullname   | idnumber |
      | CFW001         | Competency | COMP001  |
    And the following "competency type" exist in "totara_hierarchy" plugin:
      | fullname          | idnumber    |
      | Competency type 1 | COMPTYPE001 |
    Given I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Hierarchies > Competencies"
    And I should see "Competency type 1"
    # Test competency hierarchy type is added to competency hierarchy.
    And the following "textinput field for hierarchy type" exist in "totara_hierarchy" plugin:
      | hierarchy  | typeidnumber | value |
      | competency | COMPTYPE001  | Apple |
    And the following "hierarchy type assignments" exist in "totara_hierarchy" plugin:
      | hierarchy  | field | typeidnumber | idnumber | value |
      | competency | text  | COMPTYPE001  | COMP001  | Apple |
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I follow "Competency Framework 001"
    And I should see "Competency"
    And I should see "Type: Competency type 1"
    And I should see "Competency type text: Apple"

  @javascript
  Scenario: Test goal hierarchy type is created.
    And the following "goal frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber |
      | Goal Framework 001 | GFW001   |
    And the following "goals" exist in "totara_hierarchy" plugin:
      | goal_framework | fullname | idnumber |
      | GFW001         | Goal     | GOAL001  |
    And the following "goal type" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber    |
      | Goal type 1 | GOALTYPE001 |
    Given I log in as "admin"
    And I navigate to "Manage company goal types" node in "Site administration > Hierarchies > Goals"
    And I should see "Goal type 1"
    # Test goal hierarchy type is added to goal hierarchy.
    And the following "textinput field for hierarchy type" exist in "totara_hierarchy" plugin:
      | hierarchy | typeidnumber | value |
      | goal      | GOALTYPE001  | Apple |
    And the following "hierarchy type assignments" exist in "totara_hierarchy" plugin:
      | hierarchy | field | typeidnumber | idnumber | value |
      | goal      | text  | GOALTYPE001  | GOAL001  | Apple |
    And I navigate to "Manage goals" node in "Site administration > Hierarchies > Goals"
    And I follow "Goal Framework 001"
    And I should see "Goal"
    And I should see "Type: Goal type 1"
    And I should see "Goal type text: Apple"

