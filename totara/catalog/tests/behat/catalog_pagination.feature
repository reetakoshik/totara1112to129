@totara @totara_catalog @javascript
Feature: Viewing course catalog with pagination
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | firstname | lastname | username |
      | jongsuk   | lee      | jongsuk  |
    And the following "courses" exist:
      | fullname | shortname | category | visible |
      | course1  | course1   | 0        | 1       |
      | course2  | course2   | 0        | 1       |
      | course3  | course3   | 0        | 1       |
      | course4  | course4   | 0        | 1       |
      | course5  | course5   | 0        | 1       |
      | course6  | course6   | 0        | 0       |
      | course7  | course7   | 0        | 1       |
      | course8  | course8   | 0        | 1       |
      | course9  | course9   | 0        | 1       |
      | course10 | course10  | 0        | 1       |
      | course11 | course11  | 0        | 1       |
      | course12 | course12  | 0        | 1       |
      | course13 | course13  | 0        | 1       |
      | course14 | course14  | 0        | 1       |
      | course15 | course15  | 0        | 1       |
      | course16 | course16  | 0        | 1       |
      | course17 | course17  | 0        | 1       |
      | course18 | course18  | 0        | 1       |
      | course19 | course19  | 0        | 1       |
      | course20 | course20  | 0        | 1       |
      | course21 | course21  | 0        | 1       |
      | course22 | course22  | 0        | 1       |
      | course23 | course23  | 0        | 1       |

  Scenario: A learner is not able to see the course that has disabled for view
    Given I log in as "jongsuk"
    And I click on "Find Learning" in the totara menu
    And I should see "Load more"
    And I should see "Up to 30 items"
    # Course with unchecked for visibility
    And I should not see "course6"
    When I follow "Load more"
    Then I should not see "Load more"
    And I should see "22 items"
    And I should not see "Up to 22 items"

    And I log out
    And I log in as "admin"
    And I navigate to "Courses > Configure catalogue" in site administration
    And I follow "General"
    And I set the following Totara form fields to these values:
      | Items per 'load more' | 40 |
    And I click on "Save" "button"
    And I log out
    And I log in as "jongsuk"
    And I click on "Find Learning" in the totara menu
    And I should not see "Load more"
    And I should not see "course6"
    And I should see "22 items"
