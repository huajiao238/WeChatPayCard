<?php
/**
 *作者：誓言
 *日期：2020-12-21
 */
/**
 * 刷卡支付
 * 提醒：提交支付请求后微信会同步返回支付结果（没有异步回调通知）。当返回结果为“系统错误”时，商户系统等待5秒后调用【查询订单API】，查询支付实际交易结果；当返回结果为“USERPAYING”时，商户系统可设置间隔时间(建议10秒)重新查询支付结果，直到支付成功或超时(建议30秒)；
 */
header('Content-type:text/html; Charset=utf-8');
require_once 'WxPayCard.php';
$mchid = 'xxxxx';          //微信支付商户号 PartnerID 通过微信支付商户资料审核后邮件发送
$appid = 'xxxxx';  //公众号APPID 通过微信支付商户资料审核后邮件发送
$apiKey = 'xxxxx';   //https://pay.weixin.qq.com 帐户设置-安全设置-API安全-API密钥-设置API密钥
$outTradeNo = uniqid();     //你自己的商品订单号，不能重复
$payAmount = 0.01;          //付款金额，单位:元
$orderName = '支付测试';    //订单标题
$authCode = 'xxxxx';         //用户付款码（商户使用设备扫码用户的付款条码读取到的条码数字，或 打开微信-》我-》钱包-》收付款 点击可查看付款码数字）
$wxPay = new WxPayCard($mchid,$appid,$apiKey);
$wxPay->setTotalFee($payAmount);
$wxPay->setOutTradeNo($outTradeNo);
$wxPay->setOrderName($orderName);
$wxPay->setAuthCode($authCode);
$arr = $wxPay->createJsBizPackage();
if($arr['return_code']=='SUCCESS'){
    echo '付款成功！返回信息如下：<br><hr>';
    echo '<pre>'.print_r($arr).'</pre>';
    exit();
}
exit('error');