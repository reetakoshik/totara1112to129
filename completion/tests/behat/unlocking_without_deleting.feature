@core @core_completion
Feature: Unlocking without deleting course completion data
  In order to test unlocking of course completion without deletion
  I log into admin change several aspects of completion

  @javascript
  Scenario: Editing an activity used in course completion
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@example.com |
      | student1 | Student | First | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
      | Enable restricted access   | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And completion tracking is "Enabled" in current course
    And I turn editing mode on
    And I add the "Course completion status" block
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I add a "Label" to section "1" and I fill the form with:
      | Label text | Test label |
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Forum - Test forum name | 1 |
      | Label - Test label | 1 |
    And I press "Save changes"
    And "Student First" user has not completed "Test label" activity
    And "Student First" user has not completed "Test forum name" activity
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Not completed: Test forum name. Select to mark as complete." "link"
    And I click on "Not completed: Test label. Select to mark as complete." "link"
    And I trigger cron
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I expand "Reports" node
    And I follow "Activity completion"
    And "Student First" user has completed "Test forum name" activity
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Test forum name"
    And I follow "Edit settings"
    And I set the following fields to these values:
     | Forum name | Modified forum name |
    And I press "Save and return to course"
    And I expand "Reports" node
    And I follow "Activity completion"
    And "Student First" user has completed "Modified forum name" activity
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"

  @javascript
  Scenario: Unlocking completion without deleting and adding an activity
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@example.com |
      | student1 | Student | First | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
      | Enable restricted access   | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And completion tracking is "Enabled" in current course
    And I turn editing mode on
    And I add the "Course completion status" block
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I add a "Label" to section "1" and I fill the form with:
      | Label text | Test label |
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Forum - Test forum name | 1 |
      | Label - Test label | 1 |
    And I press "Save changes"
    And "Student First" user has not completed "Test label" activity
    And "Student First" user has not completed "Test forum name" activity
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Not completed: Test forum name. Select to mark as complete." "link"
    And I click on "Not completed: Test label. Select to mark as complete." "link"
    And I trigger cron
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I expand "Reports" node
    And I follow "Activity completion"
    And "Student First" user has completed "Test forum name" activity
    And "Student First" user has completed "Test label" activity
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Test assignment description |
    And I navigate to "Course completion" node in "Course administration"
    And I press "Unlock criteria without deleting"
    And I set the following fields to these values:
      | Assignment - Test assignment name | 1 |
    And I press "Save changes"
    And I trigger cron
    And I am on "Course 1" course homepage
    And "Student First" user has completed "Test forum name" activity
    And "Student First" user has completed "Test label" activity
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"

  @javascript
  Scenario: Unlocking completion without deleting and adding and editing an activity
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@example.com |
      | student1 | Student | First | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
      | Enable restricted access   | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And completion tracking is "Enabled" in current course
    And I turn editing mode on
    And I add the "Course completion status" block
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I add a "Label" to section "1" and I fill the form with:
      | Label text | Test label |
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Forum - Test forum name | 1 |
      | Label - Test label | 1 |
    And I press "Save changes"
    And "Student First" user has not completed "Test label" activity
    And "Student First" user has not completed "Test forum name" activity
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Not completed: Test forum name. Select to mark as complete." "link"
    And I click on "Not completed: Test label. Select to mark as complete." "link"
    And I trigger cron
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I expand "Reports" node
    And I follow "Activity completion"
    And "Student First" user has completed "Test forum name" activity
    And "Student First" user has completed "Test label" activity
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Test assignment description |
    And I navigate to "Course completion" node in "Course administration"
    And I press "Unlock criteria without deleting"
    And I set the following fields to these values:
      | Assignment - Test assignment name | 1 |
    And I press "Save changes"
    And I trigger cron
    And I am on "Course 1" course homepage
    And "Student First" user has completed "Test forum name" activity
    And "Student First" user has completed "Test label" activity
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Test assignment name"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Assignment name | Modified assignment name |
    And I press "Save and return to course"
    And I trigger cron
    And I am on "Course 1" course homepage
    And "Student First" user has completed "Test forum name" activity
    And "Student First" user has completed "Test label" activity
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"

  @javascript
  Scenario: Unlocking activity completion and deleting when editing
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@example.com |
      | student1 | Student | First | student1@example.com |
      | student2 | Student | Second | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
      | Enable restricted access   | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And completion tracking is "Enabled" in current course
    And I turn editing mode on
    And I add the "Course completion status" block
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Forum - Test forum name | 1 |
    And I press "Save changes"
    And "Student First" user has not completed "Test forum name" activity
    And "Student Second" user has not completed "Test forum name" activity
    And I log out
    # Log in and complete the forum as student1
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Not completed: Test forum name. Select to mark as complete." "link"
    And I trigger cron
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    # Log in and complete the forum as student2 and view the forum
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Add a new discussion topic"
    And I set the following fields to these values:
     | Subject | My first post |
     | Message | This is my first post |
    And I press "Post to forum"
    And I follow "Course 1"
    And I click on "Not completed: Test forum name. Select to mark as complete." "link"
    And I trigger cron
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    # Edit the forum, unlock deleting, and set require forum view.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And "Student First" user has completed "Test forum name" activity
    And "Student Second" user has completed "Test forum name" activity
    And I follow "Course 1"
    And I follow "Test forum name"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I press "Unlock completion and delete completion data"
    And I set the following fields to these values:
     | Completion tracking | Show activity as complete when conditions are met |
     | completiondiscussionsenabled | 1 |
     | completiondiscussions | 1 |
    And I press "Save and return to course"
    And "Student First" user has not completed "Test forum name" activity
    And "Student Second" user has not completed "Test forum name" activity
    When I run the "\core\task\completion_regular_task" task
    Then "Student First" user has not completed "Test forum name" activity
    And "Student Second" user has completed "Test forum name" activity

  @javascript
  Scenario: Unlocking activity completion and preserving when editing
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@example.com |
      | student1 | Student | First | student1@example.com |
      | student2 | Student | Second | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
      | Enable restricted access   | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And completion tracking is "Enabled" in current course
    And I turn editing mode on
    And I add the "Course completion status" block
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Forum - Test forum name | 1 |
    And I press "Save changes"
    And "Student First" user has not completed "Test forum name" activity
    And "Student Second" user has not completed "Test forum name" activity
    And I log out
    # Log in and complete the forum as student1
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Not completed: Test forum name. Select to mark as complete." "link"
    And I trigger cron
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    # Log in and complete the forum as student2 and view the forum
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "Test forum name"
    And I press "Add a new discussion topic"
    And I set the following fields to these values:
      | Subject | My first post |
      | Message | This is my first post |
    And I press "Post to forum"
    And I follow "Course 1"
    And I click on "Not completed: Test forum name. Select to mark as complete." "link"
    And I trigger cron
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    # Edit the forum, unlock deleting, and set require forum view.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And "Student First" user has completed "Test forum name" activity
    And "Student Second" user has completed "Test forum name" activity
    And I follow "Course 1"
    And I follow "Test forum name"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I press "Unlock completion and keep completion data"
    And I set the following fields to these values:
      | Completion tracking | Show activity as complete when conditions are met |
      | completiondiscussionsenabled | 1 |
      | completiondiscussions | 1 |
    And I press "Save and return to course"
    And "Student First" user has completed "Test forum name" activity
    And "Student Second" user has completed "Test forum name" activity
    # Trigger cron and make sure its still true.
    And I trigger cron
    And I am on "Course 1" course homepage
    And "Student First" user has completed "Test forum name" activity
    And "Student Second" user has completed "Test forum name" activity
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I should see "Status: Complete"
