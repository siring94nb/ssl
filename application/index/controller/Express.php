<?php
namespace app\index\controller;



/**
 * lilu
 * 快递控制器
 * 快递100
 */
class Express
{
    /**
     * lilu
     * 快递100
     * com    快递公司编码
     * num    快递单号
     * phone  手机号
     * from   出发地   选填
     * to     目的地   选填
     */
    public function express($com,$num)
    {
         //参数设置
         $key = 'aHwjIroI593';						//客户授权key
	     $customer = 'BE0B20E7A3394C4D39072AE88C6F4748';					//查询公司编号
            $param = array (
                'com' => $com,			//快递公司编码
                'num' => $num,	//快递单号
                // 'phone' => '',				//手机号
                // 'from' => '',				//出发地城市
                // 'to' => '',					//目的地城市
                // 'resultv2' => '1'			//开启行政区域解析
            );
	
	//请求参数
    $post_data = array();
    $post_data["customer"] = $customer;
    $post_data["param"] = json_encode($param);
    $sign = md5($post_data["param"].$key.$post_data["customer"]);
    $post_data["sign"] = strtoupper($sign);
	
    $url = 'http://poll.kuaidi100.com/poll/query.do';	//实时查询请求地址
    
	$params = "";
    foreach ($post_data as $k=>$v) {
        $params .= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
    }
    $post_data = substr($params, 0, -1);
    //  cho '请求参数<br/>'.$post_data;    //请求参数测试
	
	//发送post请求
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	$data = str_replace("\"", '"', $result );
    $data = json_decode($data);
    
    echo '<br/><br/>返回数据<br/>';
    echo var_dump($data);

    }
    
}
