<?php
/**
 *作者：誓言
 *日期：2020-12-21
 */
class WxPayCard
{
    protected $mchid;
    protected $appid;
    protected $apiKey;
    protected $totalFee;
    protected $outTradeNo;
    protected $orderName;
    protected $authCode;
    /**
     * 构造函数，初始化必要参数
     * WxPayCard constructor.
     * @param string $mchid
     * @param string $appid
     * @param string $key
     */
    public function __construct($mchid='', $appid='', $key='')
    {
        $this->mchid = $mchid;
        $this->appid = $appid;
        $this->apiKey = $key;
    }
    /**
     * 设置金额
     * @param $totalFee
     */
    public function setTotalFee($totalFee)
    {
        $this->totalFee = $totalFee;
    }
    /**
     * 设置订单号
     * @param $outTradeNo
     */
    public function setOutTradeNo($outTradeNo)
    {
        $this->outTradeNo = $outTradeNo;
    }
    /**
     * 设置订单名称
     * @param $orderName
     */
    public function setOrderName($orderName)
    {
        $this->orderName = $orderName;
    }
    /**
     * 设置用户付款码
     * @param $authCode
     */
    public function setAuthCode($authCode)
    {
        $this->authCode = $authCode;
    }
    /**
     * 发起订单
     * @return array
     */
    public function createJsBizPackage()
    {
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
            'total_fee' => $this->totalFee,
            'out_trade_no' => $this->outTradeNo,
            'order_name' => $this->orderName,
            'auth_code' => $this->authCode,
        );
        //$orderName = iconv('GBK','UTF-8',$orderName);
        $unified = array(
            'appid' => $config['appid'],
            'attach' => 'pay',             //商家数据包，原样返回，如果填写中文，请注意转换为utf-8
            'body' => $config['order_name'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::createNonceStr(),
            'out_trade_no' => $config['out_trade_no'],
            'spbill_create_ip' => '127.0.0.1',
            'total_fee' => intval($config['total_fee'] * 100),       //单位 转为分
            'auth_code'=>$config['auth_code'],     //收款码,
            'device_info'=>'dedemao001',        //终端设备号(商户自定义，如门店编号)
//            'limit_pay'=>'no_credit'            //指定支付方式  no_credit--指定不能使用信用卡支付
        );
        $unified['sign'] = self::getSign($unified, $config['key']);
        $responseXml = self::curlPost('https://api.mch.weixin.qq.com/pay/micropay', self::arrayToXml($unified));
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            die('parse xml error');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            die('支付失败：错误码：'.$unifiedOrder->err_code.'。错误码说明：https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_10&index=1#7');
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            die('支付失败：错误码：'.$unifiedOrder->err_code.'。错误码说明：https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_10&index=1#7');
        }
        return (array)$unifiedOrder;
    }
    /**
     * 回调处理
     * @return SimpleXMLElement
     */
    public function notify()
    {
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
        );
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($postObj === false) {
            die('parse xml error');
        }
        if ($postObj->return_code != 'SUCCESS') {
            die($postObj->return_msg);
        }
        if ($postObj->result_code != 'SUCCESS') {
            die($postObj->err_code);
        }
        $arr = (array)$postObj;
        unset($arr['sign']);
        if (self::getSign($arr, $config['key']) == $postObj->sign) {
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return $postObj;
        }
    }
    /**
     * 发送get请求
     *
     * @param string $url
     * @param array $options
     * @return mixed
     */
    public static function curlGet($url = '', $options = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    /**
     * 发送post请求
     * @param string $url
     * @param string $postData
     * @param array $options
     * @return bool|string
     */
    public static function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    /**
     * 生成随机数
     * @param int $length
     * @return string
     */
    public static function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    /**
     * 数组转XML
     * @param $arr
     * @return string
     */
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }
    /**
     * 获取签名
     * @param $params
     * @param $key
     * @return string
     */
    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }
    /**
     * 拼接参与签名的参数
     * @param $paraMap
     * @param bool $urlEncode
     * @return false|string
     */
    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}