@totara @totara_cohort @totara_hierarchy
Feature: Test organisation and position rulesets within framework display

  Background:
    Given I am on a totara site
    # Organisation within framework test.
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname             | idnumber  |
      | Org framework 12966A | OFW12966A |
      | Org framework 12966B | OFW12966B |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname            | idnumber   |
      | OFW12966A     | Organisation 12966A | ORG12966A  |
      | OFW12966A     | Organisation 12966B | ORG12966B  |
      | OFW12966A     | Organisation 12966C | ORG12966C  |
      | OFW12966B     | Organisation 12966A | ORG12966AA |
      | OFW12966B     | Organisation 12966E | ORG12966E  |
      | OFW12966B     | Organisation 12966F | ORG12966F  |

    # Position within framework test.
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname             | idnumber  |
      | Pos framework 12966A | PFW12966A |
      | Pos framework 12966B | PFW12966B |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname        | idnumber   |
      | PFW12966A     | Position 12966A | POS12966A  |
      | PFW12966A     | Position 12966B | POS12966B  |
      | PFW12966A     | Position 12966C | POS12966C  |
      | PFW12966B     | Position 12966A | POS12966AA |
      | PFW12966B     | Position 12966E | POS12966E  |
      | PFW12966B     | Position 12966F | POS12966F  |

    And the following "cohorts" exist:
      | name             | idnumber   | cohorttype |
      | Audience 12966O  | AUD12966O  | 2          |
      | Audience 12966P  | AUD12966P  | 2          |
      | Audience 12966OP | AUD12966OP | 2          |

  @javascript
  Scenario: Test organisations within the framework in dynamic audience with equals rule.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 12966O"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Organisations"
    And I set the field "equal" to "Equal to"
    And I click on "Organisation 12966A" "link"
    And I click on "Organisation 12966B" "link"
    And I click on "Organisation 12966C" "link"
    When I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    And I should see "User's organisation in any of their job assignments is equal to"
    And I should see "Organisation 12966A"
    And I should see "Organisation 12966B"
    And I should see "Organisation 12966C"
    And I should see "within the \"Org framework 12966A\" framework"

    Given I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I set the field with xpath "//*[@id='menu']" to "Org framework 12966B"
    And I click on "Organisation 12966A" "link"
    And I click on "Organisation 12966E" "link"
    And I click on "Organisation 12966F" "link"
    When I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    And I should see "User's organisation in any of their job assignments is equal to"
    And I should see "Organisation 12966A"
    And I should see "Organisation 12966E"
    And I should see "Organisation 12966F"
    And I should see "within the \"Org framework 12966B\" framework"

  @javascript
  Scenario: Test positions within the framework in dynamic audience with equals rule.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 12966P"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Positions"
    And I set the field "equal" to "Equal to"
    And I click on "Position 12966A" "link"
    And I click on "Position 12966B" "link"
    And I click on "Position 12966C" "link"
    When I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    And I should see "User's position in any of their job assignments is equal to"
    And I should see "Position 12966A"
    And I should see "Position 12966B"
    And I should see "Position 12966C"
    And I should see "within the \"Pos framework 12966A\" framework"

    Given I click on "Edit" "link" in the "ul.cohort-editing_ruleset" "css_element"
    And I set the field with xpath "//*[@id='menu']" to "Pos framework 12966B"
    And I click on "Position 12966A" "link"
    And I click on "Position 12966E" "link"
    And I click on "Position 12966F" "link"
    When I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    And I should see "User's position in any of their job assignments is equal to"
    And I should see "Position 12966A"
    And I should see "Position 12966E"
    And I should see "Position 12966F"
    And I should see "within the \"Pos framework 12966B\" framework"

  @javascript
  Scenario: Test organisations and positions within the framework in dynamic audience with equals rule.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 12966OP"
    And I switch to "Rule sets" tab

    And I set the field "addrulesetmenu" to "Organisations"
    And I set the field "equal" to "Equal to"
    And I click on "Organisation 12966A" "link"
    And I click on "Organisation 12966B" "link"
    And I click on "Organisation 12966C" "link"
    When I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    And I should see "User's organisation in any of their job assignments is equal to"
    And I should see "Organisation 12966A"
    And I should see "Organisation 12966B"
    And I should see "Organisation 12966C"
    And I should see "within the \"Org framework 12966A\" framework"

    Given I set the field "addrulesetmenu" to "Positions"
    And I set the field "equal" to "Equal to"
    And I click on "Position 12966A" "link"
    And I click on "Position 12966B" "link"
    And I click on "Position 12966C" "link"
    When I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    And I should see "User's position in any of their job assignments is equal to"
    And I should see "Position 12966A"
    And I should see "Position 12966B"
    And I should see "Position 12966C"
    And I should see "within the \"Pos framework 12966A\" framework"

  @javascript
  Scenario: Test organisations from multiple frameworks in dynamic audience with equals rule.
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 12966O"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Organisations"
    And I set the field "equal" to "Equal to"
    And I click on "Organisation 12966A" "link"
    And I click on "Organisation 12966B" "link"
    And I set the field "menu" to "Org framework 12966B"
    And I click on "Organisation 12966E" "link"
    And I click on "Organisation 12966F" "link"

    When I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"
    And I should see "User's organisation in any of their job assignments is equal to"
    And I should see "Organisation 12966A"
    And I should see "Organisation 12966B"
    And I should see "within the \"Org framework 12966A\" framework"
    And I should see "Organisation 12966E"
    And I should see "Organisation 12966F"
    And I should see "within the \"Org framework 12966B\" framework"
    And I should not see "Deleted (ID:"
