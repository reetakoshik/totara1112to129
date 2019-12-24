@totara @totara_core
Feature: Test scenario skip step
  In order to skip Behat steps with known failures
  The skip step needs to work correctly

  Scenario: Test skip step
    Given I am on a totara site
    And I change window size to "medium"

    # The previous steps should be executed until this point and the test should
    # be marked as "skipped" NOT "failed".
    Given I skip the scenario until issue "TL-SOME-REFERENCE" lands

    # These steps should be skipped even if they cause errors.
    And I click on "Find Learning" in the totara menu
    Then I should see "This step WILL FAIL"