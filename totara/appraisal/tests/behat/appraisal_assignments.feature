@totara @totara_appraisal @totara_appraisal_assignments @javascript
Feature: Test reported learners in appraisal assignments
  As an admin
  I need to see consistent reporting on assignments in appraisals


  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | learner1  | Learner   | One      | one@example.com       |
      | learner2  | Learner   | Two      | two@example.com       |
      | learner3  | Learner   | Three    | three@example.com     |
      | learner4  | Learner   | Four     | four@example.com      |
      | learner5  | Learner   | Five     | five@example.com      |
      | manager1  | Manager   | One      | mone@example.com      |
      | manager2  | Manager   | Two      | mtwo@example.com      |

    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname    | idnumber |
      | Position FW | PFW001   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | pos_framework | fullname  | idnumber |
      | PFW001        | Position1 | pos1     |

    And the following job assignments exist:
      | user       | idnumber  | fullname  | shortname | position |
      | learner1   | job1      | Job 1     | job1      | pos1     |
      | learner2   | job1      | Job 1     | job1      | pos1     |
      | learner3   | job1      | Job 1     | job1      | pos1     |

    And the following "cohorts" exist:
      | name       | idnumber | description         | contextlevel | reference | cohorttype |
      | Dynamic 1  | dyn1     | Dynamic Audience 1  | System       | 0         | 2          |
      | Set 2      | set2     | Set Audience 2      | System       | 0         | 1          |
    And the following "cohort members" exist:
      | user     | cohort |
      | manager1 | set2   |
      | manager2 | set2   |

    And I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Dynamic 1"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "Positions"
    And I follow "Position1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I press "Approve changes"
    And I log out

    And the following "appraisals" exist in "totara_appraisal" plugin:
      | name        |
      | Appraisal1  |
    And the following "stages" exist in "totara_appraisal" plugin:
      | appraisal   | name       | timedue                 |
      | Appraisal1  | App1_Stage | 1 January 2020 23:59:59 |
    And the following "pages" exist in "totara_appraisal" plugin:
      | appraisal   | stage      | name      |
      | Appraisal1  | App1_Stage | App1_Page |
    And the following "questions" exist in "totara_appraisal" plugin:
      | appraisal   | stage      | page      | name     | type          | default | ExtraInfo                          |
      | Appraisal1  | App1_Stage | App1_Page | App1-Q1  | ratingnumeric | 2       | Range:1-10,Display:slider          |

    And the following "assignments" exist in "totara_appraisal" plugin:
      | appraisal   | type     | id   |
      | Appraisal1  | audience | dyn1 |
      | Appraisal1  | audience | set2 |

  Scenario: Displayed assignments in Draft appraisals
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Appraisal1" "link" should exist
    And I should see "5 (0 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "3" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Manager One"
    And I should see "Manager Two"

    # Add users to the dynamic audience
    When the following job assignments exist:
      | user       | idnumber  | fullname  | shortname | position |
      | learner5   | job1      | Job 1     | job1      | pos1     |
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "6 (0 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "4" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Learner Five"
    And I should see "Manager One"
    And I should see "Manager Two"

    # Remove users from the dynamic audience
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner Two"
    And I click the delete icon for the "Job 1" job assignment
    And I click on "Yes, delete" "button"
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    # And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"

    Then I should see "5 (0 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "3" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Three"
    And I should see "Learner Five"
    And I should see "Manager One"
    And I should see "Manager Two"

    # Reports not available for Draft appraisal
    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "No appraisals are active."
    And I should see "No appraisals are inactive."
    And I log out

  Scenario: Displayed assignments in Active appraisals with none completed
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Appraisal1" "link" should exist

    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I click on "Activate" "button"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "3" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "5" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

  Scenario: Displayed assignments in Active appraisals with some completed
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Appraisal1" "link" should exist

    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I click on "Activate" "button"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"

    # Learner Two completes the appraisal
    When I log out
    And I log in as "learner2"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "5 (1 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "3" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "4" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

  Scenario: Displayed assignments in Closed appraisals with none completed
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Appraisal1" "link" should exist

    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I click on "Activate" "button"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Close" "link" in the "Appraisal1" "table_row"
    And I click on "sendalert" "checkbox"
    And I click on "Close appraisal" "button"
    Then I should see "Closed" in the "Appraisal1" "table_row"
    And I should see "0 (0 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should see "No data available in table"
    And I should not see "Learner One"
    And I should not see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "5" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"

  Scenario: Displayed assignments in Closed appraisals with some completed
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Appraisal1" "link" should exist

    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I click on "Activate" "button"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"

    # Learner Two completes the appraisal
    When I log out
    And I log in as "learner2"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "5 (1 completed)" in the "Appraisal1" "table_row"
    And "Close" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Close" "link" in the "Appraisal1" "table_row"
    And I click on "sendalert" "checkbox"
    And I click on "Close appraisal" "button"
    Then I should see "Closed" in the "Appraisal1" "table_row"
    And I should see "1 (1 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should see "Learner Two"
    And I should not see "Learner One"
    And I should not see "Learner Three"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "4" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"

  Scenario: Displayed assignments in Active appraisals when members of the audience changes
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Appraisal1" "link" should exist

    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I click on "Activate" "button"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"

    # Learner One and Two completes the appraisal
    When I log out
    And I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    And I log in as "learner2"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    # Add Learner Five to audience
    When the following job assignments exist:
      | user       | idnumber  | fullname  | shortname | position |
      | learner5   | job1      | Job 1     | job1      | pos1     |
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "6 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "4" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Learner Five"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "4" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

    # Remove user from audience who hasn't completed the appraisal
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner Three"
    And I click the delete icon for the "Job 1" job assignment
    And I click on "Yes, delete" "button"

    # Before cron is run
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "6 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "4" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Learner Five"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "4" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

    # After cron has ran
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "5 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "3" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should see "Learner Five"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Learner Five" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Learner Five" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

    # Re-assign user to the audience
    When the following job assignments exist:
      | user       | idnumber  | fullname  | shortname | position |
      | learner3   | job1      | Job 1     | job1      | pos1     |
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "6 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "4" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Learner Five"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "4" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

    # Remove user from audience who completed the appraisal
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I click the delete icon for the "Job 1" job assignment
    And I click on "Yes, delete" "button"
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "5 (1 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "3" in the "Dynamic 1" "table_row"
    And I should not see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Learner Five"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "4" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

    # Re-assign user to the audience
    When the following job assignments exist:
      | user       | idnumber  | fullname  | shortname | position |
      | learner1   | job1      | Job 1     | job1      | pos1     |
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "6 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "4" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Learner Five"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "4" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Learner Five" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

  Scenario: Displayed assignments in Closed appraisals when members of the audience changes after closure
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Appraisal1" "link" should exist

    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I click on "Activate" "button"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"

    # Learner One and Two completes the appraisal
    When I log out
    And I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    And I log in as "learner2"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    # Close appraisal
    When I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"
    When I click on "Close" "link" in the "Appraisal1" "table_row"
    And I click on "sendalert" "checkbox"
    And I click on "Close appraisal" "button"
    Then I should see "Closed" in the "Appraisal1" "table_row"
    And I should see "2 (2 completed)" in the "Appraisal1" "table_row"

    # Add Learner Five to audience
    When the following job assignments exist:
      | user       | idnumber  | fullname  | shortname | position |
      | learner5   | job1      | Job 1     | job1      | pos1     |
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "2 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Learner Five"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should not see "Learner Five"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And I should not see "Learner Five"

    # Remove original user from audience who hasn't completed the appraisal
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner Three"
    And I click the delete icon for the "Job 1" job assignment
    And I click on "Yes, delete" "button"
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "2 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should not see "Learner Five"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And I should not see "Learner Five"

    # Remove user from audience who completed the appraisal
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I click the delete icon for the "Job 1" job assignment
    And I click on "Yes, delete" "button"
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "2 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should not see "Learner Five"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And I should not see "Learner Five"

  Scenario: Displayed assignments in Closed appraisals when members of the audience changes
    Given I log in as "admin"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Appraisal1" "link" should exist

    When I click on "Activate" "link" in the "Appraisal1" "table_row"
    And I click on "Activate" "button"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"

    # Learner One and Two completes the appraisal
    When I log out
    And I log in as "learner1"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    And I log in as "learner2"
    And I click on "All Appraisals" in the totara menu
    And I click on "Appraisal1" "link" in the "Appraisal1" "table_row"
    And I press "Start"
    And I click on "Complete stage" "button"
    Then I should see "Completed" in the "div.appraisal-stageinfo" "css_element"
    And I log out

    # Change the audience before closure to get some "Assignment Cancelled" users
    # Remove user from audience who hasn't completed the appraisal
    When I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner Three"
    And I click the delete icon for the "Job 1" job assignment
    And I click on "Yes, delete" "button"
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "4 (2 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "2" in the "Dynamic 1" "table_row"
    And I should see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

    # Remove user from audience who completed the appraisal
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I click the delete icon for the "Job 1" job assignment
    And I click on "Yes, delete" "button"
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "3 (1 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should see "2" in the "Set 2" "table_row"
    And I should see "1" in the "Dynamic 1" "table_row"
    And I should not see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should see "Manager One"
    And I should see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    And I should see "0" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Overdue']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='On target']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "2" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Cancelled']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "On Target"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "On Target"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "On Target"

    # Now Close the appraisal
    # Changes after closure should have no effect
    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then "Close" "link" should exist in the "Appraisal1" "table_row"
    When I click on "Close" "link" in the "Appraisal1" "table_row"
    And I click on "sendalert" "checkbox"
    And I click on "Close appraisal" "button"
    Then I should see "Closed" in the "Appraisal1" "table_row"
    And I should see "1 (1 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should not see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should not see "Learner Five"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And I should not see "Learner Five"

    # Add Learner Five to audience
    When the following job assignments exist:
      | user       | idnumber  | fullname  | shortname | position |
      | learner5   | job1      | Job 1     | job1      | pos1     |
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "1 (1 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should not see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should not see "Learner Five"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And I should not see "Learner Five"

    # Remove original user from audience who hasn't completed the appraisal
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I click on "Set 2" "link"
    And I click on "Edit members" "link"
    And I click on "Manager Two (mtwo@example.com)" "option" in the "#removeselect" "css_element"
    And I click on "remove" "button"
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"

    When I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "1 (1 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should not see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should not see "Learner Five"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And I should not see "Learner Five"

    # Remove user from audience who completed the appraisal
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner Two"
    And I click the delete icon for the "Job 1" job assignment
    And I click on "Yes, delete" "button"
    And I run the scheduled task "\totara_cohort\task\cleanup_task"
    And I run the scheduled task "\totara_cohort\task\update_cohort_task"
    And I run the scheduled task "\totara_appraisal\task\update_learner_assignments_task"
    And I navigate to "Manage appraisals" node in "Site administration > Appraisals"
    Then I should see "1 (1 completed)" in the "Appraisal1" "table_row"

    When I follow "Appraisal1"
    And I switch to "Assignments" tab
    Then I should not see "Assigned Groups"
    And I should not see "Assigned Learners"
    And I should see "This appraisal is closed, no changes can be made to learner assignments"
    And I should see "Learners who completed the appraisal"
    And I should not see "Learner One"
    And I should see "Learner Two"
    And I should not see "Learner Three"
    And I should not see "Manager One"
    And I should not see "Manager Two"

    When I navigate to "Reports" node in "Site administration > Appraisals"
    Then I should see "Closed" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Status']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "1" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Complete']/preceding-sibling::th)+1]" "xpath_element"
    And I should see "3" in the "//table[contains(@class,'generaltable')]/tbody/tr/td[count(//table[contains(@class,'generaltable')]/thead/tr/th[.='Incomplete']/preceding-sibling::th)+1]" "xpath_element"
    And "Status report" "link" should exist in the "Appraisal1" "table_row"
    And "Detail report" "link" should exist in the "Appraisal1" "table_row"

    When I click on "Status report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_status" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_status" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_status" table should contain "Incomplete"
    And I should not see "Learner Five"
    And I should see "Reports" in the ".breadcrumb-nav" "css_element"

    When I click on "Reports" "link" in the ".breadcrumb-nav" "css_element"
    And I click on "Detail report" "link" in the "Appraisal1" "table_row"
    Then "Learner One" row "Status" column of "appraisal_detail" table should contain "Assignment Cancelled"
    And "Learner Two" row "Status" column of "appraisal_detail" table should contain "Complete"
    And "Learner Three" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager One" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And "Manager Two" row "Status" column of "appraisal_detail" table should contain "Incomplete"
    And I should not see "Learner Five"
