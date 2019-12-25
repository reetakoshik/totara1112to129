@core @core_block @javascript
Feature: Add blocks
  In order to add more functionality to pages
  As a teacher
  I need to add blocks to pages

  Scenario: Add a block to a course
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    When I add the "Blog menu" block
    Then I should see "View my entries about this course"

  Scenario: Add blocks to all possible regions
    Given I log in as "admin"
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I should not see the "Calendar" block
    When I add the "Calendar" block to the "top" region
    Then I should see the "Calendar" block in the "top" region

    And I should not see the "Comments" block
    When I add the "Comments" block to the "bottom" region
    Then I should see the "Comments" block in the "bottom" region

    And I should not see the "Featured Links" block
    When I add the "Featured Links" block to the "main" region
    Then I should see the "Featured Links" block in the "main" region

    And I should not see the "Tags" block
    When I add the "Tags" block to the "side-pre" region
    Then I should see the "Tags" block in the "side-pre" region

    And I should not see the "Global search" block
    When I add the "Global search" block to the "side-post" region
    Then I should see the "Global search" block in the "side-post" region