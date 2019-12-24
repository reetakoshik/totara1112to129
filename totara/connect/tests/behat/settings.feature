@totara @totara_connect
Feature: Test that Totara Connect may be enabled
  In order to use Totara Connect server
  I need to be able to enable it in advanced features

  @javascript
  Scenario: Totara Connect server may be enabled
    Given I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Totara Connect server" to "1"
    And I press "Save changes"

    When I navigate to "Settings" node in "Site administration > Users > Accounts > Totara Connect server"
    Then I should see "Sync user passwords"

    When I navigate to "Client systems" node in "Site administration > Users > Accounts > Totara Connect server"
     And I press "Add client"
    Then I should see "Name"
     And I should see "Client URL"
     And I should see "Client setup secret"
     And I should see "Restrict to audience"

