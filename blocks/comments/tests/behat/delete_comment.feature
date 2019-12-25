@block @block_comments @javascript
Feature: Delete comment block messages
  In order to refine comment block's contents
  As a teacher
  In need to delete comments from courses

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | First | teacher1@example.com |
      | student1 | Student | First | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add the "Comments" block
    And I log out

  Scenario: Delete comments with Javascript enabled
    Given I log in as "student1"
    And I follow "Course 1"
    And I add "Comment from student1" comment to comments block
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I add "Comment from teacher1" comment to comments block
    When I delete "Comment from student1" comment from comments block
    Then I should not see "Comment from student1"
    And I delete "Comment from teacher1" comment from comments block
    And I should not see "Comment from teacher1"

  Scenario: Delete comments on the second page
    Given I log in as "student1"
    And I follow "Course 1"
    When I add "Super test comment 01" comment to comments block
    And I add "Super test comment 02" comment to comments block
    And I add "Super test comment 03" comment to comments block
    And I add "Super test comment 04" comment to comments block
    And I add "Super test comment 05" comment to comments block
    And I add "Super test comment 06" comment to comments block
    And I add "Super test comment 07" comment to comments block
    And I add "Super test comment 08" comment to comments block
    And I add "Super test comment 09" comment to comments block
    And I add "Super test comment 10" comment to comments block
    And I add "Super test comment 11" comment to comments block
    And I add "Super test comment 12" comment to comments block
    And I add "Super test comment 13" comment to comments block
    And I add "Super test comment 14" comment to comments block
    And I add "Super test comment 15" comment to comments block
    And I add "Super test comment 16" comment to comments block
    And I add "Super test comment 17" comment to comments block
    And I add "Super test comment 18" comment to comments block
    And I add "Super test comment 19" comment to comments block
    And I add "Super test comment 20" comment to comments block
    And I add "Super test comment 21" comment to comments block
    And I add "Super test comment 22" comment to comments block
    And I add "Super test comment 23" comment to comments block
    And I add "Super test comment 24" comment to comments block
    And I add "Super test comment 25" comment to comments block
    And I add "Super test comment 26" comment to comments block
    And I add "Super test comment 27" comment to comments block
    And I add "Super test comment 28" comment to comments block
    And I add "Super test comment 29" comment to comments block
    And I add "Super test comment 30" comment to comments block
    And I add "Super test comment 31" comment to comments block
    And I follow "Course 1"
    And I click on "2" "link" in the ".block_comments .comment-paging" "css_element"
    When I delete "Super test comment 10" comment from comments block
    Then I should not see "Super test comment 10"
