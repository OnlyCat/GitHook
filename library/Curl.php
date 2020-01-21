<?php
class Curl {
    private static $init = null;
    private $curl = null;
    private $baseUrl = null;

    private function __construct($url) {
        $this->curl = curl_init();
        // 设置基础URL地址
        $this->baseUrl = $url;
        // 设置来源
        curl_setopt($this->curl, CURLOPT_REFERER, $url);
        // 对认证证书来源的检查
        $request = parse_url($url);
        if ("https" == $request['scheme']) {
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            // 从证书中检查SSL加密算法是否存在
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
    }

    /**
     * 初始类
     * @return mixed|null
     */
    public static function getInstance($url, $timeout = 0) {
        $key = md5($url);
        if (false == isset(self::$init[$key])) {
            self::$init[$key] = new static($url);
            self::$init[$key]->initConf($timeout);
        }
        return self::$init[$key];
    }

    /**
     * 初始化参数信息
     */
    private function initConf($timeout = 0) {
        // http版本
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_NONE);
        // 毫秒超时控制
        curl_setopt($this->curl, CURLOPT_NOSIGNAL, 1);
        // 连接超时时间
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
        // 超时时间
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $timeout);
        // 内容存储到变量
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        // 重定向次数
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 0);
        // 显示返回的Header区域内容
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        // 设置curl浏览器类型
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36');
        // http头信息
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, ["Cache-Control: max-age=0", "Connection: keep-alive", "Keep-Alive: 300"]);
        // 是否进行头部输出
        curl_setopt($this->curl, CURLINFO_HEADER_OUT, false);
    }

    /**
     * 设置cookie信息
     * @param $cookie
     * @return bool
     */
    public function setCookie($cookie = '') {
        if (true == is_string($cookie)) {
            curl_setopt($this->curl, CURLOPT_COOKIE, $cookie);
            return true;
        }
        return false;
    }

    /**
     * 设置配置信息
     * @param $key
     * @param $value
     * @return bool
     */
    public function setConfig($key, $value) {
        if (defined($key) && false == is_null($value)) {
            curl_setopt($this->curl, $key, $value);
            return true;
        }
        return false;
    }

    /**
     * 获取POST消息信息
     * @param array $params
     * @param array $fields
     * @param array $files
     * @return bool|string
     */
    public function post($params = [], $fields = [], $files = [], $reaty = 0) {
        $param = "";
        if (false == empty($params)) {
            $param = sprintf('?%s', http_build_query($params));
        }
        // 设置请求地址
        curl_setopt($this->curl, CURLOPT_URL, $this->baseUrl . $param);
        // 是否是POST模式
        curl_setopt($this->curl, CURLOPT_POST, true);
        // 处理文件上传信息
        if (false == empty($files)) {
            curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, true);
            $files = array_map(function ($v) {
                if (class_exists('\CURLFile')) {
                    return new \CURLFile(realpath($v));
                } else {
                    return '@' . realpath($v);
                }
            }, $files);
            $fields = array_merge($fields, $files);
        }
        // 字段信息
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields);
        // 执行请求
        $result = curl_exec($this->curl);
        // 执行请求
        do {
            if (200 != curl_getinfo($this->curl, CURLINFO_HTTP_CODE)) {
                $result = curl_exec($this->curl);
            } else {
                $reaty = 0;
            }
        } while ($reaty--);
        return $result;
    }

    /**
     * get方式获取消息
     * @param array $params
     * @return bool|string
     */
    public function get($params = null, $reaty = 0) {
        $param = "";
        if (false == empty($params)) {
            $param = sprintf('?%s', is_string($params) ? $params : http_build_query($params));
        }
        curl_setopt($this->curl, CURLOPT_URL, $this->baseUrl . $param);
        // 执行请求
        $result = curl_exec($this->curl);
        // 执行请求
        do {
            if (200 != curl_getinfo($this->curl, CURLINFO_HTTP_CODE)) {
                $result = curl_exec($this->curl);
            } else {
                $reaty = 0;
            }
        } while ($reaty--);
        return $result;
    }

    public function getCurlInfo($type) {
        $info = curl_getinfo($this->curl);
        if (in_array($type, array_keys($info))) {
            return $info[$type];
        }
        return false;
    }

    public function __destruct() {
        curl_close($this->curl);
    }

    /**
     * 使用完毕后关闭
     */
    public static function close($url) {
        // 关闭连接
        $key = md5($url);
        unset(self::$init[$key]);
    }
}
