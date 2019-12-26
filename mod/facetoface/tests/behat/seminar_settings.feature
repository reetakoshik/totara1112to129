@mod @mod_facetoface
Feature: Disabling approval options in global settings would
  not cause the empty radio button in seminar module's settings

  Background: I am on totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user1    | user      | lastname | email@something.com |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course101 | C101      | 0        |

  @javascript
  Scenario: Disabled approval options in global settings are not affect to seminars module's settings
    Given I log in as "admin"
    And I navigate to "Permissions > Define roles" in site administration
    And I click on "Add a new role" "button"
    And I set the following fields to these values:
      | resettype | 3 |
    And I click on "Continue" "button"
    And I set the following fields to these values:
      | System           | 1        |
      | Short name       | train    |
      | Custom full name | Training |
    And I click on "Create this role" "button"
    And I navigate to "Seminars > Global settings" in site administration
    And I set the following fields to these values:
      | Training | 1 |
    And I click on "Save changes" "button"
    And I set the following fields to these values:
      | id_s__facetoface_approvaloptions_approval_role_10 | 1 |
    And I click on "Save changes" "button"
    And I click on "Courses" in the totara menu
    And I follow "Course101"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1           |
      | Training    | 1                   |
    And I navigate to "Seminars > Global settings" in site administration
    And I set the following fields to these values:
      | id_s__facetoface_approvaloptions_approval_role_10 | 0 |
    And I click on "Save changes" "button"
    And I click on "Courses" in the totara menu
    And I follow "Course101"
    And I follow "Seminar 1"
    And I follow "Edit settings"
    And I expand all fieldsets
    Then I should see "Training"
    And I set the field "No Approval" to "1"
    And I click on "Save and display" "button"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I should not see "Training"
