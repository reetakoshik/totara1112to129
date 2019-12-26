<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @author    Murali Nair <murali.nair@totaralearning.com>
 * @package   theme_roots
 */

namespace theme_roots\output;

/**
 * Class bootstrap_grid
 */
final class bootstrap_grid {

    const REGION_PRE = 'side-pre';
    const REGION_POST = 'side-post';
    const REGION_TOP = 'top';
    const REGION_BOTTOM = 'bottom';
    const REGION_MAIN = 'main';

    const CONTENT = 'content';

    private $regions = [
        self::REGION_PRE    => false,
        self::REGION_POST   => false,
        self::REGION_TOP    => false,
        self::REGION_BOTTOM => false,
        self::REGION_MAIN   => false,
    ];

    private $classes = [];

    /**
     * Initialise a new grid given the page and output.
     *
     * @param \moodle_page   $page
     * @param \renderer_base $output
     *
     * @throws \coding_exception
     * @return bootstrap_grid
     */
    public static function initialise(\moodle_page $page, \renderer_base $output) {
        if ($page->state === $page::STATE_BEFORE_HEADER) {
            throw new \coding_exception('Bootstrap grid cannot be used until the page is being printed.');
        }
        $regions = [];
        foreach ($page->blocks->get_regions() as $region) {
            if ($page->blocks->region_has_content($region, $output)) {
                $regions[] = $region;
            }
        }

        return new self($regions, $page->blocks->get_add_block_regions());
    }

    /**
     * Bootstrap grid constructor.
     *
     * @param array $regions             An array of regions that are to be displayed.
     * @param array $regions_allowingadd An array of regions supporting the adding of blocks.
     */
    public function __construct(array $regions = [], array $regions_allowingadd = []) {
        $this->resolve_display($regions);
        $this->resolve_classes($regions_allowingadd);
    }

    /**
     * Returns true if the given region should be shown.
     *
     * @param string $region
     *
     * @return bool
     */
    public function show(string $region): bool {
        if (isset($this->regions[$region])) {
            return ($this->regions[$region] !== false);
        }

        return false;
    }

    /**
     * Returns true if the given region has classes.
     *
     * @param string $region
     *
     * @return bool
     */
    public function has_classes(string $region): bool {
        if (!isset($this->classes[$region])) {
            return false;
        }

        return $this->classes[$region] !== '';
    }

    /**
     * Returns the classes that should be added to the region, or content.
     *
     * @param string $region
     *
     * @return string
     */
    public function classes(string $region): string {
        if (!$this->has_classes($region)) {
            return '';
        }

        return $this->classes[$region];
    }

    /**
     * Resolves display of regions.
     *
     * @param array $regionswithcontent
     */
    private function resolve_display(array $regionswithcontent): void {
        foreach ($this->regions as $region => &$display) {
            if (in_array($region, $regionswithcontent)) {
                $display = true;
            }
        }
    }

    /**
     * Resolves the CSS classes that should applied to the grid regions and content.
     *
     * When making changes here make sure you apply them also to theme/roots/less/totara/core.less
     *
     * @param array $regions_allowingadd
     */
    private function resolve_classes(array $regions_allowingadd): void {
        $this->classes[self::CONTENT] = 'col-md-12';
        $this->classes[self::REGION_TOP] = 'col-sm-12';
        $this->classes[self::REGION_BOTTOM] = 'col-sm-12';
        $this->classes[self::REGION_MAIN] = '';
        $this->classes[self::REGION_PRE] = 'empty';
        $this->classes[self::REGION_POST] = 'empty';

        // The content of the page, which needs to respond to missing block regions etc.
        if ($this->show(self::REGION_PRE) && $this->show(self::REGION_POST)) {
            $this->classes[self::CONTENT] = 'col-sm-12 col-md-6 col-md-push-3';
            $this->classes[self::REGION_PRE] = 'col-sm-6 col-md-3 col-md-pull-6';
            $this->classes[self::REGION_POST] = 'col-sm-6 col-md-3';
        } else if ($this->show(self::REGION_PRE)) {
            $this->classes[self::CONTENT] = 'col-sm-12 col-md-9 col-md-push-3';
            $this->classes[self::REGION_PRE] = 'col-sm-6 col-md-3 col-md-pull-9';
            $this->classes[self::REGION_POST] = 'empty';
        } else if ($this->show(self::REGION_POST)) {
            $this->classes[self::CONTENT] = 'col-sm-12 col-md-9';
            $this->classes[self::REGION_PRE] = 'empty';
            $this->classes[self::REGION_POST] = 'col-sm-6 col-sm-offset-6 col-md-3 col-md-offset-0';
        }

        // Add the editing region border class to all regions that support adding blocks.
        foreach ($this->regions as $region => $display) {
            if ($display && in_array($region, $regions_allowingadd)) {
                $this->classes[$region] .= ' editing-region-border';
            }
        }
    }

}
