@core @core_webservice @javascript
Feature: Basic web service access
  In order to use webservices
  As a special web server user
  I need to configure and access each type of supported webservices

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | idnumber | username | firstname | lastname | email                |
      | u4  | student  | Sam1      | Student1 | student1@example.com |

  Scenario: Enable, configure and access web services
    Given I log in as "admin"

    # Enable services
    And I set the following administration settings values:
      | enablewebservices | 1 |
    And I navigate to "Manage protocols" node in "Site administration > Plugins > Web services"
    And I "Enable" the "REST protocol" web service protocol
    And I "Enable" the "SOAP protocol" web service protocol
    And I "Enable" the "XML-RPC protocol" web service protocol

    # This WS stuff is crazy, this should never allow admin to authenticate, anyway.
    And I navigate to "Manage authentication" node in "Site administration > Plugins > Authentication"
    And I click on "Enable" "link" in the "Web services authentication" "table_row"

    # Configure web service
    And I navigate to "External services" node in "Site administration > Plugins > Web services"
    And I follow "Add"
    And I set the following fields to these values:
      | Name                  | testws |
      | Enabled               | 1      |
      | Authorised users only | 0      |
    And I press "Add service"
    And I follow "Add functions"
    And I wait "2" seconds
    And I set the following fields to these values:
      | Name | core_user_get_users_by_field |
    # Note: Autocomplete fields are a bloody mess, try some hacks to make it work here. Also the fieldset and button are the same here.
    And I press key "13" in the field "Name"
    And I press "id_submitbutton"

    # Perform REST test
    When I navigate to "Web service test client" node in "Site administration > Development"
    And I set the following fields to these values:
      | Authentication method | simple                      |
      | Protocol              | REST protocol               |
      | Function              | core_user_get_users_by_field |
    And I press "Select"
    And I set the following fields to these values:
      | wsusername | admin           |
      | wspassword | admin           |
      | field      | idnumber        |
      | values[0]  | u4              |
    And I press "Execute"
    Then I should see "student1@example.com"
    And I should see "Sam1"

# We cannot perform SOAP test in behat because there is no way to add the BEHAT cookie to the initial wsdl request.
#    When I navigate to "Web service test client" node in "Site administration > Development"
#    And I set the following fields to these values:
#      | Authentication method | simple                      |
#      | Protocol              | SOAP protocol               |
#      | Function              | core_user_get_users_by_field |
#    And I press "Select"
#    And I set the following fields to these values:
#      | wsusername | admin           |
#      | wspassword | admin           |
#      | field      | idnumber        |
#      | values[0]  | u4              |
#    And I press "Execute"
#    Then I should see "student1@example.com"
#    And I should see "Sam1"

    # Perform XML-RPC test
    When I navigate to "Web service test client" node in "Site administration > Development"
    And I set the following fields to these values:
      | Authentication method | simple                      |
      | Protocol              | XML-RPC protocol            |
      | Function              | core_user_get_users_by_field |
    And I press "Select"
    And I set the following fields to these values:
      | wsusername | admin           |
      | wspassword | admin           |
      | field      | idnumber        |
      | values[0]  | u4              |
    And I press "Execute"
    Then I should see "student1@example.com"
    And I should see "Sam1"
