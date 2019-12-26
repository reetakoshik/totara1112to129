@javascript @tool @totara @tool_totara_sync
Feature: Override default settings for an HR Import element

  Scenario: HR Import elements are left to use default settings
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    # Check the value listing Elements using default settings in each settings area.
    Then I should see "None" in the "Files" "fieldset"
    And I should see "None" in the "Notifications" "fieldset"
    And I should see "None" in the "Schedule" "fieldset"

    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I click on "Enable" "link" in the "User" "table_row"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    # Check the value listing Elements using default settings in each settings area.
    Then I should see "User" in the "Files" "fieldset"
    And I should see "User" in the "Notifications" "fieldset"
    And I should see "User" in the "Schedule" "fieldset"

    When I set the following fields to these values:
      | File access              | Upload Files                      |
      | Errors                   | 1                                 |
      | Warnings                 | 1                                 |
      | Email notifications to   | jim@example.com, mary@example.com |
      # The following are in the Schedule field set.
      | Enable                   | 1                                 |
      | schedulegroup[frequency] | Monthly                           |
      | schedulegroup[monthly]   | 2nd                               |
    And I press "Save changes"
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    Then the field "File access" matches value "Default (Upload Files)"
    And "Files directory" "field" should not be visible
    And I should see "Send notifications for: Errors, Warnings"
    And I should see "Email notifications to: jim@example.com, mary@example.com"
    And I should see "Schedule (Server time): Monthly on the 2nd"

    When I set the field "CSV" to "1"
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I click on "configured here" "link"
    And I press "Save changes"
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    Then I should see "Source has configuration" in the "User" "table_row"

    When I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

  Scenario: HR Import elements can override default settings
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    # Check the value listing Elements using default settings in each settings area.
    Then I should see "None" in the "Files" "fieldset"
    And I should see "None" in the "Notifications" "fieldset"
    And I should see "None" in the "Schedule" "fieldset"

    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I click on "Enable" "link" in the "User" "table_row"
    And I click on "Enable" "link" in the "Position" "table_row"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    # Check the value listing Elements using default settings in each settings area.
    Then I should see "User" in the "Files" "fieldset"
    And I should see "Position" in the "Files" "fieldset"
    And I should see "User" in the "Notifications" "fieldset"
    And I should see "Position" in the "Notifications" "fieldset"
    And I should see "User" in the "Schedule" "fieldset"
    And I should see "Position" in the "Schedule" "fieldset"

    When I set the following fields to these values:
      | File access              | Upload Files                      |
      | Errors                   | 1                                 |
      | Warnings                 | 1                                 |
      | Email notifications to   | jim@example.com, mary@example.com |
      # The following are in the Schedule field set.
      | Enable                   | 1                                 |
      | schedulegroup[frequency] | Monthly                           |
      | schedulegroup[monthly]   | 2nd                               |
    And I press "Save changes"
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                      | 1            |
      | File access              | Upload Files |
    # We need the "click on" step for advanced checkboxes so that the js works properly.
    And I click on "Use default settings" "checkbox" in the "Notifications" "fieldset"
    And I click on "Use default settings" "checkbox" in the "Schedule" "fieldset"
    Then I should not see "Send notifications for: Errors, Warnings"
    And I should not see "Email notifications to: jim@example.com, mary@example.com"
    And I should not see "Schedule (Server time): Monthly on the 2nd"

    When I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "Default settings" node in "Site administration > HR Import"
    Then I should not see "User" in the "Files" "fieldset"
    And I should see "Position" in the "Files" "fieldset"
    And I should not see "User" in the "Notifications" "fieldset"
    And I should see "Position" in the "Notifications" "fieldset"
    And I should not see "User" in the "Schedule" "fieldset"
    And I should see "Position" in the "Schedule" "fieldset"

    # The position element has done its bit now. We need to disable it as we need a unique "CSV" filemanager below.
    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I click on "Disable" "link" in the "Position" "table_row"

    And I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I press "Save changes"
    And I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/users.01.csv" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"

    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    Then I should see "Source has configuration" in the "User" "table_row"

    When I press "Run HR Import"
    And I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

  Scenario: HR Import element file settings are hidden or disabled when not relevant
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I click on "Enable" "link" in the "User" "table_row"

    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    Then the field "File access" matches value "Default (Upload Files)"
    And "Files directory" "field" should not be visible

    When I set the following fields to these values:
      | CSV         | 1               |
      | File access | Directory Check |
    Then "Files directory" "field" should be visible
    And the "Files directory" "field" should be enabled

    When I set the following fields to these values:
      | External Database | 1 |
    Then the "File access" "field" should be disabled
    And the "Files directory" "field" should be disabled

    When I set the following fields to these values:
      | CSV         | 1            |
      | File access | Upload Files |
    Then "Files directory" "field" should not be visible

  Scenario: HR Import scheduled tasks are updated via default settings or element-specific settings
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I click on "Enable" "link" in the "User" "table_row"

    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access              | Upload Files |
      # The following are in the Schedule field set.
      | Enable                   | 1            |
      | schedulegroup[frequency] | Monthly      |
      | schedulegroup[monthly]   | 2nd          |
    And I press "Save changes"
    And I navigate to "Scheduled tasks" node in "Site administration > Server"
    And I click on "Edit task schedule: Import HR elements from external sources" "link"
    Then the following fields match these values:
      | Minute      | 0 |
      | Hour        | 0 |
      | Day         | 2 |
      | Month       | * |
      | Day of week | * |
      | Disabled    | 0 |

    When I navigate to "Scheduled tasks" node in "Site administration > Server"
    # The user import task only has its default settings, including being disabled.
    And I click on "Edit task schedule: User HR Import" "link"
    Then the following fields match these values:
      | Minute      | 0 |
      | Hour        | 0 |
      | Day         | * |
      | Month       | * |
      | Day of week | * |
      | Disabled    | 1 |

    When I navigate to "User" node in "Site administration > HR Import > Elements"
    # We must 'click on' the advanced checkbox to properly trigger the js.
    And I click on "Use default settings" "checkbox" in the "Schedule" "fieldset"
    And I set the following fields to these values:
      | CSV                      | 1     |
      | Enable                   | 1     |
      | schedulegroup[frequency] | Daily |
      | schedulegroup[daily]     | 14:00 |
    And I press "Save changes"
    And I navigate to "Scheduled tasks" node in "Site administration > Server"
    And I click on "Edit task schedule: Import HR elements from external sources" "link"
    # Nothing should have changed in the default HR Import task.
    Then the following fields match these values:
      | Minute      | 0 |
      | Hour        | 0 |
      | Day         | 2 |
      | Month       | * |
      | Day of week | * |
      | Disabled    | 0 |

    When I navigate to "Scheduled tasks" node in "Site administration > Server"
    # The user import task is now enabled and set according to the earlier form submission.
    And I click on "Edit task schedule: User HR Import" "link"
    Then the following fields match these values:
      | Minute      | 0  |
      | Hour        | 14 |
      | Day         | *  |
      | Month       | *  |
      | Day of week | *  |
      | Disabled    | 0  |
