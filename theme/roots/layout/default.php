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
 *
 * @var core_renderer $OUTPUT
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->set_popup_notification_allowed(false);

$themerenderer = $PAGE->get_renderer('theme_roots');

$full_header = $themerenderer->full_header();
if (isset($PAGE->layout_options['nonavbar']) && $PAGE->layout_options['nonavbar'] && strpos($full_header, '<input') === false) {
    $full_header = '';
}

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
<?php
$totara_core_renderer = $PAGE->get_renderer('totara_core');
/*
// This commented code has been @deprecated since 12.0.
$hastotaramenu = false;
$totaramenu = '';
if (empty($PAGE->layout_options['nocustommenu'])) {
    $menudata = totara_build_menu();
    $totaramenu = $totara_core_renderer->totara_menu($menudata);
    $hastotaramenu = !empty($totaramenu);
}
require("{$CFG->dirroot}/theme/roots/layout/partials/header.php");
*/
$hasguestlangmenu = (!isset($PAGE->layout_options['langmenu']) || $PAGE->layout_options['langmenu'] );
$nocustommenu = !empty($PAGE->layout_options['nocustommenu']);
echo $totara_core_renderer->masthead($hasguestlangmenu, $nocustommenu);
?>

<?php if ($full_header !== '') { ?>
<!-- Breadcrumb and edit buttons -->
<div class="container-fluid breadcrumb-container">
    <div class="row">
        <div class="col-sm-12">
            <?php echo $full_header; ?>
        </div>
    </div>
</div>
<?php } ?>

<!-- Content -->
<div id="page" class="container-fluid">
    <div id="page-content">

        <?php echo $themerenderer->blocks_top(); ?>
        <div class="row">
            <div id="region-main" class="<?php echo $themerenderer->main_content_classes(); ?>">
                <?php echo $themerenderer->course_content_header(); ?>
                <?php echo $themerenderer->main_content(); ?>
                <?php echo $themerenderer->blocks_main(); ?>
                <?php echo $themerenderer->course_content_footer(); ?>
            </div>
            <?php echo $themerenderer->blocks_pre(); ?>
            <?php echo $themerenderer->blocks_post(); ?>
        </div>
        <?php echo $themerenderer->blocks_bottom(); ?>

    </div>
</div>

<!-- Footer -->
<?php require("{$CFG->dirroot}/theme/roots/layout/partials/footer.php"); ?>

<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
