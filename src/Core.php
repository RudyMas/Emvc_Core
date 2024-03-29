<?php

namespace EasyMVC;

use Exception;
use RudyMas\Manipulator\Text;
use RudyMas\DBconnect;

/**
 * Class Core (PHP version 8.1)
 *
 * @author Rudy Mas <rudy.mas@rmsoft.be>
 * @copyright 2018-2022, rmsoft.be. (http://www.rmsoft.be/)
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version 0.8.1.2
 * @package EasyMVC
 */
class Core
{
    public $DB;
    public $Login;
    public $HttpRequest;
    public $Email;
    public $Menu;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        define('CORE_VERSION', '0.8.1.2');

        $this->settingUpRootMapping();

        require_once('config/version.php');
        require_once('config/server.php');
        require_once('config/config.website.php');
        date_default_timezone_set(TIME_ZONE);

        $this->loadingConfig();
        if (USE_DATABASE) $this->loadingDatabases();
        if (USE_LOGIN && isset($this->DB['DBconnect'])) $this->loadingEmvcLogin($this->DB['DBconnect']);
        if (USE_HTTP_REQUEST) $this->loadingEmvcHttpRequest();
        if (USE_EMAIL) $this->loadingEmvcEmail();
        if (USE_MENU) $this->loadingEmvcMenu();

        $Router = new Router($this);
        require_once('config/router.php');
        try {
            $Router->execute();
        } catch (Exception $exception) {
            http_response_code(500);
            print('EasyMVC : Something went wrong.<br><br>');
            print($exception->getMessage());
            print('<br><br>');
            print('<pre>');
            print_r($exception);
            print('</pre>');
        }
    }

    /**
     * Creating BASE_URL & SYSTEM_ROOT
     *
     * BASE_URL = Path to the root of the website
     * SYSTEM_ROOT = Full system path to the root of the website
     */
    private function settingUpRootMapping()
    {
        $arrayServerName = explode('.', $_SERVER['SERVER_NAME']);
        $numberOfServerNames = count($arrayServerName);
        unset($arrayServerName[$numberOfServerNames-2]);
        unset($arrayServerName[$numberOfServerNames-1]);

        $scriptName = rtrim(str_replace($arrayServerName, '', dirname($_SERVER['SCRIPT_NAME'])), '/\\');
        define('BASE_URL', $scriptName);

        $extraPath = '';
        for ($i = 0; $i < count($arrayServerName); $i++) {
            $extraPath .= '/' . $arrayServerName[$i];
        }
        define('SYSTEM_ROOT', $_SERVER['DOCUMENT_ROOT'] . $extraPath . BASE_URL);
    }

    /**
     * Loading the configuration files for the website
     *
     * Checks if certain files exist, if not, it uses the standard config file by copying it
     */
    private function loadingConfig()
    {
        if ($_SERVER['HTTP_HOST'] == SERVER_DEVELOP) {
            if (!is_file(SYSTEM_ROOT . '/config/config.local.php'))
                @copy(SYSTEM_ROOT . '/config/config.sample.php', SYSTEM_ROOT . '/config/config.local.php');
            require_once('config/config.local.php');
        } else {
            if (!is_file(SYSTEM_ROOT . '/config/config.php'))
                @copy(SYSTEM_ROOT . '/config/config.sample.php', SYSTEM_ROOT . '/config/config.php');
            require_once('config/config.php');
        }
    }

    /**
     * Loading the databases for the websites
     */
    private function loadingDatabases()
    {
        $database = [];
        if ($_SERVER['HTTP_HOST'] == SERVER_DEVELOP) {
            if (!is_file(SYSTEM_ROOT . '/config/database.local.php'))
                @copy(SYSTEM_ROOT . '/config/database.sample.php', SYSTEM_ROOT . '/config/database.local.php');
            require_once('config/database.local.php');
        } else {
            if (!is_file(SYSTEM_ROOT . '/config/database.php'))
                @copy(SYSTEM_ROOT . '/config/database.sample.php', SYSTEM_ROOT . '/config/database.php');
            require_once('config/database.php');
        }
        foreach ($database as $connect) {
            $object = $connect['objectName'];
            $this->DB[$object] = new DBconnect($connect['dbHost'], $connect['port'], $connect['dbUsername'],
                $connect['dbPassword'], $connect['dbName'], $connect['dbCharset'], $connect['dbType']);
        }
    }

    /**
     * Loading the EasyMVC Login class
     *
     * @param DBconnect $DBconnect
     */
    private function loadingEmvcLogin(DBconnect $DBconnect)
    {
        $this->Login = new Login($DBconnect, new Text(), USE_EMAIL_LOGIN);
    }

    /**
     * Loading the EasyMVC HttpRequest class
     */
    private function loadingEmvcHttpRequest()
    {
        $this->HttpRequest = new HttpRequest();
    }

    /**
     * Loading the EasyMVC Email class
     */
    private function loadingEmvcEmail()
    {
        $this->Email = new Email();
        $this->Email->emvc_config();
    }

    /**
     * Loading the EasyMVC Menu class
     */
    private function loadingEmvcMenu()
    {
        $this->Menu = new Menu();
    }
}
