@core @core_user @javascript
Feature: Create and edit user profile custom-fields.
  In order to use user profile fields
  As an admin
  I need to be able to create and edit fields

  Scenario: Can create and edit text user profile fields
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | text |
    #redirect
    And I set the following fields to these values:
      | Short name | textshort         |
      | Name       | text long profile |
    When I press "Save changes"
    Then I should see "text long profile"
    And I click on "Edit" "link" in the "text long profile" "table_row"
    And I set the following fields to these values:
      | Name | text modified |
    When I press "Save changes"
    Then I should see "text modified"
    When I click on "Delete" "link" in the "text modified" "table_row"
    Then I should not see "text modified"

  Scenario: Can create and edit textarea user profile fields
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | textarea |
    #redirect
    And I set the following fields to these values:
      | Short name | textshort         |
      | Name       | text long profile |
    When I press "Save changes"
    Then I should see "text long profile"
    And I click on "Edit" "link" in the "text long profile" "table_row"
    And I set the following fields to these values:
      | Name | text modified |
    When I press "Save changes"
    Then I should see "text modified"
    When I click on "Delete" "link" in the "text modified" "table_row"
    Then I should not see "text modified"

  Scenario: Can create and edit checkbox user profile fields
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | checkbox |
    #redirect
    And I set the following fields to these values:
      | Short name | textshort         |
      | Name       | text long profile |
    When I press "Save changes"
    Then I should see "text long profile"
    And I click on "Edit" "link" in the "text long profile" "table_row"
    And I set the following fields to these values:
      | Name | text modified |
    When I press "Save changes"
    Then I should see "text modified"
    When I click on "Delete" "link" in the "text modified" "table_row"
    Then I should not see "text modified"

  Scenario: Can create and edit datetime user profile fields
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | datetime |
    #redirect
    And I set the following fields to these values:
      | Short name | textshort         |
      | Name       | text long profile |
    When I press "Save changes"
    Then I should see "text long profile"
    And I click on "Edit" "link" in the "text long profile" "table_row"
    And I set the following fields to these values:
      | Name | text modified |
    When I press "Save changes"
    Then I should see "text modified"
    When I click on "Delete" "link" in the "text modified" "table_row"
    Then I should not see "text modified"

  Scenario: Can create and edit dropdown menu profile fields
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
        | datatype | menu     |
    And I set the following fields to these values:
        | Short name                 | menushort    |
        | Name                       | menu profile |
    And I set the field "Menu options (one per line)" to multiline:
"""
AAA
BBB
CCC
"""
    And I press "Save changes"
    Then I should see "menu profile"
    When I click on "Delete" "link" in the "menu profile" "table_row"
    Then I should not see "menu profile"
