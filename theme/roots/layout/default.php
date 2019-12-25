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

$knownregionpre = $PAGE->blocks->is_known_region('side-pre');
$knownregionpost = $PAGE->blocks->is_known_region('side-post');

$grid = new theme_roots\output\bootstrap_grid();

if ($PAGE->blocks->region_has_content('side-pre', $OUTPUT)) {
    $grid->has_side_pre();
}

if ($PAGE->blocks->region_has_content('side-post', $OUTPUT)) {
    $grid->has_side_post();
}

$regions = $grid->get_regions_classes();

$PAGE->set_popup_notification_allowed(false);

$themerenderer = $PAGE->get_renderer('theme_roots');

// TODO improve on this legacy approach.
$hastotaramenu = false;
$totaramenu = '';
if (empty($PAGE->layout_options['nocustommenu'])) {
    $menudata = totara_build_menu();
    $totara_core_renderer = $PAGE->get_renderer('totara_core');
    $totaramenu = $totara_core_renderer->totara_menu($menudata);
    $hastotaramenu = !empty($totaramenu);
}
// END

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

<!-- Main navigation -->
<?php require("{$CFG->dirroot}/theme/roots/layout/partials/header.php"); ?>

<!-- Breadcrumb and edit buttons -->
<div class="container-fluid breadcrumb-container">
    <div class="row">
        <div class="col-sm-12">
            <?php echo $OUTPUT->full_header(); ?>
        </div>
    </div>
</div>

<!-- Content -->
<div id="page" class="container-fluid">
    <div id="page-content" class="row">
        <div id="region-main" class="<?php echo $regions['content']; ?>">
            <?php echo $OUTPUT->course_content_header(); ?>
            <?php echo $OUTPUT->main_content(); ?>
            <?php echo $OUTPUT->course_content_footer(); ?>
        </div>

        <?php
        if ($knownregionpre) {
            echo $OUTPUT->blocks('side-pre', $regions['pre']);
        }?>
        <?php
        if ($knownregionpost) {
            echo $OUTPUT->blocks('side-post', $regions['post']);
        }?>
    </div>
</div>

<!-- Footer -->
<?php require("{$CFG->dirroot}/theme/roots/layout/partials/footer.php"); ?>

<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
