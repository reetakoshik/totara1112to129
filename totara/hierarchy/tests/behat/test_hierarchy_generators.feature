@totara_hierarchy @totara
Feature: The generators create the expected hierarchy elements

  Scenario: A position item can be generated
    Given I am on a totara site
    And the following "position" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test position framework   | FW001    | Framework description |
    And the following "position" hierarchy exists:
      | framework | fullname          | idnumber | description         |
      | FW001     | First position    | POS001   | This is a position  |
    When I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I follow "Test position framework"
    And I click on "Edit" "link" in the "First position" "table_row"
    Then the following fields match these values:
      | Name               | First position          |
      | Position ID number | POS001                  |
      #| Description        | This is a position      |

  Scenario: A hierarchy of positions can be generated
    Given the following "position" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test position framework   | FW001    | Framework description |
    And the following "position" hierarchy exists:
      | framework | fullname          | idnumber | parent | description         |
      | FW001     | First position    | POS001   |        | This is a position  |
      | FW001     | Second position   | POS002   | POS001 | Another position    |
    When I log in as "admin"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I follow "Test position framework"
    And I click on "Edit" "link" in the "Second position" "table_row"
    Then the following fields match these values:
      | Parent             | First position          |
      | Name               | Second position         |
      | Position ID number | POS002                  |
      #| Description        | Another position        |

  Scenario: An organisation item can be generated
    Given the following "organisation" frameworks exist:
      | fullname                    | idnumber | description           |
      | Test organisation framework | FW002    | Framework description |
    And the following "organisation" hierarchy exists:
      | framework | fullname            | idnumber | description             |
      | FW002     | First organisation  | ORG001   | This is an organisation |
    When I log in as "admin"
    And I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
    And I follow "Test organisation framework"
    And I click on "Edit" "link" in the "First organisation" "table_row"
    Then the following fields match these values:
      | Name                   | First organisation     |
      | Organisation ID number | ORG001                 |
      #| Description           | This is a organisation  |

  Scenario: A hierarchy of organisations can be generated
    Given the following "organisation" frameworks exist:
      | fullname                    | idnumber | description           |
      | Test organisation framework | FW002    | Framework description |
    And the following "organisation" hierarchy exists:
      | framework | fullname            | idnumber | parent | description             |
      | FW002     | First organisation  | ORG001   |        | This is an organisation |
      | FW002     | Second organisation | ORG002   | ORG001 | Another organisation    |
    When I log in as "admin"
    And I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
    And I follow "Test organisation framework"
    And I click on "Edit" "link" in the "Second organisation" "table_row"
    Then the following fields match these values:
      | Parent                 | First organisation   |
      | Name                   | Second organisation  |
      | Organisation ID number | ORG002               |
      #| Description            | Another organisation |

  Scenario: A competency item can be generated
    Given the following "competency" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test competency framework | FW001    | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname            | idnumber | description             |
      | FW001     | First competency    | COMP001  | This is a competency    |
    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I follow "Test competency framework"
    And I click on "Edit" "link" in the "First competency" "table_row"
    Then the following fields match these values:
      | Name                 | First competency     |
      | Competency ID number | COMP001              |
      #| Description          | This is a competency |

  Scenario: A hierarchy of competencies can be generated
    Given the following "competency" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test competency framework | FW001    | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname            | idnumber | parent  | description             |
      | FW001     | First competency    | COMP001  |         | This is a competency    |
      | FW001     | Second competency   | COMP002  | COMP001 | Another competency      |
    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I follow "Test competency framework"
    And I click on "Edit" "link" in the "Second competency" "table_row"
    Then the following fields match these values:
      | Parent               | First competency   |
      | Name                 | Second competency  |
      | Competency ID number | COMP002            |
      #| Description          | Another competency |

  Scenario: A goal item can be generated
    Given the following "goal" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test goal framework       | FW001    | Framework description |
    And the following "goal" hierarchy exists:
      | framework | fullname            | idnumber | description             |
      | FW001     | First goal          | GOAL001  | This is a goal          |
    When I log in as "admin"
    And I navigate to "Manage goals" node in "Site administration > Hierarchies > Goals"
    And I follow "Test goal framework"
    And I click on "Edit" "link" in the "First goal" "table_row"
    Then the following fields match these values:
      | Name               | First goal       |
      | Goal ID number     | GOAL001          |
      #| Description        | This is a goal   |

  Scenario: A hierarchy of goals can be generated
    Given the following "goal" frameworks exist:
      | fullname                  | idnumber | description           |
      | Test goal framework       | FW001    | Framework description |
    And the following "goal" hierarchy exists:
      | framework | fullname            | idnumber | parent  | description             |
      | FW001     | First goal          | GOAL001  |         | This is a goal          |
      | FW001     | Second goal         | GOAL002  | GOAL001 | Another goal            |
    When I log in as "admin"
    And I navigate to "Manage goals" node in "Site administration > Hierarchies > Goals"
    And I follow "Test goal framework"
    And I click on "Edit" "link" in the "Second goal" "table_row"
    Then the following fields match these values:
      | Parent             | First goal       |
      | Name               | Second goal      |
      | Goal ID number | GOAL002              |
      #| Description        | Another goal     |

