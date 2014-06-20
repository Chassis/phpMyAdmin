<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for AuthenticationCookie class
 *
 * @package PhpMyAdmin-test
 */

$GLOBALS['PMA_Config'] = new PMA_Config();

require_once 'libraries/plugins/auth/AuthenticationCookie.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';
require_once 'libraries/Error_Handler.class.php';
require_once 'libraries/Response.class.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/select_lang.lib.php';

/**
 * tests for AuthenticationCookie class
 *
 * @package PhpMyAdmin-test
 */
class PMA_AuthenticationCookie_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['available_languages'] = array(
            "en" => array("English", "US-ENGLISH"),
            "ch" => array("Chinese", "TW-Chinese")
        );
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $this->object = new AuthenticationCookie(null);

        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for AuthenticationConfig::auth
     *
     * @return void
     * @group medium
     */
    public function testAuth()
    {
        $restoreInstance = PMA_Response::getInstance();
        // Case 1

        $mockResponse = $this->getMockBuilder('PMA_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'isSuccess', 'addJSON'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(true));

        $mockResponse->expects($this->once())
            ->method('isSuccess')
            ->with(false);

        $mockResponse->expects($this->once())
            ->method('addJSON')
            ->with(
                'redirect_flag',
                '1'
            );

        $attrInstance = new ReflectionProperty('PMA_Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue(null, $mockResponse);
        $GLOBALS['conn_error'] = true;
        $GLOBALS['cfg']['PmaAbsoluteUri'] = 'https://phpmyadmin.net/';
        $this->assertTrue(
            $this->object->auth()
        );

        // Case 2

        $mockResponse = $this->getMockBuilder('PMA_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'getFooter', 'getHeader'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(false));



        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['LoginCookieRecall'] = true;
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $GLOBALS['PHP_AUTH_USER'] = 'pmauser';
        $GLOBALS['pma_auth_server'] = 'localhost';

        // mock footer
        $mockFooter = $this->getMockBuilder('PMA_Footer')
            ->disableOriginalConstructor()
            ->setMethods(array('setMinimal'))
            ->getMock();

        $mockFooter->expects($this->once())
            ->method('setMinimal')
            ->with();

        // mock header

        $mockHeader = $this->getMockBuilder('PMA_Header')
            ->disableOriginalConstructor()
            ->setMethods(
                array('setBodyId', 'setTitle', 'disableMenu', 'disableWarnings')
            )
            ->getMock();

        $mockHeader->expects($this->once())
            ->method('setBodyId')
            ->with('loginform');

        $mockHeader->expects($this->once())
            ->method('setTitle')
            ->with('phpMyAdmin');

        $mockHeader->expects($this->once())
            ->method('disableMenu')
            ->with();

        $mockHeader->expects($this->once())
            ->method('disableWarnings')
            ->with();

        // set mocked headers and footers

        $mockResponse->expects($this->once())
            ->method('getFooter')
            ->with()
            ->will($this->returnValue($mockFooter));

        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with()
            ->will($this->returnValue($mockHeader));

        $attrInstance = new ReflectionProperty('PMA_Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue(null, $mockResponse);

        $GLOBALS['pmaThemeImage'] = 'test';
        $GLOBALS['conn_error'] = true;
        $GLOBALS['cfg']['Lang'] = 'en';
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['cfg']['Servers'] = array(1, 2);
        $_SESSION['last_valid_captcha'] = true;
        $GLOBALS['target'] = 'testTarget';
        $GLOBALS['db'] = 'testDb';
        $GLOBALS['table'] = 'testTable';

        file_put_contents('testlogo_right.png', '');

        // mock error handler

        $mockErrorHandler = $this->getMockBuilder('PMA_Error_Handler')
            ->disableOriginalConstructor()
            ->setMethods(array('hasDisplayErrors', 'dispErrors'))
            ->getMock();

        $mockErrorHandler->expects($this->once())
            ->method('hasDisplayErrors')
            ->with()
            ->will($this->returnValue(true));

        $mockErrorHandler->expects($this->once())
            ->method('dispErrors')
            ->with();

        $GLOBALS['error_handler'] = $mockErrorHandler;

        ob_start();
        $this->object->auth();
        $result = ob_get_clean();

        // assertions

        $this->assertTag(
            PMA_getTagArray(
                '<img src="testlogo_right.png" id="imLogo"'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<div class="error">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<form method="post" action="index.php" name="login_form" ' .
                'class="disableAjax login hide js-show">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="text" name="pma_servername" id="input_servername" ' .
                'value="localhost"'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="text" name="pma_username" id="input_username" ' .
                'value="pmauser" size="24" class="textfield"/>'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="password" name="pma_password" id="input_password" ' .
                'value="" size="24" class="textfield" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<select name="server" id="select_server" ' .
                'onchange="document.forms[\'login_form\'].' .
                'elements[\'pma_servername\'].value = \'\'" >'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="target" value="testTarget" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="db" value="testDb" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="table" value="testTable" />'
            ),
            $result
        );

        @unlink('testlogo_right.png');

        // case 3

        $mockResponse = $this->getMockBuilder('PMA_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax', 'getFooter', 'getHeader'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(false));

        $mockResponse->expects($this->once())
            ->method('getFooter')
            ->with()
            ->will($this->returnValue(new PMA_Footer()));

        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with()
            ->will($this->returnValue(new PMA_Header()));

        $_REQUEST['old_usr'] = '';
        $GLOBALS['cfg']['LoginCookieRecall'] = false;

        $attrInstance = new ReflectionProperty('PMA_Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue(null, $mockResponse);

        $GLOBALS['pmaThemeImage'] = 'test';
        $GLOBALS['cfg']['Lang'] = '';
        $GLOBALS['cfg']['AllowArbitraryServer'] = false;
        $GLOBALS['cfg']['Servers'] = array(1);
        $_SESSION['last_valid_captcha'] = false;
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = 'testprivkey';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = 'testpubkey';
        $GLOBALS['server'] = 0;

        $GLOBALS['error_handler'] = new PMA_Error_Handler;

        ob_start();
        $this->object->auth();
        $result = ob_get_clean();

        // assertions

        $this->assertTag(
            PMA_getTagArray(
                '<img name="imLogo" id="imLogo" src="testpma_logo.png"'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<select name="lang" class="autosubmit" lang="en" dir="ltr" ' .
                'id="sel-lang">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<form method="post" action="index.php" name="login_form" ' .
                'autocomplete="off" class="disableAjax login hide js-show">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="server" value="0" />'
            ),
            $result
        );

        $this->assertContains(
            'src="https://www.google.com/recaptcha/api/challenge?k=testpubkey&amp;hl=en">',
            $result
        );

        $this->assertContains(
            'iframe src="https://www.google.com/recaptcha/api/noscript' .
            '?k=testpubkey"',
            $result
        );

        $this->assertContains(
            '<textarea name="recaptcha_challenge_field" rows="3" cols="40">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="recaptcha_response_field"',
            $result
        );

        $this->assertContains(
            '$("#recaptcha_reload_btn").addClass("disableAjax");',
            $result
        );

        $this->assertContains(
            '$("#recaptcha_switch_audio_btn").addClass("disableAjax");',
            $result
        );

        $this->assertContains(
            '$("#recaptcha_switch_img_btn").addClass("disableAjax");',
            $result
        );

        $attrInstance->setValue(null, $restoreInstance);
    }

    /**
     * Test for AuthenticationConfig::auth with headers
     *
     * @return void
     */
    public function testAuthHeader()
    {
        if (!defined('PMA_TEST_HEADERS')) {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

        $restoreInstance = PMA_Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('isAjax'))
            ->getMock();

        $mockResponse->expects($this->once())
            ->method('isAjax')
            ->with()
            ->will($this->returnValue(false));

        $attrInstance = new ReflectionProperty('PMA_Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue(null, $mockResponse);

        $_REQUEST['old_usr'] = 'user1';
        $GLOBALS['cfg']['Server']['LogoutURL'] = 'http://www.phpmyadmin.net/logout';

        $this->assertTrue(
            $this->object->auth()
        );

        $this->assertContains(
            'Location: http://www.phpmyadmin.net/logout?PHPSESSID=',
            $GLOBALS['header'][0]
        );

        $attrInstance->setValue(null, $restoreInstance);
    }

    /**
     * Test for AuthenticationConfig::authCheck
     *
     * @return void
     */
    public function testAuthCheck()
    {
        $defineAgain = 'PMA_TEST_NO_DEFINE';

        if (defined('PMA_CLEAR_COOKIES')) {
            if (! PMA_HAS_RUNKIT) {
                $this->markTestSkipped(
                    'Cannot redefine constant/function - missing runkit extension'
                );
            } else {
                $defineAgain = PMA_CLEAR_COOKIES;
                runkit_constant_remove('PMA_CLEAR_COOKIES');
            }
        }

        $GLOBALS['cfg']['Server']['auth_swekey_config'] = 'testConfigSwekey';

        file_put_contents('testConfigSwekey', '');
        $this->assertFalse(
            $this->object->authCheck()
        );
        @unlink('testConfigSwekey');

        // case 2

        $_SESSION['last_valid_captcha'] = false;
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = 'testprivkey';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = 'testpubkey';
        $_POST["recaptcha_challenge_field"] = 'captcha1';
        $_POST["recaptcha_response_field"] = '';

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'Please enter correct captcha!',
            $GLOBALS['conn_error']
        );

        // case 3

        $_SESSION['last_valid_captcha'] = false;
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = 'testprivkey';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = 'testpubkey';
        $_POST["recaptcha_challenge_field"] = '';
        $_POST["recaptcha_response_field"] = '';

        $this->assertFalse(
            $this->object->authCheck()
        );

        // case 4

        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $_REQUEST['old_usr'] = 'pmaolduser';
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = true;
        $GLOBALS['cfg']['Servers'] = array(1);

        $_COOKIE['pmaPass-0'] = 'test';

        $this->object->authCheck();

        $this->assertFalse(
            isset($_COOKIE['pmaPass-0'])
        );

        // case 5

        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = '';
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = '';
        $_REQUEST['old_usr'] = 'pmaolduser';
        $GLOBALS['cfg']['LoginCookieDeleteAll'] = false;
        $GLOBALS['cfg']['Servers'] = array(1);
        $GLOBALS['server'] = 1;

        $_COOKIE['pmaPass-1'] = 'test';

        $this->object->authCheck();

        $this->assertFalse(
            isset($_COOKIE['pmaPass-1'])
        );

        // case 6

        $_SESSION['last_valid_captcha'] = true;
        $_REQUEST['old_usr'] = '';
        $_REQUEST['pma_username'] = 'testPMAUser';
        $_REQUEST['pma_servername'] = 'testPMAServer';
        $_REQUEST['pma_password'] = 'testPMAPSWD';
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;

        $this->assertTrue(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'testPMAUser',
            $GLOBALS['PHP_AUTH_USER']
        );

        $this->assertEquals(
            'testPMAPSWD',
            $GLOBALS['PHP_AUTH_PW']
        );

        $this->assertEquals(
            'testPMAServer',
            $GLOBALS['pma_auth_server']
        );

        $this->assertFalse(
            isset($_COOKIE['pmaPass-1'])
        );

        // case 7

        $_REQUEST['pma_username'] = '';
        $GLOBALS['server'] = 1;
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = '';

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'pmaServ1',
            $GLOBALS['pma_auth_server']
        );

        // case 8

        $GLOBALS['server'] = 1;
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pmaPass-1'] = '';
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_access_time'] = time() - 1000;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;

        $this->assertFalse(
            $this->object->authCheck()
        );


        if ($defineAgain !== 'PMA_TEST_NO_DEFINE') {
            define('PMA_CLEAR_COOKIES', $defineAgain);
        }
    }

    /**
     * Test for AuthenticationConfig::authCheck with constant modifications
     *
     * @return void
     */
    public function testAuthCheckWithConstants()
    {
        if (!defined('PMA_CLEAR_COOKIES') && !PMA_HAS_RUNKIT) {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

        $remove = false;

        if (! defined('PMA_CLEAR_COOKIES')) {
            define('PMA_CLEAR_COOKIES', true);
            $remove = true;
        }

        $GLOBALS['cfg']['Server']['auth_swekey_config'] = 'testConfigSwekey';
        $GLOBALS['cfg']['Servers'] = array(1);
        $_COOKIE['pmaPass-0'] = 1;
        $_COOKIE['pmaServer-0'] = 1;
        $_COOKIE['pmaUser-0'] = 1;

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertFalse(
            isset($_COOKIE['pmaPass-0'])
        );

        $this->assertFalse(
            isset($_COOKIE['pmaServer-0'])
        );

        $this->assertFalse(
            isset($_COOKIE['pmaUser-0'])
        );

        if ($remove) {
            runkit_constant_remove('PMA_CLEAR_COOKIES');
        }
    }

    /**
     * Test for AuthenticationConfig::authCheck (mock blowfish functions reqd)
     *
     * @return void
     */
    public function testAuthCheckBlowfishCase()
    {
        $GLOBALS['cfg']['Server']['auth_swekey_config'] = 'testConfigSwekey';
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_REQUEST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_access_time'] = '';
        $_SESSION['last_valid_captcha'] = true;

        // mock for blowfish function
        $this->object = $this->getMockBuilder('AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('blowfishDecrypt'))
            ->getMock();

        $this->object->expects($this->once())
            ->method('blowfishDecrypt')
            ->will($this->returnValue('testBF'));

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertEquals(
            'testBF',
            $GLOBALS['PHP_AUTH_USER']
        );
    }

    /**
     * Test for AuthenticationConfig::authCheck (mocking blowfish functions)
     *
     * @return void
     */
    public function testAuthCheckBlowfishCaseSecond()
    {
        $GLOBALS['cfg']['Server']['auth_swekey_config'] = 'testConfigSwekey';
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_REQUEST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $_COOKIE['pmaPass-1'] = 'pmaPass1';
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_valid_captcha'] = true;
        $_SESSION['last_access_time'] = time() - 1000;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;

        // mock for blowfish function
        $this->object = $this->getMockBuilder('AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('blowfishDecrypt'))
            ->getMock();

        $this->object->expects($this->at(1))
            ->method('blowfishDecrypt')
            ->will($this->returnValue("\xff(blank)"));

        $this->assertTrue(
            $this->object->authCheck()
        );

        $this->assertTrue(
            $GLOBALS['from_cookie']
        );

        $this->assertEquals(
            '',
            $GLOBALS['PHP_AUTH_PW']
        );

    }

    /**
     * Test for AuthenticationConfig::authCheck (mocking the object itself)
     *
     * @return void
     */
    public function testAuthCheckAuthFails()
    {
        $GLOBALS['cfg']['Server']['auth_swekey_config'] = 'testConfigSwekey';
        $GLOBALS['server'] = 1;
        $_REQUEST['old_usr'] = '';
        $_REQUEST['pma_username'] = '';
        $_COOKIE['pmaServer-1'] = 'pmaServ1';
        $_COOKIE['pmaUser-1'] = 'pmaUser1';
        $GLOBALS['cfg']['blowfish_secret'] = 'secret';
        $_SESSION['last_access_time'] = 1;
        $_SESSION['last_valid_captcha'] = true;
        $GLOBALS['cfg']['LoginCookieValidity'] = 0;
        $_SESSION['last_access_time'] = -1;
        // mock for blowfish function
        $this->object = $this->getMockBuilder('AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('authFails'))
            ->getMock();

        $this->object->expects($this->once())
            ->method('authFails');

        $this->assertFalse(
            $this->object->authCheck()
        );

        $this->assertTrue(
            $GLOBALS['no_activity']
        );
    }

    /**
     * Test for AuthenticationConfig::authSetUser
     *
     * @return void
     */
    public function testAuthSetUser()
    {
        $GLOBALS['PHP_AUTH_USER'] = 'pmaUser2';
        $arr = array(
            'host' => 'a',
            'port' => 1,
            'socket' => true,
            'ssl' => true,
            'connect_type' => 'port',
            'user' => 'pmaUser2'
        );

        $GLOBALS['cfg']['Server'] = $arr;
        $GLOBALS['cfg']['Server']['user'] = 'pmaUser';
        $GLOBALS['cfg']['Servers'][1] = $arr;
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['pma_auth_server'] = 'b 2';
        $GLOBALS['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'] = 'testPW';
        $GLOBALS['server'] = 2;
        $GLOBALS['cfg']['LoginCookieStore'] = true;
        $GLOBALS['from_cookie'] = true;

        $this->object->authSetUser();

        $this->assertFalse(
            isset($GLOBALS['PHP_AUTH_PW'])
        );

        $this->assertFalse(
            isset($_SERVER['PHP_AUTH_PW'])
        );

        $this->assertTrue(
            isset($_COOKIE['pmaUser-1'])
        );

        $this->assertTrue(
            isset($_COOKIE['pmaPass-1'])
        );

        $arr['password'] = 'testPW';
        $arr['host'] = 'b';
        $arr['port'] = '2';
        $this->assertEquals(
            $arr,
            $GLOBALS['cfg']['Server']
        );

    }

    /**
     * Test for AuthenticationConfig::authSetUser (check for headers redirect)
     *
     * @return void
     */
    public function testAuthSetUserWithHeaders()
    {
        if (!defined('PMA_TEST_HEADERS')) {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

        $GLOBALS['PHP_AUTH_USER'] = 'pmaUser2';
        $arr = array(
            'host' => 'a',
            'port' => 1,
            'socket' => true,
            'ssl' => true,
            'connect_type' => 'port',
            'user' => 'pmaUser2'
        );

        $GLOBALS['cfg']['Server'] = $arr;
        $GLOBALS['cfg']['Server']['host'] = 'b';
        $GLOBALS['cfg']['Server']['user'] = 'pmaUser';
        $GLOBALS['cfg']['Servers'][1] = $arr;
        $GLOBALS['cfg']['AllowArbitraryServer'] = true;
        $GLOBALS['pma_auth_server'] = 'b 2';
        $GLOBALS['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'] = 'testPW';
        $GLOBALS['server'] = 2;
        $GLOBALS['cfg']['LoginCookieStore'] = true;
        $GLOBALS['from_cookie'] = false;
        $GLOBALS['cfg']['PmaAbsoluteUri'] = 'http://phpmyadmin.net/';
        $GLOBALS['collation_connection'] = 'utf-8';

        $restoreInstance = PMA_Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA_Response')
            ->disableOriginalConstructor()
            ->setMethods(array('disable'))
            ->getMock();

        $mockResponse->expects($this->at(0))
            ->method('disable');

        $attrInstance = new ReflectionProperty('PMA_Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue(null, $mockResponse);

        $this->object->authSetUser();

        $this->assertTrue(
            isset($_COOKIE['pmaServer-2'])
        );

        // target can be "phpunit" or "ide-phpunut.php",
        // depending on testing environment
        $this->assertStringStartsWith(
            'Location: http://phpmyadmin.net/index.php?',
            $GLOBALS['header'][0]
        );
        $this->assertContains(
            '&target=',
            $GLOBALS['header'][0]
        );
        $this->assertContains(
            '&server=2&lang=en&collation_connection=utf-8&token=token&PHPSESSID=',
            $GLOBALS['header'][0]
        );

        $attrInstance->setValue(null, $restoreInstance);
    }

    /**
     * Test for AuthenticationConfig::authFails
     *
     * @return void
     */
    public function testAuthFails()
    {
        if (!defined('PMA_TEST_HEADERS')) {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

        $this->object = $this->getMockBuilder('AuthenticationCookie')
            ->disableOriginalConstructor()
            ->setMethods(array('auth'))
            ->getMock();

        $this->object->expects($this->exactly(5))
            ->method('auth');

        $GLOBALS['server'] = 2;
        $_COOKIE['pmaPass-2'] = 'pass';

        // case 1

        $GLOBALS['login_without_password_is_forbidden'] = '1';

        $this->object->authFails();

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Login without a password is forbidden by configuration'
            . ' (see AllowNoPassword)'
        );

        $this->assertEquals(
            $GLOBALS['header'],
            array(
                'Cache-Control: no-store, no-cache, must-revalidate',
                'Pragma: no-cache'
            )
        );

        // case 2

        $GLOBALS['login_without_password_is_forbidden'] = '';
        $GLOBALS['allowDeny_forbidden'] = '1';

        $this->object->authFails();

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Access denied!'
        );

        // case 3

        $GLOBALS['allowDeny_forbidden'] = '';
        $GLOBALS['no_activity'] = '1';
        $GLOBALS['cfg']['LoginCookieValidity'] = 10;

        $this->object->authFails();

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'No activity within 10 seconds; please log in again.'
        );

        // case 4

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getError')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('getError')
            ->will($this->returnValue(false));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['no_activity'] = '';
        $GLOBALS['errno'] = 42;

        $this->object->authFails();

        $this->assertEquals(
            $GLOBALS['conn_error'],
            '#42 Cannot log in to the MySQL server'
        );

        // case 5

        $this->object->authFails();

        $this->assertEquals(
            $GLOBALS['conn_error'],
            'Cannot log in to the MySQL server'
        );
    }

    /**
     * Test for AuthenticationConfig::_getBlowfishSecret
     *
     * @return void
     */
    public function testGetBlowfishSecret()
    {
        $method = new \ReflectionMethod(
            'AuthenticationCookie',
            '_getBlowfishSecret'
        );
        $method->setAccessible(true);

        // case 1

        $GLOBALS['cfg']['blowfish_secret'] = '';
        $_SESSION['auto_blowfish_secret'] = '';

        $result = $method->invoke($this->object, null);

        $this->assertEquals(
            $result,
            $_SESSION['auto_blowfish_secret']
        );

        $this->assertEquals(
            23,
            strlen($result)
        );

        // case 2

        $GLOBALS['cfg']['blowfish_secret'] = 'notEmpty';

        $result = $method->invoke($this->object, null);

        $this->assertEquals(
            md5('notEmpty'),
            $result
        );
    }

    /**
     * Test for AuthenticationConfig::blowfishEncrypt
     *
     * @return void
     */
    public function testBlowfishEncrypt()
    {
        if (! function_exists('mcrypt_encrypt')) {
            $this->assertEquals(
                '/xytF/kXKuBx7zHzGexkFw==',
                $this->object->blowfishEncrypt('data123', 'sec321')
            );
        } else {
            //using our own iv for testing
            $tmp = $GLOBALS['iv'];
            $GLOBALS['iv'] = "testiv09";
            $this->assertEquals(
                'x/2GwHKoPyc=',
                $this->object->blowfishEncrypt('data123', 'sec321')
            );
            $GLOBALS['iv'] = $tmp;
        }
    }

    /**
     * Test for AuthenticationConfig::blowfishDecrypt
     *
     * @return void
     */
    public function testBlowfishDecrypt()
    {
        if (function_exists('mcrypt_encrypt')) {

            //using our own iv for testing
            $tmp = $GLOBALS['iv'];
            $GLOBALS['iv'] = "testiv09";
            $this->assertEquals(
                'data123',
                $this->object->blowfishDecrypt('x/2GwHKoPyc=', 'sec321')
            );
            $GLOBALS['iv'] = $tmp;

        } else {
            $this->assertEquals(
                'data123',
                $this->object->blowfishDecrypt(
                    '/xytF/kXKuBx7zHzGexkFw==',
                    'sec321'
                )
            );
        }
    }


}
?>
