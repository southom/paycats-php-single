<?php

/**
 * 支付猫单文件sdk
 * 支付猫专为个人用户提供合规的收款与付款接口服务，欢迎使用。
 *
 * @author 支付猫官方
 * @license MIT
 *
 * 官方网站 https://www.paycats.cn
 * 接口文档 https://www.paycats.nc/docs
 * 技术支持QQ群 738330782
 */
class Paycats
{
    private $config;

    const BASE_API = 'https://api.paycats.cn/v1/';
    const NATIVE_PAY = 'pay/wx/native';
    const JSAPI_PAY = 'pay/alipay/jsapi';
    const F2F_PAY = 'pay/alipay/f2f';
    const CASHIER_PAY = 'pay/wx/cashier';
    const WX_OPENID = 'wx/openid';
    const WX_USER = 'wx/user';
    const QUERY_ORDER = 'order/query';
    const CLOSE_ORDER = 'order/close';
    const REVERSE_ORDER = 'order/reverse';
    const REFUND_ORDER = 'order/refund';
    const QUERY_BANK_INFO = 'bank';

    const ORDER_SUCCEEDED = 'order.succeeded';
    const ORDER_CLOSED = 'order.closed';
    const REFUND_SUCCEEDED = 'refund.succeeded';

    public function __construct($config)
    {
        if (!isset($config['key'])) {
            throw new PaycatsInvalidConfigException('MissingParameter: key');
        }

        if (!isset($config['mch_id'])) {
            throw new PaycatsInvalidConfigException('MissingParameter: mch_id');
        }

        $this->config = $config;
    }

    public function nativePay($data)
    {
        if (
            !(isset($data['total_fee']) && $data['total_fee'] >= 1) ||
            !(isset($data['out_trade_no']) && $data['out_trade_no']) ||
            !(isset($data['body']) && $data['body'])
        ) {
            throw new PaycatsInvalidArgumentException();
        }

        return $this->doRequest(self::NATIVE_PAY, $data);
    }

    public function jsapiPay($data)
    {
        if (
            !(isset($data['total_fee']) && $data['total_fee'] > 1) ||
            !(isset($data['out_trade_no']) && $data['out_trade_no']) ||
            !(isset($data['openid']) && $data['openid']) ||
            !(isset($data['body']) && $data['body'])
        ) {
            throw new PaycatsInvalidArgumentException();
        }

        return $this->doRequest(self::JSAPI_PAY, $data);
    }

    public function f2fPay($data)
    {
        if (
            !(isset($data['total_fee']) && $data['total_fee'] >= 1) ||
            !(isset($data['out_trade_no']) && $data['out_trade_no']) ||
            !(isset($data['body']) && $data['body'])
        ) {
            throw new PaycatsInvalidArgumentException();
        }

        return $this->doRequest(self::F2F_PAY, $data);
    }

    public function cashierPay($data)
    {
        if (
            !(isset($data['total_fee']) && $data['total_fee'] > 1) ||
            !(isset($data['out_trade_no']) && $data['out_trade_no']) ||
            !(isset($data['openid']) && $data['openid']) ||
            !(isset($data['callback_url']) && $data['callback_url']) ||
            !(isset($data['body']) && $data['body'])
        ) {
            throw new PaycatsInvalidArgumentException();
        }

        return $this->doRequest(self::CASHIER_PAY, $data);
    }

    public function closeOrder($data)
    {
        $this->checkOrderRequestData($data);

        return $this->doRequest(self::CLOSE_ORDER, $data);
    }

    public function reserveOrder($data)
    {
        $this->checkOrderRequestData($data);

        return $this->doRequest(self::REVERSE_ORDER, $data);
    }

    public function refundOrder($data)
    {
        $this->checkOrderRequestData($data);

        return $this->doRequest(self::REFUND_ORDER, $data);
    }

    public function queryOrder($data)
    {
        $this->checkOrderRequestData($data);

        return $this->doRequest(self::QUERY_ORDER, $data);
    }

    public function getWxOpenId($data)
    {
        $this->checkOrderRequestData($data);

        $this->doRedirect(self::WX_OPENID, $data);
    }

    public function getWxUser($data)
    {
        $this->checkOrderRequestData($data);

        return $this->doRequest(self::WX_USER, $data);
    }

    private function doRedirect($url, $data)
    {
        if (!isset($data['sign'])) {
            $data['sign'] = PaycatsSignature::make($data, $this->config['key']);
        }

        $url = sprintf('%s?%s', $url, http_build_query($data));

        echo '<script>location.href="'.$url.'"</script>';
        return ;
    }

    private function checkOrderRequestData($data)
    {
        if (!(isset($data['order_no']) && $data['order_no'])) {
            throw new PaycatsInvalidArgumentException();
        }
    }

    /**
     * 发起请求
     */
    private function doRequest($url, $data, $https = true)
    {
        $method = 'POST';

        if (!isset($data['sign'])) {
            $data['sign'] = PaycatsSignature::make($data, $this->config['key']);
        }
        $payload = json_encode($data);

        $url = self::BASE_API.$url;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        }

        if ($method === 'POST') {
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload)
                ]
            );

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($data) {
                if (is_array($data)) {
                    $data = http_build_query($data);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $data);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        $response = curl_exec($ch);
        if ($response === FALSE) {
            throw new PaycatsHttpException(curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response);
    }

    /**
     * 处理通知请求
     * @param callable $callback
     * @return bool
     * @throws PaycatsInvalidSignatureException
     */
    public function serve(callable $callback)
    {
        $data = $_POST;

        if (isset($data['sign'])) {
            if (!PaycatsSignature::verify($data, $this->config['key'])) {
                throw new PaycatsInvalidSignatureException('签名错误');
            }
        }

        try {
            $ret = $callback($data);

            if ($ret) {
                return true;
            }
        } catch (\Exception $e) {
            // no code
        }

        return false;
    }
}


class PaycatsSignature
{
    public static function trimEmptyParam($data)
    {
        $result = [];

        foreach ($data as $k => $v) {
            if (trim((string)$v) === '')
                continue;

            $result[$k] = $v;
        }

        return $result;
    }

    public static function make($data, $key)
    {
        $data = static::trimEmptyParam($data);

        ksort($data);
        $sign = strtoupper(md5(urldecode(http_build_query($data)).'&key='.$key));

        return $sign;
    }

    public static function verify(array $data, string $key, string $sign = null): bool
    {
        if (!$sign) {
            if (!isset($data['sign'])) {
                throw new PaycatsInvalidSignatureException();
            }

            $sign = $data['sign'];
            unset($data['sign']);
        }

        if (!$sign) {
            throw new PaycatsInvalidSignatureException();
        }

        $tSign = static::make($data, $key);
        if ($sign !== $tSign) {
            throw new PaycatsInvalidSignatureException();
        }

        return true;
    }
}

class PaycatsNotifyType
{
    const ORDER_SUCCEEDED = 'order.succeeded';
    const ORDER_CLOSED = 'order.closed';
    const REFUND_SUCCEEDED = 'refund.succeeded';
}
class PaycatsException extends \Exception {}
class PaycatsHttpException extends PaycatsException{}
class PaycatsInvalidArgumentException extends PaycatsException{}
class PaycatsInvalidConfigException extends PaycatsException{}
class PaycatsInvalidSignatureException extends PaycatsException{}