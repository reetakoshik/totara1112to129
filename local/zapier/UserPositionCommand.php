<?php

class UserPositionCommand
{
    public static function create()
    {
        return new static;
    }

    public function run($params = array()) 
    {
        try {
            $validated = $this->validate($params);
            return ['Status' => 1] + $this->execute($validated);
        } catch(X $e) {
            return ['Status' => 0] + $e->getError();
        } catch (Exception $e) {
            return [
                'Status' => 0,
                'Error'  => $e->getMessage()
            ];
        }   
    }

    protected function validate($params = array())
    {
        $rules = [
            'userid'         => ['required', 'positive_integer'],
            'managerid'      => ['not_empty', 'positive_integer'],
            'positionid'     => ['not_empty', 'positive_integer'],
            'organisationid' => ['not_empty', 'positive_integer']
        ];

        $result = Validator::validate($params, $rules);

        return $result;
    }

    protected function execute($params = array())
    {   
        $params += [
            'managerid'      => null,
            'positionid'     => null,
            'organisationid' => null,
            'type'           => POSITION_TYPE_PRIMARY
        ];

        $user = $this->requireUser($params['userid']);

        $assignment = position_assignment::fetch([
            'userid' => $params['userid'],
            'type'   => $params['type'],
        ]);

        if (!$assignment) {
            $assignment = new position_assignment($params);
        }

        if ($params['managerid']) {
            $this->requireManager($params['managerid']);
            $assignment->managerid = $params['managerid'];
        }
        if ($params['positionid']) {
            $this->requirePosition($params['positionid']);
            $assignment->positionid = $params['positionid'];
        }
        if ($params['organisationid']) {
            $organization = $this->requireOrganisation($params['organisationid']);
            $assignment->organisationid = $params['organisationid'];
        }

        assign_user_position($assignment);

        return [
            'Status' => 1
        ];
    }

    private function requireUser($id)
    {
        global $DB;
        $user = $DB->get_record('user', ['id' => $id]);
        if (!$user) {
            throw new X([
                'Type'   => 'FORMAT_ERROR',
                'Fields' => ['userid' => 'NOT_FOUND']
            ]);
        }
        return $user;
    }

    private function requireManager($id)
    {
        global $DB;
        $manager = $DB->get_record('user', ['id' => $id]);
        if (!$manager || $manager->deleted) {
            throw new X([
                'Type'   => 'FORMAT_ERROR',
                'Fields' => ['managerid' => 'NOT_FOUND']
            ]);
        }
        return $manager;
    }

    private function requirePosition($id)
    {
        global $DB;
        $hierarchy = hierarchy::load_hierarchy('position');
        $position = $hierarchy->get_framework($id, true);
        if (!$position) {
            throw new X([
                'Type'   => 'FORMAT_ERROR',
                'Fields' => ['positionid' => 'NOT_FOUND']
            ]);
        }
        return $position;
    }

    private function requireOrganisation($id)
    {
        global $DB;
        $hierarchy = hierarchy::load_hierarchy('organisation');
        $organization = $hierarchy->get_framework($id, true);
        if (!$organization) {
            throw new X([
                'Type'   => 'FORMAT_ERROR',
                'Fields' => ['organisationid' => 'NOT_FOUND']
            ]);
        }
        return $organization;
    }
}