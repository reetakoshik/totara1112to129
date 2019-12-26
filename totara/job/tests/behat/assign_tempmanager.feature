@totara @totara_job @javascript
Feature: Assign a temporary manager to a user via the job assignment page
  In order to assign a temporary manager to a user
  As a user with correct permissions
  I must be able to select a user and the user's job assignment

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | user1    | User      | One      | user1@example.com       |
      | user2    | User      | Two      | user2@example.com       |
      | manager1 | Manager   | One      | manager1@example.com    |
      | manager2 | Manager   | Two      | manager2@example.com    |
      | jobadmin | Job       | Admin    | jobadmin@example.com    |
    And the following job assignments exist:
      | user     | fullname            | idnumber |
      | manager1 | Development Manager | 1        |
      | manager2 | Design Manager      | 1        |
      | manager2 | Brand Manager       | 2        |
    And I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I press "Add a new role"
    And I press "Continue"
    And I set the following fields to these values:
      | Short name       | jobadmin |
      | Custom full name | jobadmin |
      | System           | 1        |
      | User             | 1        |
    And I press "Create this role"
    And I set the following system permissions of "jobadmin" role:
      | capability                          | permission |
      | totara/hierarchy:assignuserposition | Allow      |
      | moodle/user:update                  | Allow      |
      | moodle/user:viewdetails             | Allow      |
      | totara/core:delegateusersmanager    | Allow      |
    And the following "role assigns" exist:
      | user     | role          | contextlevel | reference |
      | jobadmin | jobadmin      | System       |           |

  Scenario: Assign temporary manager - form validation ensures temp manager expiry date is set and in future
    Given I log out
    And I log in as "jobadmin"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Designer |
      | ID Number | 1        |
    And I press "Choose temporary manager"
    And I click on "Manager Two" "link" in the "Choose temporary manager" "totaradialogue"
    And I click on "Design Manager" "link" in the "Choose temporary manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose temporary manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager Two - Design Manager"
    When I press "Add job assignment"
    Then I should see "An expiry date for the temporary manager needs to be set"
    When I set the following fields to these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2010   |
    And I press "Add job assignment"
    Then I should see "The date needs to be in the future"
    When I set the following fields to these values:
      | tempmanagerexpirydate[year]    | 2030   |
    And I press "Add job assignment"
    And I click on "Designer" "link"
    Then I should see "Manager Two - Design Manager"
    And the following fields match these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |

  Scenario: Assign temporary manager - no existing manager - user has full capabilities - restricttempmanagers not set
    Given I log out
    And I log in as "jobadmin"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Designer |
      | ID Number | 1        |
    And I press "Choose temporary manager"
    Then I should see "User One - create empty job assignment" in the "Choose temporary manager" "totaradialogue"
    And I should see "Manager One" in the "Choose temporary manager" "totaradialogue"
    And I should see "Manager Two" in the "Choose temporary manager" "totaradialogue"
    When I click on "Manager Two" "link" in the "Choose temporary manager" "totaradialogue"
    Then I should see "Design Manager"
    And I should see "Brand Manager"
    And I should see "Create empty job assignment"
    When I click on "Design Manager" "link" in the "Choose temporary manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose temporary manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager Two - Design Manager"
    When I set the following fields to these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |
    And I press "Add job assignment"
    And I click on "Designer" "link"
    Then I should see "Manager Two - Design Manager"
    And the following fields match these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |
    And I press "Choose temporary manager"
    When I click on "Search" "link" in the "Choose temporary manager" "totaradialogue"
    And I search for "Manager" in the "Choose temporary manager" totara dialogue
    Then I should see "Manager One - Development Manager" in the "#search-tab" "css_element"
    And I should see "Manager One - create empty job assignment" in the "#search-tab" "css_element"
    And I should see "Manager Two - Design Manager" in the "#search-tab" "css_element"
    And I should see "Manager Two - Brand Manager" in the "#search-tab" "css_element"
    And I should see "Manager Two - create empty job assignment" in the "#search-tab" "css_element"
    When I click on "Manager One - create empty job assignment" "link" in the "#search-tab" "css_element"
    And I click on "OK" "button" in the "Choose temporary manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - create empty job assignment"
    When I click on "Update job assignment" "button"
    And I click on "Designer" "link"
    Then I should see "Manager One - Unnamed job assignment (ID: 2)"

  Scenario: Assign temporary manager - has existing manager - user has full capabilities - restricttempmanagers not set
    Given I log out
    And I log in as "jobadmin"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Designer |
      | ID Number | 1        |
    And I press "Choose manager"
    And I click on "Manager Two" "link" in the "Choose manager" "totaradialogue"
    And I click on "Design Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager Two - Design Manager"
    And I press "Add job assignment"
    And I click on "Designer" "link"
    And I press "Choose temporary manager"
    Then I should see "User One - create empty job assignment" in the "Choose temporary manager" "totaradialogue"
    And I should see "Manager One" in the "Choose temporary manager" "totaradialogue"
    And I should not see "Manager Two" in the "Choose temporary manager" "totaradialogue"
    When I click on "Manager One" "link" in the "Choose temporary manager" "totaradialogue"
    Then I should see "Development Manager" in the "Choose temporary manager" "totaradialogue"
    When I click on "Search" "link" in the "Choose temporary manager" "totaradialogue"
    And I search for "Manager" in the "Choose temporary manager" totara dialogue
    Then I should see "Manager One - Development Manager" in the "#search-tab" "css_element"
    And I should see "Manager One - create empty job assignment" in the "#search-tab" "css_element"
    And I should not see "Manager Two - Design Manager" in the "#search-tab" "css_element"
    And I should not see "Manager Two - Brand Manager" in the "#search-tab" "css_element"
    And I should not see "Manager Two - create empty job assignment" in the "#search-tab" "css_element"
    When I click on "Manager One - create empty job assignment" "link" in the "#search-tab" "css_element"
    And I click on "OK" "button" in the "Choose temporary manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - create empty job assignment"
    When I set the following fields to these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |
    And I click on "Update job assignment" "button"
    And I click on "Designer" "link"
    Then I should see "Manager One - Unnamed job assignment (ID: 2)"

  Scenario: Assign temporary manager - has existing manager - can delegate own manager only - restricttempmanagers not set
    Given I set the following system permissions of "Authenticated user" role:
      | totara/core:delegateownmanager      | Allow      |
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Designer |
      | ID Number | 1        |
    And I press "Choose manager"
    And I click on "Manager Two" "link" in the "Choose manager" "totaradialogue"
    And I click on "Design Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager Two (manager2@example.com) - Design Manager"
    And I press "Add job assignment"
    And I log out
    And I log in as "user2"
    And I follow "Profile" in the user menu
    And I click on "Designer" "link"
    And I press "Choose temporary manager"
    Then I should see "User One - requires job assignment entry" in the "Choose temporary manager" "totaradialogue"
    And I should see "Manager One - Development Manager" in the "Choose temporary manager" "totaradialogue"
    And I should not see "Manager Two" in the "Choose temporary manager" "totaradialogue"
    When I click on "Search" "link" in the "Choose temporary manager" "totaradialogue"
    And I search for "Manager" in the "Choose temporary manager" totara dialogue
    Then I should see "Manager One - Development Manager" in the "#search-tab" "css_element"
    And I should not see "Manager One - create empty job assignment" in the "#search-tab" "css_element"
    And I should not see "Manager One - requires job assignment entry" in the "#search-tab" "css_element"
    And I should not see "Manager Two - Design Manager" in the "#search-tab" "css_element"
    And I should not see "Manager Two - Brand Manager" in the "#search-tab" "css_element"
    And I should not see "Manager Two - create empty job assignment" in the "#search-tab" "css_element"
    And I should not see "Manager Two - requires job assignment entry" in the "#search-tab" "css_element"
    When I click on "Manager One - Development Manager" "link" in the "#search-tab" "css_element"
    And I click on "OK" "button" in the "Choose temporary manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - Development Manager"
    When I set the following fields to these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |
    And I click on "Update job assignment" "button"
    And I click on "Designer" "link"
    Then I should see "Manager One - Development Manager"
    And the following fields match these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |

  Scenario: Assign temporary manager - no existing manager - user has full capabilities - restricttempmanagers is set
    Given I set the following administration settings values:
      | tempmanagerrestrictselection | Only staff managers |
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Developer |
      | ID Number | 1         |
    And I press "Choose manager"
    And I click on "Manager One" "link" in the "Choose manager" "totaradialogue"
    And I click on "Development Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One (manager1@example.com) - Development Manager"
    And I press "Add job assignment"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Designer |
      | ID Number | 1        |
    And I press "Choose manager"
    And I click on "Manager Two" "link" in the "Choose manager" "totaradialogue"
    And I click on "Design Manager" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager Two (manager2@example.com) - Design Manager"
    And I press "Add job assignment"
    And I log out
    And I log in as "jobadmin"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Illustrator |
      | ID Number | 2           |
    And I press "Choose temporary manager"
    Then I should not see "User One - create empty job assignment" in the "Choose temporary manager" "totaradialogue"
    And I should see "Manager One" in the "Choose temporary manager" "totaradialogue"
    And I should see "Manager Two" in the "Choose temporary manager" "totaradialogue"
    When I click on "Manager Two" "link" in the "Choose temporary manager" "totaradialogue"
    Then I should see "Design Manager"
    And I should see "Brand Manager"
    And I should see "Create empty job assignment"
    When I click on "Search" "link" in the "Choose temporary manager" "totaradialogue"
    And I search for "User" in the "Choose temporary manager" totara dialogue
    Then I should see "No results found for \"User\"."
    And I search for "Manager" in the "Choose temporary manager" totara dialogue
    Then I should see "Manager One - Development Manager" in the "#search-tab" "css_element"
    And I should see "Manager One - create empty job assignment" in the "#search-tab" "css_element"
    And I should see "Manager Two - Design Manager" in the "#search-tab" "css_element"
    And I should not see "Manager Two - Brand Manager" in the "#search-tab" "css_element"
    And I should see "Manager Two - create empty job assignment" in the "#search-tab" "css_element"
    When I click on "Manager One - create empty job assignment" "link" in the "#search-tab" "css_element"
    And I click on "OK" "button" in the "Choose temporary manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager One - create empty job assignment"
    When I set the following fields to these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |
    And I press "Add job assignment"
    And I click on "Illustrator" "link"
    Then I should see "Manager One - Unnamed job assignment (ID: 2)"
    And the following fields match these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |

  Scenario: Assign temporary manager and then remove temporary manager to ensure that expiry checkbox is unselected
    Given I log out
    And I log in as "jobadmin"
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name | Designer |
      | ID Number | 1        |
    And I press "Choose temporary manager"
    And I click on "Manager Two" "link" in the "Choose temporary manager" "totaradialogue"
    And I click on "Design Manager" "link" in the "Choose temporary manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose temporary manager" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Manager Two - Design Manager"
    When I set the following fields to these values:
      | tempmanagerexpirydate[enabled] | 1      |
      | tempmanagerexpirydate[day]     | 15     |
      | tempmanagerexpirydate[month]   | August |
      | tempmanagerexpirydate[year]    | 2030   |
    Then I press "Add job assignment"
    When I click on "Designer" "link"
    # Check that temporary manager expiry date is set.
    And the field with xpath "//*[@id='id_tempmanagerexpirydate_enabled']" matches value "1"
    # Remove the temporary manager and verify that expiry date "Enable" is unticked.
    And I click on "//*[@id='tempmanagertitle']/a[@href='#']" "xpath_element"
    Then I should not see "Manager Two - Design Manager"
    And the field with xpath "//*[@id='id_tempmanagerexpirydate_enabled']" does not match value "1"
    # Save changes and return to reconfirm the unchecked status of the expiry "Enable" checkbox.
    And I press "Update job assignment"
    When I click on "Designer" "link"
    Then the field with xpath "//*[@id='id_tempmanagerexpirydate_enabled']" does not match value "1"
    And "//*[@id='tempmanagertitle']/a[@href='#']" "xpath_element" should not exist
