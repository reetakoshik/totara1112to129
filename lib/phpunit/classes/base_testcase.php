<?php
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

/**
 * Base test case class.
 *
 * @package    core
 * @category   test
 * @author     Tony Levi <tony.levi@blackboard.com>
 * @copyright  2015 Blackboard (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Base class for PHPUnit test cases customised for Moodle
 *
 * It is intended for functionality common to both basic and advanced_testcase.
 *
 * @package    core
 * @category   test
 * @author     Tony Levi <tony.levi@blackboard.com>
 * @copyright  2015 Blackboard (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_testcase extends \PHPUnit\Framework\TestCase {
    // @codingStandardsIgnoreStart

    /** @var float tracks the total time waiting for the next second */
    protected $totalwaitforsecond;

    /** @var int last start of class testing */
    static $lastclassstarttime = null;

    // Following code is legacy code from phpunit to support assertTag
    // and assertNotTag.

    /**
     * Note: we are overriding this method to remove the deprecated error
     * @see https://tracker.moodle.org/browse/MDL-47129
     *
     * @param  array   $matcher
     * @param  string  $actual
     * @param  string  $message
     * @param  boolean $ishtml
     *
     * @deprecated 3.0
     */
    public static function assertTag($matcher, $actual, $message = '', $ishtml = true) {
        $dom = \PHPUnit\Util\XML::load($actual, $ishtml);
        $tags = self::findNodes($dom, $matcher, $ishtml);
        $matched = count($tags) > 0 && $tags[0] instanceof DOMNode;
        self::assertTrue($matched, $message);
    }

    /**
     * Note: we are overriding this method to remove the deprecated error
     * @see https://tracker.moodle.org/browse/MDL-47129
     *
     * @param  array   $matcher
     * @param  string  $actual
     * @param  string  $message
     * @param  boolean $ishtml
     *
     * @deprecated 3.0
     */
    public static function assertNotTag($matcher, $actual, $message = '', $ishtml = true) {
        $dom = \PHPUnit\Util\XML::load($actual, $ishtml);
        $tags = self::findNodes($dom, $matcher, $ishtml);
        $matched = isset($tags[0]) && $tags[0] instanceof DOMNode; // Totara: count is slow and cannot be used on false!
        self::assertFalse($matched, $message);
    }

    /**
     * Validate list of keys in the associative array.
     *
     * @param array $hash
     * @param array $validKeys
     *
     * @return array
     *
     * @throws \PHPUnit\Framework\Exception
     */
    public static function assertValidKeys(array $hash, array $validKeys) {
        $valids = array();

        // Normalize validation keys so that we can use both indexed and
        // associative arrays.
        foreach ($validKeys as $key => $val) {
            is_int($key) ? $valids[$val] = null : $valids[$key] = $val;
        }

        $validKeys = array_keys($valids);

        // Check for invalid keys.
        foreach ($hash as $key => $value) {
            if (!in_array($key, $validKeys)) {
                $unknown[] = $key;
            }
        }

        if (!empty($unknown)) {
            throw new \PHPUnit\Framework\Exception(
                'Unknown key(s): ' . implode(', ', $unknown)
            );
        }

        // Add default values for any valid keys that are empty.
        foreach ($valids as $key => $value) {
            if (!isset($hash[$key])) {
                $hash[$key] = $value;
            }
        }

        return $hash;
    }

    /**
     * Parse out the options from the tag using DOM object tree.
     *
     * @param DOMDocument $dom
     * @param array       $options
     * @param bool        $isHtml
     *
     * @return array
     */
    public static function findNodes(DOMDocument $dom, array $options, $isHtml = true) {
        $valid = array(
            'id', 'class', 'tag', 'content', 'attributes', 'parent',
            'child', 'ancestor', 'descendant', 'children', 'adjacent-sibling'
        );

        $filtered = array();
        $options  = self::assertValidKeys($options, $valid);

        // find the element by id
        if ($options['id']) {
            $options['attributes']['id'] = $options['id'];
        }

        if ($options['class']) {
            $options['attributes']['class'] = $options['class'];
        }

        $nodes = array();

        // find the element by a tag type
        if ($options['tag']) {
            if ($isHtml) {
                $elements = self::getElementsByCaseInsensitiveTagName(
                    $dom,
                    $options['tag']
                );
            } else {
                $elements = $dom->getElementsByTagName($options['tag']);
            }

            foreach ($elements as $element) {
                $nodes[] = $element;
            }

            if (empty($nodes)) {
                return false;
            }
        } // no tag selected, get them all
        else {
            $tags = array(
                'a', 'abbr', 'acronym', 'address', 'area', 'b', 'base', 'bdo',
                'big', 'blockquote', 'body', 'br', 'button', 'caption', 'cite',
                'code', 'col', 'colgroup', 'dd', 'del', 'div', 'dfn', 'dl',
                'dt', 'em', 'fieldset', 'form', 'frame', 'frameset', 'h1', 'h2',
                'h3', 'h4', 'h5', 'h6', 'head', 'hr', 'html', 'i', 'iframe',
                'img', 'input', 'ins', 'kbd', 'label', 'legend', 'li', 'link',
                'map', 'meta', 'noframes', 'noscript', 'object', 'ol', 'optgroup',
                'option', 'p', 'param', 'pre', 'q', 'samp', 'script', 'select',
                'small', 'span', 'strong', 'style', 'sub', 'sup', 'table',
                'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'title',
                'tr', 'tt', 'ul', 'var',
                // HTML5
                'article', 'aside', 'audio', 'bdi', 'canvas', 'command',
                'datalist', 'details', 'dialog', 'embed', 'figure', 'figcaption',
                'footer', 'header', 'hgroup', 'keygen', 'mark', 'meter', 'nav',
                'output', 'progress', 'ruby', 'rt', 'rp', 'track', 'section',
                'source', 'summary', 'time', 'video', 'wbr'
            );

            foreach ($tags as $tag) {
                if ($isHtml) {
                    $elements = self::getElementsByCaseInsensitiveTagName(
                        $dom,
                        $tag
                    );
                } else {
                    $elements = $dom->getElementsByTagName($tag);
                }

                foreach ($elements as $element) {
                    $nodes[] = $element;
                }
            }

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by attributes
        if ($options['attributes']) {
            foreach ($nodes as $node) {
                $invalid = false;

                foreach ($options['attributes'] as $name => $value) {
                    // match by regexp if like "regexp:/foo/i"
                    if (preg_match('/^regexp\s*:\s*(.*)/i', $value, $matches)) {
                        if (!preg_match($matches[1], $node->getAttribute($name))) {
                            $invalid = true;
                        }
                    } // class can match only a part
                    elseif ($name == 'class') {
                        // split to individual classes
                        $findClasses = explode(
                            ' ',
                            preg_replace("/\s+/", ' ', $value)
                        );

                        $allClasses = explode(
                            ' ',
                            preg_replace("/\s+/", ' ', $node->getAttribute($name))
                        );

                        // make sure each class given is in the actual node
                        foreach ($findClasses as $findClass) {
                            if (!in_array($findClass, $allClasses)) {
                                $invalid = true;
                            }
                        }
                    } // match by exact string
                    else {
                        if ($node->getAttribute($name) !== (string) $value) {
                            $invalid = true;
                        }
                    }
                }

                // if every attribute given matched
                if (!$invalid) {
                    $filtered[] = $node;
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by content
        if ($options['content'] !== null) {
            foreach ($nodes as $node) {
                $invalid = false;

                // match by regexp if like "regexp:/foo/i"
                if (preg_match('/^regexp\s*:\s*(.*)/i', $options['content'], $matches)) {
                    if (!preg_match($matches[1], self::getNodeText($node))) {
                        $invalid = true;
                    }
                } // match empty string
                elseif ($options['content'] === '') {
                    if (self::getNodeText($node) !== '') {
                        $invalid = true;
                    }
                } // match by exact string
                elseif (strstr(self::getNodeText($node), $options['content']) === false) {
                    $invalid = true;
                }

                if (!$invalid) {
                    $filtered[] = $node;
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by parent node
        if ($options['parent']) {
            $parentNodes = self::findNodes($dom, $options['parent'], $isHtml);
            $parentNode  = isset($parentNodes[0]) ? $parentNodes[0] : null;

            foreach ($nodes as $node) {
                if ($parentNode !== $node->parentNode) {
                    continue;
                }

                $filtered[] = $node;
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by child node
        if ($options['child']) {
            $childNodes = self::findNodes($dom, $options['child'], $isHtml);
            $childNodes = !empty($childNodes) ? $childNodes : array();

            foreach ($nodes as $node) {
                foreach ($node->childNodes as $child) {
                    foreach ($childNodes as $childNode) {
                        if ($childNode === $child) {
                            $filtered[] = $node;
                        }
                    }
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by adjacent-sibling
        if ($options['adjacent-sibling']) {
            $adjacentSiblingNodes = self::findNodes($dom, $options['adjacent-sibling'], $isHtml);
            $adjacentSiblingNodes = !empty($adjacentSiblingNodes) ? $adjacentSiblingNodes : array();

            foreach ($nodes as $node) {
                $sibling = $node;

                while ($sibling = $sibling->nextSibling) {
                    if ($sibling->nodeType !== XML_ELEMENT_NODE) {
                        continue;
                    }

                    foreach ($adjacentSiblingNodes as $adjacentSiblingNode) {
                        if ($sibling === $adjacentSiblingNode) {
                            $filtered[] = $node;
                            break;
                        }
                    }

                    break;
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by ancestor
        if ($options['ancestor']) {
            $ancestorNodes = self::findNodes($dom, $options['ancestor'], $isHtml);
            $ancestorNode  = isset($ancestorNodes[0]) ? $ancestorNodes[0] : null;

            foreach ($nodes as $node) {
                $parent = $node->parentNode;

                while ($parent && $parent->nodeType != XML_HTML_DOCUMENT_NODE) {
                    if ($parent === $ancestorNode) {
                        $filtered[] = $node;
                    }

                    $parent = $parent->parentNode;
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by descendant
        if ($options['descendant']) {
            $descendantNodes = self::findNodes($dom, $options['descendant'], $isHtml);
            $descendantNodes = !empty($descendantNodes) ? $descendantNodes : array();

            foreach ($nodes as $node) {
                foreach (self::getDescendants($node) as $descendant) {
                    foreach ($descendantNodes as $descendantNode) {
                        if ($descendantNode === $descendant) {
                            $filtered[] = $node;
                        }
                    }
                }
            }

            $nodes    = $filtered;
            $filtered = array();

            if (empty($nodes)) {
                return false;
            }
        }

        // filter by children
        if ($options['children']) {
            $validChild   = array('count', 'greater_than', 'less_than', 'only');
            $childOptions = self::assertValidKeys(
                $options['children'],
                $validChild
            );

            foreach ($nodes as $node) {
                $childNodes = $node->childNodes;

                foreach ($childNodes as $childNode) {
                    if ($childNode->nodeType !== XML_CDATA_SECTION_NODE &&
                        $childNode->nodeType !== XML_TEXT_NODE) {
                        $children[] = $childNode;
                    }
                }

                // we must have children to pass this filter
                if (!empty($children)) {
                    // exact count of children
                    if ($childOptions['count'] !== null) {
                        if (count($children) !== $childOptions['count']) {
                            break;
                        }
                    } // range count of children
                    elseif ($childOptions['less_than']    !== null &&
                        $childOptions['greater_than'] !== null) {
                        if (count($children) >= $childOptions['less_than'] ||
                            count($children) <= $childOptions['greater_than']) {
                            break;
                        }
                    } // less than a given count
                    elseif ($childOptions['less_than'] !== null) {
                        if (count($children) >= $childOptions['less_than']) {
                            break;
                        }
                    } // more than a given count
                    elseif ($childOptions['greater_than'] !== null) {
                        if (count($children) <= $childOptions['greater_than']) {
                            break;
                        }
                    }

                    // match each child against a specific tag
                    if ($childOptions['only']) {
                        $onlyNodes = self::findNodes(
                            $dom,
                            $childOptions['only'],
                            $isHtml
                        );

                        // try to match each child to one of the 'only' nodes
                        foreach ($children as $child) {
                            $matched = false;

                            foreach ($onlyNodes as $onlyNode) {
                                if ($onlyNode === $child) {
                                    $matched = true;
                                }
                            }

                            if (!$matched) {
                                break 2;
                            }
                        }
                    }

                    $filtered[] = $node;
                }
            }

            $nodes = $filtered;

            if (empty($nodes)) {
                return;
            }
        }

        // return the first node that matches all criteria
        return !empty($nodes) ? $nodes : array();
    }

    /**
     * Recursively get flat array of all descendants of this node.
     *
     * @param DOMNode $node
     *
     * @return array
     */
    protected static function getDescendants(DOMNode $node) {
        $allChildren = array();
        $childNodes  = $node->childNodes ? $node->childNodes : array();

        foreach ($childNodes as $child) {
            if ($child->nodeType === XML_CDATA_SECTION_NODE ||
                $child->nodeType === XML_TEXT_NODE) {
                continue;
            }

            $children    = self::getDescendants($child);
            $allChildren = array_merge($allChildren, $children, array($child));
        }

        return isset($allChildren) ? $allChildren : array();
    }

    /**
     * Gets elements by case insensitive tagname.
     *
     * @param DOMDocument $dom
     * @param string      $tag
     *
     * @return DOMNodeList
     */
    protected static function getElementsByCaseInsensitiveTagName(DOMDocument $dom, $tag) {
        $elements = $dom->getElementsByTagName(strtolower($tag));

        if ($elements->length == 0) {
            $elements = $dom->getElementsByTagName(strtoupper($tag));
        }

        return $elements;
    }

    /**
     * Get the text value of this node's child text node.
     *
     * @param DOMNode $node
     *
     * @return string
     */
    protected static function getNodeText(DOMNode $node) {
        if (!$node->childNodes instanceof DOMNodeList) {
            return '';
        }

        $result = '';

        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeType === XML_TEXT_NODE ||
                $childNode->nodeType === XML_CDATA_SECTION_NODE) {
                $result .= trim($childNode->data) . ' ';
            } else {
                $result .= self::getNodeText($childNode);
            }
        }

        return str_replace('  ', ' ', $result);
    }

    /**
     * @internal
     * @return string
     */
    public static function get_profiling_filepath_method() {
        global $DB;
        return __DIR__ . '/../../../phpunit_profile_methods_' . $DB->get_dbfamily() . '.csv';
    }

    /**
     * @internal
     * @return string
     */
    public static function get_profiling_filepath_class() {
        global $DB;
        return __DIR__ . '/../../../phpunit_profile_classes_' . $DB->get_dbfamily() . '.csv';
    }

    public function runBare(): void {
        if (!defined('PHPUNIT_PROFILING')) {
            parent::runBare();
            return;
        }

        static $fp = null;
        if ($fp === null) {
            $filepath = self::get_profiling_filepath_method();
            @unlink($filepath);
            $fp = fopen($filepath, 'w+');
            // The file handle will get closed automatically at the end when phpunit terminates.
            fputcsv($fp, array('execution time', 'waiting time', 'memory increase', 'method', 'class'));
        }

        $startmemory = memory_get_usage();

        $this->totalwaitforsecond = 0;
        $timestart = microtime(true);
        parent::runBare();
        $totaltime = microtime(true) - $timestart;

        $name = $this->getName(true);
        $classname = get_class($this);
        $memdiff = memory_get_usage() - $startmemory;

        fputcsv($fp, array(number_format($totaltime, 2), number_format($this->totalwaitforsecond, 2), $memdiff, $name, $classname));
    }

    public static function tearDownAfterClass() {
        if (!defined('PHPUNIT_PROFILING')) {
            parent::tearDownAfterClass();
            return;
        }

        if (self::$lastclassstarttime === null) {
            @unlink(self::get_profiling_filepath_class());
            // Static variables do not work here, let's use static property instead.
            self::$lastclassstarttime = filectime(self::get_profiling_filepath_method());
        }

        $totaltime = microtime(true) - self::$lastclassstarttime;
        $classname = get_called_class();

        $fp = fopen(self::get_profiling_filepath_class(), 'a+');
        fputcsv($fp, array(number_format($totaltime, 2), $classname));
        fclose($fp);

        self::$lastclassstarttime = microtime(true);
    }

    /**
     * @param mixed      $exception
     * @param string     $message   Null means we do not check message at all, string (even empty) means we do. Default: null.
     * @param int|string $code      Null means we do not check code at all, non-null means we do.
     *
     * @deprecated Method was removed in PHPUnit 6 and is deprecated since Totara 13; use expectException() instead
     */
    public function setExpectedException($exception, $message = null, $code = null) {
        debugging("PHPUnits setExpectedException() method was removed in PHPUnit 6 and is deprecated since Totara 13; use expectException() instead.", DEBUG_DEVELOPER);

        if (null !== $message && !is_string($message)) {
            throw \PHPUnit\Util\InvalidArgumentHelper::factory(2, 'string');
        }

        $this->expectException($exception);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }

        if ($code !== null) {
            $this->expectExceptionCode($code);
        }
    }

    /**
     * @param mixed  $exception
     * @param string $messageRegExp
     * @param int    $code
     *
     * @deprecated Method was removed in PHPUnit 6 and is deprecated since Totara 13; use expectExceptionMessageRegExp() instead
     */
    public function setExpectedExceptionRegExp($exception, $messageRegExp = '', $code = null) {
        debugging("PHPUnits setExpectedExceptionRegExp() method was removed in PHPUnit 6 and is deprecated since Totara 13; use expectException() and expectExceptionMessageRegExp() instead.", DEBUG_DEVELOPER);

        if (!is_string($messageRegExp)) {
            throw \PHPUnit\Util\InvalidArgumentHelper::factory(2, 'string');
        }

        $this->expectException($exception);
        $this->expectExceptionMessageRegExp($messageRegExp);

        if ($code !== null) {
            $this->expectExceptionCode($code);
        }
    }

    /**
     * Returns a mock object for the specified class.
     *
     * @param string     $originalClassName       Name of the class to mock.
     * @param array|null $methods                 When provided, only methods whose names are in the array
     *                                            are replaced with a configurable test double. The behavior
     *                                            of the other methods is not changed.
     *                                            Providing null means that no methods will be replaced.
     * @param array      $arguments               Parameters to pass to the original class' constructor.
     * @param string     $mockClassName           Class name for the generated test double class.
     * @param bool       $callOriginalConstructor Can be used to disable the call to the original class' constructor.
     * @param bool       $callOriginalClone       Can be used to disable the call to the original class' clone constructor.
     * @param bool       $callAutoload            Can be used to disable __autoload() during the generation of the test double class.
     * @param bool       $cloneArguments
     * @param bool       $callOriginalMethods
     * @param object     $proxyTarget
     *
     * @return PHPUnit\Framework\MockObject\MockObject
     *
     * @deprecated Method was removed in PHPUnit 6 and is deprecated since Totara 13; use createMock() or getMockBuilder() instead
     */
    public function getMock($originalClassName, $methods = [], array $arguments = [], $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $cloneArguments = false, $callOriginalMethods = false, $proxyTarget = null) {
        debugging("PHPUnits getMock() method was removed in PHPUnit 6 and is deprecated since Totara 13; use createMock() or getMockBuilder() instead.", DEBUG_DEVELOPER);

        $builder = $this->getMockBuilder($originalClassName);
        if (!is_null($methods)) {
            $builder->setMethods($methods);
        }
        if (!empty($arguments)) {
            $builder->setConstructorArgs($arguments);
        }
        if (!empty($mockClassName)) {
            $builder->setMockClassName($mockClassName);
        }
        if ($callOriginalConstructor) {
            $builder->enableOriginalConstructor();
        } else {
            $builder->disableOriginalConstructor();
        }
        if ($callOriginalClone) {
            $builder->enableOriginalClone();
        } else {
            $builder->disableOriginalClone();
        }
        if ($callAutoload) {
            $builder->enableAutoload();
        } else {
            $builder->disableAutoload();
        }
        if ($cloneArguments) {
            $builder->enableArgumentCloning();
        } else {
            $builder->disableArgumentCloning();
        }
        if ($callOriginalMethods) {
            $builder->enableProxyingToOriginalMethods();
        } else {
            $builder->disableProxyingToOriginalMethods();
        }
        if ($proxyTarget) {
            $builder->setProxyTarget($proxyTarget);
        }

        return $builder->getMock();
    }

    /**
     * Returns a mock with disabled constructor object for the specified class.
     *
     * @param string $originalClassName
     *
     * @return PHPUnit\Framework\MockObject\MockObject
     *
     * @deprecated Method was removed in PHPUnit 6 and is deprecated since Totara 13; use createMock() instead
     */
    public function getMockWithoutInvokingTheOriginalConstructor($originalClassName) {
        debugging("PHPUnits getMockWithoutInvokingTheOriginalConstructor() method was removed in PHPUnit 6 and is deprecated since Totara 13; use createMock() instead.", DEBUG_DEVELOPER);

        return $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return bool
     *
     * @deprecated Method was removed in PHPUnit 6 and is deprecated since Totara 13; use hasExpectationOnOutput() instead
     */
    public function hasPerformedExpectationsOnOutput() {
        debugging("PHPUnits hasPerformedExpectationsOnOutput() method was removed in PHPUnit 6 and is deprecated since Totara 13; use hasExpectationOnOutput() instead.", DEBUG_DEVELOPER);

        return $this->hasExpectationOnOutput();
    }

    // @codingStandardsIgnoreEnd
}
