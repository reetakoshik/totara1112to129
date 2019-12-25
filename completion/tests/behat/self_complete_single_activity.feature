@core @core_completion @totara @javascript
Feature: User can self complete an activity from within a single activity course
  In order to self complete an activity
  The self completion form needs to work inside a single activity course

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | email             |
      | user1    | user1@example.com |

  Scenario Outline: Confirm single activity courses have self completion avaliable inside the activity
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | format         | activitytype |
      | Course 1 | c1        | 1                | singleactivity | <activity>   |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | c1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I expand all fieldsets
    And I set the field "Type of activity" to "<activity>"
    And I click on "Save and display" "button"
    And I set the following fields to these values:
      | <name>              | Activity Name                                        |
      | Completion tracking | Learners can manually mark the activity as completed |
      | <req1>              | <reqvalue>                                           |
    And I press "Save and display"
    # This is used instead of "I log out" as there is no footer (with a log out in an External tool activity)
    And I click on "Admin User" "link"
    And I follow "Log out"

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I set the "I have completed this activity" Totara form field to "1"
    And I reload the page
    And I set the "I have completed this activity" Totara form field to "0"
    And I reload the page
    And the field "I have completed this activity" matches value "0"

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

  Scenario: Confirm the book single activity course has self completion available inside itself
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | format         | activitytype |
      | Course 1 | c1        | 1                | singleactivity | book         |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | c1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I set the following fields to these values:
      | Name                | Book Book                                            |
      | Completion tracking | Learners can manually mark the activity as completed |
    And I click on "Save and display" "button"
    And I set the following fields to these values:
      | Chapter title | Hi there                     |
      | Content       | This is some chapter content |
    And I click on "Save changes" "button"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I set the "I have completed this activity" Totara form field to "1"
    And I reload the page
    And I set the "I have completed this activity" Totara form field to "0"
    And I reload the page
    And the field "I have completed this activity" matches value "0"

  @_file_upload
  Scenario: Confirm the SCORM single activity course has self completion available inside itself
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | format         | activitytype  |
      | Course 1 | c1        | 1                | singleactivity | SCORM package |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | c1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I expand all fieldsets
    And I set the field "Type of activity" to "SCORM package"
    And I click on "Save and display" "button"
    And I set the following fields to these values:
      | Name                | SCORMed                                              |
      | Completion tracking | Learners can manually mark the activity as completed |
    And I upload "mod/scorm/tests/packages/singlesco_scorm12.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I set the "I have completed this activity" Totara form field to "1"
    And I reload the page
    And I set the "I have completed this activity" Totara form field to "0"
    And I reload the page
    And the field "I have completed this activity" matches value "0"

  @_file_upload
  Scenario: Confirm the IMS single activity course has self completion available inside itself
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | format         | activitytype        |
      | Course 1 | c1        | 1                | singleactivity | IMS content package |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | c1     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I expand all fieldsets
    And I set the field "Type of activity" to "IMS content package"
    And I click on "Save and display" "button"
    And I set the following fields to these values:
      | Name                | This is IMS                                          |
      | Completion tracking | Learners can manually mark the activity as completed |
    And I upload "mod/imscp/tests/packages/singlescobasic.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    # Waiting for ISC to load, otherwise it will periodically load SCO resource files on pages load.
    And I wait "1" seconds
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I set the "I have completed this activity" Totara form field to "1"
    And I reload the page
    And I set the "I have completed this activity" Totara form field to "0"
    And I reload the page
    And the field "I have completed this activity" matches value "0"

  @_file_upload
  Scenario Outline: Confirm the file single activity course has self completion available inside itself
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | format         | activitytype |
      | Course 1 | c1        | 1                | singleactivity | File         |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | c1     | student |
    And I log in as "admin"
    And I navigate to "File" node in "Site administration > Plugins > Activity modules"
    And I set the field "Available display options" to "<type>"
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I expand all fieldsets
    And I set the field "Type of activity" to "File"
    And I click on "Save and display" "button"
    And I set the following fields to these values:
      | Name                | Pictured                                             |
      | Completion tracking | Learners can manually mark the activity as completed |
      | Display             | <type>                                               |
    And I upload "completion/tests/fixtures/fruit.jpg" file to "Select files" filemanager
    And I click on "Save and display" "button"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I set the "I have completed this activity" Totara form field to "1"
    And I reload the page
    And I set the "I have completed this activity" Totara form field to "0"
    And I reload the page
    And the field "I have completed this activity" matches value "0"

    Examples:
      | type      |
      | Automatic |
      | Embed     |
