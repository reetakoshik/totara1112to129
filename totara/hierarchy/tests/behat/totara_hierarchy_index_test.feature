@totara @totara_hierarchy @totara_customfield
Feature: Hierarchy index page sql splitter and joiner
  The index page needs to work with more than 60 custom fields defined.

  @javascript
  Scenario: Check that it doesn't break with 61 custom fields
    Given I am on a totara site
    And the following "organisation" frameworks exist:
      | fullname      | idnumber | description           |
      | orgframework1 | FW001    | Framework description |

    Then I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I press "Add a new type"
    And I set the following fields to these values:
      | fullname | orgtype1 |
    And I press "Save changes"
    And I click on "orgtype1" "link"


    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput1fullname |
      | shortname | textinput1         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput2fullname |
      | shortname | textinput2         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput3fullname |
      | shortname | textinput3         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput4fullname |
      | shortname | textinput4         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput5fullname |
      | shortname | textinput5         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput6fullname |
      | shortname | textinput6         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput7fullname |
      | shortname | textinput7         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput8fullname |
      | shortname | textinput8         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput9fullname |
      | shortname | textinput9         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput10fullname |
      | shortname | textinput10         |
    And I press "Save changes"


    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput11fullname |
      | shortname | textinput11         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput12fullname |
      | shortname | textinput12         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput13fullname |
      | shortname | textinput13         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput14fullname |
      | shortname | textinput14         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput15fullname |
      | shortname | textinput15         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput16fullname |
      | shortname | textinput16         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput17fullname |
      | shortname | textinput17         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput18fullname |
      | shortname | textinput18         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput19fullname |
      | shortname | textinput19         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput20fullname |
      | shortname | textinput20         |
    And I press "Save changes"


    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput21fullname |
      | shortname | textinput21         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput22fullname |
      | shortname | textinput22         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput23fullname |
      | shortname | textinput23         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput24fullname |
      | shortname | textinput24         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput25fullname |
      | shortname | textinput25         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput26fullname |
      | shortname | textinput26         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput27fullname |
      | shortname | textinput27         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput28fullname |
      | shortname | textinput28         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput29fullname |
      | shortname | textinput29         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput30fullname |
      | shortname | textinput30         |
    And I press "Save changes"


    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput31fullname |
      | shortname | textinput31         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput32fullname |
      | shortname | textinput32         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput33fullname |
      | shortname | textinput33         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput34fullname |
      | shortname | textinput34         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput35fullname |
      | shortname | textinput35         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput36fullname |
      | shortname | textinput36         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput37fullname |
      | shortname | textinput37         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput38fullname |
      | shortname | textinput38         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput39fullname |
      | shortname | textinput39         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput40fullname |
      | shortname | textinput40         |
    And I press "Save changes"



    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput41fullname |
      | shortname | textinput41         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput42fullname |
      | shortname | textinput42         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput43fullname |
      | shortname | textinput43         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput44fullname |
      | shortname | textinput44         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput45fullname |
      | shortname | textinput45         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput46fullname |
      | shortname | textinput46         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput47fullname |
      | shortname | textinput47         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput48fullname |
      | shortname | textinput48         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput49fullname |
      | shortname | textinput49         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput50fullname |
      | shortname | textinput50         |
    And I press "Save changes"


    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput51fullname |
      | shortname | textinput51         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput52fullname |
      | shortname | textinput52         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput53fullname |
      | shortname | textinput53         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput54fullname |
      | shortname | textinput54         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput55fullname |
      | shortname | textinput55         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput56fullname |
      | shortname | textinput56         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput57fullname |
      | shortname | textinput57         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput58fullname |
      | shortname | textinput58         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput59fullname |
      | shortname | textinput59         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput60fullname |
      | shortname | textinput60         |
    And I press "Save changes"


    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname  | textinput61fullname |
      | shortname | textinput61         |
    And I press "Save changes"


    Then I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I click on "orgframework1" "link"
    And I press "Add new organisation"
    And I set the following fields to these values:
      | fullname | org1     |
      | typeid   | orgtype1 |
    And I press "Save changes"
    And I click on "Edit" "link"
    And I set the following fields to these values:
      | textinput1fullname  | value1  |
      | textinput2fullname  | value2  |
      | textinput3fullname  | value3  |
      | textinput4fullname  | value4  |
      | textinput5fullname  | value5  |
      | textinput6fullname  | value6  |
      | textinput7fullname  | value7  |
      | textinput8fullname  | value8  |
      | textinput9fullname  | value9  |
      | textinput10fullname | value10 |
      | textinput11fullname | value11 |
      | textinput12fullname | value12 |
      | textinput13fullname | value13 |
      | textinput14fullname | value14 |
      | textinput15fullname | value15 |
      | textinput16fullname | value16 |
      | textinput17fullname | value17 |
      | textinput18fullname | value18 |
      | textinput19fullname | value19 |
      | textinput20fullname | value20 |
      | textinput21fullname | value21 |
      | textinput22fullname | value22 |
      | textinput23fullname | value23 |
      | textinput24fullname | value24 |
      | textinput25fullname | value25 |
      | textinput26fullname | value26 |
      | textinput27fullname | value27 |
      | textinput28fullname | value28 |
      | textinput29fullname | value29 |
      | textinput30fullname | value30 |
      | textinput31fullname | value31 |
      | textinput32fullname | value32 |
      | textinput33fullname | value33 |
      | textinput34fullname | value34 |
      | textinput35fullname | value35 |
      | textinput36fullname | value36 |
      | textinput37fullname | value37 |
      | textinput38fullname | value38 |
      | textinput39fullname | value39 |
      | textinput40fullname | value40 |
      | textinput41fullname | value41 |
      | textinput42fullname | value42 |
      | textinput43fullname | value43 |
      | textinput44fullname | value44 |
      | textinput45fullname | value45 |
      | textinput46fullname | value46 |
      | textinput47fullname | value47 |
      | textinput48fullname | value48 |
      | textinput49fullname | value49 |
      | textinput50fullname | value50 |
      | textinput51fullname | value51 |
      | textinput52fullname | value52 |
      | textinput53fullname | value53 |
      | textinput54fullname | value54 |
      | textinput55fullname | value55 |
      | textinput56fullname | value56 |
      | textinput57fullname | value57 |
      | textinput58fullname | value58 |
      | textinput59fullname | value59 |
      | textinput60fullname | value60 |
      | textinput61fullname | value61 |
    And I press "Save changes"

    Then I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I click on "orgframework1" "link"
    And I should see "org1"
    And I should see "Type: orgtype1"
    And I should see "textinput1fullname: value1"
    And I should see "textinput2fullname: value2"
    And I should see "textinput3fullname: value3"
    And I should see "textinput4fullname: value4"
    And I should see "textinput5fullname: value5"
    And I should see "textinput6fullname: value6"
    And I should see "textinput7fullname: value7"
    And I should see "textinput8fullname: value8"
    And I should see "textinput9fullname: value9"
    And I should see "textinput10fullname: value10"
    And I should see "textinput11fullname: value11"
    And I should see "textinput12fullname: value12"
    And I should see "textinput13fullname: value13"
    And I should see "textinput14fullname: value14"
    And I should see "textinput15fullname: value15"
    And I should see "textinput16fullname: value16"
    And I should see "textinput17fullname: value17"
    And I should see "textinput18fullname: value18"
    And I should see "textinput19fullname: value19"
    And I should see "textinput20fullname: value20"
    And I should see "textinput21fullname: value21"
    And I should see "textinput22fullname: value22"
    And I should see "textinput23fullname: value23"
    And I should see "textinput24fullname: value24"
    And I should see "textinput25fullname: value25"
    And I should see "textinput26fullname: value26"
    And I should see "textinput27fullname: value27"
    And I should see "textinput28fullname: value28"
    And I should see "textinput29fullname: value29"
    And I should see "textinput30fullname: value30"
    And I should see "textinput31fullname: value31"
    And I should see "textinput32fullname: value32"
    And I should see "textinput33fullname: value33"
    And I should see "textinput34fullname: value34"
    And I should see "textinput35fullname: value35"
    And I should see "textinput36fullname: value36"
    And I should see "textinput37fullname: value37"
    And I should see "textinput38fullname: value38"
    And I should see "textinput39fullname: value39"
    And I should see "textinput40fullname: value40"
    And I should see "textinput41fullname: value41"
    And I should see "textinput42fullname: value42"
    And I should see "textinput43fullname: value43"
    And I should see "textinput44fullname: value44"
    And I should see "textinput45fullname: value45"
    And I should see "textinput46fullname: value46"
    And I should see "textinput47fullname: value47"
    And I should see "textinput48fullname: value48"
    And I should see "textinput49fullname: value49"
    And I should see "textinput50fullname: value50"
    And I should see "textinput51fullname: value51"
    And I should see "textinput52fullname: value52"
    And I should see "textinput53fullname: value53"
    And I should see "textinput54fullname: value54"
    And I should see "textinput55fullname: value55"
    And I should see "textinput56fullname: value56"
    And I should see "textinput57fullname: value57"
    And I should see "textinput58fullname: value58"
    And I should see "textinput59fullname: value59"
    And I should see "textinput60fullname: value60"
    And I should see "textinput61fullname: value61"

    Then I set the field "search" to "value1"
    And I press "Go"
    Then I should see "org1"
    And I should see "Type: orgtype1"
    And I should see "textinput1fullname: value1"
    # Others skipped, checked above
    And I should see "textinput17fullname: value17"
    # Others skipped, checked above
    And I should see "textinput61fullname: value61"

    Then I set the field "search" to "value17"
    And I press "Go"
    Then I should see "org1"
    And I should see "Type: orgtype1"
    And I should see "textinput1fullname: value1"
    # Others skipped, checked above
    And I should see "textinput17fullname: value17"
    # Others skipped, checked above
    And I should see "textinput61fullname: value61"
