@totara @totara_reportbuilder @javascript
Feature: Check reports for subsystems are not visible in reportbuilder when disabled.

  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: Verify Record of Learning appears in reportbuilder if enabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable Record of Learning" to "Show"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Record of Learning"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Record of Learning: Certifications" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Competencies" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Courses" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Evidence" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Objectives" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Previous Certifications" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Previous Course Completions" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Programs" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Recurring Programs" in the "#manage_embedded_reports" "css_element"
    And I should see "Record of Learning: Programs Completion History " in the "#manage_embedded_reports" "css_element"
    And I set the field "report-name" to "My Current Courses"
    And I press "id_submitgroupstandard_addfilter"
    And I should see "My Current Courses" in the "#manage_embedded_reports" "css_element"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should contain "Record of Learning: Certifications"
    And the "Source" select box should contain "Record of Learning: Competencies"
    And the "Source" select box should contain "Record of Learning: Courses"
    And the "Source" select box should contain "Record of Learning: Evidence"
    And the "Source" select box should contain "Record of Learning: Objectives"
    And the "Source" select box should contain "Record of Learning: Previous Certifications"
    And the "Source" select box should contain "Record of Learning: Previous Course Completions"
    And the "Source" select box should contain "Record of Learning: Programs"
    And the "Source" select box should contain "Record of Learning: Recurring Programs"

  Scenario: Verify Record of Learning does not appear in reportbuilder if disabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Enable Record of Learning" to "Disable"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Record of Learning"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "Record of Learning: Certifications" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Competencies" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Courses" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Evidence" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Objectives" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Previous Certifications" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Previous Course Completions" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Programs" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Recurring Programs" in the ".rb-display-table-container" "css_element"
    And I should not see "Record of Learning: Programs Completion History " in the ".rb-display-table-container" "css_element"
    And I set the field "report-name" to "My Current Courses"
    And I press "id_submitgroupstandard_addfilter"
    And I should not see "My Current Courses" in the ".rb-display-table-container" "css_element"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should not contain "Record of Learning: Certifications"
    And the "Source" select box should not contain "Record of Learning: Competencies"
    And the "Source" select box should not contain "Record of Learning: Courses"
    And the "Source" select box should not contain "Record of Learning: Evidence"
    And the "Source" select box should not contain "Record of Learning: Objectives"
    And the "Source" select box should not contain "Record of Learning: Previous Certifications"
    And the "Source" select box should not contain "Record of Learning: Previous Course Completions"
    And the "Source" select box should not contain "Record of Learning: Programs"
    And the "Source" select box should not contain "Record of Learning: Recurring Programs"

  Scenario: Verify Program Completion Editor reports appear in reportbuilder if enabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "program completion editor" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Certification Membership"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Certification Membership" in the "#manage_embedded_reports" "css_element"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should contain "Certification Membership"

  Scenario: Verify Program Completion Editor reports appear in reportbuilder if disabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "program completion editor" to "0"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Certification Membership"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "Certification Membership"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should contain "Certification Membership"

  Scenario: Verify Totara Connect server reports appear in reportbuilder if enabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Totara Connect server" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Totara Connect clients"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Totara Connect clients" in the "#manage_embedded_reports" "css_element"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should contain "Totara Connect clients"

  Scenario: Verify Totara Connect server reports appear in reportbuilder if disabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "Totara Connect server" to "0"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Totara Connect clients"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "Totara Connect servers clients"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should not contain "Totara Connect clients"

  Scenario: Verify audience-based visibility reports appear in reportbuilder if enabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "audience-based visibility" to "1"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Audience: Visible Learning"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Audience: Visible Learning" in the "#manage_embedded_reports" "css_element"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should contain "Audience: Visible Learning"

  Scenario: Verify audience-based visibility reports appear in reportbuilder if disabled
    When I navigate to "Advanced features" node in "Site administration > System information"
    And I set the field "audience-based visibility" to "0"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Audience: Visible Learning"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "Audience: Visible Learning"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should not contain "Audience: Visible Learning"

  Scenario: Verify Totara Connect client reports appear in reportbuilder if enabled
    When I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Totara Connect client" "table_row"

    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Totara Connect servers"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Totara Connect servers" in the "#manage_embedded_reports" "css_element"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should contain "Totara Connect servers"

  Scenario: Verify Totara Connect client reports appear in reportbuilder if disabled
    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Totara Connect servers"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "Totara Connect servers"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Then the "Source" select box should not contain "Totara Connect servers"
