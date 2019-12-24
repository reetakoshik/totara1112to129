@core_enrol
Feature: User enrolment status is properly reflected when enrolment method is disabled
  In order to avoid confusion with enrolment status
  As an admin
  I must be able to see correct effective status of enrolment.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Studie    | One      | student1@example.com |
    And the following "courses" exist:
      | fullname   | shortname |
      | Course 001 | C001      |
    And the following "course enrolments" exist:
      | user     | course   | role    |
      | student1 | C001     | student |
    And I log in as "admin"
    And I am on course index
    And I follow "Course 001"

  Scenario: Active enrolment displayed when all active
    Given I navigate to "Enrolled users" node in "Course administration > Users"
    And I should see "Studie One"
    When I click on "Edit enrolment" "link"
    Then I should see "Active" in the "#mform1" "css_element"

  Scenario: Active enrolment displayed as effectively suspended when module is disabled
    Given I navigate to "Manual enrolments" node in "Course administration > Users > Enrolment methods"
    And I set the field "Enable manual enrolments" to "No"
    And I press "Save changes"

    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I should see "Studie One"
    When I click on "Edit enrolment" "link"
    Then I should see "Effectively suspended" in the "#mform1" "css_element"
    And I should not see "Active" in the "#mform1" "css_element"
