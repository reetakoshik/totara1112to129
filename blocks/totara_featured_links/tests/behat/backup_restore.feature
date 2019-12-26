@block @totara @javascript @block_totara_featured_links @course
Feature: The content of the featured links blocks should be carried over when backing up and restoring a course
  As a user I should be able to
   - Add a featured links block to a course
   - Backup the course and include the featured links block
   - Restore the course and see the content of the featured links block

  Scenario: Backing up and restoring the featured links block should not wipe the block of its contents
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on

    And I add the "Featured Links" block
    And I configure the "Featured Links" block
      | Override default block title | Yes             |
      | Block title                  | Featured Links1 |
    And I click on "Save changes" "button"
    And I click on "Add Tile" "link" in the "Featured Links" "block"
    And I set the following fields to these values:
      | URL         | https://www.example.com        |
      | Description | The featured links tile exists |
    And I click on "Save changes" "button"

    And I add the "Featured Links" block
    And I click on "Add Tile" "link" in the "Featured Links" "block"
    And I set the following fields to these values:
      | URL         | https://www.example.com |
      | Description | Second FLB |
    And I click on "Save changes" "button"

    And I click on "Backup" "link"
    And I click on "Jump to final step" "button"
    And I click on "Continue" "button"
    And I click on "Restore" "button" in the "backup" "table"
    And I click on "Next" "button"
    And I click on "Miscellaneous" "text"
    And I click on "Next" "button"
    And I click on "Next" "button"
    And I click on "Next" "button"
    And I click on "Perform restore" "button"
    And I click on "Continue" "button"

    Then I should see "C1_1"
    And I should see "The featured links tile exists" in the "Featured Links" "block"
    And I should see "Second FLB" in the "Featured Links" "block"
