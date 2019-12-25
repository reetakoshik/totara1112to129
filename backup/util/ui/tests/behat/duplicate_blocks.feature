@core @core_backup
Feature: Duplicate block
  In order to check duplicated blocks are well restored
  As admin
  I need to duplicate blocks in a course and then restore it

  @javascript
  Scenario: Restore course with duplicate blocks inside the same course
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I follow "Course 1"
    And I navigate to "Restore" node in "Course administration"
    And I merge "test_backup.mbz" backup into the current course using this options:
      | Schema | Section 3 | 0 |
    Then I should see "Course 1" in the page title
    And I should see "hcontent" exactly "2" times

  @javascript
  Scenario: Restore course that has less duplicate blocks than the restore file
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
      | Course 2 | C2 | 0 |
    And I log in as "admin"
    And I am on "Course 2" course homepage with editing mode on
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I follow "Course 1"
    And I navigate to "Restore" node in "Course administration"
    And I restore "test_backup.mbz" backup into "Course 2" course using this options:
      | Schema | Section 3 | 0 |
    Then I should see "Course 2" in the page title
    And I should see "hcontent" exactly "2" times

  @javascript
  Scenario: Restore in course that has more duplicate blocks than the backup file
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
      | Course 2 | C2 | 0 |
    And I log in as "admin"
    And I am on "Course 2" course homepage with editing mode on
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I follow "Course 1"
    And I navigate to "Restore" node in "Course administration"
    And I restore "test_backup.mbz" backup into "Course 2" course using this options:
      | Schema | Section 3 | 0 |
    Then I should see "Course 2" in the page title
    And I should see "hcontent" exactly "3" times

  @javascript
  Scenario: Restore duplicate blocks inside a new course
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    And I add the "HTML" block
    And I configure the "(new HTML block)" block
    And I set the following fields to these values:
      | Override default block title | Yes      |
      | Block title                  | htitle   |
      | Content                      | hcontent |
    And I press "Save changes"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I follow "Course 1"
    And I navigate to "Restore" node in "Course administration"
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 1 restored in a new course |
    Then I should see "Course 1 restored in a new course" in the page title
    And I should see "hcontent" exactly "2" times
