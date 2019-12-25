@totara @totara_form
Feature: Totara form url element tests
  In order to test the url element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic url elements in Totara forms
    When I select "Basic url element [totara_form\form\testform\element_url]" from the "Test form" singleselect
    Then I should see "Form: Basic url element"

    When I set the following Totara form fields to these values:
      | Basic url | https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo |
      | Required basic url | http://example.totaralms.com/case-studies |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"
    And "url_with_current_data" row "Value" column of "form_results" table should contain "«http://www.totaralms.com»"
    And "url_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "url_frozen_with_current_data" row "Value" column of "form_results" table should contain "«https://www.totaralms.com»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_url»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I set the following Totara form fields to these values:
      | Basic url | https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo |
      | Required basic url | http://example.totaralms.com/case-studies |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I set the following Totara form fields to these values:
      | Basic url | https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo |
      | Required basic url | ftp://www.totaralms.com/blog |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo»"
    And "url_required" row "Value" column of "form_results" table should contain "«ftp://www.totaralms.com/blog»"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I set the following Totara form fields to these values:
      | Basic url | http://test.totaralms.com/#one |
      | Required basic url | http://test.totaralms.com/?one=two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«http://test.totaralms.com/#one»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://test.totaralms.com/?one=two»"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I set the following Totara form fields to these values:
      | Required basic url | http://example.totaralms.com/case-studies |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"

  @javascript
  Scenario: Test invalid values in url elements in Totara forms with JavaScript enabled
    Given I select "Basic url element [totara_form\form\testform\element_url]" from the "Test form" singleselect
    Then I should see "Form: Basic url element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic url | \r |
      | Required basic url | http://test.totaralms.com/#valid |
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Basic url | magic |
      | Required basic url | http://test.totaralms.com/#valid |
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Basic url | ! |
      | Required basic url | http://test.totaralms.com/#valid |
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Basic url | - |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Basic url | http:// |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Basic url | www |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Basic url | www.totaralms.com |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Basic url | #test |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Basic url | ?test |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And a new page should not have loaded since I started watching

  Scenario: Test invalid values in url elements in Totara forms with JavaScript disabled
    Given I select "Basic url element [totara_form\form\testform\element_url]" from the "Test form" singleselect
    Then I should see "Form: Basic url element"
    And I set the following Totara form fields to these values:
      | Basic url | \r |
      | Required basic url | http://test.totaralms.com/#valid |
    When I press "Save changes"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Invalid URL address format"

    # This is valid with JavaScript off.
    When I set the following Totara form fields to these values:
      | Basic url | magic |
      | Required basic url | http://test.totaralms.com/#valid |
    When I press "Save changes"
    Then I should see "The form has been submit"
    And I should not see "Form could not be submitted, validation failed"
    And I should not see "Invalid URL address format"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I set the following Totara form fields to these values:
      | Basic url | ! |
      | Required basic url | http://test.totaralms.com/#valid |
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Invalid URL address format"

    When I set the following Totara form fields to these values:
      | Basic url | - |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Invalid URL address format"

    When I set the following Totara form fields to these values:
      | Basic url | www |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And I should not see "Form could not be submitted, validation failed"
    And I should not see "Invalid URL address format"

  @javascript
  Scenario: Test required url elements in Totara forms with JavaScript enabled
    When I select "Basic url element [totara_form\form\testform\element_url]" from the "Test form" singleselect
    Then I should see "Form: Basic url element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic url | https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo |
      | Required basic url | http://example.totaralms.com/case-studies |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I set the following Totara form fields to these values:
      | Basic url | |
      | Required basic url | http://example.totaralms.com/case-studies |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic url | https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo |
      | Required basic url | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic url element"

    When I set the following Totara form fields to these values:
      | Basic url | http://www.totaralms.com#alt |
      | Required basic url | http://test.totaralms.com/#valid |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«http://www.totaralms.com#alt»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://test.totaralms.com/#valid»"

  Scenario: Test required url elements in Totara forms with JavaScript disabled
    When I select "Basic url element [totara_form\form\testform\element_url]" from the "Test form" singleselect
    Then I should see "Form: Basic url element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic url | https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo |
      | Required basic url | http://example.totaralms.com/case-studies |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I set the following Totara form fields to these values:
      | Basic url | |
      | Required basic url | http://example.totaralms.com/case-studies |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"

    When I press "Reset"
    Then I should see "Form: Basic url element"
    When I set the following Totara form fields to these values:
      | Basic url | https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo |
      | Required basic url | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic url element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic url | http://example.totaralms.com/case-studies |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"

  @javascript
  Scenario: Test hidden if on url elements in Totara forms
    When I select "Basic url element [totara_form\form\testform\element_url]" from the "Test form" singleselect
    Then I should see "Form: Basic url element"
    And I click on "Expand all" "link"
    And I should see "Visible when test is empty"
    And I should not see "Visible when test is not empty"
    And I should not see "Visible when test equals 'https://totaralms.com'"
    And I should see "Visible when test is not equal to 'https://totaralms.com'"
    And I should see "Visible when test is not filled"
    And I should not see "Visible when test is filled"
    And I should see "Visible when required url is empty"
    And I should not see "Visible when required url is not empty"

    When I set the following Totara form fields to these values:
      | Visible when test is empty | http://example.totaralms.com |
      | Visible when test is not equal to 'https://totaralms.com' | https://www.totaralms.com |
      | Visible when test is not filled           | https://www.totaralms.com/#test |
      | Visible when required url is empty | https://www.totaralms.com/#123456789 |
    When I set the following Totara form fields to these values:
      | Hidden if reference | http://test.totaralms.com/#frank |
    Then I should see "Form: Basic url element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should not see "Visible when test equals 'https://totaralms.com'"
    And I should see "Visible when test is not equal to 'https://totaralms.com'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should see "Visible when required url is empty"
    And I should not see "Visible when required url is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | https://totaralms.com |
      | Required basic url | http://test.totaralms.com/#frank  |
    Then I should see "Form: Basic url element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should see "Visible when test equals 'https://totaralms.com'"
    And I should not see "Visible when test is not equal to 'https://totaralms.com'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should not see "Visible when required url is empty"
    And I should see "Visible when required url is not empty"

    When I set the following Totara form fields to these values:
      | Basic url | https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo |
      | Required basic url | http://example.totaralms.com/case-studies |
      | url with current data | http://totaralms.com |
      | Visible when test is not empty | http://totaralms.com |
      | Visible when test equals 'https://totaralms.com' | https://www.totaralms.com/ |
      | Visible when test is filled | http://test.totaralms.com/#test |
      | Visible when required url is not empty | http://test.totaralms.com/?test=blah |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "url_basic" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/about-us/contact-us/form?type=Request%20a%20Demo»"
    And "url_required" row "Value" column of "form_results" table should contain "«http://example.totaralms.com/case-studies»"
    And "url_with_current_data" row "Value" column of "form_results" table should contain "«http://totaralms.com»"
    And "url_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "url_frozen_with_current_data" row "Value" column of "form_results" table should contain "«https://www.totaralms.com»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«https://totaralms.com»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«http://totaralms.com»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«http://example.totaralms.com»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«https://www.totaralms.com»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/#test»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«http://test.totaralms.com/#test»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«http://test.totaralms.com/?test=blah»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«https://www.totaralms.com/#123456789»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_url»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
