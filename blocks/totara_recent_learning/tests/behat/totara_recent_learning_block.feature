@totara @block @block_totara_recent_learning @totara_courseprogressbar
Feature: Test Recent Learning block

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |

  @javascript
  Scenario: Learner can add and remove Recent Learning block on Dashboard
    Given the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
      | Course 2 | course2   | 1                |
    And the following "course enrolments" exist:
      | user     | course   | role           |
      | learner1 | course1  | student        |

    And I log in as "learner1"

    When I click on "Dashboard" in the totara menu
    Then I should not see "Recent Learning"

    When I press "Customise this page"
    And I add the "Recent Learning" block
    Then I should see "Course 1" in the "Recent Learning" "block"

    When I open the "Recent Learning" blocks action menu
    And I follow "Delete Recent Learning block"
    And I press "Yes"
    And I wait "1" seconds
    Then I should not see the "Recent Learning" block

    And I log out

  @javascript
  Scenario: Learner can see course progress in the Recent Learning block
    Given the following "courses" exist:
      | fullname  | shortname  | enablecompletion |
      | Course 1  | course1    | 1                |
      | Course 2  | course2    | 1                |
      | Course 3  | course3    | 1                |
      | Course 4  | course4    | 1                |
      | Course 5  | course5    | 0                |
    And the following "activities" exist:
      | activity   | name              | intro           | course               | idnumber    | completion   |
      | label      | c1label1          | course1 label1  | course1              | c1label1    | 1            |
      | label      | c1label2          | course1 label2  | course1              | c1label2    | 1            |
      | label      | c2label1          | course2 label1  | course2              | c2label1    | 1            |
      | label      | c2label2          | course2 label2  | course2              | c2label2    | 1            |
      | label      | c3label1          | course3 label1  | course3              | c3label1    | 1            |
      | label      | c3label2          | course3 label2  | course3              | c3label2    | 1            |
      | label      | c4label1          | course4 label1  | course4              | c4label1    | 1            |
      | label      | c4label2          | course4 label2  | course4              | c4label2    | 1            |
      | label      | c5label1          | course5 label1  | course5              | c5label1    | 0            |
      | label      | c5label2          | course5 label2  | course5              | c5label2    | 0            |

    # Enrolling the user directly to the course as well as through the program
    And the following "course enrolments" exist:
      | user     | course   | role |
      | learner1 | course1 | student |
      | learner1 | course2 | student |
      | learner1 | course3 | student |
      | learner1 | course4 | student |
      | learner1 | course5 | student |

    And I log in as "admin"
    # Set course completion criteria
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - course1 label1" to "1"
    And I set the field "Label - course1 label2" to "1"
    And I press "Save changes"

    And I am on "Course 2" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "id_activity_aggregation" to "2"
    And I set the field "Label - course2 label1" to "1"
    And I set the field "Label - course2 label2" to "1"
    And I press "Save changes"

    # Don't add course completion for Course 3

    And I am on "Course 4" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - course4 label1" to "1"
    And I set the field "Label - course4 label2" to "1"
    And I press "Save changes"

    # Course 5 doesn't have completion enabled

    Then I log out

    When I log in as "learner1"
    When I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Recent Learning" block
    Then I should see "Course 1" in the "Recent Learning" "block"
    And I should see "0%" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 1')]" "xpath_element"
    And I should see "Course 2" in the "Recent Learning" "block"
    And I should see "0%" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 2')]" "xpath_element"
    And I should see "Course 3" in the "Recent Learning" "block"
    And I should see "No criteria" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 3')]" "xpath_element"
    And I should see "Course 4" in the "Recent Learning" "block"
    And I should see "0%" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 4')]" "xpath_element"
    And I should see "Course 5" in the "Recent Learning" "block"
    And I should see "Not tracked" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 5')]" "xpath_element"

    # Complete some activities
    And I am on "Course 1" course homepage
    And I click on "Not completed: course1 label1. Select to mark as complete." "link"
    Then I should see "Completed: course1 label1. Select to mark as not complete."

    When I am on "Course 2" course homepage
    And I click on "Not completed: course2 label1. Select to mark as complete." "link"
    Then I should see "Completed: course2 label1. Select to mark as not complete."

    When I am on "Course 3" course homepage
    And I click on "Not completed: course3 label1. Select to mark as complete." "link"
    Then I should see "Completed: course3 label1. Select to mark as not complete."

    # Not completing anything in course4
    # Can't complete activities in course5 - completion tracking not enabled

    When I click on "Dashboard" in the totara menu
    Then I should see "Course 1" in the "Recent Learning" "block"
    And I should see "50%" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 1')]" "xpath_element"
    And I should see "Course 2" in the "Recent Learning" "block"
    And I should see "100%" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 2')]" "xpath_element"
    And I should see "Course 3" in the "Recent Learning" "block"
    And I should see "No criteria" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 3')]" "xpath_element"
    And I should see "Course 4" in the "Recent Learning" "block"
    And I should see "0%" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 4')]" "xpath_element"
    And I should see "Course 5" in the "Recent Learning" "block"
    And I should see "Not tracked" in the "//div[contains(@class, 'recent_learning')]//li[contains (., 'Course 5')]" "xpath_element"

    And I log out