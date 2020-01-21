<?php
class Apply {

    private static $init = null;
    private static $objConfig = null;

    /**
     * 模块初始化
     * @param $appName
     * @return null|Apply
     */
     static function Initialize() {
        if(self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    /**
     * 获取配置信息
     * @param null $item
     * @return bool|mixed|null
     */
    public static function getConfig($item = null) {
         if(null == $item) {
             return Apply::$objConfig;
         }
         if(true == isset(Apply::$objConfig[$item])) {
             return Apply::$objConfig[$item];
         }
         return false;
    }


    /**
     * 主运行入口
     */
    public function runing() {
        $this->loadAppConfig();
        $git = new Git();
        $ret = $git->Process();
        echo json_encode($ret); die();
    }


    /**
     * 进行项目配置信息加载
     */
    private function loadAppConfig() {
        $localConfPath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "local.ini";
        $remoteConfPath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "remote.ini";
        self::$objConfig['local'] = $this->loadLocalConfig($localConfPath);
        self::$objConfig['remote'] = $this->remoteLocalConfig($remoteConfPath);
    }

    /**
     * 加载远程配置信息
     * @param $remoteConfPath
     * @return array
     */
    private function remoteLocalConfig($remoteConfPath) {
        $result = [];
        $objConfig = parse_ini_file($remoteConfPath, true);
        foreach ($objConfig as $key => $value) {
            $result[$key] = [
                'name' => $value['resources.project.name'],
                'url' => $value['resources.project.url']
            ];
        }
        return $result;
    }


    /**
     * 加载本地配置信息
     * @param $localConfPath
     * @return array
     */
    private function loadLocalConfig($localConfPath) {
        $result = [];
        $objConfig = parse_ini_file($localConfPath, true);
        $configKey = array_keys($objConfig);
        foreach ($configKey as $key => $value) {
            $itemKeys = explode(":", $value);
            // 获取项目信息
            if(true == in_array('common', $itemKeys) && 1 < count($itemKeys)) {
                $projectKey = sprintf("%s:common", $itemKeys[0]);
                $eventKey = sprintf("%s:common:event", $itemKeys[0]);
                $result[$itemKeys[0]]['project'] = [
                    'name' => $objConfig[$projectKey]['resources.project.name'],
                    'url' => $objConfig[$projectKey]['resources.project.url'],
                    'path' => $objConfig[$projectKey]['resources.project.path'],
                ];
                $result[$itemKeys[0]]['event'] = [
                    'push' => isset($objConfig[$eventKey]['resources.event.push']) ? $objConfig[$eventKey]['resources.event.push'] : 'deny',
                    'tag_push' => isset($objConfig[$eventKey]['resources.event.tag_push']) ? $objConfig[$eventKey]['resources.event.tag_push'] : 'deny',
                    'issue' => isset($objConfig[$eventKey]['resources.event.issue']) ? $objConfig[$eventKey]['resources.event.issue'] : 'deny',
                    'note' => isset($objConfig[$eventKey]['resources.event.note']) ? $objConfig[$eventKey]['resources.event.note'] : 'deny',
                    'merge_request' => isset($objConfig[$eventKey]['resources.event.merge_request']) ? $objConfig[$eventKey]['resources.event.merge_request'] : 'deny',
                    'wiki_page' => isset($objConfig[$eventKey]['resources.event.wiki_page']) ? $objConfig[$eventKey]['resources.event.wiki_page'] : 'deny',
                    'pipeline' => isset($objConfig[$eventKey]['resources.event.pipeline']) ? $objConfig[$eventKey]['resources.event.pipeline'] : 'deny',
                ];
            }
        }
        return $result;
    }
}