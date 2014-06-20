<?php
/**
 * Tests for Script.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Scripts.class.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/url_generating.lib.php';

/**
 * Tests for Script.class.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_Scripts_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new PMA_Scripts();
        if (! defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Call private functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the private method.
     */
    private function _callPrivateFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_Scripts');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for _includeFile
     *
     * @return void
     *
     * @group medium
     */
    public function testIncludeFile()
    {
        $this->assertEquals(
            '<script type="text/javascript" src="js/get_scripts.js.php?lang=en'
            . '&amp;token=token&amp;scripts[]=common.js"></script>',
            $this->_callPrivateFunction(
                '_includeFiles',
                array(
                    array(
                        array(
                            'has_onload' => false,
                            'filename' => 'common.js',
                            'conditional_ie' => false
                        )
                    )
                )
            )
        );
    }

    /**
     * Test for getDisplay
     *
     * @return void
     */
    public function testGetDisplay()
    {

        $this->object->addFile('common.js');
        $this->object->addEvent('onClick', 'doSomething');

        $this->assertRegExp(
            '@<script type="text/javascript" src="js/get_scripts.js.php\\?lang=en'
            . '&amp;token=token&amp;scripts\\[\\]=common.js"></script><script type="text/'
            . 'javascript">// <!\\[CDATA\\[' . "\n"
            . 'AJAX.scriptHandler.add\\("common.js",1\\);' . "\n"
            . '\\$\\(function\\(\\) \\{AJAX.fireOnload\\("common.js"\\);\\}\\);'
            . "\n" . '\\$\\(window\\).bind\\(\'onClick\', doSomething\\);' . "\n"
            . '// ]]></script>@',
            $this->object->getDisplay()
        );

    }

    /**
     * test for addCode
     *
     * @return void
     */
    public function testAddCode()
    {

        $this->object->addCode('alert(\'CodeAdded\')');

        $this->assertEquals(
            '<script type="text/javascript">// <![CDATA[
alert(\'CodeAdded\')
AJAX.scriptHandler;
$(function() {});
// ]]></script>',
            $this->object->getDisplay()
        );
    }

     /**
     * test for getFiles
     *
     * @return void
     */
    public function testGetFiles()
    {
        // codemirror's onload event is blacklisted
        $this->object->addFile('codemirror/lib/codemirror.js');

        $this->object->addFile('common.js');
        $this->assertEquals(
            array(
                array('name' => 'codemirror/lib/codemirror.js', 'fire' => 0),
                array('name' => 'common.js', 'fire' => 1)
            ),
            $this->object->getFiles()
        );
    }
}
