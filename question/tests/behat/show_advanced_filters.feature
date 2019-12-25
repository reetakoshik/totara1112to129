@core @core_question
Feature: The question bank advanced search options expand and collapse work
  In order to use advanced search options
  As a user
  I need to expand and collapse search options

  Background:
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | weeks |
    And the following "question categories" exist:
      | contextlevel | reference | questioncategory | name           |
      | Course       | C1        | Top              | Default for C1 |
      | Course       | C1        | Default for C1   | Subcategory    |
      | Course       | C1        | Top              | Used category  |
    And the following "questions" exist:
      | questioncategory | qtype | name                      | questiontext                  |
      | Used category    | essay | Test question to be moved | Write about whatever you want |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"


  @javascript
  Scenario: Move a question between categories via the question page
    When I navigate to "Questions" node in "Course administration > Question bank"
    Then I should see "Also show questions from subcategories"

    # Collapse
    When I click on "Search options" "text"
    Then I should not see "Also show questions from subcategories"

    # Expand
    When I click on "Search options" "text"
    Then I should see "Also show questions from subcategories"