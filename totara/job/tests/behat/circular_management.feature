@totara @totara_job @javascript
Feature: Verify circular line management cannot be created.
  Background:
    Given I am on a totara site
    When the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | learner1  | Bob1      | Learner1  | learner1@example.com  |
      | manager1  | Dave1     | Manager1  | manager1@example.com  |
      | director1 | Frank1    | Director1 | director1@example.com |
      | ceo1      | Charlie1  | Ceo1      | ceo1@example.com |
    And the following job assignments exist:
      | idnumber     | fullname    | user      | manager   | managerjaidnumber   |
      | coejaid      | ceojob      | ceo1      |           |                     |
      | directorjaid | directorjob | director1 |           |                     |
      | managerjaid  | managerjob  | manager1  | director1 | directorjaid        |
      | learnerjaid  | learnerjob  | learner1  | manager1  | managerjaid         |

  Scenario: Check line management is defined correctly.
    Given I log in as "admin"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Bob1 Learner1"
    And I follow "learnerjob"
    Then I should see "Dave1 Manager1" in the "#managertitle" "css_element"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Dave1 Manager1"
    And I follow "managerjob"
    Then I should see "Frank1 Director1" in the "#managertitle" "css_element"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Frank1 Director1"
    Then I should see "directorjob"

    # Unfortunately, there's currently no way of checking that the
    # manager is not set for director1.

  Scenario: Check it's not possible to create circular line management with an immediate subordinate.

    Given I log in as "admin"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Frank1 Director1"
    And I follow "directorjob"
    And I press "Choose manager"
    Then I should see "Dave1 Manager1" in the "Choose manager" "totaradialogue"

    When I click on "Dave1 Manager1 (manager1@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "managerjob" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    Then I should see "Dave1 Manager1" in the "#managertitle" "css_element"

    # You should not be able to save a job assignment when the manager is an immediate subordinate.\
    When I press "Update job assignment"
    Then I should see "The problems indicated below must be fixed before your changes can be saved."
    And I should see "Selecting this job assignment will create a circular management structure. Please select another." in the ".error" "css_element"

    When I follow "Delete"
    And I press "Update job assignment"
    Then I should see "Job assignment saved"

  Scenario: Check it's not possible to create circular line management with an non-immediate subordinate.

    Given I log in as "admin"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Frank1 Director1"
    And I follow "directorjob"
    And I press "Choose manager"
    Then I should see "Bob1 Learner1" in the "Choose manager" "totaradialogue"

    When I click on "Bob1 Learner1 (learner1@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "learnerjob" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    Then I should see "Bob1 Learner1" in the "#managertitle" "css_element"

    # You should not be able to save a job assignment when the manager is a non-immediate subordinate.
    When I press "Update job assignment"
    Then I should see "The problems indicated below must be fixed before your changes can be saved."
    And I should see "Selecting this job assignment will create a circular management structure. Please select another." in the ".error" "css_element"

    When I follow "Delete"
    And I press "Update job assignment"
    Then I should see "Job assignment saved"

  Scenario: Check it's not possible to create circular line management in the middle of the structure.

    Given I log in as "admin"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Charlie1 Ceo1"
    And I follow "ceojob"
    And I press "Choose manager"
    Then I should see "Dave1 Manager1" in the "Choose manager" "totaradialogue"

    When I click on "Dave1 Manager1 (manager1@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "managerjob" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    Then I should see "Dave1 Manager1" in the "#managertitle" "css_element"

    When I press "Update job assignment"
    Then I should see "Job assignment saved"

    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Frank1 Director1"
    And I follow "directorjob"
    And I press "Choose manager"
    Then I should see "Charlie1 Ceo1" in the "Choose manager" "totaradialogue"

    When I click on "Charlie1 Ceo1 (ceo1@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "ceojob" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    Then I should see "Charlie1 Ceo1" in the "#managertitle" "css_element"

    When I press "Update job assignment"
    Then I should see "The problems indicated below must be fixed before your changes can be saved."
    And I should see "Selecting this job assignment will create a circular management structure. Please select another." in the ".error" "css_element"

    # Remove the erroneous manager from the CEO.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Charlie1 Ceo1"
    And I follow "ceojob"
    And I follow "Delete"
    And I press "Update job assignment"
    Then I should see "Job assignment saved"

    # Add the correct manager and update the job assignment.
    When I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I follow "Frank1 Director1"
    And I follow "directorjob"
    And I press "Choose manager"
    Then I should see "Charlie1 Ceo1" in the "Choose manager" "totaradialogue"

    When I click on "Charlie1 Ceo1 (ceo1@example.com)" "link" in the "Choose manager" "totaradialogue"
    And I click on "ceojob" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    Then I should see "Charlie1 Ceo1" in the "#managertitle" "css_element"

    When I press "Update job assignment"
    Then I should see "Job assignment saved"
