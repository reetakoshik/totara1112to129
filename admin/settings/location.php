<?php

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page

    // "locations" settingpage
    $temp = new admin_settingpage('locationsettings', new lang_string('locationsettings', 'admin'));
    $temp->add(new admin_setting_servertimezone());
    $temp->add(new admin_setting_forcetimezone());

    // Totara feature.
    $temp->add(new admin_setting_configtext('csvdateformat', new lang_string('csvdateformat', 'totara_core'), new lang_string('csvdateformatconfig', 'totara_core'), new lang_string('csvdateformatdefault', 'totara_core')));

    $temp->add(new admin_settings_country_select('country', new lang_string('country', 'admin'), new lang_string('configcountry', 'admin'), 0));
    $temp->add(new admin_setting_configtext('defaultcity', new lang_string('defaultcity', 'admin'), new lang_string('defaultcity_help', 'admin'), ''));

    $temp->add(new admin_setting_heading('iplookup', new lang_string('iplookup', 'admin'), new lang_string('iplookupinfo', 'admin')));
    $temp->add(new admin_setting_configfile('geoip2file', new lang_string('geoipfile', 'admin'),
        new lang_string('configgeoipfile', 'admin', $CFG->dataroot.'/geoip/'), $CFG->dataroot.'/geoip/GeoLite2-City.mmdb'));

    $temp->add(new admin_setting_configtext('allcountrycodes', new lang_string('allcountrycodes', 'admin'), new lang_string('configallcountrycodes', 'admin'), '', '/^(?:\w+(?:,\w+)*)?$/'));

    $temp->add(
        new admin_setting_heading(
            'googlemaps',
            new lang_string('googlemaps', 'admin'),
            ''
        )
    );

    $temp->add(
        new admin_setting_configtext(
            'googlemapkey3',
            new lang_string('googlemapkey3', 'admin'),
            new lang_string('googlemapkey3_help', 'admin'),
            '',
            PARAM_RAW
        )
    );

    $temp->add(
        new admin_setting_configtext(
            'gmapsregionbias',
            new lang_string('gmapsregionbias', 'admin'),
            new lang_string('gmapsregionbias_help', 'admin'),
            '',
            PARAM_ALPHA,
            2
        )
    );

    $temp->add(
        new admin_setting_configtext(
            'gmapsforcemaplanguage',
            new lang_string('gmapsforcemaplanguage', 'admin'),
            new lang_string('gmapsforcemaplanguage_help', 'admin'),
            '',
            PARAM_ALPHANUMEXT,
            5
        )
    );

    $temp->add(
        new admin_setting_configtext(
            'gmapsdefaultzoomlevel',
            new lang_string('gmapsdefaultzoomlevel', 'admin'),
            new lang_string('gmapsdefaultzoomlevel_help', 'admin'),
            12,
            PARAM_INT,
            2
        )
    );

    $ADMIN->add('localisation', $temp);

} // end of speedup
