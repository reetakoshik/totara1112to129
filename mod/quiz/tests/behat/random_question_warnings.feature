@mod @mod_quiz
Feature: Warnings are shown on quiz with random questions and insufficient questions in the question bank

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name             | questioncategory |
      | Course       | C1        | MyCategory 1     | Top              |
      | Course       | C1        | MyCategory 2     | Top              |
      | Course       | C1        | MyCategory 2-1   | MyCategory 2     |
      | Course       | C1        | MyCategory 2-1-1 | MyCategory 2-1   |
    And the following "questions" exist:
      | questioncategory | qtype     | name             | questiontext                         |
      | MyCategory 1     | truefalse | Question 1-A     | This is question in MyCategory 1     |
      | MyCategory 2     | truefalse | Question 2-A     | This is question in MyCategory 2     |
      | MyCategory 2-1   | truefalse | Question 2-1-A   | This is question in MyCategory 2-1   |
      | MyCategory 2-1   | truefalse | Question 2-1-B   | This is question in MyCategory 2-1   |
      | MyCategory 2-1-1 | truefalse | Question 2-1-1-A | This is question in MyCategory 2-1-1 |
      | MyCategory 2-1-1 | truefalse | Question 2-1-1-B | This is question in MyCategory 2-1-1 |
      | MyCategory 2-1-1 | truefalse | Question 2-1-1-C | This is question in MyCategory 2-1-1 |
      | MyCategory 2-1-1 | truefalse | Question 2-1-1-D | This is question in MyCategory 2-1-1 |

    And the following "activities" exist:
      | activity   | name   | course | idnumber |
      | quiz       | Quiz 1 | C1     | quiz1    |

  @javascript
  Scenario: Warnings shown when creating random question using categories with insufficient questions
    Given I log in as "admin"
    And I follow "Course 1"
    And I follow "Quiz 1"
    And I press "Edit quiz"
    And I click on "Add" "link" in the "region-main" "region"
    And I follow "a random question"
    Then I should see "Random question from an existing category"
    And "//select[@id='id_category']/optgroup[@label='Quiz: Quiz 1']/option[contains(., 'Default for Quiz 1 (0)')]" "xpath_element" should exist
    And "//select[@id='id_category']/optgroup[@label='Course: C1']/option[contains(., 'Default for MyCategory 1 (1)')]" "xpath_element" should exist
    And "//select[@id='id_category']/optgroup[@label='Course: C1']/option[contains(., 'MyCategory 2 (1)')]" "xpath_element" should exist
    And "//select[@id='id_category']/optgroup[@label='Course: C1']/option[contains(., 'MyCategory 2-1 (2)')]" "xpath_element" should exist
    And "//select[@id='id_category']/optgroup[@label='Course: C1']/option[contains(., 'MyCategory 2-1-1 (4)')]" "xpath_element" should exist

    # The select from singleselect have trouble with the optgroups - using xpath instead of
    # When I select "Default for Quiz 1 (0)" from the "Category" singleselect
    When I set the field with xpath "//select[@id='id_category']" to "Default for Quiz 1 (0)"
    Then I should see "The selected category does not contain enough questions"

    When I set the field with xpath "//select[@id='id_category']" to "MyCategory 2 (1)"
    Then I should not see "The selected category does not contain enough questions"

    When I set the field "Number of random questions" to "4"
    Then I should see "The selected category does not contain enough questions"

    When I set the field "Include questions from subcategories too" to "1"
    Then I should not see "The selected category does not contain enough questions"

  @javascript
  Scenario: Warnings shown for random questions with insufficient category questions on the Editig quiz page
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Edit quiz"
    Then I should not see "This quiz contains one or more random questions drawn from question categories that contain an insufficient number of questions"

    When I click on "Add" "link" in the "region-main" "region"
    And I follow "a random question"
    And I set the field with xpath "//select[@id='id_category']" to "MyCategory 2-1-1 (4)"
    When I set the field "Number of random questions" to "2"
    And I press "Add random question"
    Then I should see "Random (MyCategory 2-1-1)"
    And I should not see "This quiz contains one or more random questions drawn from question categories that contain an insufficient number of questions"

    When I click on "Add" "link" in the "region-main" "region"
    And I follow "a random question"
    And I set the field with xpath "//select[@id='id_category']" to "MyCategory 2 (1)"
    When I set the field "Number of random questions" to "2"
    And I press "Add random question"
    Then I should see "Random (MyCategory 2)"
    And I should see "This quiz contains one or more random questions drawn from question categories that contain an insufficient number of questions"

  @javascript
  Scenario: Warnings shown for random questions sub category used in parent and on its own
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Quiz 1"
    And I press "Edit quiz"

    And I click on "Add" "link" in the "region-main" "region"
    And I follow "a random question"
    And I set the field with xpath "//select[@id='id_category']" to "MyCategory 2-1-1 (4)"
    When I set the field "Number of random questions" to "2"
    And I press "Add random question"
    Then I should see "Random (MyCategory 2-1-1)"
    And I should not see "This quiz contains one or more random questions drawn from question categories that contain an insufficient number of questions"

    When I click on "Add" "link" in the "region-main" "region"
    And I follow "a random question"
    And I set the field with xpath "//select[@id='id_category']" to "MyCategory 2 (1)"
    And I set the field "Include questions from subcategories too" to "1"
    And I set the field "Number of random questions" to "6"
    Then I should not see "The selected category does not contain enough questions"
    And I press "Add random question"
    Then I should see "Random (MyCategory 2 and subcategories)"
    And I should see "This quiz contains one or more random questions drawn from question categories that contain an insufficient number of questions"
