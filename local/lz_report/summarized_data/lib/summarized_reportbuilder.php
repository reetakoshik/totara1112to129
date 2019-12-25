<?php

class public_reportbuilder extends reportbuilder
{
    public function __get($name) { return $this->$name; }
    public function __call($name, $arguments) { return call_user_func_array([$this, $name], $arguments); }
}

class summarized_reportbuilder
{
    private $report;
    private $coreBuilder;

    public function __construct($reportId)
    {
        global $DB;
        $this->report = $DB->get_record('report_builder', array('id' => $reportId), '*', MUST_EXIST);
        $restrictions = rb_global_restriction_set::create_from_page_parameters($this->report);
        $this->coreBuilder = new public_reportbuilder(
            $reportId, null, false, 0, null, false, [], $restrictions
        );
    }

    public function make_query(array $fields, array $group)
    {
        global $DB;

        list($where,
            $_group,
            $having,
            $sqlparams,
            $allgrouped
        ) = $this->coreBuilder->collect_restrictions(true);

        $params    = $this->coreBuilder->get_global_restriction_parameters();
        $sqlparams = array_merge($sqlparams, $params);

        $filter = reportbuilder::FILTER;
        $joins  = $this->coreBuilder->collect_joins($filter, false);

        $this->coreBuilder->grouped = true;

        $sql = $this->coreBuilder->collect_sql(
            $fields, $this->coreBuilder->src->base, $joins, $where, $group, $having, false, false
        );

        $records = $DB->get_recordset_sql($sql, $sqlparams);
        
        return $records;
    }
}
