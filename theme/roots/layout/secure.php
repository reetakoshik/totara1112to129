<?php
// This file is part of The Bootstrap Moodle theme
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package     theme_roots
 * @copyright   2014 Bas Brands, www.basbrands.nl
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Bas Brands
 * @author      David Scotson
 * @author      Joby Harding <joby.harding@totaralearning.com>
 * @author      Petr Skoda <petr.skoda@totaralms.com>
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->set_popup_notification_allowed(false);

$themerenderer = $PAGE->get_renderer('theme_roots');

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html(); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<nav role="navigation" class="navbar navbar-default">
    <div class="container-fluid">
    <div class="navbar-header">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#moodle-navbar">
            <span class="sr-only"><?php echo get_string('togglenavigation', 'core');?></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
        <span class="navbar-brand"><?php echo $SITE->shortname; ?></span>
    </div>

    <div id="moodle-navbar" class="navbar-collapse collapse">
        <ul class="nav pull-right">
            <li><?php echo $themerenderer->page_heading_menu(); ?></li>
            <li class="navbar-text"><?php echo $themerenderer->login_info(false) ?></li>
        </ul>
    </div>
    </div>
</nav>
<header class="moodleheader">
    <div class="container-fluid">
    <a href="<?php echo $CFG->wwwroot ?>" class="logo"></a>
    <?php echo $themerenderer->page_heading(); ?>
    </div>
</header>

<div id="page" class="container-fluid">
    <header id="page-header" class="clearfix">
        <div id="course-header">
            <?php echo $themerenderer->course_header(); ?>
        </div>
    </header>

    <div id="page-content">

        <?php echo $themerenderer->blocks_top(); ?>
        <div class="row">
            <div id="region-main" class="<?php echo $themerenderer->main_content_classes(); ?>">
                <?php echo $themerenderer->course_content_header(); ?>
                <?php echo $themerenderer->main_content(); ?>
                <?php echo $themerenderer->course_content_footer(); ?>
            </div>
            <?php echo $themerenderer->blocks_pre(); ?>
            <?php echo $themerenderer->blocks_post(); ?>
        </div>
        <?php echo $themerenderer->blocks_bottom(); ?>

    </div>

    <?php echo $OUTPUT->standard_end_of_body_html() ?>

</div>
</body>
</html>
