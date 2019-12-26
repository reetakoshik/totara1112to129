<?php

namespace local_lz_extension\watcher;

require_once __DIR__.'/../../lib/xlsx_export_writer.php';
  
class xlsx_export_writer 
{
    public static function execute(\local_lz_extension\hook\xlsx_export_writer $hook)
    {
    	$hook->export->add_data([]);
        $hook->export = new \local_lz_extension\xlsx_export_writer();
    }
}