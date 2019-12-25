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
 * @package   theme_roots
 */

namespace theme_roots\output;

/**
 * Class bootstrap_grid
 *
 * Class based implementation of theme_bootstrap
 * lib function with the same name. We re-implement
 * as that function contains some rtl switching which
 * actually breaks the layout in Totara.
 *
 * @package theme_bootstrap
 */
class bootstrap_grid {

    /**
     * @var bool
     */
    protected $side_pre = false;

    /**
     * @var bool
     */
    protected $side_post = false;

    /**
     * Set gridf
     */
    public function has_side_pre() {
        $this->side_pre = true;

        return $this;
    }

    public function has_side_post() {
        $this->side_post = true;

        return $this;
    }

    /**
     * Return classes which should be applied to configured regions.
     */
    public function get_regions_classes() {

        // NOTE: when making changes here make sure you apply them also to theme/roots/less/totara/core.less

        if ($this->side_pre && $this->side_post) {
            return array(
                'content' => 'col-sm-12 col-md-6 col-md-push-3',
                'pre' => 'col-sm-6 col-md-3 col-md-pull-6',
                'post' => 'col-sm-6 col-md-3',
            );
        }

        if ($this->side_pre && !$this->side_post) {
            return array(
                'content' => 'col-sm-12 col-md-9 col-md-push-3',
                'pre' => 'col-sm-6 col-md-3 col-md-pull-9',
                'post' => 'empty',
            );
        }

        if (!$this->side_pre && $this->side_post) {
            return array(
                'content' => 'col-sm-12 col-md-9',
                'pre' => 'empty',
                'post' => 'col-sm-6 col-sm-offset-6 col-md-3 col-md-offset-0',
            );
        }

        // No side-pre or side-post.
        return array(
            'content' => 'col-md-12',
            'pre' => 'empty',
            'post' => 'empty',
        );

    }

}
