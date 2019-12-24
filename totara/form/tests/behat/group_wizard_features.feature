@totara @totara_form @javascript
Feature: Totara form wizard group tests for non-default settings
  In order to test the wizard group
  As an admin
  I use the group wizard test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"
    And I select "Basic wizard group features [totara_form\form\testform\group_wizard_features]" from the "Test form" singleselect
    And I should see "Form: Basic wizard group features"
    And I should see "Personal data"

  Scenario: Test jumping ahead is possible when activated on wizard group in Totara forms
    When I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage5']" "css_element"
    Then I should see "File upload" in the ".tf_wizard_progress_bar_item_current" "css_element"
    When I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage1']" "css_element"
    Then I should see "Personal data" in the ".tf_wizard_progress_bar_item_current" "css_element"
    When I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage2']" "css_element"
    Then I should see "Learning activity" in the ".tf_wizard_progress_bar_item_current" "css_element"
    # Also mix in a click on a next button.
    When I click on "Next: Learning records" "button"
    Then I should see "Learning records" in the ".tf_wizard_progress_bar_item_current" "css_element"
    When I click on ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage4']" "css_element"
    Then I should see "Other data" in the ".tf_wizard_progress_bar_item_current" "css_element"

  Scenario: Test CSS is as expected when jumping ahead is activated on wizard group in Totara forms
    When I click on "Next: Learning activity" "button"
    Then ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage1']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage2']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage3']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage4']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage5']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_current[data-jump-to-stage='stage2']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_disabled[data-jump-to-stage='stage3']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_disabled[data-jump-to-stage='stage4']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_disabled[data-jump-to-stage='stage5']" "css_element" should not exist
    And I click on "Next: Learning records" "button"
    And I click on "Next: Other data" "button"
    Then ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage1']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage2']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage3']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage4']" "css_element" should not exist
    And ".tf_wizard_progress_bar_item_jumpable[data-jump-to-stage='stage5']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_current[data-jump-to-stage='stage4']" "css_element" should exist
    And ".tf_wizard_progress_bar_item_disabled[data-jump-to-stage='stage5']" "css_element" should not exist

