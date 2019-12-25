@core_enrol @javascript @totara
Feature: Manual enrolments
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | user1    | One       | Uno      | user1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course   | role    |
      | admin    | C1       | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage

  Scenario: Set manual enrolments notification to notify all
    Given I navigate to "Manual enrolments" node in "Course administration > Users > Enrolment methods"
    And I set the following fields to these values:
      | id_expirynotify        | Enroller and enrolled user |
    And I press "Save changes"
    When I click on "Edit" "link" in the "Manual enrolments" "table_row"
    Then the field "Notify before enrolment expires" matches value "Enroller and enrolled user"

  Scenario: Manually enrol a user with 5 minutes enrol duration
    Given I navigate to "Manual enrolments" node in "Course administration > Users > Enrolment methods"
    And I set the following fields to these values:
      | id_enrolperiod_enabled | 1 |
      | enrolperiod[number]    | 5 |
      | enrolperiod[timeunit]  | minutes |
    And I press "Save changes"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Enrol users" "button"
    # When I click on "Enrolment options" "button" in the ".user-enroller-panel" "css_element"
    When I click on ".uep-searchoptions > .collapsibleheading" "css_element" in the ".user-enroller-panel" "css_element"
    # Then the field "Enrolment duration" matches value "≈0 days"
    Then I should see "≈0 days" in the ".uep-enrolment-option.duration" "css_element"
    # When I click on "Enrol" "button" in the "One Uno" "table_row"
    When I click on "Enrol" "button" in the ".user-enroller-panel .user:first-child" "css_element"
    Then I should not see "Exception" in the ".userenrolment" "css_element"
    # Then "Enrol" "button" should not exist in the "One Uno" "table_row"
    Then "Enrol" "button" should not exist in the ".user-enroller-panel .user:first-child" "css_element"
    When I click on "Finish enrolling users" "button"
    Then I should see "user1@example.com" in the ".userenrolment" "css_element"
