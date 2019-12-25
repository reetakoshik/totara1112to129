<?php
    // latex.php
    // render TeX stuff using latex - this will not work on all platforms
    // or configurations. Only works on Linux and Mac with appropriate
    // software installed.
    // Much of this inspired/copied from Benjamin Zeiss' work

    class latex {

        var $temp_dir;
        var $error;

        /**
         * Constructor - create temporary directories and build paths to
         * external 'helper' binaries.
         * Other platforms could/should be added
         */
        public function __construct() {
            global $CFG;

            // construct directory structure
            $this->temp_dir = $CFG->tempdir . "/latex";
            make_temp_directory('latex');
        }

        /**
         * Old syntax of class constructor. Deprecated in PHP7.
         *
         * @deprecated since Moodle 3.1
         */
        public function latex() {
            debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
            self::__construct();
        }

        /**
         * Accessor function for support_platform field.
         * @return boolean value of supported_platform
         */
        function supported() {
            return $this->supported_platform;
        }

        /**
         * Turn the bit of TeX into a valid latex document
         * @param string $forumula the TeX formula
         * @param int $fontsize the font size
         * @return string the latex document
         */
        function construct_latex_document( $formula, $fontsize=12 ) {
            global $CFG;

            $formula = filter_tex_sanitize_formula($formula);

            // $fontsize don't affects to formula's size. $density can change size
            $doc =  "\\documentclass[{$fontsize}pt]{article}\n";
            $doc .= get_config('filter_tex', 'latexpreamble');
            $doc .= "\\pagestyle{empty}\n";
            $doc .= "\\begin{document}\n";
//dlnsk            $doc .= "$ {$formula} $\n";
            if (preg_match("/^[[:space:]]*\\\\begin\\{(gather|align|alignat|multline).?\\}/i",$formula)) {
               $doc .= "$formula\n";
            } else {
               $doc .= "$ {$formula} $\n";
            }
            $doc .= "\\end{document}\n";
            return $doc;
        }

        /**
         * Render TeX string into gif/png
         * @param string $formula TeX formula
         * @param string $filename filename for output (including extension)
         * @param int $fontsize font size
         * @param int $density density value for .ps to .gif/.png conversion
         * @param string $background background color (e.g, #FFFFFF).
         * @param file $log valid open file handle for optional logging (debugging only)
         * @return bool true if successful
         */
        function render( $formula, $filename, $fontsize=12, $density=240, $background='', $log=null ) {

            global $CFG;

            // quick check - will this work?
            $pathlatex = get_config('filter_tex', 'pathlatex');
            if (empty($pathlatex)) {
                return false;
            }

            $doc = $this->construct_latex_document( $formula, $fontsize );

            // construct some file paths
            $convertformat = get_config('filter_tex', 'convertformat');
            if (!strpos($filename, ".{$convertformat}")) {
                $convertformat = 'png';
            }
            $filename = str_replace(".{$convertformat}", '', $filename);
            $tex = "{$this->temp_dir}/$filename.tex";
            $dvi = "{$this->temp_dir}/$filename.dvi";
            $ps  = "{$this->temp_dir}/$filename.ps";
            $img = "{$this->temp_dir}/$filename.{$convertformat}";

            // turn the latex doc into a .tex file in the temp area
            $fh = fopen( $tex, 'w' );
            fputs( $fh, $doc );
            fclose( $fh );

            // Some other temp files are generated in the current directory when the commands below are run.
            // Let's change to the temp directory so they are generated there.
            chdir( $this->temp_dir );

            // run latex on document
            $latex = new \core\command\executable($pathlatex);
            $latex->add_argument('--interaction', 'nonstopmode', PARAM_ALPHA, '=')
                  ->add_switch('--halt-on-error')
                  ->add_value($tex, \core\command\argument::PARAM_FULLFILEPATH)
                  ->execute();
            if ($log) {
                fwrite($log, "COMMAND PATH: " . $pathlatex . "\n");
                fwrite($log, "OUTPUT: " . implode("\n", $latex->get_output()) . "\n");
                fwrite($log, "RETURN CODE: " . $latex->get_return_status() . "\n");
            }

            // run dvips (.dvi to .ps)
            $pathdvips = get_config('filter_tex', 'pathdvips');
            $dvips = new \core\command\executable($pathdvips);
            $dvips->add_switch('-q')->add_switch('-E')
                  ->add_value($dvi, \core\command\argument::PARAM_FULLFILEPATH)
                  ->add_switch('-o')->add_value($ps, \core\command\argument::PARAM_FULLFILEPATH)
                  ->execute();
            if ($log) {
                fwrite($log, "COMMAND PATH: " . $pathdvips . "\n");
                fwrite($log, "OUTPUT: " . implode("\n", $dvips->get_output()) . "\n");
                fwrite($log, "RETURN CODE: " . $dvips->get_return_status() . "\n");
            }

            if ($dvips->get_return_status()) {
                return false;
            }

            if ($convertformat == 'svg') {
                $convert = new \core\command\executable(get_config('filter_tex', 'pathdvisvgm'));
                $convert->add_argument('-E', $ps,\core\command\argument::PARAM_FULLFILEPATH)
                        ->add_argument('-o', $img,\core\command\argument::PARAM_FULLFILEPATH);
                if ($log) {
                    fwrite($log, "COMMAND PATH: " . get_config('filter_tex', 'pathdvisvgm') . "\n");
                }
            } else {
                $convert = new \core\command\executable(get_config('filter_tex', 'pathconvert'));
                $convert->add_argument('-density', $density,PARAM_INT)
                        ->add_switch('-trim');
                if ($background) {
                    // Make the background transparent, providing it matches the given $background colour.
                    $convert->add_argument('-transparent', $background,'/^#?[a-zA-Z0-9]*$/');
                }
                $convert->add_value($ps, \core\command\argument::PARAM_FULLFILEPATH);
                $convert->add_value($img, \core\command\argument::PARAM_FULLFILEPATH);
                if ($log) {
                    fwrite($log, "COMMAND PATH: " . get_config('filter_tex', 'pathconvert') . "\n");
                }
            }

            // Now run whichever of the above $convert objects we built.
            $convert->execute();
            if ($log) {
                fwrite($log, "OUTPUT: " . implode("\n", $convert->get_output()) . "\n");
                fwrite($log, "RETURN CODE: " . $convert->get_return_status() . "\n");
            }
            if ($convert->get_return_status()) {
                return false;
            }

            return $img;
        }

        /**
         * Delete files created in temporary area
         * Don't forget to copy the final gif/png before calling this
         * @param string $filename file base (no extension)
         */
        function clean_up( $filename ) {
            global $CFG;

            unlink( "{$this->temp_dir}/$filename.tex" );
            unlink( "{$this->temp_dir}/$filename.dvi" );
            unlink( "{$this->temp_dir}/$filename.ps" );
            $convertformat = get_config('filter_tex', 'convertformat');
            unlink( "{$this->temp_dir}/$filename.{$convertformat}" );
            unlink( "{$this->temp_dir}/$filename.aux" );
            unlink( "{$this->temp_dir}/$filename.log" );
            return;
        }

    }



