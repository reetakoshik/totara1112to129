@totara @tool @_file_upload @javascript @profile_fields
Feature: User profile fields handle the unique values correctly.

  Background:
    Given I am on a totara site
    When I log in as "admin"

    # Create a user checkbox custom field set as unique.
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype   | checkbox |
    And I set the following fields to these values:
      | Name                       | Checkbox 1 |
      | Short name                 | checkbox1  |
      | Should the data be unique? | Yes        |
    And I press "Save changes"
    Then I should see "User profile fields"
    Then I should see "Checkbox 1"

    # Create a user date (no timezone) custom field set as unique.
    When I set the following fields to these values:
      | datatype   | date |
    And I set the following fields to these values:
      | Name                       | Date 1 |
      | Short name                 | date1  |
      | Should the data be unique? | Yes    |
    And I press "Save changes"
    Then I should see "User profile fields"
    Then I should see "Date 1"

    # Create a user date/time custom field set as unique.
    When I set the following fields to these values:
      | datatype   | datetime |
    And I set the following fields to these values:
      | Name                       | Date/time 1 |
      | Short name                 | datetime1   |
      | Start year                 | 2017        |
      | End year                   | 2050        |
      | Should the data be unique? | Yes         |
    And I press "Save changes"
    Then I should see "User profile fields"
    Then I should see "Date/time 1"

    # Create a user dropdown menu custom field set as unique.
    # Note: menu custom fields do not currently enforce uniqueness. Keeping this in though in case this changes.
    When I set the following fields to these values:
      | datatype   | menu |
    And I set the following fields to these values:
      | Name                       | Menu 1 |
      | Short name                 | menu1  |
      | Should the data be unique? | Yes    |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Option 1
      Option 2
      Option 3
      """
    And I press "Save changes"
    Then I should see "User profile fields"
    Then I should see "Menu 1"

    # Create a user textarea custom field set as unique.
    When I set the following fields to these values:
      | datatype   | textarea |
    And I set the following fields to these values:
      | Name                       | Textarea 1 |
      | Short name                 | textarea1  |
      | Should the data be unique? | Yes        |
    And I press "Save changes"
    Then I should see "User profile fields"
    Then I should see "Textarea 1"

    # Create a user text input custom field set as unique.
    When I set the following fields to these values:
      | datatype | text |
    And I set the following fields to these values:
      | Name                       | Text 1 |
      | Short name                 | text1  |
      | Should the data be unique? | Yes    |
    And I press "Save changes"
    Then I should see "User profile fields"
    Then I should see "Text 1"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I press "Add a new user"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Username                         | learner1             |
      | New password                     | P4ssword!            |
      | First name                       | Bob1                 |
      | Surname                          | Learner1             |
      | Email address                    | learner1@example.com |
      | Checkbox 1                       | 1                    |
      | profile_field_date1[enabled]     | 1                    |
      | profile_field_date1[year]        | 2049                 |
      | profile_field_date1[month]       | 7                    |
      | profile_field_date1[day]         | 20                   |
      | profile_field_datetime1[enabled] | 1                    |
      | profile_field_datetime1[year]    | 2049                 |
      | profile_field_datetime1[month]   | 8                    |
      | profile_field_datetime1[day]     | 21                   |
      | Menu 1                           | Option 1             |
      | Textarea 1                       | Textarea Content     |
      | Text 1                           | Text Content         |

    And I press "Create user"

  Scenario: Verify unique user profile fields when creating a user with non-unique values fail uniqueness check in manual user creation.

    Given I navigate to "Browse list of users" node in "Site administration > Users"
    When I press "Add a new user"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Username                         | learner2             |
      | New password                     | P4ssword!            |
      | First name                       | Bob2                 |
      | Surname                          | Learner2             |
      | Email address                    | learner2@example.com |
      | Checkbox 1                       | 1                    |
    And I press "Create user"
    Then I should see the form validation error "This value has already been used." for the "checkbox1" user profile field

    When I set the following fields to these values:
      | Checkbox 1                       | 0                    |
      | profile_field_date1[enabled]     | 1                    |
      | profile_field_date1[year]        | 2049                 |
      | profile_field_date1[month]       | 7                    |
      | profile_field_date1[day]         | 20                   |
    And I press "Create user"
    Then I should not see the form validation error "This value has already been used." for the "checkbox1" user profile field
    And I should see the form validation error "This value has already been used." for the "date1" user profile field

    When I set the following fields to these values:
      | profile_field_date1[enabled]     | 1                    |
      | profile_field_date1[year]        | 2049                 |
      | profile_field_date1[month]       | 7                    |
      | profile_field_date1[day]         | 21                   |
      | profile_field_datetime1[enabled] | 1                    |
      | profile_field_datetime1[year]    | 2049                 |
      | profile_field_datetime1[month]   | 8                    |
      | profile_field_datetime1[day]     | 21                   |
    And I press "Create user"
    Then I should not see the form validation error "This value has already been used." for the "date1" user profile field
    And I should see the form validation error "This value has already been used." for the "datetime1" user profile field

    When I set the following fields to these values:
      | profile_field_datetime1[enabled] | 1                    |
      | profile_field_datetime1[year]    | 2049                 |
      | profile_field_datetime1[month]   | 8                    |
      | profile_field_datetime1[day]     | 22                   |
      | Menu 1                           | Option 1             |
    And I press "Create user"
    Then I should not see the form validation error "This value has already been used." for the "datetime1" user profile field
    And I should see the form validation error "This value has already been used." for the "menu1" user profile field

    When I set the following fields to these values:
      | Menu 1                           | Option 2             |
      | Textarea 1                       | Textarea Content     |
    And I press "Create user"
    Then I should not see the form validation error "This value has already been used." for the "menu1" user profile field
    And I should see the form validation error "This value has already been used." for the "textarea1" user profile field

    When I set the following fields to these values:
      | Textarea 1                       | Textarea Content 2   |
      | Text 1                           | Text Content         |
    And I press "Create user"
    Then I should not see the form validation error "This value has already been used." for the "textarea1" user profile field
    And I should see the form validation error "This value has already been used." for the "text1" user profile field

    When I set the following fields to these values:
      | Text 1                           | Text Content 2       |
    And I press "Create user"
    Then I should see "Browse list of users: 4 records shown"
    And the following should exist in the "users" table:
      | username |
      | guest    |
      | admin    |
      | learner1 |
      | learner2 |

  Scenario: Verify unique user profile fields when updating a user with non-unique values fail uniqueness check in manual user creation.

    Given I navigate to "Browse list of users" node in "Site administration > Users"
    When I press "Add a new user"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Username                         | learner2             |
      | New password                     | P4ssword!            |
      | First name                       | Bob2                 |
      | Surname                          | Learner2             |
      | Email address                    | learner2@example.com |
      | Checkbox 1                       | 0                    |
      | profile_field_date1[enabled]     | 1                    |
      | profile_field_date1[year]        | 2049                 |
      | profile_field_date1[month]       | 7                    |
      | profile_field_date1[day]         | 21                   |
      | profile_field_datetime1[enabled] | 1                    |
      | profile_field_datetime1[year]    | 2049                 |
      | profile_field_datetime1[month]   | 8                    |
      | profile_field_datetime1[day]     | 22                   |
      | Menu 1                           | Option 2             |
      | Textarea 1                       | Textarea Content 2   |
      | Text 1                           | Text Content 2       |
    And I press "Create user"
    Then I should see "Browse list of users: 4 records shown"
    And the following should exist in the "users" table:
      | username |
      | learner2 |

    When I click on "Edit" "link" in the "Bob2 Learner2" "table_row"
    And I expand all fieldsets
    When I set the following fields to these values:
      | Checkbox 1                       | 1                    |
    And I press "Update profile"
    Then I should see the form validation error "This value has already been used." for the "checkbox1" user profile field

    When I set the following fields to these values:
      | Checkbox 1                       | 0                    |
      | profile_field_date1[enabled]     | 1                    |
      | profile_field_date1[year]        | 2049                 |
      | profile_field_date1[month]       | 7                    |
      | profile_field_date1[day]         | 20                   |
    And I press "Update profile"
    Then I should not see the form validation error "This value has already been used." for the "checkbox1" user profile field
    And I should see the form validation error "This value has already been used." for the "date1" user profile field

    When I set the following fields to these values:
      | profile_field_date1[enabled]     | 1                    |
      | profile_field_date1[year]        | 2049                 |
      | profile_field_date1[month]       | 7                    |
      | profile_field_date1[day]         | 22                   |
      | profile_field_datetime1[enabled] | 1                    |
      | profile_field_datetime1[year]    | 2049                 |
      | profile_field_datetime1[month]   | 8                    |
      | profile_field_datetime1[day]     | 21                   |
    And I press "Update profile"
    Then I should not see the form validation error "This value has already been used." for the "date1" user profile field
    And I should see the form validation error "This value has already been used." for the "datetime1" user profile field

    When I set the following fields to these values:
      | profile_field_datetime1[enabled] | 1                    |
      | profile_field_datetime1[year]    | 2049                 |
      | profile_field_datetime1[month]   | 8                    |
      | profile_field_datetime1[day]     | 23                   |
      | Menu 1                           | Option 1             |
    And I press "Update profile"
    Then I should not see the form validation error "This value has already been used." for the "datetime1" user profile field
    And I should see the form validation error "This value has already been used." for the "menu1" user profile field

    When I set the following fields to these values:
      | Menu 1                           | Option 3             |
      | Textarea 1                       | Textarea Content     |
    And I press "Update profile"
    Then I should not see the form validation error "This value has already been used." for the "menu1" user profile field
    And I should see the form validation error "This value has already been used." for the "textarea1" user profile field

    When I set the following fields to these values:
      | Textarea 1                       | Textarea Content 3   |
      | Text 1                           | Text Content         |
    And I press "Update profile"
    Then I should not see the form validation error "This value has already been used." for the "textarea1" user profile field
    And I should see the form validation error "This value has already been used." for the "text1" user profile field

    When I set the following fields to these values:
      | Text 1                           | Text Content 3       |
    And I press "Update profile"
    Then I should see "Browse list of users: 4 records shown"
    And the following should exist in the "users" table:
      | username |
      | guest	 |
      | admin    |
      | learner1 |
      | learner2 |
