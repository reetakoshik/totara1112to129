@core @block_frontpage_combolist
Feature: Courses and categories block displays items in different modes
  In order to show a clean and clear list of the site categories and courses
  As an admin
  I need to set different block display modes

  Background:
    Given I am on a totara site
    And the following "categories" exist:
      | name                   | category | idnumber |
      | Category 1             | 0        | CAT1     |
      | Category 2             | 0        | CAT2     |
      | Category 1 child       | CAT1     | CAT11    |
      | Category 2 child       | CAT2     | CAT21    |
      | Category 1 child child | CAT11    | CAT111   |
      | Category 3             | 0        | CAT3     |
    And the following "courses" exist:
      | fullname     | shortname   | category |
      | Course 1 1   | COURSE1_1   | CAT1     |
      | Course 2 1   | COURSE2_1   | CAT2     |
      | Course 11 1  | COURSE11_1  | CAT11    |
      | Course 2 2   | COURSE2_2   | CAT2     |
      | Course 21 1  | COURSE21_1  | CAT21    |
      | Course 111 1 | COURSE111_1 | CAT111   |
      | Course 111 2 | COURSE111_2 | CAT111   |
    And I log in as "admin"
    And I am on site homepage
    And I navigate to "Turn editing on" node in "Front page settings"

  @javascript
  Scenario: Displays a list of categories
    And I configure the "Available courses" block
    And I set the following fields to these values:
      | Display                | Categories only |
      | Maximum category depth | 2               |
    And I press "Save changes"
    Then I should see "Category 1" in the "Available course categories" "block"
    And I should see "Category 1 child" in the "Available course categories" "block"
    And I should not see "Category 1 child child" in the "Available course categories" "block"
    And I toggle "Category 1" category children visibility in frontpage
    And I should not see "Category 1 child" in the "Available course categories" "block"
    And I toggle "Category 1" category children visibility in frontpage
    And I should see "Category 1 child" in the "Available course categories" "block"
    And I toggle "Category 1 child" category children visibility in frontpage
    And I should see "Category 1 child child" in the "Available course categories" "block"

  @javascript
  Scenario: Displays a combo list
    And I configure the "Available courses" block
    And I set the following fields to these values:
      | Display                | Courses nested in categories |
      | Maximum category depth | 2                            |
    And I press "Save changes"
    Then I should see "Category 1" in the "Available courses" "block"
    And I should see "Category 1 child" in the "Available courses" "block"
    And I should not see "Category 1 child child" in the "Available courses" "block"
    And I should see "Course 1 1" in the "Available courses" "block"
    And I should see "Course 2 2" in the "Available courses" "block"
    And I should not see "Course 11 1" in the "Available courses" "block"
    And I toggle "Category 1 child" category children visibility in frontpage
    And I should see "Course 11 1" in the "Available courses" "block"
    And I should see "Category 1 child child" in the "Available courses" "block"
    And I toggle "Category 1" category children visibility in frontpage
    And I should not see "Course 1 1" in the "Available courses" "block"
    And I should not see "Category 1 child" in the "Available courses" "block"
    And I toggle "Category 1" category children visibility in frontpage
    And I should see "Course 11 1" in the "Available courses" "block"
