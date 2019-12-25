@totara @totara_form
Feature: Totara form tel element tests
  In order to test the tel element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic tel elements in Totara forms
    When I select "Basic tel element [totara_form\form\testform\element_tel]" from the "Test form" singleselect
    Then I should see "Form: Basic tel element"

    When I set the following Totara form fields to these values:
      | Basic tel | 0800 My Apple |
      | Required basic tel | 0800-692-753 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«0800 My Apple»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"
    And "tel_with_current_data" row "Value" column of "form_results" table should contain "«202-555-0167»"
    And "tel_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "tel_frozen_with_current_data" row "Value" column of "form_results" table should contain "«+1-202-555-0149»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_tel»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | 2016 |
      | Required basic tel | 0800-692-753 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | 2016.16 |
      | Required basic tel | 2016.15.14 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«2016.16»"
    And "tel_required" row "Value" column of "form_results" table should contain "«2016.15.14»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | -70 |
      | Required basic tel | False |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«-70»"
    And "tel_required" row "Value" column of "form_results" table should contain "«False»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Required basic tel | 0800-692-753 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | True |
      | Required basic tel | False |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«True»"
    And "tel_required" row "Value" column of "form_results" table should contain "«False»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | \r |
      | Required basic tel | \n |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«\r»"
    And "tel_required" row "Value" column of "form_results" table should contain "«\n»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | <p></p> |
      | Required basic tel | <br /> |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«<p></p>»"
    And "tel_required" row "Value" column of "form_results" table should contain "«<br />»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | <br> |
      | Required basic tel | <p></p> |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«<br>»"
    And "tel_required" row "Value" column of "form_results" table should contain "«<p></p>»"

  @javascript
  Scenario: Test required tel elements in Totara forms with JavaScript enabled
    When I select "Basic tel element [totara_form\form\testform\element_tel]" from the "Test form" singleselect
    Then I should see "Form: Basic tel element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic tel | 0800 My Apple |
      | Required basic tel | 0800-692-753 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«0800 My Apple»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | |
      | Required basic tel | 0800-692-753 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic tel | 0800 My Apple |
      | Required basic tel | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic tel element"

    When I set the following Totara form fields to these values:
      | Basic tel | 1 |
      | Required basic tel | 0 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«1»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0»"

  Scenario: Test required tel elements in Totara forms with JavaScript disabled
    When I select "Basic tel element [totara_form\form\testform\element_tel]" from the "Test form" singleselect
    Then I should see "Form: Basic tel element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic tel | 0800 My Apple |
      | Required basic tel | 0800-692-753 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«0800 My Apple»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | |
      | Required basic tel | 0800-692-753 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"

    When I press "Reset"
    Then I should see "Form: Basic tel element"
    When I set the following Totara form fields to these values:
      | Basic tel | 0800 My Apple |
      | Required basic tel | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic tel element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic tel | 0800-692-753 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«0800 My Apple»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"

  @javascript
  Scenario: Test hidden if on tel elements in Totara forms
    When I select "Basic tel element [totara_form\form\testform\element_tel]" from the "Test form" singleselect
    Then I should see "Form: Basic tel element"
    And I click on "Expand all" "link"
    And I should see "Visible when test is empty"
    And I should not see "Visible when test is not empty"
    And I should not see "Visible when test equals '202-555-0191'"
    And I should see "Visible when test is not equal to '202-555-0191'"
    And I should see "Visible when test is not filled"
    And I should not see "Visible when test is filled"
    And I should see "Visible when required tel is empty"
    And I should not see "Visible when required tel is not empty"

    When I set the following Totara form fields to these values:
      | Visible when test is empty | 202-555-0149 DDN 713 |
      | Visible when test is not equal to '202-555-0191' | 202-555-0149#713 |
      | Visible when test is not filled           | DDN:713 |
      | Visible when required tel is empty | *123 |
    When I set the following Totara form fields to these values:
      | Hidden if reference | Test |
    Then I should see "Form: Basic tel element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should not see "Visible when test equals '202-555-0191'"
    And I should see "Visible when test is not equal to '202-555-0191'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should see "Visible when required tel is empty"
    And I should not see "Visible when required tel is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | 202-555-0191 |
      | Required basic tel | Test  |
    Then I should see "Form: Basic tel element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should see "Visible when test equals '202-555-0191'"
    And I should not see "Visible when test is not equal to '202-555-0191'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should not see "Visible when required tel is empty"
    And I should see "Visible when required tel is not empty"

    When I set the following Totara form fields to these values:
      | Basic tel | 0800 My Apple |
      | Required basic tel | 0800-692-753 |
      | tel with current data | (089) / 636-48018 |
      | Visible when test is not empty | 19-49-89-636-48018 |
      | Visible when test equals '202-555-0191' | +1-541-754-3010 |
      | Visible when test is filled | +6568897445 |
      | Visible when required tel is not empty | FAX:(202) 5550149 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "tel_basic" row "Value" column of "form_results" table should contain "«0800 My Apple»"
    And "tel_required" row "Value" column of "form_results" table should contain "«0800-692-753»"
    And "tel_with_current_data" row "Value" column of "form_results" table should contain "«(089) / 636-48018»"
    And "tel_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "tel_frozen_with_current_data" row "Value" column of "form_results" table should contain "«+1-202-555-0149»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«202-555-0191»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«19-49-89-636-48018»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«202-555-0149 DDN 713»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«202-555-0149#713»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«+1-541-754-3010»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«DDN:713»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«+6568897445»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«FAX:(202) 5550149»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«*123»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_tel»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
