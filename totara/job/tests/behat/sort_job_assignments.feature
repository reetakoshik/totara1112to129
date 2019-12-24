@totara @totara_job
Feature: Test job assignments can be sorted
  In order to test that job assignments can be sorted
  As an admin
  I create several job assignments and change their sort order

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | user1    | User      | One      | user1@example.com       |
      | user2    | User      | Two      | user2@example.com       |

  @javascript
  Scenario: Job Assignments can be sorted by the admin
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "User One" "link" in the "User One" "table_row"
    Then I should see "User One"
    And I should see "Job assignments"
    And there should be "0" totara job assignments
    And I should see "Add job assignment"

    When I follow "Add job assignment"
    And I set the following fields to these values:
     | Full name    | Assignment 1  |
     | Short name   | Assign 1      |
     | ID Number    | A1            |
    And I press "Add job assignment"
    Then I should see "Job assignments"
    And there should be "1" totara job assignments
    And I should see "Add job assignment"

    When I follow "Add job assignment"
    And I set the following fields to these values:
      | Full name   | Assignment 2  |
      | Short name  | Assign 2      |
      | ID Number   | A2            |
    And I press "Add job assignment"
    Then I should see "Job assignments"
    And there should be "2" totara job assignments
    And I should see "Add job assignment"

    When I follow "Add job assignment"
    And I set the following fields to these values:
      | Full name   | Assignment 3 |
      | Short name  | Assign 3     |
      | ID Number   | A3           |
    And I press "Add job assignment"
    And I start watching to see if a new page loads
    Then I should see "Job assignments"
    And there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Assignment 2"
    And job assignment at position "3" should be "Assignment 3"
    And a new page should not have loaded since I started watching

    When I move job assignment "Assignment 1" down
    Then there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 2"
    And job assignment at position "2" should be "Assignment 1"
    And job assignment at position "3" should be "Assignment 3"
    And a new page should not have loaded since I started watching

    When I move job assignment "Assignment 3" up
    And I move job assignment "Assignment 3" up
    Then there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 3"
    And job assignment at position "2" should be "Assignment 2"
    And job assignment at position "3" should be "Assignment 1"
    And a new page should not have loaded since I started watching

    When I follow "Home"
    And a new page should have loaded since I started watching
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "User One" "link" in the "User One" "table_row"
    Then I should see "User One"
    And I should see "Job assignments"
    And there should be "3" totara job assignments
    And job assignment at position "1" should be "Assignment 3"
    And job assignment at position "2" should be "Assignment 2"
    And job assignment at position "3" should be "Assignment 1"
    And I should see "Add job assignment"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "User Two" "link" in the "User Two" "table_row"
    And I follow "Add job assignment"
    And I set the following fields to these values:
      | Full name    | Assignment 1  |
      | Short name   | Assign 1      |
      | ID Number    | A1            |
    And I press "Add job assignment"
    Then I should see "Job assignments"
    And there should be "1" totara job assignments
    And I should see "Add job assignment"

    When I follow "Add job assignment"
    And I set the following fields to these values:
      | Full name    | Assignment 2  |
      | Short name   | Assign 2      |
      | ID Number    | A2            |
    And I press "Add job assignment"
    Then I should see "Job assignments"
    And there should be "2" totara job assignments
    And I should see "Add job assignment"
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Assignment 2"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "User One" "link" in the "User One" "table_row"
    And I follow "Add job assignment"
    And I set the following fields to these values:
      | Full name    | Assignment 4  |
      | Short name   | Assign 4      |
      | ID Number    | A4            |
    And I press "Add job assignment"
    Then I should see "Job assignments"
    And there should be "4" totara job assignments
    And I should see "Add job assignment"
    And job assignment at position "1" should be "Assignment 3"
    And job assignment at position "2" should be "Assignment 2"
    And job assignment at position "3" should be "Assignment 1"
    And job assignment at position "4" should be "Assignment 4"

    When I move job assignment "Assignment 1" down
    Then there should be "4" totara job assignments
    And job assignment at position "1" should be "Assignment 3"
    And job assignment at position "2" should be "Assignment 2"
    And job assignment at position "3" should be "Assignment 4"
    And job assignment at position "4" should be "Assignment 1"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "User Two" "link" in the "User Two" "table_row"
    Then there should be "2" totara job assignments
    And job assignment at position "1" should be "Assignment 1"
    And job assignment at position "2" should be "Assignment 2"

    When I move job assignment "Assignment 1" down
    Then there should be "2" totara job assignments
    And job assignment at position "1" should be "Assignment 2"
    And job assignment at position "2" should be "Assignment 1"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "User One" "link" in the "User One" "table_row"
    Then there should be "4" totara job assignments
    And job assignment at position "1" should be "Assignment 3"
    And job assignment at position "2" should be "Assignment 2"
    And job assignment at position "3" should be "Assignment 4"
    And job assignment at position "4" should be "Assignment 1"