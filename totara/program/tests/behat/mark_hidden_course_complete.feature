@totara @totara_program @totara_courseprogressbar @javascript
Feature: Mark hidden course in a program complete via recognition of prior learning
  Mark a course hidden by cohort complete within a program
  As admin
  Via recognition of prior learning

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username       | firstname | lastname | email                      |
      | learner1       | Learner   | One      | learner1@example.com       |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion | audiencevisible |
      | Course One | course1   | topics | 1                | 1               |
    And the following "programs" exist in "totara_program" plugin:
      | fullname    | shortname | idnumber |
      | Program One | prog1     | prog1    |
    And I add a courseset with courses "course1" to "prog1":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user     |
      | prog1   | learner1 |

  Scenario: Admin can mark a learners course complete by RPL in ROL program page
    Given I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Learner One" "link"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Programs" tab
    And I click on "Program One" "link"
    Then I should see "Mark complete"
    # Below is the screen reader text for the mark complete checkbox (actually image of a checkbox with a link)
    And I click on "Not completed: Course One. Select to mark as complete." "link"
    And I press "Save changes"
    When I click on "Program One" "link"
    Then "Completed: Course One. Select to mark as not complete." "link" should exist
