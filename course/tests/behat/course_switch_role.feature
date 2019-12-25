@core @core_course @javascript
Feature: Switch course roles
  To see what other users will see
  I need the ability to switch my role within the course

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And I log in as "admin"
    And I am on "Course 1" course homepage

  # Currently logged in as Site Manager; attempt to switch to Staff Manager role
  Scenario: Switch role to Staff Manager
    Given I navigate to "Switch role to..." in current page administration
    And I set the field "Role" to "Staff Manager"
    When I press "Save changes"
    Then I should see "Staff Manager"
    And I should not see "Turn editing on"

  Scenario: Press cancel and not see Staff Manager
    Given I navigate to "Switch role to..." in current page administration
    And I set the field "Role" to "Staff Manager"
    When I press "Cancel"
    Then I should not see "Staff Manager"
    And I should see "Turn editing on"
    And I log out

  # Makes sure the cancel button also works when the dropdown menu value is "Return to my normal role" (0)
  Scenario: Press cancel when given the option of returning to normal role
    Given I navigate to "Switch role to..." in current page administration
    And I set the field "Role" to "Staff Manager"
    When I press "Save changes"
    Then I should not see "Turn editing on"
    When I navigate to "Switch role to..." in current page administration
    Then I should see "Return to my normal role"
    When I press "Cancel"
    Then I should see "Staff Manager"
    And I should not see "Turn editing on"