<h1 align="center">paycats-php-single </h1>
<p align="center">支付猫个人支付官方 PHP SDK 单文件版. </p>

## Requirement

1. PHP >= 5.4

## Documentation

[官网](https://www.paycats.cn)  · [文档](https://www.paycats.cn/docs)  


## Installing

1. 下载文件
2. 引入

    ```php
    <?php
 
    include './paycats.php';
    ```

## Usage
### 发起支付

```php
<?php

include './paycats.php';

$config = [
  'mch_id' => 'you app id',
  'key' => 'your api key'  
];
$paycats = new Paycats($config);

$data = [
    'mch_id' => '162934501',
    'total_fee' => 1,
    'out_trade_no' => 'test-order-18481',
    'sign' => '',
    'body' => '',
];

try {
    $result = $paycats->nativePay($data);
} catch (PaycatsExceptionException $exception) {
    // 异常
    echo $exception->getMessage();
}

if ($result['return_code'] === 0) {
    // 已经验证签名
    // 请求成功，业务逻辑
    // your code
}
```

### 接收 webhook 通知

```php
<?php

include './paycats.php';

$config = [
  'mch_id' => 'you app id',
  'key' => 'your api key'  
];
$paycats = new Paycats($config);

$ret = $paycats->serve(function ($notifyData) {
    switch ($notifyData['notify_type']) {
        case PaycatsNotifyType::ORDER_SUCCEEDED:
            // 订单支付成功通知
            
            break;
            
        case PaycatsNotifyType::REFUND_SUCCEEDED:
            // 订单退款成功 
            
            break;
    }
    
    // 处理成功返回 true,  失败返回 false
    return true;
});

if ($ret) {
    echo 'success';

    return;
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'fail';

    return;
}
```

## License

MIT
