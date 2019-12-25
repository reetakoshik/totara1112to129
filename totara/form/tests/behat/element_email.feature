@totara @totara_form
Feature: Totara form email element tests
  In order to test the email element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic email elements in Totara forms
    When I select "Basic email element [totara_form\form\testform\element_email]" from the "Test form" singleselect
    Then I should see "Form: Basic email element"
    And I should see the following Totara form fields having these values:
      | Basic email                    |                     |
      | email with current data        | contact@example.com |
      | Frozen email with current data | sales@example.com   |
    And I should see the "Empty frozen email" Totara form field is frozen
    And I should see the "Frozen email with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Basic email                    | learner@example.com |
      | Required basic email           | trainer@example.com |
    And I should see the following Totara form fields having these values:
      | Basic email                    | learner@example.com |
      | Required basic email           | trainer@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«learner@example.com»"
    And "email_required" row "Value" column of "form_results" table should contain "«trainer@example.com»"
    And "email_with_current_data" row "Value" column of "form_results" table should contain "«contact@example.com»"
    And "email_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "email_frozen_with_current_data" row "Value" column of "form_results" table should contain "«sales@example.com»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_email»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic email element"
    When I set the following Totara form fields to these values:
      | Basic email          | samh@example.com    |
      | Required basic email | trainer@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«samh@example.com»"
    And "email_required" row "Value" column of "form_results" table should contain "«trainer@example.com»"

  Scenario: Test basic required email elements in Totara forms without JavaScript disabled
    When I select "Basic email element [totara_form\form\testform\element_email]" from the "Test form" singleselect
    Then I should see "Form: Basic email element"
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test basic required email elements in Totara forms with JavaScript enabled
    When I select "Basic email element [totara_form\form\testform\element_email]" from the "Test form" singleselect
    Then I should see "Form: Basic email element"
    When I start watching to see if a new page loads
    And I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "Form could not be submitted, validation failed"
    And I should not see "The form has been submit"
    And I should see "Form: Basic email element"

  @javascript
  Scenario: Test required email elements in Totara forms with JavaScript enabled
    When I select "Basic email element [totara_form\form\testform\element_email]" from the "Test form" singleselect
    Then I should see "Form: Basic email element"
    And I should see the following Totara form fields having these values:
      | Basic email                    |                     |
      | email with current data        | contact@example.com |
      | Frozen email with current data | sales@example.com   |
    And I should see the "Empty frozen email" Totara form field is frozen
    And I should see the "Frozen email with current data" Totara form field is frozen
    And I should see "There are required fields in this form marked"

    When I set the following Totara form fields to these values:
      | Basic email                    | learner@example.com |
      | Required basic email           | trainer@example.com |
    And I should see the following Totara form fields having these values:
      | Basic email                    | learner@example.com |
      | Required basic email           | trainer@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«learner@example.com»"
    And "email_required" row "Value" column of "form_results" table should contain "«trainer@example.com»"

    When I press "Reset"
    Then I should see "Form: Basic email element"
    When I set the following Totara form fields to these values:
      | Basic email | |
      | Required basic email | trainer@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«»"
    And "email_required" row "Value" column of "form_results" table should contain "«trainer@example.com»"

    When I press "Reset"
    Then I should see "Form: Basic email element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic email | learner@example.com |
      | Required basic email | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic email element"

    When I set the following Totara form fields to these values:
      | Basic email | one@example.com |
      | Required basic email | zero@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«one@example.com»"
    And "email_required" row "Value" column of "form_results" table should contain "«zero@example.com»"

  Scenario: Test required email elements in Totara forms with JavaScript disabled
    When I select "Basic email element [totara_form\form\testform\element_email]" from the "Test form" singleselect
    Then I should see "Form: Basic email element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic email | learner@example.com |
      | Required basic email | trainer@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«learner@example.com»"
    And "email_required" row "Value" column of "form_results" table should contain "«trainer@example.com»"

    When I press "Reset"
    Then I should see "Form: Basic email element"
    When I set the following Totara form fields to these values:
      | Basic email | |
      | Required basic email | trainer@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«»"
    And "email_required" row "Value" column of "form_results" table should contain "«trainer@example.com»"

    When I press "Reset"
    Then I should see "Form: Basic email element"
    When I set the following Totara form fields to these values:
      | Basic email | learner@example.com |
      | Required basic email | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic email element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic email | trainer@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«learner@example.com»"
    And "email_required" row "Value" column of "form_results" table should contain "«trainer@example.com»"

  @javascript
  Scenario: Test hidden if on email elements in Totara forms
    When I select "Basic email element [totara_form\form\testform\element_email]" from the "Test form" singleselect
    Then I should see "Form: Basic email element"
    And I click on "Expand all" "link"
    And I should see "Visible when test is empty"
    And I should not see "Visible when test is not empty"
    And I should not see "Visible when test equals 'behat@example.com'"
    And I should see "Visible when test is not equal to 'behat@example.com'"
    And I should see "Visible when test is not filled"
    And I should not see "Visible when test is filled"
    And I should see "Visible when required email is empty"
    And I should not see "Visible when required email is not empty"

    When I set the following Totara form fields to these values:
      | Visible when test is empty | alpha@example.com |
      | Visible when test is not equal to 'behat@example.com' | bravo@example.com |
      | Visible when test is not filled           | charlie@example.com |
      | Visible when required email is empty | delta@example.com |
    When I set the following Totara form fields to these values:
      | Hidden if reference | test@example.com |
    Then I should see "Form: Basic email element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should not see "Visible when test equals 'behat@example.com'"
    And I should see "Visible when test is not equal to 'behat@example.com'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should see "Visible when required email is empty"
    And I should not see "Visible when required email is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | behat@example.com |
      | Required basic email | test@example.com  |
    Then I should see "Form: Basic email element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should see "Visible when test equals 'behat@example.com'"
    And I should not see "Visible when test is not equal to 'behat@example.com'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should not see "Visible when required email is empty"
    And I should see "Visible when required email is not empty"

    When I set the following Totara form fields to these values:
      | Basic email | learner@example.com |
      | Required basic email | trainer@example.com |
      | email with current data | staffmanager@example.com |
      | Visible when test is not empty | sitemanager@example.com |
      | Visible when test equals 'behat@example.com' | support@example.com |
      | Visible when test is filled | marketting@example.com |
      | Visible when required email is not empty | admin@example.com |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "email_basic" row "Value" column of "form_results" table should contain "«learner@example.com»"
    And "email_required" row "Value" column of "form_results" table should contain "«trainer@example.com»"
    And "email_with_current_data" row "Value" column of "form_results" table should contain "«staffmanager@example.com»"
    And "email_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "email_frozen_with_current_data" row "Value" column of "form_results" table should contain "«sales@example.com»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«behat@example.com»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«sitemanager@example.com»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«alpha@example.com»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«bravo@example.com»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«support@example.com»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«charlie@example.com»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«marketting@example.com»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«admin@example.com»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«delta@example.com»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_email»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
