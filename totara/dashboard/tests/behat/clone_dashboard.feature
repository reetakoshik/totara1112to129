@totara @totara_dashboard
Feature: Clone a dashboard
  In order to ensure that dashboards can be cloned
  As an admin
  I create and customise a dashboard
  I then clone the dashboard

  @javascript
  Scenario: Cloning a dashboard copies blocks
    Given I am on a totara site
    And the following totara_dashboards exist:
      | name | locked | published |
      | Primary dashboard | 0 | 1 |
      | Secondary dashboard | 0 | 1 |

    # Add a block to the primary dashboard
    When I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Primary dashboard" "link"
    And I press "Blocks editing on"
    And I add the "Latest announcements" block
    Then "Latest announcements" "block" should exist

    # Check the latest news block is definitely there
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Primary dashboard" "link"
    And "Latest announcements" "block" should exist

    # Check that the Latest announcements block is not present
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Secondary dashboard" "link"
    And "Latest announcements" "block" should not exist

    # Clone the primary dashboard
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Clone dashboard" "link" in the "Primary dashboard" "table_row"
    Then I should see "Do you really want to clone dashboard Primary dashboard?"
    # Confirm the clone
    When I press "Continue"
    Then I should see "Dashboard 'Primary dashboard' successfully cloned to 'Primary dashboard copy 1'"

    # Confirm I now have three dashboard in the expected order
    When I click on "Edit dashboard" "link" in the "Primary dashboard" "table_row"
    And I set the following fields to these values:
    | Name | Original dashboard |
    And I press "Save changes"
    Then I should see "Dashboard saved"
    And I should see "Original dashboard"
    And I should see "Secondary dashboard"
    And I should see "Primary dashboard copy 1"
    And "Original dashboard" "text" should appear before "Secondary dashboard" "text"
    And "Secondary dashboard" "text" should appear before "Primary dashboard" "text"

    # Confirm the cloned dashboard
    When I click on "Primary dashboard" "link"
    And "Latest announcements" "block" should exist

  @javascript
  Scenario: Cloning a dashboard copies audiences
    Given I am on a totara site
    And the following "users" exist:
      | username |
      | learner1 |
      | learner2 |
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort 1 | CH1 |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | CH1    |
    And the following totara_dashboards exist:
      | name | locked | published | cohorts |
      | Primary dashboard | 0 | 1 | CH1     |
      | Secondary dashboard | 0 | 1 |       |

    # Check the audience is assigned to the first dashboard.
    When I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Edit dashboard" "link" in the "Primary dashboard" "table_row"
    Then I should see "Cohort 1"

    # Check the second doesn't have it.
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Edit dashboard" "link" in the "Secondary dashboard" "table_row"
    Then I should not see "Cohort 1"

    # Clone the primary dashboard
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Clone dashboard" "link" in the "Primary dashboard" "table_row"
    Then I should see "Do you really want to clone dashboard Primary dashboard?"
    # Confirm the clone
    When I press "Continue"
    Then I should see "Dashboard 'Primary dashboard' successfully cloned to 'Primary dashboard copy 1'"

    # Rename the first dashboard.
    When I click on "Edit dashboard" "link" in the "Primary dashboard" "table_row"
    And I set the following fields to these values:
      | Name | Original dashboard |
    And I press "Save changes"
    Then I should see "Dashboard saved"

    # Confirm the cloned dashboard has the audience.
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Edit dashboard" "link" in the "Original dashboard" "table_row"
    Then I should see "Cohort 1"

    # Confirm the original dashboard has the audience.
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Edit dashboard" "link" in the "Secondary dashboard" "table_row"
    Then I should not see "Cohort 1"

    # Check the second doesn't have it.
    When I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Edit dashboard" "link" in the "Primary dashboard copy 1" "table_row"
    Then I should see "Cohort 1"

  @javascript
  Scenario: Cloning a dashboard multiple times creates unique names
    Given I am on a totara site
    And the following totara_dashboards exist:
      | name | locked | published |
      | Primary dashboard | 0 | 1 |
      | Secondary dashboard | 0 | 1 |

    # Clone the primary dashboard 5 times.
    When I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Clone dashboard" "link" in the "Primary dashboard" "table_row"
    And I should see "Do you really want to clone dashboard Primary dashboard?"
    And I press "Continue"
    Then I should see "Dashboard 'Primary dashboard' successfully cloned to 'Primary dashboard copy 1'"

    When I click on "Clone dashboard" "link" in the "Primary dashboard" "table_row"
    And I should see "Do you really want to clone dashboard Primary dashboard?"
    And I press "Continue"
    Then I should see "Dashboard 'Primary dashboard' successfully cloned to 'Primary dashboard copy 2'"

    When I click on "Clone dashboard" "link" in the "Primary dashboard" "table_row"
    And I should see "Do you really want to clone dashboard Primary dashboard?"
    And I press "Continue"
    Then I should see "Dashboard 'Primary dashboard' successfully cloned to 'Primary dashboard copy 3'"

    When I click on "Clone dashboard" "link" in the "Primary dashboard" "table_row"
    And I should see "Do you really want to clone dashboard Primary dashboard?"
    And I press "Continue"
    Then I should see "Dashboard 'Primary dashboard' successfully cloned to 'Primary dashboard copy 4'"

    When I click on "Clone dashboard" "link" in the "Primary dashboard" "table_row"
    And I should see "Do you really want to clone dashboard Primary dashboard?"
    And I press "Continue"
    Then I should see "Dashboard 'Primary dashboard' successfully cloned to 'Primary dashboard copy 5'"

    # Now clone a copy three times
    When I click on "Clone dashboard" "link" in the "Primary dashboard copy 3" "table_row"
    And I should see "Do you really want to clone dashboard Primary dashboard copy 3?"
    And I press "Continue"
    Then I should see "Dashboard 'Primary dashboard copy 3' successfully cloned to 'Primary dashboard copy 3 copy 1'"

    When I click on "Clone dashboard" "link" in the "Primary dashboard copy 3" "table_row"
    And I should see "Do you really want to clone dashboard Primary dashboard copy 3?"
    And I press "Continue"
    Then I should see "Dashboard 'Primary dashboard copy 3' successfully cloned to 'Primary dashboard copy 3 copy 2'"

    When I click on "Clone dashboard" "link" in the "Primary dashboard copy 3" "table_row"
    And I should see "Do you really want to clone dashboard Primary dashboard copy 3?"
    And I press "Continue"
    Then I should see "Dashboard 'Primary dashboard copy 3' successfully cloned to 'Primary dashboard copy 3 copy 3'"
    And I should see "Primary dashboard"
    And I should see "Primary dashboard copy 1"
    And I should see "Primary dashboard copy 2"
    And I should see "Primary dashboard copy 3"
    And I should see "Primary dashboard copy 4"
    And I should see "Primary dashboard copy 5"
    And I should see "Primary dashboard copy 3 copy 1"
    And I should see "Primary dashboard copy 3 copy 2"
    And I should see "Primary dashboard copy 3 copy 3"

    # Rename the third dashboard just to confirm there is no cross interaction.
    When I click on "Edit dashboard" "link" in the "Primary dashboard copy 3" "table_row"
    And I set the following fields to these values:
      | Name | The third dashboard |
    And I press "Save changes"
    And I should see "Dashboard saved"
    Then I should see "Primary dashboard"
    And I should see "Primary dashboard copy 1"
    And I should see "Primary dashboard copy 2"
    And I should see "The third dashboard"
    And I should see "Primary dashboard copy 4"
    And I should see "Primary dashboard copy 5"
    And I should see "Primary dashboard copy 3 copy 1"
    And I should see "Primary dashboard copy 3 copy 2"
    And I should see "Primary dashboard copy 3 copy 3"
