@core @core_backup
Feature: Backup Totara courses
  In order to save and store course contents
  As an admin
  I need to create backups of courses

  # NOTE: the javascript tag is used here because the browser emulation does not wait for the archive to extract...

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | numsections |
      | Course 1 | C1 | 0 | 10 |
      | Course 2 | C2 | 0 | 2 |
    And the following "activities" exist:
      | activity | course | idnumber | name | intro | section |
      | assign | C2 | assign1 | Test assign | Assign description | 1 |
      | data | C2 | data1 | Test data | Database description | 2 |
    And I log in as "admin"

  Scenario: Backup a course providing options
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    Then I should see "Restore"
    And I click on "Restore" "button" in the "test_backup.mbz" "table_row"
    And I should see "URL of backup"
    And I should see "Anonymize user information"

  @javascript
  Scenario: Backup a course with default options
    When I backup "Course 1" course using this options:
      | Initial | Include calendar events | 0 |
      | Initial | Include course logs | 1 |
      | Schema | Topic 5 | 0 |
      | Confirmation | Filename | test_backup.mbz |
    Then I should see "Restore"
    And I click on "Restore" "button" in the "test_backup.mbz" "table_row"
    And I should not see "Section 3"
    And I set the field "destinationcurrent" to "1"
    And I press "Next"
    And I press "Next"
    And "//div[contains(concat(' ', normalize-space(@class), ' '), ' fitem ')][contains(., 'Include calendar events')]/descendant::span[contains(@class,'flex-icon')]" "xpath_element" should exist
    And "Include course logs" "checkbox" should exist
    And I press "Next"

  Scenario: Backup a course without blocks
    When I backup "Course 1" course using this options:
      | 1 | setting_root_blocks | 0 |
    Then I should see "Course backup area"

  @javascript
  Scenario: Backup selecting just one section
    When I backup "Course 2" course using this options:
      | Schema | Test data | 0 |
      | Schema | Topic 2 | 0 |
      | Confirmation | Filename | test_backup.mbz |
    Then I should see "Course backup area"
    And I click on "Restore" "button" in the "test_backup.mbz" "table_row"
    And I should not see "Section 2"
    And I set the field "destinationcurrent" to "1"
    And I press "Next"
    And I press "Next"
    And I press "Next"
    And I should see "Test assign"
    And I should not see "Test data"

  @javascript
  Scenario: Backup a course using the one click backup button
    When I perform a quick backup of course "Course 2"
    Then I should see "Import a backup file"
    And I should see "Course backup area"
    And I should see "backup-totara-course-"
