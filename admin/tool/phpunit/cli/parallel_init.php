<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package tool_phpunit
 */

if (isset($_SERVER['REMOTE_ADDR'])) {
    die; // no access from web!
}

require_once(__DIR__.'/../../../../lib/clilib.php');
require_once(__DIR__.'/../../../../lib/phpunit/bootstraplib.php');
require_once(__DIR__.'/../../../../lib/testing/lib.php');
require_once(__DIR__.'/../../../../vendor/autoload.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(
    array(
        'force'                 => false,
        'processes'             => 5, // This must match \ParaTest\Console\Commands\ParaTestCommand::configure()
        'help'                  => false,
    ),
    array(
        'h' => 'help',
    )
);

// Replicate the process number logic from \ParaTest\Runners\PHPUnit\Options class.
if ($options['processes'] === 'auto') {
    $processes = \ParaTest\Runners\PHPUnit\Options::getNumberOfCPUCores();
} else if ($options['processes'] === 'half') {
    $processes = intdiv(\ParaTest\Runners\PHPUnit\Options::getNumberOfCPUCores(), 2);
} else {
    $processes = intval($options['processes']);
}

if ($processes < 1 or $processes > 99) {
    cli_error('Processes argument cannot be higher than 99');
}

if ($options['help'] or !$processes) {
    $help = "Init PHPUnit parallel runs

Options:
--force          Force dropping of existing environments
--processes  Specifies how many environments should be initialised, use integer, 'auto' or 'half'

-h, --help        Print out this help

Example:
\$ php parallel_init.php --processes=4
\$ php parallel_run.php --processes=4
";
    echo $help;
    if ($processes) {
        exit(0);
    } else {
        exit(1);
    }
}

if ($unrecognized) {
    echo('Unkown parameter: ' . implode("\n  ", $unrecognized) . "\n");
    die(1);
}

testing_update_composer_dependencies();
echo "\nInitialising Totara PHPUnit test environment for parallel testing with $processes processes\n";

chdir(__DIR__);

$output = null;
exec('php --version', $output, $code);
if ($code != 0) {
    phpunit_bootstrap_error(1, 'Cannot execute \'php\' binary.');
}

$timestart = time();

// NOTE: The '0' instance is used by paratest before the wrappers are started.
$dropprinted = false;
for ($i = 0; $i <= $processes; $i++) {
    if (!$options['force']) {
        exec("php util.php --diag --instance=$i", $output, $code);
        if ($code == PHPUNIT_EXITCODE_INSTALL or $code == 0) {
            continue;
        }
    }
    if (!$dropprinted) {
        echo " * drop instances:";
        $dropprinted = true;
    }
    echo " $i";
    exec("php util.php --drop --instance=$i", $output, $code);
    if ($code != 0) {
        echo implode("\n", $output);
        // Do not stop here, let the install fail later.
    }
}
if ($dropprinted) {
    echo "\n";
}

echo " * init instances: ";
/** @var Symfony\Component\Process\Process[] $pending */
$pending = array();
for ($i = 0; $i <= $processes; $i++) {
    exec("php util.php --diag --instance=$i", $output, $code);
    if ($code == 0) {
        // Already installed and ready/
        continue;
    }
    if ($code == PHPUNIT_EXITCODE_INSTALL) {
        // NOTE: there is no point in limiting the number of concurrent init commands
        //       because phpunit processes later will be even heavier than init.
        $process = new Symfony\Component\Process\Process("php util.php --install --instance=$i", __DIR__);
        $process->setTimeout(60*30); // Max 30 minutes for install, we do not want this to be stuck forever.
        $process->start();
        $pending[$i] = $process;
        continue;
    }
    exit($code);
}
while ($pending) {
    sleep(2);
    foreach ($pending as $k => $process) {
        if (!$process->isRunning()) {
            $code = $process->getExitCode();
            if ($code != 0) {
                echo "\nInstance '$k' install failed:\n";
                echo $process->getOutput();
                exit($code);
            }
            unset($pending[$k]);
        }
    }
    // There is no need for full output of install, just print a dot to show fake progress.
    echo '.';
}
echo "\n";

echo " * build configs\n";
for ($i = 0; $i <= $processes; $i++) {
    passthru("php util.php --buildconfig --instance=$i", $code);
    if ($code != 0) {
        exit($code);
    }
}

$now = time();
$minutes = intdiv($now - $timestart, 60);
$seconds = ($now - $timestart) % 60;
echo "\nPHPUnit parallel test environment init completed in {$minutes} minutes $seconds seconds.\n";
exit(0);
