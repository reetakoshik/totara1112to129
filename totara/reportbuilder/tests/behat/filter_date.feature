@totara @totara_reportbuilder @javascript
Feature: Use the reportbuilder date filter
  To filter report data
  by date
  I need to use date filter

  Scenario: Reportbuilder date filter validation
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Test user report"
    And I set the field "Source" to "User"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "User Last Login" from the "newstandardfilter" singleselect
    And I press "Save changes"
    And I follow "View This Report"

    When I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to "1"
    And I set the field "user-lastlogindaysafterchkbox" to "0"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "1"
    And the field "user-lastlogindaysbefore" matches value "1"
    And the field "user-lastlogindaysafterchkbox" matches value "0"

    When I set the field "user-lastlogindaysbeforechkbox" to "0"
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "0"
    And the field "user-lastlogindaysafterchkbox" matches value "1"
    And the field "user-lastlogindaysafter" matches value "1"

    When I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to "2"
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to "3"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "1"
    And the field "user-lastlogindaysbefore" matches value "2"
    And the field "user-lastlogindaysafterchkbox" matches value "1"
    And the field "user-lastlogindaysafter" matches value "3"

    When I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to "-2"
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to "3"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "0"
    And the field "user-lastlogindaysafterchkbox" matches value "1"
    And the field "user-lastlogindaysafter" matches value "3"

    When I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to "2"
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to "-3"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "1"
    And the field "user-lastlogindaysbefore" matches value "2"
    And the field "user-lastlogindaysafterchkbox" matches value "0"

    When I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to "0"
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to "0"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "0"
    And the field "user-lastlogindaysafterchkbox" matches value "0"

    When I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to ""
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to ""
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "0"
    And the field "user-lastlogindaysafterchkbox" matches value "0"

    When I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to "aa"
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to "bb"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "0"
    And the field "user-lastlogindaysafterchkbox" matches value "0"

    When I set the field "user-lastlogindaysbeforechkbox" to "0"
    And I set the field "user-lastlogindaysafterchkbox" to "0"
    And I set the field "user-lastlogin_sck" to "1"
    And I set the field "user-lastlogin_eck" to "1"
    And I set the field "user-lastlogindaysbeforechkbox" to "1"
    And I set the field "user-lastlogindaysbefore" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysbeforechkbox" matches value "1"
    And the field "user-lastlogindaysbefore" matches value "1"
    And the field "user-lastlogin_sck" matches value "0"
    And the field "user-lastlogin_eck" matches value "0"

    When I set the field "user-lastlogindaysbeforechkbox" to "0"
    And I set the field "user-lastlogindaysafterchkbox" to "0"
    And I set the field "user-lastlogin_sck" to "1"
    And I set the field "user-lastlogin_eck" to "1"
    And I set the field "user-lastlogindaysafterchkbox" to "1"
    And I set the field "user-lastlogindaysafter" to "1"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the field "user-lastlogindaysafterchkbox" matches value "1"
    And the field "user-lastlogindaysafter" matches value "1"
    And the field "user-lastlogin_sck" matches value "0"
    And the field "user-lastlogin_eck" matches value "0"

  Scenario: After date criteria does not include the same date for course report
    Given I am on a totara site
    # All startdate timestamp is in UTC
    # C17 Europe/London: 17 Jan 2017 at 00:00, America/Los_Angeles: 16 Jan 2017 at 16:00 Pacific/Auckland: 17 Jan 2017 at 13:00
    # C18 Europe/London: 18 Jan 2017 at 08:00, America/Los_Angeles: 18 Jan 2017 at 00:00 Pacific/Auckland: 18 Jan 2017 at 21:00
    # C19 Europe/London: 18 Jan 2017 at 11:00, America/Los_Angeles: 18 Jan 2017 at 03:00 Pacific/Auckland: 19 Jan 2017 at 00:00
    And the following "courses" exist:
      | fullname  | shortname | startdate  |
      | Course 17 | C17       | 1484611200 |
      | Course 18 | C18       | 1484726400 |
      | Course 19 | C19       | 1484737200 |
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I click on "Courses" in the totara menu
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I select "Course Start Date" from the "newstandardfilter" singleselect
    And I press "Add"
    And I press "Save changes"

    # Check "is after 16" from London
    When I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I set the following fields to these values:
      | Timezone | Europe/London |
    And I press "Update profile"
    And I click on "Courses" in the totara menu
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 16      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Course 17"
    And I should see "Course 18"
    And I should see "Course 19"

    # Check "is after 17" from London
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 17      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 17"
    And I should see "Course 18"
    And I should see "Course 19"

    # Check "is after 18" from London
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 18      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 17"
    And I should not see "Course 18"
    And I should not see "Course 19"

    # Check "is after 16" from Los Angeles
    When I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I set the following fields to these values:
      | Timezone | America/Los_Angeles |
    And I press "Update profile"
    And I click on "Courses" in the totara menu
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 16      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 17"
    And I should see "Course 18"
    And I should see "Course 19"

    # Check "is after 17" from Los Angeles
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 17      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 17"
    And I should see "Course 18"
    And I should see "Course 19"

    # Check "is after 18" from Los Angeles
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 18      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 17"
    And I should not see "Course 18"
    And I should not see "Course 19"

    # Check "is after 16" from Auckland
    When I follow "Profile" in the user menu
    And I follow "Edit profile"
    And I set the following fields to these values:
      | Timezone | Pacific/Auckland |
    And I press "Update profile"
    And I click on "Courses" in the totara menu
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 16      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Course 17"
    And I should see "Course 18"
    And I should see "Course 19"

    # Check "is after 17" from Auckland
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 17      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 17"
    And I should see "Course 18"
    And I should see "Course 19"

    # Check "is after 18" from Auckland
    And I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 18      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2017    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 17"
    And I should not see "Course 18"
    And I should see "Course 19"

  Scenario: Report builder date filter validation
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I click on "Courses" in the totara menu
    And I click on "Edit this report" "link_or_button"
    And I switch to "Filters" tab
    And I set the field "newsidebarfilter" to "Course Start Date"
    And I press "Save changes"
    And I follow "View This Report"
    When I set the following fields to these values:
      | course-startdatedaysbeforechkbox | 1     |
      | course-startdatedaysbefore       | 12345 |
    Then I should see "Maximum of 4 characters"
    When I set the field "course-startdatedaysbefore" to "1234"
    Then I should not see "Maximum of 4 characters"

  Scenario: Report builder date filter validation for range of dates
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And I click on "Courses" in the totara menu
    And I click on "Edit this report" "link_or_button"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Course Start Date"
    And I press "Save changes"
    And I follow "View This Report"
    When I set the following fields to these values:
      | course-startdate_sck        | 1       |
      | course-startdate_sdt[day]   | 16      |
      | course-startdate_sdt[month] | January |
      | course-startdate_sdt[year]  | 2020    |
      | course-startdate_eck        | 1       |
      | course-startdate_edt[day]   | 15      |
      | course-startdate_edt[month] | January |
      | course-startdate_edt[year]  | 2020    |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Please enter a valid date or date range"
    When I set the field "course-startdate_edt[day]" to "16"
    And  I set the field "course-startdate_sdt[day]" to "15"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Please enter a valid date or date range"
