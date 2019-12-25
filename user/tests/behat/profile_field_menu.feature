@core @core_user @totara @totara_cohort @javascript
Feature: User profile menu field management
  In order to use user profile menu field
  As an admin
  I need to be able to create, edit, and use fields

  Scenario: Can create, edit and use dropdown menu profile field
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
    And I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users > Accounts"
    And I set the following fields to these values:
      | datatype | menu     |
    And I set the following fields to these values:
      | Short name                 | menushort    |
      | Name                       | menu profile |
    And I set the field "Menu options (one per line)" to multiline:
"""
A & B
text < term
"""
    And I press "Save changes"
    Then I should see "menu profile"

    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Student 1"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | menu profile | A & B |
    And I press "Update profile"
    # Confirm that selected item does not reset
    And I follow "Edit profile"
    And I press "Update profile"
    And I should see "A & B"

    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Student 2"
    And I follow "Edit profile"
    And I expand all fieldsets
    And I set the following fields to these values:
      | menu profile | text < term |
    And I press "Update profile"
    # Confirm that selected item does not reset
    And I follow "Edit profile"
    And I press "Update profile"
    And I should see "text < term"

    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I switch to "Add new audience" tab
    And I set the following fields to these values:
      | Name | test audience |
      | Type | Dynamic       |
    And I click on "Save changes" "button"

    And I set the field "addrulesetmenu" to "menu profile"
    And I click on "A & B" "option" in the "Add rule" "totaradialogue"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "Student 1"
    And I should not see "Student 2"
    And I should not see "Student 3"


