@totara @totara_hierarchy @totara_hierarchy_competency @javascript
Feature: Test competencies achieved is updated
  In order to test the competencies achieved is updated
  As a user
  I need to be able to assign a manager

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                        | role         | context |
      | bilbo    | Bilbo     | Baggins  | bilbo.baggins@example.com    |              |         |
      | gandalf  | Gandalf   | the Grey | gandalf.the.grey@example.com | staffmanager | system  |
    And the following "courses" exist:
      | fullname              | shortname | format |enablecompletion | completionstartonenrol |
      | An Unexpected Journey | C1        | weeks  | 1               | 1                      |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | bilbo | C1     | student |
    And the following "competency" frameworks exist:
      | fullname                    | idnumber | description                               |
      | Reclaim the Lonely Mountain | CFW001   | The mountain in the north of Rhovanion... |
    And the following "competency" hierarchy exists:
      | framework | fullname        | idnumber | description                                        |
      | CFW001    | Kill the Smaug  | COMP001  | The dragon who invaded the Dwarf kingdom of Erebor |

  Scenario: Add a choice activity and complete the activity as a user
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I click on "Courses" in the totara menu
    And I click on "An Unexpected Journey" "link"
    And I turn editing mode on
    And I add a "Choice" to section "1" and I fill the form with:
      | Choice name         | Help to Gandalf the Grey                          |
      | Description         | The wizard, member of the Istari order            |
      | option[0]           | Join the Dwarves                                  |
      | option[1]           | Stay home                                         |
      | id_completion       | Show activity as complete when conditions are met |
      | id_completionsubmit | 1                                                 |
    And I turn editing mode off
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Choice - Help to Gandalf the Grey" "checkbox"
    And I press "Save changes"
    And I click on "Home" in the totara menu
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "Bilbo Baggins" "link"
    And I click on "Add job assignment" "link"
    And I set the following fields to these values:
      | Full name    | Burglar Contracted By Dwarves |
      | Short name   | Burglar Contracted By Dwarves |
      | ID Number    | 12345                         |
    And I click on "Choose manager" "button"
    And I click on "Gandalf the Grey" "link"
    And I click on "OK" "button" in the ".totara-dialog[aria-describedby=manager]" "css_element"
    And I click on "Add job assignment" "button"
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I click on "Reclaim the Lonely Mountain" "link"
    And I click on "Kill the Smaug" "link"
    And I press "Assign course completions"
    And I click on "Miscellaneous" "link"
    And I click on "An Unexpected Journey" "link"
    And I click on "Save" "button" in the ".totara-dialog[aria-describedby=evidence]" "css_element"
    And I wait "2" seconds
    And I press "Return to competency framework"
    And I log out
    And I log in as "bilbo"
    And I click on "An Unexpected Journey" "link"
    And I choose "Join the Dwarves" from "Help to Gandalf the Grey" choice activity
    And I should see "Your selection: Join the Dwarves"
    And I click on "Dashboard" in the totara menu
    And I click on "Record of Learning" in the totara menu
    And I should see "Complete"
    And I log out
    And I log in as "admin"
    And I run the "\totara_hierarchy\task\update_competencies_task" task
    And I log out
    And I log in as "gandalf"
    And I click on "Team" in the totara menu
    And I should see "1" in the "td.statistics_competenciesachieved" "css_element"
