@totara @totara_plan @totara_courseprogressbar @javascript
Feature: Check visibility of courses in Record of Learning for unenrolled users
  Courses that I am unenrolled from but have made progress in
  As a learner
  Should be visible in record of learning regardless of settings

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Joe        | Bloggs    | learner1@example.com |
    And the following "courses" exist:
      | fullname | shortname  | enablecompletion |
      | Course 1 | Course 1   | 1                |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Page One                                          |
      | Page content        | Some content                                      |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require view        | 1                                                 |
    And I follow "Course 1"
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Page Two                                          |
      | Page content        | Some content                                      |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require view        | 1                                                 |
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Page - Page One | 1 |
      | Page - Page Two | 1 |
    And I press "Save changes"
    And I enrol "learner1" user as "Learner"
    And I log out

  Scenario: Unenrolled learner can see in-progress course in rol when course visibility is set to Show
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Page One" "link"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 0 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Course visibility | Show |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "50%" in the "Course 1" "table_row"

  Scenario: Unenrolled learner can see in-progress course in rol when course visibility is set to Hide
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Page One" "link"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 0 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Course visibility | Hide |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "50" in the "Course 1" "table_row"

  Scenario: Unenrolled learner can see in-progress course in rol when audience visibility is set to All users
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Page One" "link"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | All users |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "50%" in the "Course 1" "table_row"

  Scenario: Unenrolled learner can see in-progress course in rol when audience visibility is set to Enrolled users only
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Page One" "link"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | Enrolled users only |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "50%" in the "Course 1" "table_row"

  Scenario: Unenrolled learner can see in-progress course in rol when audience visibility is set to No users
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Page One" "link"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | No users |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "50%" in the "Course 1" "table_row"

  Scenario: Unenrolled learner cannot see course with no progress in rol when course visibility is set to Show
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 0 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Course visibility | Show |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Course 1"

  Scenario: Unenrolled learner cannot see course with no progress in rol when course visibility is set to Hide
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 0 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Course visibility | Hide |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Course 1"

  Scenario: Unenrolled learner cannot see course with no progress in rol when audience visibility is set to All users
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | All users |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Course 1"

  Scenario: Unenrolled learner cannot see course with no progress in rol when audience visibility is set to No users
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | No users |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Course 1"

  # We'll make sure the visibility settings don't hide complete courses.
  # There's no need to test the Show and All users settings when testing complete courses. If in-progress
  # courses work with those settings, and the below tests pass, there should be no problem with those.

  Scenario: Unenrolled learner can see complete course in rol when course visibility is set to Hide
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Page One" "link"
    And I follow "Course 1"
    And I click on "Page Two" "link"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 0 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Course visibility | Hide |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "100%" in the "Course 1" "table_row"

  Scenario: Unenrolled learner can see complete course in rol when audience visibility is set to No users
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Page One" "link"
    And I follow "Course 1"
    And I click on "Page Two" "link"
    And I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Unenrol" "link" in the "Joe Bloggs" "table_row"
    And I press "Continue"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Visibility | No users |
    And I press "Save and display"
    And I log out
    And I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Course 1"
    And I should see "100%" in the "Course 1" "table_row"
