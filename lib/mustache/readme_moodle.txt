Description of Mustache library import into Totara.

1) Download the latest version of mustache.php from upstream (found
at https://github.com/bobthecow/mustache.php/releases)

2) Move the src/ and LICENSE file into lib/mustache

e.g.
wget https://github.com/bobthecow/mustache.php/archive/v2.11.1.zip
unzip v2.11.1.zip
cd mustache.php-2.11.1/
mv src /path/to/moodle/lib/mustache/
mv LICENSE /path/to/moodle/lib/mustache/

3) Add dynamic partial support from TL-9430
