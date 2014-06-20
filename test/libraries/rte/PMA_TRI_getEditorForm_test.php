<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating trigger editor
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
/*
 * Include to test.
 */
require_once 'libraries/rte/rte_triggers.lib.php';

/**
 * Test for generating trigger editor
 *
 * @package PhpMyAdmin-test
 */
class PMA_TRI_GetEditorForm_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['ServerDefault'] = '';
        $GLOBALS['db'] = 'pma_test';
    }


    /**
     * Test for PMA_TRI_getEditorForm
     *
     * @param string $data    Data for trigger
     * @param array  $matcher Matcher
     *
     * @return void
     *
     * @dataProvider provider_add
     * @group medium
     */
    public function testgetEditorForm_add($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_TRI_setGlobals();
        $this->assertTag($matcher, PMA_TRI_getEditorForm('add', $data), '', false);
    }

    /**
     * Provider for testgetEditorForm_add
     *
     * @return array
     */
    public function provider_add()
    {
        $data = array(
            'item_name'               => '',
            'item_table'              => 'table1',
            'item_original_name'      => '',
            'item_action_timing'      => '',
            'item_event_manipulation' => '',
            'item_definition'         => '',
            'item_definer'            => ''
        );

        return array(
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'add_item'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_name'
                    )
                )
            ),
            array(
                $data,
                 array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_table'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_timing'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_event'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'textarea',
                    'attributes' => array(
                        'name' => 'item_definition'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_definer'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'editor_process_add'
                    )
                )
            )
        );
    }

    /**
     * Test for PMA_TRI_getEditorForm
     *
     * @param string $data    Data for trigger
     * @param array  $matcher Matcher
     *
     * @return void
     *
     * @dataProvider provider_edit
     * @group medium
     */
    public function testgetEditorForm_edit($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_TRI_setGlobals();
        $this->assertTag($matcher, PMA_TRI_getEditorForm('edit', $data), '', false);
    }

    /**
     * Provider for testgetEditorForm_edit
     *
     * @return array
     */
    public function provider_edit()
    {
        $data = array(
            'item_name'               => 'foo',
            'item_table'              => 'table1',
            'item_original_name'      => 'bar',
            'item_action_timing'      => 'BEFORE',
            'item_event_manipulation' => 'INSERT',
            'item_definition'         => 'SET @A=1;',
            'item_definer'            => ''
        );

        return array(
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'edit_item'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_name'
                    )
                )
            ),
            array(
                $data,
                 array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_table'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_timing'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_event'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'textarea',
                    'attributes' => array(
                        'name' => 'item_definition'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_definer'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'editor_process_edit'
                    )
                )
            )
        );
    }

    /**
     * Test for PMA_TRI_getEditorForm
     *
     * @param string $data    Data for trigger
     * @param array  $matcher Matcher
     *
     * @return void
     *
     * @dataProvider provider_ajax
     */
    public function testgetEditorForm_ajax($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = true;
        PMA_TRI_setGlobals();
        $this->assertTag($matcher, PMA_TRI_getEditorForm('edit', $data), '', false);
    }

    /**
     * Provider for testgetEditorForm_ajax
     *
     * @return array
     */
    public function provider_ajax()
    {
        $data = array(
            'item_name'               => 'foo',
            'item_table'              => 'table1',
            'item_original_name'      => 'bar',
            'item_action_timing'      => 'BEFORE',
            'item_event_manipulation' => 'INSERT',
            'item_definition'         => 'SET @A=1;',
            'item_definer'            => ''
        );

        return array(
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'editor_process_edit'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'ajax_request'
                    )
                )
            )
        );
    }
}
?>
