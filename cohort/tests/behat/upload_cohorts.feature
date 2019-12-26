@core @core_cohort @_file_upload
Feature: A privileged user can create cohorts using a CSV file
  In order to create cohorts using a CSV file
  As an admin
  I need to be able to upload a CSV file and navigate through the upload process

  # Totara: audiences are very different from upstream.

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
      | Cat 3 | CAT1     | CAT3     |

  @javascript
  Scenario: Upload audiences with default System context as admin
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Upload audiences"
    And I upload "cohort/tests/fixtures/uploadcohorts1.csv" file to "File" filemanager
    And I click on "Preview" "button"
    Then the following should exist in the "previewuploadedcohorts" table:
      | name          | idnumber  | description       | Category      | Status |
      | cohort name 1 | cohortid1 | first description | System        |        |
      | cohort name 2 | cohortid2 |                   | System        |        |
      | cohort name 3 | cohortid3 |                   | Miscellaneous |        |
      | cohort name 4 | cohortid4 |                   | Cat 1         |        |
      | cohort name 5 | cohortid5 |                   | Cat 2         |        |
      | cohort name 6 | cohortid6 |                   | Cat 3         |        |
    And I press "Upload audiences"
    And I should see "Uploaded 6 audiences"
    And I press "Continue"
    And the following should exist in the "cohort_admin" table:
      | Audience Name | Id        | No. of Members | Type |
      | cohort name 1 | cohortid1 | 0              | Set  |
      | cohort name 2 | cohortid2 | 0              | Set  |
    And I follow "All audiences"
    And the following should exist in the "cohort_admin" table:
      | Category      | Audience Name | Id        | No. of Members | Type |
      | System        | cohort name 1 | cohortid1 | 0              | Set  |
      | System        | cohort name 2 | cohortid2 | 0              | Set  |
      | Miscellaneous | cohort name 3 | cohortid3 | 0              | Set  |
      | Cat 1         | cohort name 4 | cohortid4 | 0              | Set  |
      | Cat 2         | cohort name 5 | cohortid5 | 0              | Set  |
      | Cat 3         | cohort name 6 | cohortid6 | 0              | Set  |

  @javascript
  Scenario: Upload audiences with default category context as admin
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Upload audiences"
    And I upload "cohort/tests/fixtures/uploadcohorts1.csv" file to "File" filemanager
    And I set the field "Default context" to "Cat 1 / Cat 3"
    And I click on "Preview" "button"
    Then the following should exist in the "previewuploadedcohorts" table:
      | name          | idnumber  | description       | Category      | Status |
      | cohort name 1 | cohortid1 | first description | Cat 3         |        |
      | cohort name 2 | cohortid2 |                   | Cat 3         |        |
      | cohort name 3 | cohortid3 |                   | Miscellaneous |        |
      | cohort name 4 | cohortid4 |                   | Cat 1         |        |
      | cohort name 5 | cohortid5 |                   | Cat 2         |        |
      | cohort name 6 | cohortid6 |                   | Cat 3         |        |
    And I press "Upload audiences"
    And I should see "Uploaded 6 audiences"
    And I press "Continue"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "All audiences"
    And the following should exist in the "cohort_admin" table:
      | Category      | Audience Name | Id        | No. of Members | Type |
      | Cat 3         | cohort name 1 | cohortid1 | 0              | Set  |
      | Cat 3         | cohort name 2 | cohortid2 | 0              | Set  |
      | Miscellaneous | cohort name 3 | cohortid3 | 0              | Set  |
      | Cat 1         | cohort name 4 | cohortid4 | 0              | Set  |
      | Cat 2         | cohort name 5 | cohortid5 | 0              | Set  |
      | Cat 3         | cohort name 6 | cohortid6 | 0              | Set  |

  @javascript
  Scenario: Upload audiences with default category context as manager
    Given the following "users" exist:
      | username | firstname | lastname | email                  |
      | user1    | User      | 1        | user1@example.com |
    And the following "role assigns" exist:
      | user  | role    | contextlevel | reference |
      | user1 | manager | Category     | CAT1      |
    When I log in as "user1"
    And I am on course index
    And I follow "Cat 1"
    And I navigate to "Audiences" node in "Category: Cat 1"
    And I follow "Upload audiences"
    And I upload "cohort/tests/fixtures/uploadcohorts1.csv" file to "File" filemanager
    And I click on "Preview" "button"
    Then the following should exist in the "previewuploadedcohorts" table:
      | name          | idnumber  | description       | Category| Status |
      | cohort name 1 | cohortid1 | first description | Cat 1   |        |
      | cohort name 2 | cohortid2 |                   | Cat 1   |        |
      | cohort name 3 | cohortid3 |                   | Cat 1   | Category Miscellaneous not found or you don't have permission to create an audience there. The default context will be used. |
      | cohort name 4 | cohortid4 |                   | Cat 1   |        |
      | cohort name 5 | cohortid5 |                   | Cat 1   | Category CAT2 not found or you don't have permission to create an audience there. The default context will be used. |
      | cohort name 6 | cohortid6 |                   | Cat 3   |        |
    And I press "Upload audiences"
    And I should see "Uploaded 6 audiences"

  @javascript
  Scenario: Upload audiences with conflicting id number
    Given the following "cohorts" exist:
      | name   | idnumber  |
      | Cohort | cohortid2 |
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Upload audiences"
    And I upload "cohort/tests/fixtures/uploadcohorts1.csv" file to "File" filemanager
    And I click on "Preview" "button"
    Then I should see "Errors were found in CSV data. See details below."
    Then the following should exist in the "previewuploadedcohorts" table:
      | name | idnumber | description | Category | Status |
      | cohort name 1 | cohortid1 | first description | System |  |
      | cohort name 2 | cohortid2 |  | System | Audience with the same ID number already exists |
      | cohort name 3 | cohortid3 |  | Miscellaneous |  |
      | cohort name 4 | cohortid4 |  | Cat 1 |  |
      | cohort name 5 | cohortid5 |  | Cat 2 |  |
      | cohort name 6 | cohortid6 |  | Cat 3 |  |
    And "Upload audiences" "button" should not exist

  @javascript
  Scenario: Upload audiences with different ways of specifying context
    When I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Upload audiences"
    And I upload "cohort/tests/fixtures/uploadcohorts2.csv" file to "File" filemanager
    And I click on "Preview" "button"
    Then the following should exist in the "previewuploadedcohorts" table:
      | name                         | idnumber  | description | Category      | Status |
      | Specify category as name     | cohortid1 |             | Miscellaneous |        |
      | Specify category as idnumber | cohortid2 |             | Cat 1         |        |
      | Specify category as id       | cohortid3 |             | Miscellaneous |        |
      | Specify category as path     | cohortid4 |             | Cat 3         |        |
      | Specify category_id          | cohortid5 |             | Miscellaneous |        |
      | Specify category_idnumber    | cohortid6 |             | Cat 1         |        |
      | Specify category_path        | cohortid7 |             | Cat 3         |        |
    And I should not see "not found or you"
    And I press "Upload audiences"
    And I should see "Uploaded 7 audiences"
    And I press "Continue"
    And I follow "Upload audiences"
    And I upload "cohort/tests/fixtures/uploadcohorts3.csv" file to "File" filemanager
    And I click on "Preview" "button"
    And the following should exist in the "previewuploadedcohorts" table:
      | name                                         | idnumber   | description | Category| Status |
      | Specify context as id (system)               | cohortid8  |             | System  |        |
      | Specify context as name (system)             | cohortid9  |             | System  |        |
      | Specify context as category name only        | cohortid10 |             | Cat 1   |        |
      | Specify context as category path             | cohortid12 |             | Cat 3   |        |
      | Specify context as category idnumber         | cohortid13 |             | Cat 2   |        |
    And I should not see "not found or you"
    And I press "Upload audiences"
    And I should see "Uploaded 5 audiences"
