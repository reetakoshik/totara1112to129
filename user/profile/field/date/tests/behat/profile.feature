@profile_fields @totara
Feature: Test date user profile field
  In order to use date field
  As a user
  I need to go to profile and set date

  @javascript
  Scenario: Defining date user profile field
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    When I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the field "Create a new profile field:" to "Date (no timezone)"
    And I wait to be redirected
    And I set the field "Short name (must be unique)" to "bday"
    And I set the field "Name" to "Day of birth"
    And I press "Save changes"
    Then I should see "Day of birth" in the "profilefield" "table"

    Given I log out
    When I log in as "student1"
    And I click on "Student 1" "link"
    And I click on "Profile" "link"
    And I should not see "Day of birth" in the ".profile_tree" "css_element"
    And I should not see "Date not set" in the ".profile_tree" "css_element"
    And I click on "Edit profile" "link"
    And I expand all fieldsets
    And I set the field "id_profile_field_bday_enabled" to "1"
    And I set the field "id_profile_field_bday_day" to "13"
    And I set the field "id_profile_field_bday_month" to "1"
    And I set the field "id_profile_field_bday_year" to "1975"
    And I press "Update profile"
    Then I should see "Day of birth" in the ".profile_tree" "css_element"
    And I should see "13 January 1975" in the ".profile_tree" "css_element"

    Given I log out
    When I log in as "admin"
    And I click on "Admin User" "link"
    And I click on "Profile" "link"
    And I click on "Edit profile" "link"
    And I set the field "Timezone" to "America/Mexico_City"
    And I press "Update profile"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Student 1" "link"
    Then I should see "Day of birth" in the ".profile_tree" "css_element"
    And I should see "13 January 1975" in the ".profile_tree" "css_element"
    And I should see "Australia/Perth" in the ".profile_tree" "css_element"
