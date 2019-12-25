@totara_hierarchy @totara
Feature: The generators create the expected frameworks

  Scenario: A position framework can be generated
    Given I am on a totara site
    And the following "position" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test position framework   | FW001    | Framework description |
    When I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I click on "Edit" "link" in the "Test position framework" "table_row"
    Then the following fields match these values:
      | Name        | Test position framework |
      | ID Number   | FW001                   |
      | Description | Framework description   |

  Scenario: An organisation framework can be generated
    Given the following "organisation" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test org framework        | FW002    | Framework description |
    When I log in as "admin"
    And I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
    And I click on "Edit" "link" in the "Test org framework" "table_row"
    Then the following fields match these values:
      | Name        | Test org framework      |
      | ID Number   | FW002                   |
      | Description | Framework description   |

  Scenario: A competency framework can be generated
    Given the following "competency" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test competency framework | FW003    | Framework description |
    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I click on "Edit" "link" in the "Test competency framework" "table_row"
    Then the following fields match these values:
      | Name        | Test competency framework |
      | ID Number   | FW003                     |
      | Description | Framework description     |

  Scenario: A goal framework can be generated
    Given the following "goal" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test goal framework       | FW004    | Framework description |
    When I log in as "admin"
    And I navigate to "Manage goals" node in "Site administration > Hierarchies > Goals"
    And I click on "Edit" "link" in the "Test goal framework" "table_row"
    Then the following fields match these values:
      | Name        | Test goal framework     |
      | ID Number   | FW004                   |
      | Description | Framework description   |
