@totara @tool_totara_sync @javascript
Feature: Verify CSV notifications are displayed correctly.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    Then I should see "Settings saved"

  Scenario: Verify Job Assignmnets CSV notifications.

    Given I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Job assignment" HR Import element

    # Check the empty fields are ignored notification.
    When I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                         |
      | Empty string behaviour in CSV | Empty strings are ignored |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Job assignment"
    Then I should see "The use of empty fields in your CSV file will leave the field's current value in your site."

    # Check the empty fields are deleted notification.
    When I navigate to "Job assignment" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                         |
      | Empty string behaviour in CSV | Empty strings are ignored |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Job assignment"
    Then I should see "The use of empty fields in your CSV file will leave the field's current value in your site."

    # Check the selected fields are displayed in the file structure information.
    When I set the following fields to these values:
      | Full name    | 1 |
      | Start date   | 1 |
      | End date     | 1 |
      | Organisation | 1 |
      | Position     | 1 |
      | Manager      | 1 |
      | Appraiser    | 1 |
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "The current config requires a CSV file with the following structure:"
    And I should see "\"idnumber\",\"useridnumber\",\"timemodified\",\"deleted\",\"fullname\",\"startdate\","
    And I should see "\"enddate\",\"orgidnumber\",\"posidnumber\",\"manageridnumber\",\"appraiseridnumber\""

    When I set the following fields to these values:
      | idnumber          | field1  |
      | useridnumber      | field2  |
      | timemodified      | field3  |
      | deleted           | field4  |
      | fullname          | field5  |
      | startdate         | field6  |
      | enddate           | field7  |
      | orgidnumber       | field8  |
      | posidnumber       | field9  |
      | manageridnumber   | field10 |
      | appraiseridnumber | field11 |
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "The current config requires a CSV file with the following structure:"
    And I should see "\"field1\",\"field2\",\"field3\",\"field4\",\"field5\",\"field6\",\"field7\",\"field8\","
    And I should see "\"field9\",\"field10\",\"field11\""

  Scenario: Verify Organisation CSV notifications.

    Given I navigate to "Manage types" node in "Site administration > Organisations"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name              | Organisation Type 1 |
      | Organisation type ID number | OT1                 |
    When I press "Save changes"
    Then I should see "The organisation type \"Organisation Type 1\" has been created"

    When I follow "Organisation Type 1"
    And I set the field "Create a new custom field" to "Text input"
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name  | Text Input 1 |
      | Short name | textinput1   |
    And I press "Save changes"
    Then I should see "Text Input 1" in the "Text input" "table_row"

    # Check the empty fields are ignored notification.
    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Organisation" HR Import element
    And I navigate to "Organisation" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                         |
      | Empty string behaviour in CSV | Empty strings are ignored |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Organisation"
    Then I should see "The use of empty fields in your CSV file will leave the field's current value in your site."

    # Check the empty fields are deleted notification.
    When I navigate to "Organisation" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                         |
      | Empty string behaviour in CSV | Empty strings are ignored |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Organisation"
    Then I should see "The use of empty fields in your CSV file will leave the field's current value in your site."

    # Check the selected fields are displayed in the file structure information.
    When I set the following fields to these values:
      | Shortname    | 1 |
      | Description  | 1 |
      | Parent       | 1 |
      | Type         | 1 |
      | Text Input 1 | 1 |
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "The current config requires a CSV file with the following structure:"
    And I should see "\"idnumber\",\"fullname\",\"shortname\",\"deleted\",\"description\",\"frameworkidnumber\","
    And I should see "\"parentidnumber\",\"typeidnumber\",\"timemodified\",\"customfield_textinput1\""

    When I set the following fields to these values:
      | idnumber          | field1  |
      | fullname          | field2  |
      | shortname         | field3  |
      | deleted           | field4  |
      | description       | field5  |
      | frameworkidnumber | field6  |
      | parentidnumber    | field7  |
      | typeidnumber      | field8  |
      | timemodified      | field9  |
      | textinput1        | field10 |
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "The current config requires a CSV file with the following structure:"
    And I should see "\"field1\",\"field2\",\"field3\",\"field4\",\"field5\",\"field6\",\"field7\",\"field8\","
    And I should see "\"field9\",\"field10\""

  Scenario: Verify Position CSV notifications.

    Given I navigate to "Manage types" node in "Site administration > Positions"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name          | Position Type 1 |
      | Position type ID number | PT1             |
    When I press "Save changes"
    Then I should see "The position type \"Position Type 1\" has been created"

    When I follow "Position Type 1"
    And I set the field "Create a new custom field" to "Text input"
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name  | Text Input 1 |
      | Short name | textinput1   |
    And I press "Save changes"
    Then I should see "Text Input 1" in the "Text input" "table_row"

    # Check the empty fields are ignored notification.
    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "Position" HR Import element
    And I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                         |
      | Empty string behaviour in CSV | Empty strings are ignored |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Position"
    Then I should see "The use of empty fields in your CSV file will leave the field's current value in your site."

    # Check the empty fields are deleted notification.
    When I navigate to "Position" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                         |
      | Empty string behaviour in CSV | Empty strings are ignored |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > Position"
    Then I should see "The use of empty fields in your CSV file will leave the field's current value in your site."

    # Check the selected fields are displayed in the file structure information.
    When I set the following fields to these values:
      | Shortname    | 1 |
      | Description  | 1 |
      | Parent       | 1 |
      | Type         | 1 |
      | Text Input 1 | 1 |
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "The current config requires a CSV file with the following structure:"
    And I should see "\"idnumber\",\"fullname\",\"shortname\",\"deleted\",\"description\",\"frameworkidnumber\","
    And I should see "\"parentidnumber\",\"typeidnumber\",\"timemodified\",\"customfield_textinput1\""

    When I set the following fields to these values:
      | idnumber          | field1  |
      | fullname          | field2  |
      | shortname         | field3  |
      | deleted           | field4  |
      | description       | field5  |
      | frameworkidnumber | field6  |
      | parentidnumber    | field7  |
      | typeidnumber      | field8  |
      | timemodified      | field9  |
      | textinput1        | field10 |
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "The current config requires a CSV file with the following structure:"
    And I should see "\"field1\",\"field2\",\"field3\",\"field4\",\"field5\",\"field6\",\"field7\",\"field8\","
    And I should see "\"field9\",\"field10\""

  Scenario: Verify User CSV notifications.

    Given I navigate to "User profile fields" node in "Site administration > Users"
    And I set the field "Create a new profile field" to "Text input"
    And I should see "Creating a new 'Text input' profile field"
    And I set the following fields to these values:
      | Name       | Text Input 1 |
      | Short name | textinput1   |
    And I press "Save changes"
    Then I should see "Edit" in the "Text Input 1" "table_row"

    # Check the empty fields are ignored notification.
    When I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                         |
      | Empty string behaviour in CSV | Empty strings are ignored |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should see "The use of empty fields in your CSV file will leave the field's current value in your site."

    # Check the empty fields are deleted notification.
    When I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV                           | 1                         |
      | Empty string behaviour in CSV | Empty strings are ignored |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    Then I should see "The use of empty fields in your CSV file will leave the field's current value in your site."

    # Check the selected fields are displayed in the file structure information.
    When I set the following fields to these values:
      | City         | 1 |
      | Country      | 1 |
      | Password     | 1 |
      | Text Input 1 | 1 |
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "The current config requires a CSV file with the following structure:"
    And I should see "\"idnumber\",\"timemodified\",\"username\",\"deleted\",\"firstname\",\"lastname\",\"email\","
    And I should see "\"city\",\"country\",\"password\",\"customfield_textinput1\""

    When I set the following fields to these values:
      | idnumber           | field1  |
      | timemodified       | field2  |
      | username           | field3  |
      | deleted            | field4  |
      | firstname          | field5  |
      | lastname           | field6  |
      | fieldmapping_email | field7  |
      | city               | field8  |
      | country            | field9  |
      | password           | field10 |
      | textinput1         | field11 |
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "The current config requires a CSV file with the following structure:"
    And I should see "\"field1\",\"field2\",\"field3\",\"field4\",\"field5\",\"field6\",\"field7\",\"field8\","
    And I should see "\"field9\",\"field10\",\"field11\""
