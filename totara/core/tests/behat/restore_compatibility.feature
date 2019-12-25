@totara @core @core_course @totara_customfield @javascript @_file_upload
Feature: Restoring backups files must be backwards compatible
  In order to restore old courses
  As an admin
  I must be able to restore backups from previous Totara versions

  Background:
    Given I am on a totara site
    And the following "custom course fields" exist in "totara_core" plugin:
      | datatype    | shortname  | fullname   | param1                                         |
      | file        | cffile     | cffile     |                                                |
      | menu        | cfmenu     | cfmenu     | A/B/C                                          |
      | textarea    | cftextarea | cftextarea |                                                |
      | multiselect | cfmulti    | cfmulti    | [{"option":"X","icon":"","default":"0","delete":0},{"option":"Y","icon":"","default":"0","delete":0},{"option":"Z","icon":"","default":"0","delete":0}] |
    And I log in as "admin"
    And I set the following administration settings values:
      | Allow admin conflict resolution | 1 |
    And I navigate to "Restore course" node in "Site administration > Courses"

  Scenario: I must be able to restore a 1.1 backup without fatal errors
    When I upload "totara/core/tests/fixtures/backup-course-from-1.1.29.zip" file to "Files" filemanager
    And I press "Restore"
    And I set the field "destinationnew" to "1"
    And I press "Next"
    And I set the field "Miscellaneous" to "1"
    And I press "Next"
    And I press "Next"
    And I press "Next"
    And I press "Perform restore"
    And I press "Continue"
    And I am on "Course Two" course homepage
    Then I should see "Text activity"
    And I should see "F2f"
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I navigate to "Edit settings" node in "Course administration"

  Scenario: I must be able to restore a 2.2 backup without fatal errors
    When I upload "totara/core/tests/fixtures/backup-course-from-2.2.47.mbz" file to "Files" filemanager
    And I press "Restore"
    And I set the field "destinationnew" to "1"
    And I press "Next"
    And I set the field "Miscellaneous" to "1"
    And I press "Next"
    And I press "Next"
    And I press "Next"
    And I press "Perform restore"
    And I press "Continue"
    And I should see "page1"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | cftextarea | Some text in the textarea. |
      | cfmenu     | B                          |

  Scenario: I must be able to restore a 2.5 backup without fatal errors
    When I upload "totara/core/tests/fixtures/backup-course-from-2.5.39.mbz" file to "Files" filemanager
    And I press "Restore"
    And I set the field "destinationnew" to "1"
    And I press "Next"
    And I set the field "Miscellaneous" to "1"
    And I press "Next"
    And I press "Next"
    And I press "Next"
    And I press "Perform restore"
    And I press "Continue"
    And I should see "Page 1"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | cftextarea | Here is some text. |
      | cfmenu     | C                  |

  Scenario: I must be able to restore a 2.7 backup without fatal errors
    When I upload "totara/core/tests/fixtures/backup-course-from-2.7.16.mbz" file to "Files" filemanager
    And I press "Restore"
    And I set the field "destinationnew" to "1"
    And I press "Next"
    And I set the field "Miscellaneous" to "1"
    And I press "Next"
    And I press "Next"
    And I press "Next"
    And I press "Perform restore"
    And I press "Continue"
    And I should see "page1"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | cftextarea | <p>Some text...</p><p></p> |
      | cfmenu     | B                          |
