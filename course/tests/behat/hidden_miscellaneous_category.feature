@totara @core_course @totara_catalog @totara_coursecatalog
Feature: Check that hiding the Miscellaneous category does not break Find Learning for learners
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | first1    | last1    | user1@example.com |
    And the following "categories" exist:
      | name | category | idnumber |
      | cat1 | 0        | cat1     |
    And the following "courses" exist:
      | fullname     | shortname    | category |
      | cat0_course1 | cat0_course1 | 0        |
      | cat1_course1 | cat1_course1 | cat1     |
    And the following "programs" exist in "totara_program" plugin:
      | fullname      | shortname     | category |
      | cat0_program1 | cat0_program1 | 0        |
      | cat1_program1 | cat1_program1 | cat1     |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname   | shortname  | category |
      | cat0_cert1 | cat0_cert1 | 1        |
      | cat1_cert1 | cat1_cert1 | 2        |
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    And I click on ".action-hide" "css_element" in the "#category-listing .listitem-category[data-id=\"1\"][data-visible]" "css_element"

  @javascript
  Scenario: Check that learner can view the Moodle Catalogue without exception when Miscellaneous is hidden
    Given I set the following administration settings values:
      | catalogtype | moodle |
    Then I log out
    And I log in as "user1"
    And I click on "Courses" in the totara menu
    Then I should not see "Miscellaneous"
    And I click on "cat1" "link"
    Then I should see "cat1_course1"
    And I click on "Programs" in the totara menu
    Then I should not see "Miscellaneous"
    And I click on "cat1" "link"
    Then I should see "cat1_program1"
    And I click on "Certifications" in the totara menu
    Then I should not see "Miscellaneous"
    And I click on "cat1" "link"
    Then I should see "cat1_cert1"

  @javascript
  Scenario: Check that learner can view the Enhanced Catalogue without exception when Miscellaneous is hidden
    Given I set the following administration settings values:
      | catalogtype | enhanced |
    Then I log out
    And I log in as "user1"
    And I click on "Courses" in the totara menu
    Then I should see "cat1_course1"
    And I should not see "cat0_course1"
    And I click on "Programs" in the totara menu
    And I should see "cat1_program1"
    And I should not see "cat0_program1"
    And I click on "Certifications" in the totara menu
    And I should see "cat1_cert1"
    And I should not see "cat0_cert1"

  @javascript
  Scenario: Check that learner can view the Totara Catalog without exception when Miscellaneous is hidden
    Given I set the following administration settings values:
      | catalogtype | totara |
    Then I log out
    And I log in as "user1"
    And I click on "Find Learning" in the totara menu
    Then I should see "cat1_course1"
    And I should not see "cat0_course1"
    And I should see "cat1_program1"
    And I should not see "cat0_program1"
    And I should see "cat1_cert1"
    And I should not see "cat0_cert1"
