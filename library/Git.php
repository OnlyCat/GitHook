<?php
class Git {

    private $objRequest = null;

    /**
     * git处理程序
     */
    public function Process(){
        $result = [];

        // 获取输入信息
        $this->getRequest();

        // 处理本地任务情况
        $objLocakTaskItem = $this->getLocalTask();
        if(false == empty($objLocakTaskItem)) {

            // 执行本地任务列表
            $localTaskRet = $this->execLocalTask($objLocakTaskItem);
            if('master' == $this->objRequest['sync_schema']) {
                $result['master'] = $localTaskRet;
            } else {
                $result = $localTaskRet;
            }

            // 如果是主程序则进行消息扩散
            $envConfig = BootStrap::getConfig('ENV');
            if('master' == strtolower($envConfig['schema']) && 'master' == $this->objRequest['sync_schema']) {
                $remoteTaskList = $this->getRemoteTask($objLocakTaskItem);
                $this->objRequest['sync_schema'] = 'slave';
                $result = $result + $this->execRemoteTask($remoteTaskList, $this->objRequest, $result);
                $this->sendMail($result);
            }
        }
        return $result;
    }


    /**
     * 获取输入信息
     */
    private function getRequest(){
        $objInputStream = json_decode(file_get_contents('php://input'), true);
        $this->objRequest = isset($objInputStream) ? $objInputStream : $_POST;
        $this->objRequest['sync_schema'] = isset($this->objRequest['sync_schema']) ?  $this->objRequest['sync_schema'] : 'master';
    }


    /**
     * 创建远程调用队列
     * @param $localTaskItem
     */
    private function getRemoteTask($localTaskItem) {
        $result = [];
        $appRemoteConfig = Apply::getConfig('remote');
        foreach ($appRemoteConfig as $key => $value) {
            if($localTaskItem['git_name'] == $value['name']) {
                $result[$key] = [
                    'project_name' => $localTaskItem['git_name'],
                    'remote_url' => $value['url']
                ];
            }
         }
        return $result;
    }


    /**
     * 获取本地任务
     * @return bool
     */
    private function getLocalTask() {
        $appLocalConf = Apply::getConfig('local');
        $gitEvent = isset($this->objRequest['object_kind']) ? $this->objRequest['object_kind'] : "";
        $gitName = isset($this->objRequest['project']['name']) ? $this->objRequest['project']['name'] : "";
        $gitUrl = isset($this->objRequest['project']['git_http_url']) ? $this->objRequest['project']['git_http_url'] : "";
        $objTaskItem = $this->filterEvent($appLocalConf, $gitEvent, $gitName, $gitUrl);
        return $objTaskItem;
    }


    /**
     * 执行本地同步代码信息
     * @param $objTaskItem
     * @return array
     */
    private function execLocalTask($objTaskItem) {
        $ret = $this->localCodeSync($objTaskItem);
        $result = [
            'project_name' => $objTaskItem['project_name'],
            'git_name' => $objTaskItem['git_name'],
            'timestamp' => time(),
            'ip' => $_SERVER['SERVER_ADDR'],
            'sync_ret' => $ret,
        ];
        return $result;
    }



    /**
     * 过滤git事件信息
     * @param $appLocalConf
     * @param $gitEvent
     * @return bool
     */
    private function filterEvent($appLocalConf, $gitEvent, $gitName, $gitUrl) {
        $result = false;
        foreach ($appLocalConf as $key => $value) {
            if($gitName != $value['project']['name'] || $gitUrl != $value['project']['url']) {
                continue;
            }
            $envet = strtolower($gitEvent);
            if(true == isset($value['event'][$envet]) && 'allow' == $value['event'][$envet]) {
                $result = [
                    'project_name' => $key,
                    'git_name' => $value['project']['name'],
                    'git_url' => $value['project']['url'],
                    'git_event' => $envet,
                    'local_path'=> $value['project']['path'],
                ];
                break;
            }
        }
        return $result;
    }


    /**
     * 本地代码同步
     * @param $objTaskItem
     * @return bool
     */
    private function localCodeSync($objTaskItem) {
        $shellScript = sprintf("sh %s/script/sync_code.sh %s", dirname(dirname(__FILE__)), $objTaskItem['local_path']);
        $syncRet = shell_exec($shellScript);
        if(1 == $syncRet) {
            return true;
        }
        return false;
    }


    /**
     * 执行远程调用队列
     * @param $objRemoteTaskList
     */
    private function execRemoteTask($objRemoteTaskList, $hookInfo, &$result) {
        foreach ($objRemoteTaskList as $key => $value) {
            $conn = Curl::getInstance($value['remote_url'], 300);
            $ret = $conn->post(null, http_build_query($hookInfo));
            $result['slave'][$key] = (false == $ret) ? false : json_decode($ret, true);
            Curl::close($value['remote_url']);
            usleep(100);
        }
        return $result;
    }


    /**
     * 合成数据
     * @param $data
     * @return string
     */
    private function sendMail($sendQueue) {

        $strMailStyle = $this->getMailStyle();
        $strMailTemplate = $this->getMailHtmlTemplate();
        $operator = $this->objRequest['user_username'];
        $commit = $this->objRequest['commits'][0]['message'];
        $href = sprintf("<div style='overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 80%%;'<a href=%s>%s</a></div>", $this->objRequest['commits'][0]['url'], $this->objRequest['commits'][0]['url']) ;


        $syscStaus = sprintf("<tr><td>主</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>",
            $sendQueue['master']['project_name'],
            $sendQueue['master']['git_name'],
            $sendQueue['master']['ip'],
            date('Y-m-d G:i:s', $sendQueue['master']['timestamp']),
            $sendQueue['master']['sync_ret'] ? "<span class='text-success'>成功</span>" : "<span class='text-danger'>成功</span>"
        );

        foreach ($sendQueue['slave'] as $key => $value) {
            $syscStaus .= sprintf("<tr><td>从</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>",
                $key,
                $value['git_name'],
                $value['ip'],
                date('Y-m-d G:i:s', $value['timestamp']),
                $value['sync_ret'] ? "<span class='text-success'>成功</span>" : "<span class='text-danger'>成功</span>"
            );
        }

        $data = sprintf($strMailTemplate, $strMailStyle, $operator, $commit, $href, $syscStaus);

        // 邮件发送
        Mail::Initialize()->send('上线服务通知', $data);
        return true;
    }


    /**
     * 加载邮件模版样式
     * @return string
     */
    public function getMailStyle() {
        $result = <<<EOF
                * {padding:0; margin:0}
                body { background:#fafafa}
                .main {width:80%; min-height: 500px;  margin:0 auto; margin-top:50px; border: 1px solid #f3f0f0; border-radius: 4px; padding:5px; background:#ffffff; position: relative;}
                .header { height:50px; width:100%; border-bottom: 1px solid #f3f0f0; padding-bottom:5px;}
                .logo {width:320px; height:100%;float: left;}
                .logo img {height:80%;  float: left; margin-top: 10px;}
                .logo span {display: inline-block; font-size: 20px; text-align: center; font-weight: bold; padding-left: 5px; margin-top: 16px; background-image: linear-gradient(130deg, #94376a, #844aa9); -webkit-background-clip: text; color: transparent;}
                .tip {height: 100%; float:right}
                .tip span {height: 100%;  display: inline-block; line-height: 80px; font-size: 12px;  font-style: italic;  padding-right: 5px; color: #7d7979;  text-indent: 2px; letter-spacing: 5px; font-weight: 300;}
                .footer {width: 97%; position: inherit;  bottom: 0; border-top: 1px solid #f3f0f0; padding: 5px;text-align: left;}
                .footer p {font-size:7.5pt; color:#a9a9a9; font-weight: 300;}
                .content { width:100%; padding:5px; box-sizing: border-box; min-height: 400px;}
                .inner-mail {margin:0 auto; width: 100%; padding:10px 20px; box-sizing: border-box;}
                .inner-mail-table {width:100%; text-align: center; font-size:15px;}
                .inner-mail-table tbody{margin-top:20px;}
                .inner-mail-table th{font-weight: 400; color: #4e4c4c; font-size: 13px; border: 1px solid #eeeeee; padding:2px 1px; background: #f5f5f5}
                .inner-mail-table td{font-weight: 300; border:1px solid #eeeeee; padding:5px 2px; font-size:13px}
                .subject-line{font-size: 13px; color: #ffffff; font-weight: 400 !important; text-align: left;  background: #53635e;  padding-left: 10px;}
                .inner-describe td{ text-align: left; padding-left:10px}
                .inner-status td{padding-left:10px}
                .text-success {font-weight:bold; color:green}
                .text-danger {font-weight:bold; color:red}
EOF;
        return $result;

    }


    /**
     * 加载模版信息
     * @return string
     */
    public function getMailHtmlTemplate() {
        $result = <<<EOF
        <!doctype html>
        <html>
            <meta charset="utf-8">
            <head>
                <style>
                         %s
                </style>
            </head>
            <body>
                <div class="main">
                    <div class="header">
                        <div class="logo">
<!--                            <img src= "#">-->
                            <span>上线通知</span>
                        </div>
                        <div class="tip">
                            <span>Online Business otice</span>
                        </div>
                    </div>
                    <div class="content">
                        <div class="inner-mail">
                            <table class="inner-mail-table"  border="1" bordercolor="#fff" style="border-collapse:collapse;">
                            <tbody class="inner-describe">	
                                <tr>
                                    <td colspan="6" class="subject-line">发布信息</td>
                                </tr>
                                <tr>
                                    <th  width="100px" >操作人</th>
                                    <td colspan="5">%s</td>
                                </tr>
                                <tr>
                                    <th  width="100px" >描述信息</th>
                                    <td colspan="5">%s</td>
                                </tr>
                                <tr>
                                    <th>详情链接</th>
                                    <td colspan="5">%s</td>
                                </tr>
                            </tbody>
                            <tbody class="inner-status">
                                <tr>
                                    <td colspan="6" class="subject-line">同步状态</td>
                                </tr>
                                <tr>
                                    <th>同步级别</th>
                                    <th>项目名称</th>
                                    <th>GIT名称</th>
                                    <th>服务器IP</th>
                                    <th>发布时间</th>
                                    <th>发布状态</th>
                                </tr>
                                %s
                            </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="footer">
                        <p></p>
                    </div>
                </div>
            </body>
        </html>
EOF;
    return $result;
    }


}