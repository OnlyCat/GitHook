<?php

class BootStrap {

    private static $objSignature = null;
    private static $objConfig = [];

    /**
     * 单例模式入口
     * @return BootStrap|null
     */
    public static function init() {
        if(self::$objSignature == null) {
            self::$objSignature = new self();
        }
        return self::$objSignature;
    }

    /**
     * 项目启动入口
     */
    public function run() {
        $this->setIncludePath();

        // 加载配置信息
        $this->loadConfig();

        // 启动项目
        $app = Apply::Initialize();
        $app->runing();

    }




    /**
     * 设置加载
     */
    private function setIncludePath() {
        set_include_path(
            dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "config" . PATH_SEPARATOR.
            dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "script" . PATH_SEPARATOR.
            dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "library" . PATH_SEPARATOR .
            dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "mailer" . PATH_SEPARATOR.
            dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "action" . PATH_SEPARATOR.
            get_include_path()
        );
        spl_autoload_register(function($className) {
            $classFile = sprintf("%s.php", $className);
            include_once($classFile);
        });
    }

    /**
     * 加载配置信息
     * @param $item
     * @return array|mixed
     */
    public static function getConfig($item) {
        if(isset(self::$objConfig[$item])) {
            return self::$objConfig[$item];
        }
        return  self::$objConfig;
    }


    /**
     * 加载配置信息
     */
    private function loadConfig(){
        $objEnvConfig = $this->loadEnvConfig();
        self::$objConfig['ENV'] = $objEnvConfig;
    }


    /**
     * 加载全集配置信息
     * @return array|bool
     */
    private function loadEnvConfig() {
        $result = false;
        $envConfPath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "env.ini";
        if(true == is_file($envConfPath)) {
            $objConfig = parse_ini_file($envConfPath, true);
            $result = $this->parseEnvConfig($objConfig);
        }
        return $result;
    }


    /**
     * 解析全局配置信息
     * @param $objConfig
     * @result Array
     */
    private function parseEnvConfig($objConfig) {
        $result = [];
        $configKey = array_keys($objConfig);
        foreach ($configKey as $key => $value) {
            if ('security' == $value) {
                $result['git'] = [
                    'token' => $objConfig[$value]['token'],
                    'user' => $objConfig[$value]['user'],
                    'password' => $objConfig[$value]['password'],
                ];
            }
            if ('schema' == $value) {
                $result['schema'] = $objConfig[$value];
            }
        }
        return $result;
    }
}