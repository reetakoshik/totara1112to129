@core @core_badges @totara_cohort @_file_upload @javascript
Feature: Verify badge issue based on cohort / audience completion criterion.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | A1       |
      | Audience 2 | A2       |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | A1     |
      | learner1 | A2     |

    # Create a badge.
    When I log in as "admin"
    And I navigate to "Manage badges" node in "Site administration > Badges"
    And I click on "Add a new badge" "button"
    And I set the following fields to these values:
      | Name        | Audience Badge             |
      | Description | Audience Badge description |
      | issuername  | Mr Test                    |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    And I press "Create badge"
    Then I should see "To start adding criteria, please select one of the options from the drop-down menu."

    # Give the badge 'audience membership' criteria.
    When I set the field "Add badge criteria" to "Audience membership"
    # Redirects to audience selection form.
    When I set the field "Qualifying audience(s)" to "Audience 1,Audience 2"
    And I press "Save"
    Then I should see "Membership in ANY of the following audiences is required:"

    # Enable the badge.
    When I press "Enable access"
    Then I should see "Changes in badge access"
    And I should see "This will make your badge visible to users and allow them to start earning it."

    # Once the badge is enabled, it is immediately issued.
    When I press "Continue"
    Then I should see "This badge is currently available to users, and its criteria are locked"

  Scenario: Verify badge is issued when user is member of audience.

    Given I navigate to "Manage badges" node in "Site administration > Badges"
    When I follow "Audience Badge"
    Then I should see "Recipients (1)"

    # Check that the user has been issued the badge.
    When I follow "Recipients (1)"
    Then I should see "Bob1 Learner1"

  Scenario: Verify audience badge can still be enabled and issued when multiple criteria is only partially available.

    Given I navigate to "Audiences" node in "Site administration > Audiences"
    When I click on "Delete" "link" in the "Audience 2" "table_row"
    Then I should see "Do you really want to delete audience 'Audience 2'?"

    When I press "Yes"
    Then I should see "Successfully deleted audience"
    And I should not see "Audience 2"

    # Disable and re-enable the badge so we can check that as it's
    # got valid criteria it can still be activated and issued.
    When I navigate to "Manage badges" node in "Site administration > Badges"
    Then I should see "Warning: An audience is no longer available." in the "Audience Badge" "table_row"

    When I click on "Disable access" "link" in the "Audience Badge" "table_row"
    Then I should see "Access to the badges was successfully disabled."

    When I click on "Enable access" "link" in the "Audience Badge" "table_row"
    Then I should see "Changes in badge access"

    When I press "Continue"
    Then I should see "Available to users" in the "Audience Badge" "table_row"

    When I click on "Edit" "link" in the "Audience Badge" "table_row"
    And I follow "Recipients (1)"
    Then I should see "Bob1 Learner1"

  Scenario: Verify audience badge can't be enabled and when no criteria is available.

    # Delete the audiences so all the badge criteria is missing.
    Given I navigate to "Audiences" node in "Site administration > Audiences"
    When I click on "Delete" "link" in the "Audience 1" "table_row"
    Then I should see "Do you really want to delete audience 'Audience 1'?"

    When I press "Yes"
    Then I should see "Successfully deleted audience"
    And I should not see "Audience 1"

    When I click on "Delete" "link" in the "Audience 2" "table_row"
    Then I should see "Do you really want to delete audience 'Audience 2'?"

    When I press "Yes"
    Then I should see "Successfully deleted audience"
    And I should not see "Audience 2"

    # The missing audiences should be flagged as a problem against the badge.
    When I navigate to "Manage badges" node in "Site administration > Badges"
    Then I should see "Warning: 2 audiences are no longer available." in the "Audience Badge" "table_row"

    When I click on "Disable access" "link" in the "Audience Badge" "table_row"
    Then I should see "Access to the badges was successfully disabled."

    When I click on "Enable access" "link" in the "Audience Badge" "table_row"
    Then I should see "Changes in badge access"

    # The badge should not be be re-enabled because of he missing criteria.
    When I press "Continue"
    Then I should see "Cannot activate the badge \"Audience Badge\". Invalid criteria parameters. 2 audiences do not exist."

    When I press "Continue"
    Then I should see "Not available to users" in the "Audience Badge" "table_row"
