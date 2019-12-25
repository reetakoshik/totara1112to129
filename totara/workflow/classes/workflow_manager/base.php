<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_workflow
 */

namespace totara_workflow\workflow_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Base workflow manager class for managing workflow instances.
 */
abstract class base {

    /**
     * @var array $params;
     */
    protected $params = [];

    /**
     * Name for the workflow manager. Must be implemented by
     * manager instance.
     *
     * @return string Name of workflow manager.
     */
    abstract public function get_name(): string;

    /**
     * Extract component and manager data from the manager classname.
     *
     * @param string $managerclass Name of the workflow class to split.
     * @return string[] Array of [$component, $manager] as strings.
     */
    protected static function split_classname(string $managerclass): array {
        if (!preg_match('/^([a-z][a-z0-9_]*)\\\\workflow_manager\\\\([a-zA-Z0-9_-]+)$/', $managerclass, $matches)) {
            throw new \coding_exception("Workflow manager class '{$managerclass}' does not match expected format.");
        }
        $component = $matches[1] ?? null;
        $manager = $matches[2] ?? null;

        return [$component, $manager];
    }

    /**
     * Url for the workflow manager selection page.
     *
     * Defaults to generic page but can be overridden by a specific
     * workflow manager if desired.
     *
     * @return \moodle_url URL of selection page.
     */
    public function get_url(): \moodle_url {
        list($component, $manager) = self::split_classname(get_class($this));

        $url = new \moodle_url('/totara/workflow/manager.php', [
            'component' => $component,
            'manager' => $manager,
        ]);
        $url->params($this->get_params());
        return $url;
    }

    /**
     * Return an array of workflow objects for the specified namespace.
     *
     * @param bool $all If true returns all workflows, otherwise only returns available workflows.
     * @return \totara_workflow\workflow\base[] Array of workflow objects.
     */
    public function get_workflows(bool $all = false): array {

        if (!$all && !$this->can_access()) {
            return [];
        }

        $classes = $this->get_all_workflow_classes();

        $workflows = [];
        foreach ($classes as $classname) {
            $workflow = $this->get_workflow($classname);
            if ($all ||
                ($workflow->can_access() &&
                 $workflow->is_enabled())) {

                $workflows[$classname] = $workflow;
            }
        }

        return $workflows;
    }

    /**
     * Method to determine if current user can access at least
     * one workflow from this manager.
     *
     * @return bool True if at least one workflow is accessible.
     */
    public function workflows_available(): bool {
        return !empty($this->get_workflows());
    }

    /**
     * Return an instance of a specific workflow.
     *
     * Note: Workflow always returned if it exists.
     * Call $workflow->is_available() to check it
     * is enabled and accessible.
     *
     * @param string $classname Name of workflow class.
     * @return \totara_workflow\workflow\base Workflow object
     */
    public function get_workflow(string $classname): \totara_workflow\workflow\base {

        if (!class_exists($classname)) {
            throw new \coding_exception("Attempt to instantiate class '{$classname}' which does not exist.");
        }
        $workflow = new $classname($this);

        return $workflow;
    }

    /**
     * Determines whether this type of workflow
     * can be used.
     *
     * @return bool True if current user can use this workflow.
     */
    protected function can_access(): bool {
        return true;
    }

    /**
     * Returns a list of all workflow classes of this type.
     *
     * @return string[] Array of fully-qualified class names.
     */
    protected final function get_all_workflow_classes(): array {

        list($managercomponent, $manager) = self::split_classname(get_class($this));

        return \core_component::get_namespace_classes("workflow\\{$managercomponent}\\{$manager}", '\\totara_workflow\\workflow\\base');
    }

    /**
     * Defines data required by the workflow manager.
     * This data is included in the workflow URL and
     * workflow form (via hidden fields defined below).
     *
     * @return array Workflow manager data.
     */
    public function get_workflow_manager_data(): array {
        return [];
    }

    /**
     * Defines workflow form elements required by the manager to pass
     * required data through the form.
     *
     * This should be called by the workflow form.
     *
     * @param \totara_form\model $model
     */
    public function add_workflow_manager_form_elements(\totara_form\model $model): void {
    }

    /**
     * Get list of workflow manager classes.
     *
     * @return string[] Array of all workflow manager classes.
     */
    public static function get_all_workflow_manager_classes(): array {
        return \core_component::get_namespace_classes('workflow_manager', '\\totara_workflow\\workflow_manager\\base');
    }

    /**
     * Set workflow manager parameters.
     *
     * @param array $params Key/value array of parameters to store.
     */
    public function set_params(array $params): void {
        $this->params = $params;
    }

    /**
     * Get workflow manager parameters.
     *
     * @return array Array of parameters.
     */
    public function get_params(): array {
        return $this->params;
    }

    /**
     * Return name of template to use for the workflow selector.
     *
     * Can override in manager or use the default tile template.
     *
     * @return string
     */
    public function get_workflow_template(): string {
        return 'totara_workflow/workflow_selector';
    }

    /**
     * Return the data required to render the workflow manager.
     *
     * @param \renderer_base Output renderer.
     * @return array Template context data.
     */
    public function export_for_template(\renderer_base $output): array {
        global $CFG;
        $data = [
            'managername' => $this->get_name(),
            'managerurl' => $this->get_url(),
            'wwwroot' => $CFG->wwwroot,
            'sesskey' => sesskey(),
        ];
        // Also export the workflow data.
        $data['workflows'] = [];
        $workflows = $this->get_workflows(true);
        foreach ($workflows as $workflow) {
            $data['workflows'][] = $workflow->export_for_template($output);
        }

        return $data;
    }

    /**
     * Enable a specific workflow.
     *
     * NOTE: This method is safe to call during install and upgrades.
     *
     * @param string $workflowclass Classname of workflow to enable.
     */
    public final function enable_workflow(string $workflowclass): void {
        if ($this->is_workflow_enabled($workflowclass)) {
            // Already enabled.
            return;
        }
        [$managercomponent, $settingname] = $this->get_manager_setting_name();
        $setting = get_config($managercomponent, $settingname);
        if (empty($setting)) {
            $newsetting = $workflowclass;
        } else {
            $workflows = explode(',', $setting);
            $workflows[] = $workflowclass;
            $newsetting = implode(',', $workflows);
        }
        set_config($settingname, $newsetting, $managercomponent);
    }

    /**
     * Disable a specific workflow.
     *
     * NOTE: This method is safe to call during install and upgrades.
     *
     * @param string $workflowclass Classname of workflow to disable.
     */
    public final function disable_workflow(string $workflowclass): void {
        if (!$this->is_workflow_enabled($workflowclass)) {
            // Already disabled.
            return;
        }
        [$managercomponent, $settingname] = $this->get_manager_setting_name();
        $setting = get_config($managercomponent, $settingname);

        $workflows = explode(',', $setting);
        $index = array_search($workflowclass, $workflows);
        unset($workflows[$index]);
        $newsetting = implode(',', $workflows);
        set_config($settingname, $newsetting, $managercomponent);
    }

    /**
     * Check if specific workflow is enabled.
     *
     * @param string $workflowclass Classname of workflow to check.
     * @return bool True if the specified workflow is enabled.
     */
    public final function is_workflow_enabled(string $workflowclass): bool {
        [$managercomponent, $settingname] = $this->get_manager_setting_name();
        $setting = get_config($managercomponent, $settingname);
        if (empty($setting)) {
            return false;
        }

        $workflows = explode(',', $setting);
        return in_array($workflowclass, $workflows);
    }

    /**
     * Return the name of the current manager's config setting.
     * @return string[] Array containing [$managercomponent, $settingname] for config setting used by this manager.
     */
    private function get_manager_setting_name(): array {
        list($component, $manager) = self::split_classname(get_class($this));

        return [$component, "workflow_manager_{$manager}_enabled_workflows"];
    }
}
