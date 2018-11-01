<?php
/**
 * Created by PhpStorm.
 * User: youthage
 * Date: 2018/11/1 2:16 PM
 */

namespace Httpsqs;

class HttpSQS
{
    /**
     * http methods
     */
    const GET = 'GET';
    const POST = 'POST';

    /**
     * curl链接资源
     */
    protected $curl;

    /**
     * httpsqs host
     */
    protected $httpsqs_host;

    /**
     * httpsqs port
     */
    protected $httpsqs_port;

    /**
     * httpsqs auth
     */
    protected $httpsqs_auth;

    /**
     * httpsqs charset
     */
    protected $httpsqs_charset;


    public function __construct($host='127.0.0.1', $port='1218', $auth='myauth', $charset='utf-8') {
        $this->httpsqs_host = $host;
        $this->httpsqs_port = $port;
        $this->httpsqs_auth = $auth;
        $this->httpsqs_charset = $charset;
        return true;
    }

    /**
     * 入队
     */
    public function put($queue_name, $queue_data)
    {

        $params = array(
            'name' => $queue_name
        );

        //post 请求
        $result = $this->request($params, $queue_data, self::POST);

        if ($result["data"] == "HTTPSQS_PUT_OK") {
            return true;
        } else if ($result["data"] == "HTTPSQS_PUT_END") {
            return $result["data"];
        }
    }

    /**
     * 出队（获取值）
     */
    public function get($queue_name)
    {
        $result = $this->gets($queue_name);

        return $result["data"];
    }

    /**
     * 出队（位置和值）
     */
    public function gets($queue_name)
    {
        $params = array(
            'name' => $queue_name,
            'opt' => 'get',
        );

        $result = $this->request($params);
        if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
            return false;
        }

        return $result;
    }

    /**
     * 获取队列状态
     */
    public function status($queue_name, $type = 'status')
    {
        $params = array(
            'name' => $queue_name,
            'opt' => $type,
        );

        $result = $this->request($params);
        if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
            return false;
        }
        return $result["data"];
    }

    /**
     * 查看指定队列位置点的内容
     */
    public function view($queue_name, $queue_pos)
    {
        $params = array(
            'name' => $queue_name,
            'pos' => $queue_pos,
            'opt' => 'view'
        );

        $result = $this->request($params);
        if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
            return false;
        }
        return $result['data'];
    }

    /**
     * 重置指定队列
     *
     * @return $bool
     */
    public function reset($queue_name)
    {
        $params = array(
            'name' => $queue_name,
            'opt' => 'reset'
        );

        $result = $this->request($params);
        if ($result["data"] == "HTTPSQS_RESET_OK") {
            return true;
        }
        return false;
    }

    /**
     * 更改指定队列的最大队列数量,默认100万条
     * @param $queue_name string
     * @param $num int  $num >=10 && $num <= 1000000000
     *
     * @return bool
     */
    public function maxqueue($queue_name, $num)
    {
        $params = array(
            'name' => $queue_name,
            'num' => $num,
            'opt' => 'maxqueue'
        );

        $result = $this->request($params);
        if ($result["data"] == "HTTPSQS_MAXQUEUE_OK") {
            return true;
        }
        return false;
    }

    /**
     * 不停止服务的情况下，修改定时刷新内存缓冲区内容到磁盘的间隔时间
     * @param $num int   $num >=1 and <= 1000000000
     *
     * @return bool
     */
    public function synctime($num)
    {
        $params = array(
            'num' => $num,
            'name' => 'httpsqs_synctime',
            'opt' => 'synctime'
        );

        $result = $this->request($params);
        if ($result["data"] == "HTTPSQS_SYNCTIME_OK") {
            return true;
        }
        return false;
    }

    /**
     * Make a HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param array  $params
     * @param array  $options
     *
     * @return array
     */
    protected function request($params = array(),$data = '', $method = self::GET)
    {
        //初始化链接
        $this->curl = curl_init();
        //请求参数
        $default = array(
            'opt'  => 'put',
            'auth' => $this->httpsqs_auth
        );
        //构造请求参数
        $query = http_build_query(array_merge($default, $params));

        $url = "http://{$this->httpsqs_host}:{$this->httpsqs_port}/?".$query;

        //设置header头
        $header = array(
            "Content-type: text/plain;charset={$this->httpsqs_charset}"
        );
        curl_setopt($this->curl, CURLOPT_HEADER, 1);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER,$header);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->curl, CURLOPT_URL, $url);

        if($method === 'POST' && !empty($data)){
            curl_setopt($this->curl, CURLOPT_POST, 1);//post提交方式
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        }


        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($this->curl);
        if (curl_errno($this->curl)) {
            throw new \Exception(curl_error($this->curl), 1);
        }
        //分解响应
        $response = $this->splitHeaders($response);

        //关闭cURL资源，并且释放系统资源
        curl_close($this->curl);

        return $response;
    }

    /**
     * Split the HTTP headers.
     *
     * @param string $rawHeaders
     *
     * @return array
     */
    protected function splitHeaders($rawHeaders)
    {
        $headers = array();
        $lines = explode("\n", trim($rawHeaders));
        $headers['HTTP'] = array_shift($lines);
        foreach ($lines as $h) {
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }

        // 获得响应结果里的：头大小
        $headerSize = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        // 根据头大小去获取消息体内容
        $body = substr($rawHeaders, $headerSize);

        $result = array(
            'pos' => isset($headers['Pos'])?$headers['Pos']:0,
            'data'=> $body
        );
        return $result;
    }

}