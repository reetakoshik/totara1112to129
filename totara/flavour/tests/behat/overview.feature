@totara @totara_flavour
Feature: Flavours overview and activation
  In order to use use flavours
  As an admin
  I need to be able to see flavour overview

  Scenario: Verify only enterprise flavour is displayed by default
    Given I log in as "admin"
    When I navigate to "Feature overview" node in "Site administration"
    Then I should see "Enterprise" in the "table.flavour-overview-table" "css_element"
    And I should not see "Testing" in the "table.flavour-overview-table" "css_element"

  @javascript
  Scenario: Verify detail expand and collapse works
    Given I log in as "admin"
    When I navigate to "Feature overview" node in "Site administration"
    Then I should not see "Global report restrictions allows rules to be applied to a report"
    And I should not see "Open Badges are digital certificates that are awarded to learners"

    # Expand
    When I click on "Report Restrictions" "text"
    Then I should see "Global report restrictions allows rules to be applied to a report"
    And I should not see "Open Badges are digital certificates that are awarded to learners"

    # collapse
    When I click on "Report Restrictions" "text"
    Then I should not see "Global report restrictions allows rules to be applied to a report"
    And I should not see "Open Badges are digital certificates that are awarded to learners"