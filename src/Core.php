<?php

namespace EasyMVC\Core;

use EasyMVC\Email\Email;
use EasyMVC\HttpRequest\HttpRequest;
use EasyMVC\Login\Login;
use EasyMVC\Menu\Menu;
use Exception;
use RudyMas\Manipulator\Text;
use RudyMas\PDOExt\DBconnect;
use RudyMas\Router\EasyRouter;

/**
 * Class Core (PHP version 7.1)
 *
 * @author      Rudy Mas <rudy.mas@rmsoft.be>
 * @copyright   2018, rmsoft.be. (http://www.rmsoft.be/)
 * @license     https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version     0.1.0.1
 * @package     EasyMVC\Core
 */
class Core
{
    public $CoreDB;
    public $CoreLogin;
    public $CoreHttpRequest;
    public $CoreEmail;
    public $CoreMenu;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        $this->settingUpRootMapping();
        $this->loadingConfig();
        if (USE_DATABASE) $this->loadingDatabases();
        if (USE_LOGIN && isset($this->DB['DBconnect'])) $this->loadingEmvcLogin($this->DB['DBconnect']);
        if (USE_HTTP_REQUEST) $this->loadingEmvcHttpRequest();
        if (USE_EMAIL) $this->loadingEmvcEmail();
        if (USE_MENU) $this->loadingEmvcMenu();
        $this->startRouting();
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
        $scriptName = rtrim(str_replace($arrayServerName, '', dirname($_SERVER['SCRIPT_NAME'])), '/\\');
        define('BASE_URL', $scriptName);
        define('SYSTEM_ROOT', $_SERVER['DOCUMENT_ROOT'] . BASE_URL);
    }

    /**
     * Loading the configuration files for the website
     *
     * Checks if certain files exist, if not, it uses the standard config file by copying it
     */
    private function loadingConfig()
    {
        if ($_SERVER['HTTP_HOST'] == 'localhost') {
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
        if (!is_file(SYSTEM_ROOT . '/config/database.php'))
            @copy(SYSTEM_ROOT . '/config/database.sample.php', SYSTEM_ROOT . '/config/database.php');
        require_once('config/database.php');
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
    }

    /**
     * Loading the EasyMVC Menu class
     */
    private function loadingEmvcMenu()
    {
        $this->Menu = new Menu();
    }

    /**
     * Start loading the website by use of routing
     */
    private function startRouting()
    {
        $router = new EasyRouter($this);
        require_once('config/router.php');
        try {
            $router->execute();
        } catch (Exception $exception) {
            http_response_code(500);
            print($exception->getMessage());
        }
    }
}

/** End of File: Core.php **/