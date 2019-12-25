@javascript @totara @totara_customfield
Feature: Administrators can add a custom URL field to complete during program creation
  In order for the custom field to appear during program creation
  As admin
  I need to select the URL custom field and add the relevant settings

  Background:

    Given I am on a totara site
    When I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    And I follow "Programs / Certifications"
    Then I should see "Create a new custom field"

    When I set the field "Create a new custom field" to "URL"
    Then I should see "Editing custom field: URL"


  Scenario: Check a duplicate program URL custom field cannot be created within.

    When I set the following fields to these values:
      | Full name            | Custom URL Field 1    |
      | Short name           | url1                  |
      | Default URL          | http://www.google.com |
      | Default text         | Google                |
      | Open in a new window | 1                     |
    And I press "Save changes"
    Then I should see "Custom URL Field 1"

    When I set the field "Create a new custom field" to "URL"
    Then I should see "Editing custom field: URL"

    When I set the following fields to these values:
      | Full name  | Custom URL Field 2 |
      | Short name | url1               |
    And I press "Save changes"
    Then I should see "This short name is already in use"
    And I log out


  Scenario: Check a program cannot be created when a required URL custom field is empty.

    When I set the following fields to these values:
      | Full name              | Custom URL Field 1 |
      | Short name             | url1               |
      | This field is required | Yes                |
    And I press "Save changes"
    Then I should see "Custom URL Field 1"

    When I navigate to "Manage programs" node in "Site administration > Courses"
    And I press "Add a new program"
    Then I should see "Create new program"

    When I set the following fields to these values:
      | Full name  | Program 1 |
      | Short name | program1  |
    And I press "Save changes"
    Then I should see "You must supply a value here." in the "#fgroup_id_customfield_url1_group" "css_element"

    When I set the following fields to these values:
      | customfield_url1[url] | https://www.totaralms.com  |
    And I press "Save changes"
    Then I should see "Program 1"
    And I log out


  Scenario: Check a hidden URL custom field is not shown in the program form.

    When I set the following fields to these values:
      | Full name                   | Custom URL Field 1 |
      | Short name                  | url1               |
      | Hidden on the settings page | Yes                |
    And I press "Save changes"
    Then I should see "Custom URL Field 1"

    When I navigate to "Manage programs" node in "Site administration > Courses"
    And I press "Add a new program"
    Then I should see "Create new program"

    When I expand all fieldsets
    Then I should not see "Custom URL Field 1"

    When I set the following fields to these values:
      | Full name  | Program 1 |
      | Short name | program1  |
    And I press "Save changes"
    Then I should see "Program 1"
    And I log out


  Scenario: Check program custom URL fields are validated correctly when the custom field is created.

    # Check that an invalid URL is rejected/
    When I set the following fields to these values:
      | Full name         | Custom URL Field 1 |
      | Short name        | url1               |
      | Default URL       | thisisnotavalidurl |
    And I press "Save changes"
    Then I should see "The URL needs to start with http://, https:// or /"

    # Check an http URL is accepted as valid.
    When I set the following fields to these values:
      | Default URL | http://thisisiavalidurl.com |
    And I press "Save changes"
    Then I should see "Custom URL Field 1"

    # Check that a https URl is accepted as valid.
    When I click on "Edit" "link" in the "Custom URL Field 1" "table_row"
    And I set the following fields to these values:
      | Default URL | https://validsecureurl.org |
    And I press "Save changes"
    Then I should see "Custom URL Field 1"

    # Check that a local URl is accepted as valid.
    When I click on "Edit" "link" in the "Custom URL Field 1" "table_row"
    And I set the following fields to these values:
      | Default URL | /my |
    And I press "Save changes"
    Then I should see "Custom URL Field 1"
    And I log out


  Scenario: Check multiple URL custom fields are validated and work correctly in the program form.

    When I set the following fields to these values:
      | Full name            | Custom URL Field 1    |
      | Short name           | url1                  |
      | Default URL          | http://www.google.com |
      | Default text         | Google                |
      | Open in a new window | 1                     |
    And I press "Save changes"
    Then I should see "Custom URL Field 1"

    When I set the field "Create a new custom field" to "URL"
    Then I should see "Editing custom field: URL"

    When I set the following fields to these values:
      | Full name  | Custom URL Field 2  |
      | Short name | url2                |
    And I press "Save changes"
    Then I should see "Custom URL Field 2"

    When I set the field "Create a new custom field" to "URL"
    Then I should see "Editing custom field: URL"

    When I set the following fields to these values:
      | Full name  | Custom URL Field 3 |
      | Short name | url3               |
    And I press "Save changes"
    Then I should see "Custom URL Field 3"

    When I navigate to "Manage programs" node in "Site administration > Courses"
    And I press "Add a new program"
    Then I should see "Create new program"

    # Check that the default field values are present.
    When I expand all fieldsets
    Then I should see "Custom URL Field 1"
    And I should see "Custom URL Field 2"
    And I should see "Custom URL Field 3"
    # Check the first field has the default values.
    And the field "customfield_url1[url]" matches value "http://www.google.com"
    And the field "customfield_url1[text]" matches value "Google"
    And the field "customfield_url1[target]" matches value "1"

    When I set the following fields to these values:
      | Full name             | Program 1          |
      | Short name            | program1           |
      | customfield_url2[url] | thisisnotavalidurl |
    And I press "Save changes"
    Then I should see "The URL needs to start with http://, https:// or /" in the "#fgroup_id_customfield_url2_group" "css_element"

    When I set the following fields to these values:
      | customfield_url2[url]    | https://www.totaralms.com |
      | customfield_url2[text]   | Totara LMS                |
      | customfield_url2[target] | 1                         |
      | customfield_url3[url]    | /my                       |
      | customfield_url3[text]   | My Learning               |
      | customfield_url3[target] | 0                         |
    And I press "Save changes"
    Then I should see "Program 1"

    # Check the field have been set correctly
    When I follow "Details"
    Then the field "customfield_url2[url]" matches value "https://www.totaralms.com"
    And the field "customfield_url2[text]" matches value "Totara LMS"
    And the field "customfield_url2[target]" matches value "1"
    And the field "customfield_url3[url]" matches value "/my"
    And the field "customfield_url3[text]" matches value "My Learning"
    And the field "customfield_url3[target]" matches value "0"
    And I log out
