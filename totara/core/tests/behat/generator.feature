@totara @totara_core @totara_customfield
Feature: Test Totara core generators
  In order to use Totara generators
  they need to work properly

  Scenario: Test custom profile field generator
    Given I am on a totara site
    And the following "custom profile fields" exist in "totara_core" plugin:
    | datatype | shortname | name    | param1      |
    | checkbox | pok1      | Pokus 1 |             |
    | datetime | pok2      | Pokus 2 |             |
    | menu     | pok3      | Pokus 3 | aa/bb/cc    |
    | text     | pok4      | Pokus 4 |             |
    | textarea | pok5      | Pokus 5 |             |
    | date     | pok6      | Pokus 6 |             |
    And I log in as "admin"
    # Unfortunately new custom fields are popping up in auth plugin settings.
    And I confirm new default admin settings
    And I navigate to "User profile fields" node in "Site administration > Users"
    Then I should see "Pokus 1"
    And I should see "Pokus 2"
    And I should see "Pokus 3"
    And I should see "Pokus 4"
    And I should see "Pokus 5"
    And I should see "Pokus 6"

  Scenario: Test custom course field generator
    Given I am on a totara site
    And the following "custom course fields" exist in "totara_core" plugin:
      | datatype | shortname | fullname | param1      |
      | checkbox | pok1      | Pokus 1  |             |
      | datetime | pok2      | Pokus 2  |             |
      | menu     | pok3      | Pokus 3  | aa/bb/cc    |
      | text     | pok4      | Pokus 4  |             |
      | textarea | pok5      | Pokus 5  |             |
    And I log in as "admin"
    # Unfortunately new custom fields are popping up in auth plugin settings.
    And I confirm new default admin settings
    And I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Pokus 1"
    And I should see "Pokus 2"
    And I should see "Pokus 3"
    And I should see "Pokus 4"
    And I should see "Pokus 5"

  Scenario: Test custom program field generator
    Given I am on a totara site
    And the following "custom program fields" exist in "totara_core" plugin:
      | datatype | shortname | fullname | param1      |
      | checkbox | pok1      | Pokus 1  |             |
      | datetime | pok2      | Pokus 2  |             |
      | menu     | pok3      | Pokus 3  | aa/bb/cc    |
      | text     | pok4      | Pokus 4  |             |
      | textarea | pok5      | Pokus 5  |             |
    And I log in as "admin"
    # Unfortunately new custom fields are popping up in auth plugin settings.
    And I confirm new default admin settings
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I click on "Programs / Certifications" "link"
    Then I should see "Pokus 1"
    And I should see "Pokus 2"
    And I should see "Pokus 3"
    And I should see "Pokus 4"
    And I should see "Pokus 5"
