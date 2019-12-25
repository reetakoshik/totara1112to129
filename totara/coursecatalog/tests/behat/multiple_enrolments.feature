@javascript @totara @totara_coursecatalog @enrol
Feature: Users can auto-enrol themselves in courses where self enrolment is allowed from course-catalogue
  In order to test self-enrolments are working as expect in course-catalog
  As a user
  I need to try to auto enrol me in courses

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
      | Course 2 | C2        | topics |
      | Course 3 | C3        | topics |
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype        | enhanced |
      | Guest login button | Show     |
#    Make self-enrolment available for C1. Enrolment plugins for a Course 1: manual, self, program.
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I should see "Disable" in the "Manual enrolments" "table_row"
    And I should see "Disable" in the "Program" "table_row"
    And I should see "Disable" in the "Self enrolment (Learner)" "table_row"
#    Make sure Course 2 does not have self or guest enrolment enabled.
    And I click on "Courses" in the totara menu
    And I follow "Course 2"
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I should see "Disable" in the "Manual enrolments" "table_row"
    And I should see "Disable" in the "Program" "table_row"
    And I should see "Enable" in the "Self enrolment (Learner)" "table_row"
    And I should see "Enable" in the "Guest access" "table_row"
#    Make sure Course 3 has self and guest enrolments enabled.
    And I click on "Courses" in the totara menu
    And I follow "Course 3"
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I click on "Enable" "link" in the "Guest access" "table_row"
    And I log out

  Scenario: Guest trying to auto enrol in courses
    Given I log in as "guest"
    And I click on "Courses" in the totara menu
    When I click on ".rb-display-expand" "css_element" in the "Course 1" "table_row"
    Then "Enrol" "link_or_button" should not exist
    When I click on ".rb-display-expand" "css_element" in the "Course 2" "table_row"
    Then I should not see "Enrol with"
    When I click on ".rb-display-expand" "css_element" in the "Course 3" "table_row"
    Then "Enrol with - Guest access" "link_or_button" should exist

  Scenario: Student trying to auto enrol in courses
    Given I log in as "student1"
    And I click on "Courses" in the totara menu
    When I click on ".rb-display-expand" "css_element" in the "Course 1" "table_row"
    Then "Enrol" "link_or_button" should exist
    # Lets auto enrol and visit the page again.
    And I press "Enrol"
    And I click on "Courses" in the totara menu
    When I click on ".rb-display-expand" "css_element" in the "Course 1" "table_row"
    Then "Launch course" "link_or_button" should exist
    When I click on ".rb-display-expand" "css_element" in the "Course 2" "table_row"
    Then I should not see "Enrol with"
    When I click on ".rb-display-expand" "css_element" in the "Course 3" "table_row"
    Then "Enrol with - Guest access" "link_or_button" should exist
    And I log out

  Scenario: Admin logged in as student and watching enrolment methods for courses
    Given I log in as "admin"
    And I click on "Dashboard" in the totara menu
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Student 1"
    And I click on "Log in as" "link"
    And I press "Continue"
    And I click on "Courses" in the totara menu
    When I click on ".rb-display-expand" "css_element" in the "Course 1" "table_row"
    Then "Enrol" "link_or_button" should exist
    When I click on ".rb-display-expand" "css_element" in the "Course 2" "table_row"
    Then "View course" "link_or_button" should exist
    When I click on ".rb-display-expand" "css_element" in the "Course 3" "table_row"
    Then "Enrol with - Guest access" "link_or_button" should exist
    And "Enrol with - Self enrolment" "link_or_button" should exist
    And I log out
