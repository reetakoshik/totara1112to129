@totara @totara_form @javascript
Feature: Totara form wizard group tests for default settings
  In order to test the wizard group
  As an admin
  I use the group wizard test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"
    And I select "Basic wizard group default [totara_form\form\testform\group_wizard_default]" from the "Test form" singleselect
    And I should see "Form: Basic wizard group default"
    And I should see "Personal data"

  Scenario: Test progressing through stages on wizard group in Totara forms
    When I set the following Totara form fields to these values:
      | fullname      | Tester One   |
      | preferredname | Behat Tester |
      | gender        | female       |
      | keepalldata   | m            |
    When I click on "Next: Learning activity" "button"
    Then I should see "Learning activity" in the ".tf_wizard_progress_bar_item_current" "css_element"
    When I set the following Totara form fields to these values:
      | phonenumber | 0724233333        |
      | address     | 150 Willis Street |
      | country     | NZ                |
    And I click on "Next: Learning records" "button"
    Then I should see "Learning records" in the ".tf_wizard_progress_bar_item_current" "css_element"
    When I set the following Totara form fields to these values:
      | yesno | 1 |
    When I click on "Next: Other data" "button"
    Then I should see "Other data" in the ".tf_wizard_progress_bar_item_current" "css_element"
    When I set the following Totara form fields to these values:
      | favourite_colour | red |
    And I click on "Next: File upload" "button"
    Then I should see "File upload" in the ".tf_wizard_progress_bar_item_current" "css_element"
    When I click on "Submit me" "button"
    Then I should see "The form has been submit"
    And "fullname" row "Value" column of "form_results" table should contain "Tester One"
    And "preferredname" row "Value" column of "form_results" table should contain "Behat Tester"
    And "gender" row "Value" column of "form_results" table should contain "female"
    And "keepalldata" row "Value" column of "form_results" table should contain "m"
    And "phonenumber" row "Value" column of "form_results" table should contain "0724233333"
    And "address" row "Value" column of "form_results" table should contain "150 Willis Street"
    And "country" row "Value" column of "form_results" table should contain "NZ"
    And "yesno" row "Value" column of "form_results" table should contain "1"
    And "favourite_colour" row "Value" column of "form_results" table should contain "red"
    And "filepicker" row "Value" column of "form_results" table should contain "«»"

  Scenario: Test going back to previous stages on wizard group in Totara forms
    # Proceed two stages, then go back to first stage.
    When I click on "Next: Learning activity" "button"
    And I click on "Next: Learning records" "button"
    And I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage1']" "css_element"
    Then I should see "Personal data" in the ".tf_wizard_progress_bar_item_current" "css_element"
    # Proceed to the end, then go back one stage at a time.
    When I click on "Next: Learning activity" "button"
    And I click on "Next: Learning records" "button"
    And I click on "Next: Other data" "button"
    And I click on "Next: File upload" "button"
    And I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage4']" "css_element"
    Then I should see "Other data" in the ".tf_wizard_progress_bar_item_current" "css_element"
    And I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage3']" "css_element"
    Then I should see "Learning records" in the ".tf_wizard_progress_bar_item_current" "css_element"
    And I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage2']" "css_element"
    Then I should see "Learning activity" in the ".tf_wizard_progress_bar_item_current" "css_element"
    And I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage1']" "css_element"
    Then I should see "Personal data" in the ".tf_wizard_progress_bar_item_current" "css_element"

  Scenario: Test validation error landing on correct stage on wizard group in Totara forms
    # Proceed to the last stage, leaving all fields empty and submit.
    When I click on "Next: Learning activity" "button"
    And I click on "Next: Learning records" "button"
    And I click on "Next: Other data" "button"
    And I click on "Next: File upload" "button"
    And I click on "Submit me" "button"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "Learning activity" in the ".tf_wizard_progress_bar_item_current" "css_element"
    # Fill in first required field, leave everything else empty and submit again.
    When I set the following Totara form fields to these values:
      | phonenumber | 0724233333 |
    And I click on "Next: Learning records" "button"
    And I click on "Next: Other data" "button"
    And I click on "Next: File upload" "button"
    And I click on "Submit me" "button"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "Other data" in the ".tf_wizard_progress_bar_item_current" "css_element"
    # Fill in second required field and submit successfully.
    When I set the following Totara form fields to these values:
      | favourite_colour | red |
    And I click on "Next: File upload" "button"
    And I click on "Submit me" "button"
    Then I should see "The form has been submit"
    And "phonenumber" row "Value" column of "form_results" table should contain "0724233333"
    And "favourite_colour" row "Value" column of "form_results" table should contain "red"

  Scenario: Test cancel button on a stage with required field on wizard group in Totara forms
    # Required fields can potentially be a problem for leaving the form, so go to a stage that has them and click cancel.
    When I click on "Next: Learning activity" "button"
    And I click on "Cancel" "button"
    Then I should see "The form has been cancelled"

  Scenario: Test cancel button on last stage on wizard group in Totara forms
    When I click on "Next: Learning activity" "button"
    And I click on "Next: Learning records" "button"
    And I click on "Next: Other data" "button"
    And I click on "Next: File upload" "button"
    And I click on "Cancel" "button"
    Then I should see "The form has been cancelled"

  Scenario: Test jumping ahead is not possible as a default on wizard group in Totara forms
    When I click on "Next: Learning activity" "button"
    Then ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage1']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage2']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage3']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage4']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage5']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_current[data-jump-to-stage='stage2']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_disabled[data-jump-to-stage='stage3']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_disabled[data-jump-to-stage='stage4']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_disabled[data-jump-to-stage='stage5']" "css_element" should exist
    And I click on "Next: Learning records" "button"
    And I click on "Next: Other data" "button"
    Then ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage1']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage2']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage3']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage4']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage5']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_current[data-jump-to-stage='stage4']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_disabled[data-jump-to-stage='stage5']" "css_element" should exist

