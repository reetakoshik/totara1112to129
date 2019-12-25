@core @totara @core_course @block_recent_activity
Feature: Correct results are shown in the recent activity screen

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email     |
      | user1    | user1     | user1    | user1@a.a |
      | user2    | user2     | user2    | user2@a.a |
    And I log in as "admin"
    And I create a course with:
      | Course full name | Course 1 |
      | Course short name | C1      |
    And I enrol "user1" user as "Teacher"
    And I enrol "user2" user as "Student"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | name | TestForum1 |
    And I add a "Forum" to section "2" and I fill the form with:
      | name | TestForum2 |
    And I add a "Forum" to section "3" and I fill the form with:
      | name | TestForum3 |
    And I delete "TestForum1" activity

  Scenario: Adding and removing activities items to the report
    When I follow "Go to full activity report"
    Then I should see "TestForum1" in the ".info > span" "css_element"
    And "(//a[./@href][contains(text(), 'TestForum2')])[2]" "xpath_element" should appear after "//span[contains(text(), \"Added\")]" "xpath_element"
    And "(//a[./@href][contains(text(), 'TestForum3')])[2]" "xpath_element" should appear after "//span[contains(text(), \"Added\")]" "xpath_element"
    And I should see "Removed Modules"
    And "(//span[contains(text(), 'TestForum1')])[3]" "xpath_element" should appear after "//span[contains(text(), \"Deleted\")]" "xpath_element"

  @javascript
  Scenario: Rename should add an update
    When I change "TestForum2" activity name to "TestForumChanged"
    And I follow "Go to full activity report"
    And "(//a[./@href][contains(text(), 'TestForumChanged')])[2]" "xpath_element" should appear after "//span[contains(text(), \"Added\")]" "xpath_element"
    And "(//a[./@href][contains(text(), 'TestForumChanged')])[3]" "xpath_element" should appear after "//span[contains(text(), \"Updated\")]" "xpath_element"
    And I should not see "TestForum2"
    # Name is preserved.
    When I am on "Course 1" course homepage
    And I delete "TestForumChanged" activity
    And I follow "Go to full activity report"
    Then "(//span[contains(text(), 'TestForumChanged')])[2]" "xpath_element" should appear after "//span[contains(text(), \"Added\")]" "xpath_element"
    And "(//span[contains(text(), 'TestForumChanged')])[3]" "xpath_element" should appear after "//span[contains(text(), \"Updated\")]" "xpath_element"
    And "(//span[contains(text(), 'TestForumChanged')])[4]" "xpath_element" should appear after "//span[contains(text(), \"Deleted\")]" "xpath_element"
    And I should not see "TestForum2"

  Scenario: Posting in a forum adds a row
    When I add a new discussion to "TestForum2" forum with:
      | Subject | Post |
      | Message | This is the body |
    And I am on "Course 1" course homepage
    And I follow "Go to full activity report"
    Then I should see "Post"
    And I should see "Admin User"
    And I should see "TestForum2"

  @javascript
  Scenario: Filter by activities
    # Filter by activity
    When I follow "Go to full activity report"
    And I click on "Show more..." "link"
    And I set the field "Activities" to "section/2"
    And I click on "Show recent activity" "button"
    Then "TestForum2" "link" should exist
    And "TestForum3" "link" should not exist
    And "TestForum1" "link" should not exist
    # Sort Ascending
    When I set the field "Sort by" to "dateasc"
    And I set the field "Activities" to "All activities"
    And I click on "Show recent activity" "button"
    Then I should not see "Removed Modules"
    And "(//span[contains(text(), 'TestForum1')])[1]" "xpath_element" should appear after "//span[contains(text(), \"Added\")]" "xpath_element"
    And "(//span[contains(text(), 'TestForum1')])[2]" "xpath_element" should appear after "//span[contains(text(), \"Deleted\")]" "xpath_element"
    # Sort Descending
    When I set the field "Sort by" to "datedesc"
    And I click on "Show recent activity" "button"
    Then I should not see "Removed Modules"
    And "(//span[contains(text(), 'TestForum1')])[1]" "xpath_element" should appear after "//span[contains(text(), \"Deleted\")]" "xpath_element"

  Scenario: Test sort by sorts the items in the recent activity report
    When I follow "Go to full activity report"
    And I set the field "Sort by" to "dateasc"
    And I click on "Show recent activity" "button"
    # Check correct ordering.
    Then I should see "TestForum1"
    And "TestForum2" "link" should appear after "TestForum1" "text"
    And "TestForum3" "link" should appear after "TestForum2" "link"
    And "TestForum1" "text" should appear after "TestForum3" "link"
    When I set the field "Sort by" to "datedesc"
    And I click on "Show recent activity" "button"
    # Check correct ordering.
    Then I should see "TestForum1"
    And "TestForum3" "link" should appear after "TestForum1" "text"
    And "TestForum2" "link" should appear after "TestForum3" "link"
    And "TestForum1" "text" should appear after "TestForum2" "link"

  Scenario: The sql for each module should throw any errors in the recent activity block
    When I add a "Glossary" to section "1" and I fill the form with:
      | Name | Test glossary name |
      | Description | Test glossary description |
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Test quiz 1           |
      | Description | Test quiz description |
    And I add a "Feedback" to section "1" and I fill the form with:
      | Name        | Test feedback 1           |
      | Description | Test feedback description |
    And I add a "Folder" to section "1" and I fill the form with:
      | Name        | Folder name        |
      | Description | Folder description |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name               | Test assignment 1 |
      | Description                   | description for assignment 1 |
    And I add a "Workshop" to section "1" and I fill the form with:
      | Workshop name | Workshop 1 |
    And I add a "Chat" to section "1" and I fill the form with:
      | Name of this chat room | Chat room |
      | Description | Chat description |
    And I add a "Survey" to section "1" and I fill the form with:
      | Name | Test survey name |
      | Survey type | ATTLS (20 item version) |
      | Description | Test survey description |
    And I add a "Wiki" to section "1" and I fill the form with:
      | Wiki name       | Test wiki name        |
      | Description     | Test wiki description |
      | First page name | First page            |
      | Wiki mode       | Collaborative wiki    |

    # Check that the structual changes were added
    Then I should see "Added Forum"
    And I should see "Added Glossary"
    And I should see "Added Quiz"
    And I should see "Added Feedback"
    And I should see "Added Folder"
    And I should see "Added Assignment"
    And I should see "Added Workshop"
    And I should see "Added Chat"
    And I should see "Added Survey"
    And I should see "Added Wiki"

    #Check that all the links go to the right place
    When I click on "TestForum2" "text" in the "Recent activity" "block"
    Then I should see "(There are no discussion topics yet in this forum)"

    When I click on "C1" "link"
    And I click on "Test glossary name" "text" in the "Recent activity" "block"
    Then I should see "Browse the glossary using this index"

    When I click on "C1" "link"
    And I click on "Test quiz 1" "text" in the "Recent activity" "block"
    Then I should see "Test quiz description"

    When I click on "C1" "link"
    And I click on "Test feedback 1" "text" in the "Recent activity" "block"
    Then I should see "Test feedback description"

    When I click on "C1" "link"
    And I click on "Folder name" "text" in the "Recent activity" "block"
    Then I should see "Folder description"

    When I click on "C1" "link"
    And I click on "Test assignment 1" "text" in the "Recent activity" "block"
    Then I should see "description for assignment 1"

    When I click on "C1" "link"
    And I click on "Workshop 1" "text" in the "Recent activity" "block"
    Then I should see "Setup phase"

    When I click on "C1" "link"
    And I click on "Chat room" "text" in the "Recent activity" "block"
    Then I should see "Chat description"

    When I click on "C1" "link"
    And I click on "Test survey name" "text" in the "Recent activity" "block"
    Then I should see "Test survey description"

    When I click on "C1" "link"
    And I click on "Test wiki name" "text" in the "Recent activity" "block"
    Then I should see "Test wiki description"

  Scenario: The sql for each module should throw any errors in the recent activity page
    When I add a "Glossary" to section "1" and I fill the form with:
      | Name | Test glossary name |
      | Description | Test glossary description |
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Test quiz 1           |
      | Description | Test quiz description |
    And I add a "Feedback" to section "1" and I fill the form with:
      | Name              | Test feedback 1           |
      | Description       | Test feedback description |
      | Record user names | 2                         |
    And I add a "Folder" to section "1" and I fill the form with:
      | Name        | Folder name        |
      | Description | Folder description |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name               | Test assignment 1 |
      | Description                   | description for assignment 1 |
    And I add a "Workshop" to section "1" and I fill the form with:
      | Workshop name | Workshop 1 |
    And I add a "Chat" to section "1" and I fill the form with:
      | Name of this chat room | Chat room |
      | Description | Chat description |
    And I add a "Survey" to section "1" and I fill the form with:
      | Name | Test survey name |
      | Survey type | ATTLS (20 item version) |
      | Description | Test survey description |
    And I add a "Wiki" to section "1" and I fill the form with:
      | Wiki name       | Test wiki name        |
      | Description     | Test wiki description |
      | First page name | First page            |
      | Wiki mode       | Collaborative wiki    |

    And I click on "Go to full activity report" "link"

  # Check that the structural changes were added
    Then I should see "TestForum2"
    And I should see "Test glossary name"
    And I should see "Test quiz 1"
    And I should see "Test feedback 1"
    And I should see "Folder name"
    And I should see "Test assignment 1"
    And I should see "Workshop 1"
    And I should see "Chat room"
    And I should see "Test survey name"
    And I should see "Test wiki name"

  @javascript
  Scenario: Recent activity note for each module is generated correctly
    When I add a "Glossary" to section "1" and I fill the form with:
      | Name | Test glossary name |
      | Description | Test glossary description |
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Test quiz 1           |
      | Description | Test quiz description |
    And I add a "Feedback" to section "1" and I fill the form with:
      | Name              | Test feedback 1           |
      | Description       | Test feedback description |
      | Record user names | 2                         |
    And I add a "Folder" to section "1" and I fill the form with:
      | Name        | Folder name        |
      | Description | Folder description |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name               | Test assignment 1 |
      | Description                   | description for assignment 1 |
    And I add a "Workshop" to section "1" and I fill the form with:
      | Workshop name | Workshop 1 |
      | Use self-assessment | 1    |
    And I add a "Chat" to section "1" and I fill the form with:
      | Name of this chat room | Chat room |
      | Description | Chat description |
    And I add a "Survey" to section "1" and I fill the form with:
      | Name | Test survey name |
      | Survey type | ATTLS (20 item version) |
      | Description | Test survey description |
    And I add a "Wiki" to section "1" and I fill the form with:
      | Wiki name       | Test wiki name        |
      | Description     | Test wiki description |
      | First page name | First page            |
      | Wiki mode       | Collaborative wiki    |

    # Check Forum
    And I click on "TestForum2" "link"
    And I click on "Add a new discussion topic" "button"
    And I set the following fields to these values:
      | Subject | newTopic   |
      | Message | newMessage |
    And I click on "Post to forum" "button"
    And I click on "C1" "link"

    Then I should see "newTopic" in the "Recent activity" "block"
    When I click on "newTopic" "link" in the "Recent activity" "block"
    Then I should see "newMessage"

    # Check Glossary
    When I click on "C1" "link"
    And I click on "Test glossary name" "link"
    And I click on "Add a new entry" "button"
    And I set the following fields to these values:
      | Concept    | newConcept    |
      | Definition | newDefinition |
    And I click on "Save changes" "button"
    And I click on "C1" "link"

    Then I should see "newConcept" in the "Recent activity" "block"
    When I click on "newConcept" "link" in the "Recent activity" "block"
    Then I should see "newDefinition"

    # Check quiz
    When I click on "C1" "link"
    And I click on "Test quiz 1" "link"
    And I click on "Edit quiz" "button"
    And I click on "Add" "link" in the "div#region-main" "css_element"
    And I click on "a new question" "link"
    And I click on "True/False" "text"
    And I click on "Add" "button" in the ".chooserdialogue-mod_quiz-questionchooser" "css_element"
    And I set the following fields to these values:
      | Question name | question1    |
      | Question text | questionText |
    And I click on "submitbutton" "button"

    And I log out
    And I log in as "user2"
    And I am on "Course 1" course homepage
    And I click on "Test quiz 1" "link"
    And I click on "Attempt quiz now" "button"
    And I click on "True" "text"
    And I click on "Finish attempt" "button"
    And I click on "Submit all and finish" "button"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage

    Then I should see "New Quiz responses:" in the "Recent activity" "block"
    When I click on "Test quiz 1" "link" in the "Recent activity" "block"
    Then I should see "Attempts: 1"

    # Check Feedback
    When I click on "C1" "link"
    And I click on "Test feedback 1" "link"
    And I click on "Edit questions" "link"
    And I set the field "Add question" to "Short text answer"
    And I wait "1" seconds
    And I set the following fields to these values:
      | Question | newQuestion |
      | Label    | newLabel    |
    And I click on "Save question" "button"
    And I click on "Overview" "link"
    And I log out
    And I log in as "user2"
    And I am on "Course 1" course homepage
    And I click on "Test feedback 1" "link"
    And I click on "Answer the questions..." "link"
    And I set the field "newQuestion" to "newAnswer"
    And I click on "Submit your answers" "button"
    And I click on "Continue" "button"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage

    Then I should see "New Feedback response:" in the "Recent activity" "block"
    When I click on "Test feedback 1" "link" in the "Recent activity" "block"
    And I click on "Show responses" "link"
    Then I should see "newAnswer"

    # Check Folder
    When I click on "C1" "link"
    And I click on "Folder name" "link"
    And I click on "Edit" "button"
    And I upload "course/tests/fixtures/example.txt" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I click on "C1" "link"

    Then I should see "New folder content:" in the "Recent activity" "block"
    And I should see "example.txt" in the "Recent activity" "block"
    Then following "example.txt" should download "7" bytes

    # Check assignments
    And I log out
    And I log in as "user2"
    And I am on "Course 1" course homepage
    And I click on "Test assignment 1" "link"
    And I click on "Add submission" "button"
    And I upload "course/tests/fixtures/example.txt" file to "File submissions" filemanager
    And I click on "Save changes" "button"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage

    Then I should see "Assignments submitted:" in the "Recent activity" "block"
    When I click on "Test assignment 1" "link" in the "Recent activity" "block"
    Then I should see "Grading summary"

    # Check workshop
    When I click on "C1" "link"
    And I click on "Workshop 1" "link"
    And I click on "Set the workshop description" "link"
    And I set the following fields to these values:
      | Description                 | workshopDescription |
      | Instructions for submission | how submit          |
    And I click on "Save and display" "button"
    And I click on "Switch to the next phase" "link"
    And I click on "Continue" "button"
    And I log out
    And I log in as "user2"
    And I am on "Course 1" course homepage
    And I click on "Workshop 1" "link"
    And I click on "Start preparing your submission" "button"
    And I set the following fields to these values:
      | Title              | submissionTitle   |
      | Submission content | submissionContent |
    And I click on "Save changes" "button"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage

    Then I should see "Workshop submissions:" in the "Recent activity" "block"
    And I should not see "Workshop assessments:" in the "Recent activity" "block"
    When I click on "submissionTitle" "link" in the "Recent activity" "block"
    Then I should see "submissionContent"

    When I click on "C1" "link"
    And I click on "Workshop 1" "link"
    And I click on "Switch to the next phase" "link"
    And I click on "Continue" "button"

    And I click on "Workshop 1" "link"
    And I click on "Edit assessment form" "link"
    And I set the following fields to these values:
      | Description | blah |
    And I click on "Save and close" "button"

    And I click on "submissionTitle" "link"
    And I click on "Assess" "button"
    And I set the following fields to these values:
      | Feedback for the author | feedback |
      | Grade for Aspect 1      | 10       |
      | Comment for Aspect 1    | comment  |
    And I click on "Save and close" "button"
    And I click on "C1" "link"

    Then I should see "Workshop assessments:" in the "Recent activity" "block"

    # Chat is unable to be tested because multiple browser tabs is needed

    # Check Survey
    And I log out
    And I log in as "user2"
    And I am on "Course 1" course homepage
    And I click on "Test survey name" "link"
    And I set the field "q45_1" to "1"
    And I set the field "q46_1" to "1"
    And I set the field "q47_1" to "1"
    And I set the field "q48_1" to "1"
    And I set the field "q49_1" to "1"
    And I set the field "q50_1" to "1"
    And I set the field "q51_1" to "1"
    And I set the field "q52_1" to "1"
    And I set the field "q53_1" to "1"
    And I set the field "q54_1" to "1"
    And I set the field "q55_1" to "1"
    And I set the field "q56_1" to "1"
    And I set the field "q57_1" to "1"
    And I set the field "q58_1" to "1"
    And I set the field "q59_1" to "1"
    And I set the field "q60_1" to "1"
    And I set the field "q61_1" to "1"
    And I set the field "q62_1" to "1"
    And I set the field "q63_1" to "1"
    And I set the field "q64_1" to "1"
    And I click on "Click here to continue" "button"
    And I click on "Continue" "button"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage

    Then I should see "New survey responses:"
    When I click on "Test survey name" "link" in the "Recent activity" "block"
    Then I should see "View 1 survey responses"

    # Check Wiki
    And I click on "C1" "link"
    And I click on "Test wiki name" "link"
    And I click on "Create page" "button"
    And I set the field "HTML format" to "This is a new wiki page"
    And I click on "Save" "button"
    And I click on "C1" "link"

    Then I should see "Updated wiki pages:" in the "Recent activity" "block"
    When I click on "Test wiki name" "link" in the "Recent activity" "block"
    Then I should see "This is a new wiki page"

