<?php
/*
 * This file is part of Totara LMS
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
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2015 Bas Brands <www.sonsbeekmedia.nl>
 * @author    Bas Brands
 * @author    David Scotson
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 * @author    Murali Nair <murali.nair@totaralearning.com>
 * @package   theme_basis
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->set_popup_notification_allowed(false);

$themerenderer = $PAGE->get_renderer('theme_basis');
$full_header = $themerenderer->full_header();
if (isset($PAGE->layout_options['nonavbar']) && $PAGE->layout_options['nonavbar'] && strpos($full_header, '<input') === false) {
    $full_header = '';
}
echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<?php require("{$CFG->dirroot}/theme/basis/layout/partials/head.php"); ?>

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
<?php require("{$CFG->dirroot}/theme/basis/layout/partials/footer.php"); ?>

<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
