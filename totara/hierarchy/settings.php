<?php // $Id$
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package totara
 * @subpackage totara_hierarchy
 */

// This file defines settingpages and externalpages under the "hierarchies" category

    // Positions.
    $ADMIN->add('positions', new admin_externalpage('positionmanage', get_string('positionmanage', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/framework/index.php?prefix=position",
        array('totara/hierarchy:viewpositionframeworks'), totara_feature_disabled('positions')));

    $ADMIN->add('positions', new admin_externalpage('positiontypemanage', get_string('managepositiontypes', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=position",
        array('totara/hierarchy:createpositiontype', 'totara/hierarchy:updatepositiontype', 'totara/hierarchy:deletepositiontype'),
        totara_feature_disabled('positions')
    ));

    // Organisations.
    $ADMIN->add('organisations', new admin_externalpage('organisationmanage', get_string('organisationmanage', 'totara_hierarchy'),
            "{$CFG->wwwroot}/totara/hierarchy/framework/index.php?prefix=organisation",
            array('totara/hierarchy:vieworganisationframeworks')));

    $ADMIN->add('organisations', new admin_externalpage('organisationtypemanage', get_string('manageorganisationtypes', 'totara_hierarchy'),
            "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=organisation",
            array('totara/hierarchy:createorganisationtype', 'totara/hierarchy:updateorganisationtype', 'totara/hierarchy:deleteorganisationtype')));


    // Competencies.
    $ADMIN->add('competencies', new admin_externalpage('competencymanage', get_string('competencymanage', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/framework/index.php?prefix=competency",
        array('totara/hierarchy:viewcompetencyscale', 'totara/hierarchy:viewcompetencyframeworks'),
        totara_feature_disabled('competencies')
    ));

    $ADMIN->add('competencies', new admin_externalpage('competencytypemanage', get_string('managecompetencytypes', 'totara_hierarchy'),
        "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=competency",
        array('totara/hierarchy:createcompetencytype', 'totara/hierarchy:updatecompetencytype', 'totara/hierarchy:deletecompetencytype'),
        totara_feature_disabled('competencies')
    ));

    // Goals.
    $ADMIN->add('goals', new admin_externalpage('goalmanage', get_string('goalmanage', 'totara_hierarchy'),
            "{$CFG->wwwroot}/totara/hierarchy/framework/index.php?prefix=goal",
            array('totara/hierarchy:creategoalframeworks', 'totara/hierarchy:updategoalframeworks', 'totara/hierarchy:deletegoalframeworks',
                  'totara/hierarchy:creategoal', 'totara/hierarchy:updategoal', 'totara/hierarchy:deletegoal',
                  'totara/hierarchy:creategoalscale', 'totara/hierarchy:updategoalscale', 'totara/hierarchy:deletegoalscale'),
            totara_feature_disabled('goals')));

    $ADMIN->add('goals', new admin_externalpage('companygoaltypemanage', get_string('managecompanygoaltypes', 'totara_hierarchy'),
            "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=goal&class=company",
            array('totara/hierarchy:creategoaltype', 'totara/hierarchy:updategoaltype', 'totara/hierarchy:deletegoaltype'),
            totara_feature_disabled('goals')));

    $ADMIN->add('goals', new admin_externalpage('personalgoaltypemanage', get_string('managepersonalgoaltypes', 'totara_hierarchy'),
            "{$CFG->wwwroot}/totara/hierarchy/type/index.php?prefix=goal&class=personal",
            array('totara/hierarchy:creategoaltype', 'totara/hierarchy:updategoaltype', 'totara/hierarchy:deletegoaltype'),
            totara_feature_disabled('goals')));

    $ADMIN->add('goals', new admin_externalpage('goalreport', get_string('goalreports', 'totara_hierarchy'),
            "{$CFG->wwwroot}/totara/hierarchy/prefix/goal/reports.php",
            array('totara/hierarchy:viewgoalreport'), totara_feature_disabled('goals')));
