<?php
defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true or die();

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

class testcasefusion extends CModule
{
    public $MODULE_ID = 'testcasefusion';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');
        
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'Test Case Fusion';
        $this->MODULE_DESCRIPTION = 'Модуль для управления тест-кейсами с автоматическими уведомлениями';
    }
    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        if (!Loader::includeModule('crm')) {
            file_put_contents(
                $_SERVER['DOCUMENT_ROOT'] . '/testcasefusion_install.log',
                date('Y-m-d H:i:s') . " - ERROR: CRM module not found\n",
                FILE_APPEND
            );
            return true;
        }

        Loader::includeModule($this->MODULE_ID);
        \Testcasefusion\Creator::CreateSmartProcess($this->MODULE_ID);
        return true;
    }

    public function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
