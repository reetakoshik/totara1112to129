@core @core_completion @totara @javascript
Feature: User can self complete an activity from within an activity
  In order to self complete an activity
  The self completion form needs to work inside an activity

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | email             |
      | user1    | user1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | c1        | 1                |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | c1     | student |

  Scenario Outline: Confirm activities have self completion avaliable inside the activity
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "<activity>" to section "1" and I fill the form with:
      | <name>              | Activity Name                                        |
      | Completion tracking | Learners can manually mark the activity as completed |
      | <req1>              | <reqvalue>                                           |
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Activity Name"
    And I set the "I have completed this activity" Totara form field to "1"
    And I follow "c1"
    Then I should see "Completed: Activity Name. Select to mark as not complete."

    When I follow "Activity Name"
    And I set the "I have completed this activity" Totara form field to "0"
    And I follow "c1"
    Then I should see "Not completed: Activity Name. Select to mark as complete."

    Examples:
      | activity      | name            | req1            | reqvalue        |
      | Assignment    | Assignment name | Description     | lorum ipsum     |
      | Certificate   | Name            | Introduction    | lorum ipsum     |
      | Chat          | Name            | Description     | lorum ipsum     |
      | Choice        | Choice name     | Option 1        | lorum ipsum     |
      | Database      | Name            | Description     | lorum ipsum     |
      | External tool | Activity name   | Tool URL        | https://lti-examples.heroku.com/index.html |
      | Feedback      | Name            | Description     | lorum ipsum     |
      | Forum         | Forum name      | Description     | lorum ipsum     |
      | Glossary      | Name            | Description     | lorum ipsum     |
     #| Lesson        | Name            | Description     | lorum ipsum     | This has been purposefully left out as a user should require a passing grade
      | Page          | Name            | Page content    | lorum ipsum     |
     #| Quiz          | Name            | Description     | lorum ipsum     | This has been purposefully left out as a user should require a passing grade
      | Seminar       | Name            | Description     | lorum ipsum     |
      | Survey        | Name            | Survey type     | Critical incidents |
      | Folder        | Name            | Description     | lorum ipsum     |
      | URL           | Name            | External URL    | www.example.com |
      | Wiki          | Wiki name       | First page name | lorum ipsum     |

  @_file_upload
  Scenario Outline: Confirm the file activity has self completion available inside itself
    And I log in as "admin"
    And I navigate to "File" node in "Site administration > Plugins > Activity modules"
    And I set the field "Available display options" to "<type>"
    And I press "Save changes"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "File" to section "1" and I fill the form with:
      | Name                | Pictured                                             |
      | Completion tracking | Learners can manually mark the activity as completed |
      | Display             | <type>                                               |
    And I upload "completion/tests/fixtures/fruit.jpg" file to "Select files" filemanager
    And I click on "Save and return to course" "button"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Pictured"
    And I set the "I have completed this activity" Totara form field to "1"
    And I follow "c1"
    Then I should see "Completed: Pictured. Select to mark as not complete."

    When I follow "Pictured"
    And I set the "I have completed this activity" Totara form field to "0"
    And I follow "c1"
    Then I should see "Not completed: Pictured. Select to mark as complete."

    Examples:
      | type      |
      | Automatic |
      | Embed     |

  Scenario: Confirm the book activity has self completion available inside itself
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Book" to section "1" and I fill the form with:
      | Name                | Book Book                                            |
      | Completion tracking | Learners can manually mark the activity as completed |
    And I follow "Book Book"
    And I set the following fields to these values:
      | Chapter title | Hi there                     |
      | Content       | This is some chapter content |
    And I click on "Save changes" "button"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Book Book"
    And I set the "I have completed this activity" Totara form field to "1"
    And I follow "c1"
    Then I should see "Completed: Book Book. Select to mark as not complete."

    When I follow "Book Book"
    And I set the "I have completed this activity" Totara form field to "0"
    And I follow "c1"
    Then I should see "Not completed: Book Book. Select to mark as complete."

  @_file_upload
  Scenario: Confirm the SCORM activity has self completion available inside itself
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1" and I fill the form with:
      | Name                | SCORMed                                              |
      | Completion tracking | Learners can manually mark the activity as completed |
    # This works as validation fails
    And I upload "mod/scorm/tests/packages/singlesco_scorm12.zip" file to "Package file" filemanager
    And I click on "Save and return to course" "button"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "SCORMed"
    And I set the "I have completed this activity" Totara form field to "1"
    And I follow "c1"
    Then I should see "Completed: SCORMed. Select to mark as not complete."

    When I follow "SCORMed"
    And I set the "I have completed this activity" Totara form field to "0"
    And I follow "c1"
    Then I should see "Not completed: SCORMed. Select to mark as complete."

  @_file_upload
  Scenario: Confirm the IMS activity has self completion available inside itself
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "IMS content package" to section "1" and I fill the form with:
      | Name                | This is IMS                                          |
      | Completion tracking | Learners can manually mark the activity as completed |
    And I upload "mod/imscp/tests/packages/singlescobasic.zip" file to "Package file" filemanager
    And I click on "Save and return to course" "button"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "This is IMS"
    And I set the "I have completed this activity" Totara form field to "1"
    And I follow "c1"
    Then I should see "Completed: This is IMS. Select to mark as not complete."

    When I follow "This is IMS"
    And I set the "I have completed this activity" Totara form field to "0"
    And I follow "c1"
    Then I should see "Not completed: This is IMS. Select to mark as complete."

  Scenario: Confirm self completion form is not displayed when other completion options are used
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | No completion                       |
      | Completion tracking | Do not indicate activity completion |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name     | System completion                                 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require view        | 1                                                 |
    And I log out

    Given I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "No completion"
    Then I should not see "I have completed this activity"

    When I follow "c1"
    And I follow "System completion"
    Then I should not see "I have completed this activity"
