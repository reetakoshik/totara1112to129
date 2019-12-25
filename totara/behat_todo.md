List of Behat related TODOs
===========================


Temporary hacks
---------------

* reenable add_to_log() detection in behat


Report bugs
-----------

* cohort->cohorttype should default to 1 not 0
* fix dialog submissions in totara/appraisal/tests/behat/create_apprasial.feature
* warn developers about AJAX_SCRIPT + html failures with $.get() - see mod_facetoface
* decide what to do with title in question preview - question/tests/behat/edit_questions.feature
* recommend 'I navigate to' instead of unreliable 'I expand', see i_enrol_user_as()
* fix behat init to backup dataroot files later - upstream


Duplicate id problems
---------------------

For some reason behat non-js runs are able to detect duplicate ids in DOM
and report it as warnings into console. But it seems that nobody else except
me sees that. --skodak

* action_link duplicate ids on the risks in permissiosn page - upstream
* graderubrics duplicate ids - id_name, rubrick-options-enableremarks, rubrick-options-showremarksstudent - upstream
* groups duplicate ids - existingcell

In any case this is a HTML validation problem that should be fixed upstream.
