// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/* jshint node: true, browser: false */
/* eslint-env node */

/**
 * @copyright  2014 Andrew Nicols
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Grunt configuration
 */

module.exports = function(grunt) {
    var path = require('path'),
        tasks = {},
        cwd = process.env.PWD || process.cwd(),
        async = require('async'),
        DOMParser = require('xmldom').DOMParser,
        xpath = require('xpath'),
        semver = require('semver');

    // Verify the node version is new enough.
    var expected = semver.validRange(grunt.file.readJSON('package.json').engines.node);
    var actual = semver.valid(process.version);
    if (!semver.satisfies(actual, expected)) {
        grunt.fail.fatal('Node version too old. Require ' + expected + ', version installed: ' + actual);
    }

    // Windows users can't run grunt in a subdirectory, so allow them to set
    // the root by passing --root=path/to/dir.
    if (grunt.option('root')) {
        var root = grunt.option('root');
        if (grunt.file.exists(__dirname, root)) {
            cwd = path.join(__dirname, root);
            grunt.log.ok('Setting root to ' + cwd);
        } else {
            grunt.fail.fatal('Setting root to ' + root + ' failed - path does not exist');
        }
    }

    /**
     * Function to generate the destination for the uglify task
     * (e.g. build/file.min.js). This function will be passed to
     * the rename property of files array when building dynamically:
     * http://gruntjs.com/configuring-tasks#building-the-files-object-dynamically
     *
     * @param {String} destPath the current destination
     * @param {String} srcPath the  matched src path
     * @return {String} The rewritten destination path.
     */
    var uglifyRename = function(destPath, srcPath) {
        destPath = srcPath.replace('src', 'build');
        destPath = destPath.replace('.js', '.min.js');
        destPath = path.resolve(cwd, destPath);
        return destPath;
    };

    /**
     * Find thirdpartylibs.xml and generate an array of paths contained within
     * them (used to generate ignore files and so on).
     *
     * @return {array} The list of thirdparty paths.
     */
    var getThirdPartyPathsFromXML = function() {
        var thirdpartyfiles = grunt.file.expand('*/**/thirdpartylibs.xml');
        var libs = ['node_modules/', 'vendor/'];

        thirdpartyfiles.forEach(function(file) {
          var dirname = path.dirname(file);

          var doc = new DOMParser().parseFromString(grunt.file.read(file));
          var nodes = xpath.select("/libraries/library/location/text()", doc);

          nodes.forEach(function(node) {
            var lib = path.join(dirname, node.toString());
            if (grunt.file.isDir(lib)) {
                // Ensure trailing slash on dirs.
                lib = lib.replace(/\/?$/, '/');
            }

            // Look for duplicate paths before adding to array.
            if (libs.indexOf(lib) === -1) {
                libs.push(lib);
            }
          });
        });
        return libs;
    };


    // Totara: Promise polyfill for legacy Node versions.
    if (typeof Promise === 'undefined') {
        global.Promise = require('promise/lib/es6-extensions');
    }

    // Windows users can't run grunt in a subdirectory, so allow them to set
    // the root by passing --root=path/to/dir.
    if (grunt.option('root')) {
        var root = grunt.option('root');
        if (grunt.file.exists(__dirname, root)) {
            cwd = path.join(__dirname, root);
            grunt.log.ok('Setting root to ' + cwd);
        } else {
            grunt.fail.fatal('Setting root to ' + root + ' failed - path does not exist');
        }
    }

    var inAMD = path.basename(cwd) == 'amd';

    // Globbing pattern for matching all AMD JS source files.
    var amdSrc = [inAMD ? cwd + '/src/*.js' : '**/amd/src/*.js'];

    // Non AMD JS which still needs to uglify through grunt
    var independentSrc = [
        cwd + '/totara/core/js/lib/src/*.js',
        cwd + '/lib/javascript_polyfill/src/*.js'
    ];

    /**
     * Totara: Test if given path is for a RTL stylesheet.
     *
     * @param {String} path
     * @return {Boolean}
     */
    var isRTLStylesheet = function(path) {
         return path.match(/-rtl\.css$/);
    };

    /**
     * Totara: Is path is inside a theme with CSS that Grunt should preprocess?
     *
     * @param {String} path
     * @return {Boolean}
     */
    var preprocessTheme = function(path) {
        var dontProcess = [
            'base'
        ];

        for (var i = 0; i < dontProcess.length; i++) {
            if (grunt.file.isMatch('**/theme/' + dontProcess[i] + '/**', path)) {
                return false;
            }
        }

        return true;
    };

    // Totara: Compilation of Less in core / themes
    // source files based on current dir.
    var localLess = grunt.file.isDir(cwd, 'less');
    var customThemeDir = grunt.option('themedir') || '';
    var inTheme = false;

    // Standard theme location.
    if (path.basename(path.dirname(cwd)) === 'theme') {
        inTheme = true;
    }

    // Custom theme directory.
    if (path.basename(path.dirname(cwd)) === path.basename(customThemeDir)) {
        inTheme = true;
    }

    // Globbing pattern for Less source files.
    var lessSrc;
    var themeStylelintIgnores;

    if (inTheme) {
        // Single theme less only.
        lessSrc = [cwd + '/less/*.less'];
        themeStylelintIgnores = [cwd + '/.stylelintignore'];
        grunt.verbose.writeln('Current directory is a theme.');
    } else if (localLess) {
        // Single component less only.
        lessSrc = [cwd + '/less/styles.less'];
        themeStylelintIgnores = [cwd + '/.stylelintignore'];
        grunt.verbose.writeln('Detected local less directory.');
    } else {
        // All theme and component less files.
        lessSrc = [
            '**/less/styles.less',
            'theme/*/less/*.less'
        ];
        themeStylelintIgnores = ['theme/*/.stylelintignore'];
    }

    /**
     * Generate destination paths for compiled Less files.
     *
     * @param {String} destPath The current destination
     * @param {String} srcPath The  matched src path
     * @return {String} The rewritten destination path.
     */
    var less_rename = function(destPath, srcPath) {
        var themePath = false;
        var upThreeDirs = path.basename(path.dirname(path.dirname(path.dirname(srcPath))));
        var customThemeDir = path.basename(grunt.config('themedir') || '');

        if (upThreeDirs === 'theme' || upThreeDirs === customThemeDir) {
            themePath = true;
        }

        // In themes CSS files are stored in styles directory.
        if (themePath === true) {
            var filename = path.basename(srcPath, '.less') + '.css';
            return path.join(path.dirname(path.dirname(srcPath)), 'style', filename);
        }

        // Component - styles.css file only.
        return path.join(path.dirname(path.dirname(srcPath)), 'styles.css');
    };

    var rtlSrc = 'theme/roots/style/*.css';

    if (inTheme) {
        // Single theme only. Ignore files with noprocess suffix.
        // These are intended to contain non-standard CSS placeholders
        // which cause a fatal error.
        rtlSrc = [cwd + '/style/*.css', '!' + cwd + '/style/*-noprocess.css'];
    } else if (localLess) {
        rtlSrc = [];
    } else {
        // All theme style files. Ignore *-noprocess.css files as above.
        rtlSrc = ['theme/*/style/*.css', '!theme/*/style/*-noprocess.css'];
    }

    /**
     * Rewrite destination path for RTL styles.
     *
     * @param {String} destPath
     * @param {String} srcPath
     * @return {String}
     */
    var rtl_rename = function(destPath, srcPath) {
        return srcPath.replace('.css', '-rtl.css');
    };

    /**
     * Filter expanded RTL source matches.
     *
     * @param {String} srcPath
     * @return {Boolean}
     */
    var rtl_filter = function(srcPath) {

        if (!preprocessTheme(srcPath)) {
            return false;
        }

        // Don't flip RTL files.
        return !isRTLStylesheet(srcPath);
    };

    // Imports are tried in these locations:
    //  1/ current directory
    //  2/ theme and themedir directories
    //  3/ dirroot directory
    var lessImportPaths = ['theme'];

    // Facilitate working with custom $CFG->themedir.
    if (customThemeDir !== '') {
        customThemeDir = path.resolve(cwd, customThemeDir);
        if (grunt.file.isDir(customThemeDir)) {
            grunt.log.ok("Adding custom themedir '" + customThemeDir + "' to less import search paths.");
            lessImportPaths.push(customThemeDir);
            if (!inTheme) {
                grunt.log.ok("Adding custom themedir '" + customThemeDir + "' to less sources.");
                lessSrc.push(customThemeDir + '/*/less/*.less');
                grunt.log.ok("Adding custom themedir '" + customThemeDir + "' to RTL sources.");
                rtlSrc.push(customThemeDir + '/*/style/*.css');
            }
        } else {
            grunt.fail.fatal("Custom themedir '" + customThemeDir + "' is not accessible.");
        }
    }

    // Auto prefixer source globs.
    var prefixSrc;

    if (inTheme) {
        // Current theme only.
        prefixSrc = rtlSrc;
    } else if (localLess) {
        // Single component only.
        prefixSrc = [cwd + '/styles.css'];
    } else {
        // All styles compiled from Less.
        prefixSrc = [
            rtlSrc,
            '**/styles.css'
        ];
    }

    /**
     * Totara: Filter out styles not generated from Less.
     *
     * @param {String} srcPath
     * @return {Boolean}
     */
    var prefix_filter = function(srcPath) {

        // RTL stylesheets are generated from the result of this processing.
        if (isRTLStylesheet(srcPath)) {
            return false;
        }

        if (!preprocessTheme(srcPath)) {
            return false;
        }

        // In these cases we know sources are ok.
        if (localLess || inTheme) {
            return true;
        }

        // Theme styles were included based on RTL sources so they are also ok.
        if (grunt.file.isMatch('**/theme/*/style/*.css', srcPath)) {
            return true;
        }

        // Is there a less/styles.less locally?
        return grunt.file.isFile(path.dirname(srcPath), 'less', 'styles.css');
    };

    // Create an array of less processed paths, we want to ensure that css files processed
    // from less are not css linted.
    var lessProcessedPaths = (function() {
        var lessSrcFiles = grunt.file.expand({matchBase: true}, lessSrc),
            cssFiles = [];
        lessSrcFiles.forEach(function(lessFile) {
            var cssFile = less_rename(null, lessFile);
            if (cssFiles.indexOf(cssFile) === -1) {
                cssFiles.push(cssFile);
                if (rtl_filter(cssFile)) {
                    cssFiles.push(rtl_rename(null, cssFile)); // And ignore the rtl version that gets compiled.
                }
            }
        });
        return cssFiles;
    })();

    // Project configuration.
    grunt.initConfig({
        eslint: {
            // Even though warnings dont stop the build we don't display warnings by default because
            // at this moment we've got too many core warnings.
            options: {quiet: !grunt.option('show-lint-warnings')},
            amd: {
              src: amdSrc,
              // Check AMD with some slightly stricter rules.
              rules: {
                'no-unused-vars': 'error',
                'no-implicit-globals': 'error'
              }
            },
            // Check YUI module source files.
            yui: {
               src: ['**/yui/src/**/*.js', '!*/**/yui/src/*/meta/*.js'],
               options: {
                   // Disable some rules which we can't safely define for YUI rollups.
                   rules: {
                     'no-undef': 'off',
                     'no-unused-vars': 'off',
                     'no-unused-expressions': 'off'
                   }
               }
            }
        },
        uglify: {
            amd: {
                files: [{
                    expand: true,
                    src: amdSrc,
                    rename: uglifyRename
                }],
                options: {report: 'none'}
            },
            independent: {
                files: [{
                    expand: true,
                    src: independentSrc,
                    rename: uglifyRename
                }],
                options: {report: 'none'}
            }
        },
        less: {
            // Totara: Dedicated Less target.
            totara: {
                options: {
                    compress: true,
                    paths: lessImportPaths
                },
                files: [{
                    expand: true,
                    src: lessSrc,
                    rename: less_rename
                }]
            }
        },
        watch: {
            options: {
                nospawn: true // We need not to spawn so config can be changed dynamically.
            },
            amd: {
                files: ['**/amd/src/**/*.js'],
                tasks: ['amd']
            },
            yui: {
                files: ['**/yui/src/**/*.js'],
                tasks: ['yui']
            },
            gherkinlint: {
                files: ['**/tests/behat/*.feature'],
                tasks: ['gherkinlint']
            },
            // Totara: Add less watch target.
            less: {
                files: ['**/less/**/*.less', '!**/node_modules/**/*'],
                tasks: ['less:totara', 'postcss:prefix', 'postcss:rtl']
            },
        },
        shifter: {
            options: {
                recursive: true,
                paths: [cwd]
            }
        },
        // Totara: PostCSS for prefixing and theme RTL.
        postcss: {
            prefix: {
                options: {
                    processors: [
                        require('autoprefixer')({browsers: 'last 2 versions, ie >= 9'})
                    ]
                },
                files: [{
                    expand: true,
                    src: prefixSrc,
                    filter: prefix_filter
                }]
            },
            rtl: {
                options: {
                    processors: [
                        require('rtlcss')()
                    ]
                },
                files: [{
                    expand: true,
                    src: rtlSrc,
                    rename: rtl_rename,
                    filter: rtl_filter
                }]
            }
        },
        stylelint: {
            less: {
                options: {
                    syntax: 'less',
                    configOverrides: {
                        rules: {
                            // These rules have to be disabled in .stylelintrc for scss compat.
                            "at-rule-no-unknown": true,
                            "no-browser-hacks": [true, {"severity": "warning"}]
                        }
                    }
                },
                src: ['theme/**/*.less']
            },
            scss: {
                options: {syntax: 'scss'},
                src: ['*/**/*.scss']
            },
            css: {
                src: ['*/**/*.css'],
                options: {
                    configOverrides: {
                        rules: {
                            // These rules have to be disabled in .stylelintrc for scss compat.
                            "at-rule-no-unknown": true,
                            "no-browser-hacks": [true, {"severity": "warning"}]
                        }
                    }
                }
            }
        },
        gherkinlint: {
            options: {
                files: ['**/tests/behat/*.feature'],
            }
        },
    });

    var getThemeStylelintIgnores = function() {
        var themeStylelintIgnoreFiles = grunt.file.expand({matchBase: true}, themeStylelintIgnores);
        var customIgnores = [];
        themeStylelintIgnoreFiles.forEach(function(file) {
            var base = file.replace(/\.stylelintignore$/, '');
            var doc = grunt.file.read(file);
            var ignores = doc.split("\n");
            ignores.forEach(function(line) {
                if (line.trim() !== '') {
                    customIgnores.push(base + line);
                }
            });
        });
        return customIgnores;
    };

    /**
     * Generate ignore files (utilising thirdpartylibs.xml data)
     */
    tasks.ignorefiles = function() {
        // An array of paths to third party directories.
        var thirdPartyPaths = getThirdPartyPathsFromXML();
        var customIgnores = getThemeStylelintIgnores();
        // Generate .eslintignore.
        var eslintIgnores = ['# Generated by "grunt ignorefiles"', '*/**/yui/src/*/meta/', '*/**/build/'].concat(thirdPartyPaths);
        grunt.file.write('.eslintignore', eslintIgnores.join('\n'));
        // Generate .stylelintignore.
        var stylelintIgnores = [
            '# Generated by "grunt ignorefiles"',
        ]
            .concat(['# CSS compiled from less'], lessProcessedPaths) // TOTARA: We add css files processed from less.
            .concat(['# Third party libraries'], thirdPartyPaths) // Add third party libraries at the end.
            .concat(['# Custom theme ignores'], customIgnores); // Add custom ignores from all themes
        grunt.file.write('.stylelintignore', stylelintIgnores.join('\n'));
    };

    /**
     * Shifter task. Is configured with a path to a specific file or a directory,
     * in the case of a specific file it will work out the right module to be built.
     *
     * Note that this task runs the invidiaul shifter jobs async (becase it spawns
     * so be careful to to call done().
     */
    tasks.shifter = function() {
        var done = this.async(),
            options = grunt.config('shifter.options');

        // Run the shifter processes one at a time to avoid confusing output.
        async.eachSeries(options.paths, function(src, filedone) {
            var args = [];
            args.push(path.normalize(__dirname + '/node_modules/shifter/bin/shifter'));

            // Always ignore the node_modules directory.
            args.push('--excludes', 'node_modules');

            // Skip lint, we've got eslint now.
            args.push('--no-lint');

            // Determine the most appropriate options to run with based upon the current location.
            if (grunt.file.isMatch('**/yui/**/*.js', src)) {
                // When passed a JS file, build our containing module (this happen with
                // watch).
                grunt.log.debug('Shifter passed a specific JS file');
                src = path.dirname(path.dirname(src));
                options.recursive = false;
            } else if (grunt.file.isMatch('**/yui/src', src)) {
                // When in a src directory --walk all modules.
                grunt.log.debug('In a src directory');
                args.push('--walk');
                options.recursive = false;
            } else if (grunt.file.isMatch('**/yui/src/*', src)) {
                // When in module, only build our module.
                grunt.log.debug('In a module directory');
                options.recursive = false;
            } else if (grunt.file.isMatch('**/yui/src/*/js', src)) {
                // When in module src, only build our module.
                grunt.log.debug('In a source directory');
                src = path.dirname(src);
                options.recursive = false;
            }

            if (grunt.option('watch')) {
                grunt.fail.fatal('The --watch option has been removed, please use `grunt watch` instead');
            }

            // Add the stderr option if appropriate
            if (grunt.option('verbose')) {
                args.push('--lint-stderr');
            }

            if (grunt.option('no-color')) {
                args.push('--color=false');
            }

            var execShifter = function() {

                grunt.log.ok("Running shifter on " + src);
                grunt.util.spawn({
                    cmd: "node",
                    args: args,
                    opts: {cwd: src, stdio: 'inherit', env: process.env}
                }, function(error, result, code) {
                    if (code) {
                        grunt.fail.fatal('Shifter failed with code: ' + code);
                    } else {
                        grunt.log.ok('Shifter build complete.');
                        filedone();
                    }
                });
            };

            // Actually run shifter.
            if (!options.recursive) {
                execShifter();
            } else {
                // Check that there are yui modules otherwise shifter ends with exit code 1.
                if (grunt.file.expand({cwd: src}, '**/yui/src/**/*.js').length > 0) {
                    args.push('--recursive');
                    execShifter();
                } else {
                    grunt.log.ok('No YUI modules to build.');
                    filedone();
                }
            }
        }, done);
    };

    tasks.gherkinlint = function() {
        var done = this.async(),
            options = grunt.config('gherkinlint.options');

        var args = grunt.file.expand(options.files);
        args.unshift(path.normalize(__dirname + '/node_modules/.bin/gherkin-lint'));
        grunt.util.spawn({
            cmd: 'node',
            args: args,
            opts: {stdio: 'inherit', env: process.env}
        }, function(error, result, code) {
            // Propagate the exit code.
            done(code === 0);
        });
    };

    tasks.startup = function() {
        // Are we in a YUI directory?
        if (path.basename(path.resolve(cwd, '../../')) == 'yui') {
            grunt.task.run('yui');
        // Are we in an AMD directory?
        } else if (inAMD) {
            grunt.task.run('amd');
        } else {
            // Run them all!.
            grunt.task.run('css');
            grunt.task.run('js');
            grunt.task.run('gherkinlint');
        }
    };

    // On watch, we dynamically modify config to build only affected files. This
    // method is slightly complicated to deal with multiple changed files at once (copied
    // from the grunt-contrib-watch readme).
    var changedFiles = Object.create(null);
    var onChange = grunt.util._.debounce(function() {
          var files = Object.keys(changedFiles);
          grunt.config('eslint.amd.src', files);
          grunt.config('eslint.yui.src', files);
          grunt.config('uglify.amd.files', [{expand: true, src: files, rename: uglifyRename}]);
          grunt.config('shifter.options.paths', files);
          grunt.config('stylelint.less.src', files);
          grunt.config('gherkinlint.options.files', files);
          changedFiles = Object.create(null);
    }, 200);

    grunt.event.on('watch', function(action, filepath) {
          changedFiles[filepath] = action;
          onChange();
    });

    // Register NPM tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-eslint');
    grunt.loadNpmTasks('grunt-stylelint');

    // Totara: Load PostCSS.
    grunt.loadNpmTasks('grunt-postcss');

    // Register JS tasks.
    grunt.registerTask('shifter', 'Run Shifter against the current directory', tasks.shifter);
    grunt.registerTask('gherkinlint', 'Run gherkinlint against the current directory', tasks.gherkinlint);
    grunt.registerTask('ignorefiles', 'Generate ignore files for linters', tasks.ignorefiles);
    grunt.registerTask('yui', ['eslint:yui', 'shifter']);
    grunt.registerTask('amd', ['eslint:amd', 'uglify']);
    grunt.registerTask('js', ['amd', 'yui']);

    // Register CSS taks.
    grunt.registerTask('css', [
        // 'stylelint:scss', TOTARA: commented out as we don't have any and the library doesn't like that.
        'stylelint:less',
        'less:totara',
        'postcss:prefix',
        'postcss:rtl',
        'stylelint:css'
    ]);

    // Register the startup task.
    grunt.registerTask('startup', 'Run the correct tasks for the current directory', tasks.startup);

    // Register the default task.
    grunt.registerTask('default', ['startup']);
};
