@totara @auth_connect
Feature: Test that Totara Connect auth plugin may be enabled
  In order to use Totara Connect client
  I need to be able to enable the auth plugin and create server request

  Scenario: Totara Connect client may be enabled
    Given I log in as "admin"
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Totara Connect client" "table_row"
    And I navigate to "Servers" node in "Site administration > Plugins > Authentication > Totara Connect client"

    When I press "Connect to new server"
    Then I should see "Client url" in the ".alert-info" "css_element"
    And I should see "Client setup secret" in the ".alert-info" "css_element"
