<?php


/* *
 * 配置文件
 * 版本：3.3
 * 日期：2012-07-19
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

 * 提示：如何获取安全校验码和合作身份者id
 * 1.用您的签约支付宝账号登录支付宝网站(www.alipay.com)
 * 2.点击“商家服务”(https://b.alipay.com/order/myorder.htm)
 * 3.点击“查询合作者身份(pid)”、“查询安全校验码(key)”

 * 安全校验码查看时，输入支付密码后，页面呈灰色的现象，怎么办？
 * 解决方法：
 * 1、检查浏览器配置，不让浏览器做弹框屏蔽设置
 * 2、更换浏览器或电脑，重新登录查询。
 */

//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//合作身份者id，以2088开头的16位纯数字
//$alipay_config['partner'] = '2088921120585982';
//$alipay_config['partner'] = '2088921891438094';//公司
$alipay_config['partner'] = '2088721153912991';

//安全检验码，以数字和字母组成的32位字符
//$alipay_config['key'] = 'tvebsohi4n6sdxzdenw15r3ru17brlao';
//$alipay_config['key'] = 'idfd9z9usk4v9c89jifu7n62dwkbg1uy';//公司
$alipay_config['key'] = '2yaltrfu0d0699657m5uh3nm1s2z4ml0';
//$alipay_config['seller_id'] ='50087335@qq.com';//公司
$alipay_config['seller_id'] ='347211838@qq.com';

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
define('LEM_ALIPAY', '717797081@qq.com'); //官方支付宝账号
//define('LEM_ALIPAY_PASSWORD', ''); //支付宝登陆密码
//define('LEM_PAYMENT_CODE', ''); //支付密码
//define('LEM_COMPANY', '深圳市思锐信息技术有限公司');
define('LEM_COMPANY', '甩甩乐');

//签名方式 不需修改
$alipay_config['sign_type'] = strtoupper('MD5');

//字符编码格式 目前支持 gbk 或 utf-8
$alipay_config['input_charset'] = strtolower('utf-8');

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
$alipay_config['cacert'] = getcwd() . '\\cacert.pem';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$alipay_config['transport'] = 'http';

//购物支付即时回调，异步回调url
$alipay_config['return_url'] = 'http://ssl.siring.com.cn/index';
$alipay_config['notify_url'] = 'http://ssl.siring.com.cn/alinotify';

?>