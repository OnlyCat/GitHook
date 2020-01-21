<?php
class Mail {


    private static $init = null;
    private static $objConfig = null;
    private static $objMailer = null;


    /**
     * 模块初始化
     * @param $appName
     * @return null|Apply
     */
    static function Initialize() {
        if(self::$init == null) {
            self::$init = new self();
            // 加载邮箱配置信息
            self::$objConfig = self::$init->loadMailConfig();
            // 加载邮箱对象
            self::$objMailer = self::$init->createObjMailer();
        }
        return self::$init;
    }


    /**
     * 创建邮箱对象
     * @throws Exception
     */
    private  function createObjMailer() {
        $result = null;
        if(false !== self::$objConfig) {
            $result = new Mailer(true);
            $result->SMTPDebug = Smtp::DEBUG_OFF;
            $result->isSMTP();
            $result->CharSet    = "UTF-8";
            $result->Host       = self::$objConfig['transport']['host'];
            $result->SMTPAuth   = true;
            $result->Username   = self::$objConfig['transport']['user'];
            $result->Password   = self::$objConfig['transport']['passwd'];
            $result->SMTPSecure = Mailer::ENCRYPTION_SMTPS;
            $result->Port       = self::$objConfig['transport']['port'];
            $result->setFrom(self::$objConfig['source']['address'], self::$objConfig['source']['title']);
            foreach (self::$objConfig['target'] as $item) {
                $result->addAddress($item['address'], $item['title']);
            }
            $result->isHTML(true);
        }
        return $result;
    }


    /**
     * 发送邮件
     * @param $title
     * @param $message
     */
    public function send($title, $message) {

        if(false !== self::$objMailer) {
            self::$objMailer->Subject = $title;
            self::$objMailer->Body = $message;
            self::$objMailer->send();
        }

    }


    /**
     * 加载配置信息
     */
    private function loadMailConfig() {
        $result = false;
        $mailConfig = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "mail.ini";
        if(true == is_file($mailConfig)) {
            $objConfig = parse_ini_file($mailConfig, true);
            $result = $this->parseMailConfig($objConfig);
        }
        return $result;
    }

    /**
     * 解析邮件配置文件
     * @param $objConfig
     */
    private function parseMailConfig($objConfig) {
       $result = [
            'transport' => [
                'host' => isset($objConfig['transport']['host']) ? $objConfig['transport']['host'] : "",
                'user' => isset($objConfig['transport']['user']) ? $objConfig['transport']['user'] : "",
                'passwd' => isset($objConfig['transport']['user']) ? $objConfig['transport']['passwd'] : "",
                'port' => isset($objConfig['transport']['user']) ? $objConfig['transport']['port'] : "",
            ],
            'source' => [
                'address' => isset($objConfig['source']['address']) ? $objConfig['source']['address'] : "",
                'title' => isset($objConfig['source']['title']) ? $objConfig['source']['title'] : "",
            ]
       ];
       $targetUser = isset($objConfig['target']['address']) ? $objConfig['target']['address'] : "";
       $objTargetUser = explode(",", $targetUser);
       foreach ($objTargetUser as $item){
            $result['target'][] = [
                'address' => $item,
                'title' => $item,
            ];
       }
       return $result;
    }
}