<?php

namespace local_lz_extension;

class xlsx_export_writer 
{
    private $rows;

    public function __construct()
    {
        $this->filename = "Moodle-data-export";
    }

    public function add_data($row)
    {
        $this->rows[] = $row;
    }

    public function set_filename($dataname, $extension = '') {
        $filename  = clean_filename($dataname);
        $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
        $filename .= $extension;

        $this->filename = $filename;
    }

    public function download_file() {
        global $CFG;
        require_once("$CFG->libdir/excellib.class.php");

        $workbook = new \MoodleExcelWorkbook($this->filename);
        $worksheet = [ $workbook->add_worksheet('') ];

        foreach ($this->rows as $row => $record_data) {
            $col = 0;
            foreach ($record_data as $value) {
                $worksheet[0]->write($row, $col++, $value);
            }
        }

        $workbook->close();

        die;
    }
}
