<?php
namespace app\index\controller;

use think\Controller;
use app\index\controller\Base;
use app\index\Model\Game as Game2;
use think\Session;
use think\Request;
use think\Db;
use app\index\controller\Wxpay2 as pay;
use app\index\controller\Alipay as alipay;

/**
 * lilu
 * 游戏模块
 */
class Game extends Base
{

    /**
     * lilu
     * 根据概率获取游戏种类及游戏接口
     * token
     */
    public function game()
    {
          
          //随机取出1条数据
          $sql='SELECT * FROM tb_problem_house WHERE id >= ((SELECT MAX(id) FROM tb_problem_house)-(SELECT MIN(id) FROM tb_problem_house)) * RAND() + (SELECT MIN(id) FROM tb_problem_house) LIMIT 1';
          $list=DB::query($sql);
          foreach($list as $k =>$v){
            $answer=json_decode($v['answer']);
            $list[$k]['answer']=$answer;
            $problem_type=json_decode($v['problem_type'],true);
            $arr='';
            $num=count($problem_type);
            $i=0;
            
            foreach($problem_type as $k2=>$v2){
                if($k2=='twdl'){
                    $problem_type[$k2]='天文地理';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='rwls'){
                    $problem_type[$k2]='人物历史';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='shbk'){
                    $problem_type[$k2]='生活百科';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='mxbg'){
                    $problem_type[$k2]='明星八卦';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='wlhx'){
                    $problem_type[$k2]='物理化学';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='keji'){
                    $problem_type[$k2]='科技';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='politics'){
                    $problem_type[$k2]='政治';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='history'){
                    $problem_type[$k2]='文学';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='music'){
                    $problem_type[$k2]='音乐';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                if($k2=='sport'){
                    $problem_type[$k2]='体育';
                    if($i==$num-1){
                        $arr .=$problem_type[$k2];
                    }else{
                        $arr .=$problem_type[$k2].',';
                    }
                }
                $i++;
            }
            $list[$k]['problem_type']=$arr;
            if($v['problem_status']=='1'){
                $list[$k]['problem_status']="简单";
            }
            if($v['problem_status']=='2'){
                $list[$k]['problem_status']="中难";
            }
            if($v['problem_status']=='3'){
                $list[$k]['problem_status']="较难";
            }
            if($v['problem_status']=='4'){
                $list[$k]['problem_status']="很难";
            }
        }
        //获取参数 member_id  goods_id
        $input=input();
        $member=db('member')->where('token',$this->token)->find();
        $data['member_id']=$member['id'];
        $data['goods_id']=$input['goods_id'];
        $re=db('answer_record')->where($data)->setField('answer_id',$list[0]['id']);
        $list2=$list[0];
        unset($list2['true_ans']);
        if($list2){
              return  ajax_success('获取成功',$list2);
        }else{
             return   ajax_error('获取失败');
        }
    }
    /**
     * lilu
     * 判断用户是否答题----自己答题
     * token
     * goods_id
     */
    public function is_answer()
    {
        //获取参数
        $input=input();
            $member=db('member')->where('token',$this->token)->find();
            //没有答题判断
            $data['member_id']=$member['id'];
            $data['goods_id']=$input['goods_id'];
            $data['status']='2';
            $re=db('answer_record')->where($data)->find();
            //答错题
            $data2['member_id']=$member['id'];
            $data2['goods_id']=$input['goods_id'];
            $data2['status']='0';
            $re2=db('answer_record')->where($data2)->find();
            if($re){
                $map['answer_status']='2';
                $map['lock_time']='';
                return ajax_success('用户没有答题',$map);
            }
            if($re2){
                 //根据配置获取锁定时间
                $key="lock_time";
                $info=db('sys_setting')->where('key',$key)->find();
                $info['value']=json_decode($info['value'],true);
                if($re2['help_id']=='0'){
                    $lock_time=$re2['lock_time'];
                }else{
                    $lock_time=$re2['lock_time'];
                }
                $map['lock_time']=$lock_time;
                $map['answer_status']='0';
                return ajax_error('用户答题错误',$map);
            }
            $map['answer_status']='1';
            $map['lock_time']='';
            return ajax_success('答题成功',$map);
    }
    /**
     * lilu
     * 判断用户是否答题----帮甩
     * token
     * token_help
     * goods_id
     */
    public function is_answer_help2()
    {
        //获取参数
        $input=input();
            $member=db('member')->where('token',$this->token)->find();
            $member2=db('member')->where('token',$input['token_help'])->find();
            //没有答题判断
            $data['member_id']=$member2['id'];
            $data['help_id']=$member['id'];
            $data['goods_id']=$input['goods_id'];
            $data['status']='2';
            $re=db('answer_record')->where($data)->find();
            //答错题
            $data2['member_id']=$member2['id'];
            $data2['help_id']=$member['id'];
            $data2['goods_id']=$input['goods_id'];
            $data2['status']='0';
            $re2=db('answer_record')->where($data2)->find();
            if($re){
                $map['answer_status']='2';
                $map['lock_time']='';
                return ajax_success('用户没有答题',$map);
            }
            if($re2){
                if($re2['help_id']=='0'){
                    $lock_time=$re2['lock_time'];
                }else{
                    $lock_time=$re2['lock_time'];
                }
                $map['lock_time']=$lock_time;
                $map['answer_status']='0';
                return ajax_success('用户答题错误',$map);
            }
            $map['answer_status']='1';
            $map['lock_time']='';
            return ajax_success('答题成功',$map);
    }
    /**
     * lilu
     * 判断答案是否正确--帮答题-----帮甩
     * 问题id
     * 答案
     * token
     * token_help
     * goods_id
     */
    public function is_right_help2()
    {
        //获取参数
        $input=input();
            //自己答题
            $info=db('problem_house')->where('id',$input['answer_id'])->find();
            // $order_number = $input['order_number'];
            $member=db('member')->where('token',$input['token_help'])->find();
            $member2=db('member')->where('token',$input['token'])->find();
            $ww['member_id']=$member['id'];
            $ww['help_id']=$member2['id'];
            $ww['goods_id']=$input['goods_id'];
            $ww['status']=2;
            //插入答题列表
            if($info['true_ans']==$input['true_ans'])
            {
                //答题正确,修改客户答题记录
                $map['status']='1';
                $re=db('answer_record')->where($ww)->update($map);
                $lock['lock_time']='';
                return ajax_success('答题正确',$lock);
                // //根据概率，判断小游戏的种类
                // $youxi =new Game2();
                // $game=$youxi->get_games_chance();
                // $data=$game;
            }else{
                $map2['status']='0';
                //根据配置获取锁定时间
                $key="lock_time";
                $info=db('sys_setting')->where('key',$key)->find();
                $info['value']=json_decode($info['value'],true);
                $lock_time=time()+$info['value']['lock_time']['other']*60;
                $map2['lock_time']=$lock_time;
                $re=db('answer_record')->where($ww)->update($map2);
                $lock['lock_time']=$lock_time;
                return ajax_error('答题失败',$lock);
            }
    }
    /**
     * lilu
     * 判断帮答用户是否锁定----帮答题
     * token     帮答题的token
     * goods_id
     * order_number
     */
    public function is_answer_help()
    {
          //获取参数
          $input=input();
          $order=db('order')->where('order_number',$input['order_number'])->find();
          //判断用户是否有未答的题或锁定的题
          $memb=db('member')->where('token',$input['token'])->find();
          $res=db('answer_record')->where('order_number',$input['order_number'])->find();
          $re=db('help_answer')->where(['order_number'=>$input['order_number'],'help_id'=>$memb['id'],'status'=>0])->find();
           //添加好友关系----帮答题
           $member_info=db('member')->where('id',$order['member_id'])->find();     //
           //判断是否已是好友
           $is=db($order['member_id'])->where('account',$memb['account'])->find();
           if($is)
           {   //已是好友关系

           }else{       //需添加好友关系
               db($order['member_id'])->insert($memb);
           }
        //   $member_info=db('member')->where('id',$res['member_id'])->find();
        //   if(!empty($member_info['fid']))
        //   {
        //       $fid=json_decode($member_info['fid'],true);
        //       if(!in_array($memb['id'],$fid)){
        //           $fid[]=$memb['id'];
        //           $fid2=json_encode($fid);
        //           db('member')->where('id',$res['member_id'])->setField('fid',$fid2);
        //       }
        //   }
          if($res['status']=='1' || $res['status']=='3' || $res['status']=='4'){
              $map2['lock_time']='';
              return   ajax_success('用户已解锁',$map2);
          }
          if($re)
          {
              $map2['lock_time']=$re['lock_time'];
              return ajax_success('用户帮答题已锁定',$map2);
          }else{
                $map2['lock_time']='';
                return ajax_success('用户可以帮答题',$map2);
          }
    }
    /**
     * lilu
     * 判断答案是否正确----自己答题----自己甩
     * 问题id
     * 答案
     * token
     * order_number
     */
    public function is_right()
    {
        //获取参数
        $input=input();
            //自己答题
            $info=db('problem_house')->where('id',$input['answer_id'])->find();
            $order_number = $input['order_number'];
            //插入答题列表
            if($info['true_ans']==$input['true_ans'])
            {
                //答题正确,修改客户答题记录
                $map['status']='1';
                $map['answer_id']=$input['answer_id'];
                $re=db('answer_record')->where('order_number',$order_number)->update($map);
                // if($re){
                    $lock['lock_time']='';
                    return ajax_success('答题正确',$lock);
                // }
                // //根据概率，判断小游戏的种类
                // $youxi =new Game2();
                // $game=$youxi->get_games_chance();
                // $data=$game;
            }else{
                $map2['status']='0';
                $map2['answer_id']=$input['answer_id'];
                $re=db('answer_record')->where('order_number',$order_number)->update($map2);
                $res=db('answer_record')->where('order_number',$order_number)->find();
                //根据配置获取锁定时间
                $key="lock_time";
                $info=db('sys_setting')->where('key',$key)->find();
                $info['value']=json_decode($info['value'],true);
                if($res['help_id']==0)
                {
                     $lock_time=time()+$info['value']['lock_time']['own']*60;
                     $lock['lock_time']=$lock_time;
                     db('answer_record')->where('order_number',$order_number)->update($lock);
                }else{
                     $lock_time=time()+$info['value']['lock_time']['other']*60;
                     $lock['lock_time']=$lock_time;
                     db('answer_record')->where('order_number',$order_number)->update($lock);
                }
                return ajax_error('答题失败',$lock);
            }
    }
    /**
     * lilu
     * 判断答案是否正确--甩客答错题--帮答题
     * 问题id
     * 答案
     * token   帮答题token
     * order_number   
     */
    public function is_right_help()
    {
        //获取参数
        $input=input();
             //帮答题
            $info=db('problem_house')->where('id',$input['answer_id'])->find();
            $order_number = $input['order_number'];
            //添加好友关系----帮答题
            $member_id=db('member')->where('token',$this->token)->find();   //帮答题人的信息
            $order=db('order')->where('order_number',$input['order_number'])->find();
            $re3=db('member')->where('id',$order['member_id'])->find();     //帅客的信息
            //判断是否已是好友
            $is1=db($re3['id'])->where('account',$member_id['account'])->find();
            $is2=db($member_id['id'])->where('account',$re3['account'])->find();
            if(!$is1)
            {   //不是好友关系
                db($re3['id'])->insert($member_id);
            }
            if(!$is2)
            {  
               //需添加好友关系
                db($member_id['id'])->insert($re3);
            }
            //插入答题列表
            if($info['true_ans']==$input['true_ans'])
            {
                //答题正确,修改客户答题记录
                $map['status']='4';
                $map['lock_time']='';
                $map['unlock_time']=time();
                $re=db('answer_record')->where('order_number',$order_number)->update($map);
                $res=db('answer_record')->where('order_number',$order_number)->find();
                $lock['goods_id']=$res['goods_id'];
                $mem=db('member')->where('token',$input['token'])->find();
                $lock['help_id']=$mem['id'];
                $lock['order_number']=$order_number;
                $lock['lock_time']='';
                $lock['status']='1';
                $re=db('help_answer')->insert($lock);
                $lock2['lock_time']='';
                return ajax_success('答题正确',$lock2);
                // //根据概率，判断小游戏的种类
                // $youxi =new Game2();
                // $game=$youxi->get_games_chance();
                // $data=$game;
            }else{
                $map3['status']='5';
                $map3['lock_time']=time()+60*60*24*30*12*10;
                $map3['unlock_time']=time();
                $re=db('answer_record')->where('order_number',$order_number)->update($map3);
                //帮答题错误，生成帮答题错误记录，以及锁定时间
                     $res=db('answer_record')->where('order_number',$order_number)->find();
                     $lock_time=time()+60*60*24*30*12*10;
                     $lock['lock_time']=$lock_time;
                     $lock['goods_id']=$res['goods_id'];
                     $mem=db('member')->where('token',$input['token'])->find();
                     $lock['help_id']=$mem['id'];
                     $lock['order_number']=$order_number;
                     $lock['status']='0';
                     $lock['member_id']=$mem['id'];
                     $re=db('help_answer')->insert($lock);
                     $map2['lock_time']=$lock_time;
                     return ajax_error('答题失败',$map2);
            }
    }
    /**
     * lilu
     * 免甩单的金额----自己甩
     * token
     * goods_id
     * order_number
     */
    public function get_money()
    {
        $input=input();
        //获取用户信息
        $member=db('member')->where('token',$this->token)->find();
        $data['order_number']=$input['order_number'];
        //根据商品设置，获取甩免单和红包的金额以及概率
        $goods_info=db('goods')->where('id',$input['goods_id'])->find();
        if($goods_info['free_tactics'])      //商品免单策略是否配置
        {
            $value=json_decode($goods_info['free_tactics'],true);
            $free_percent_own=$value['own'][0]['percent']/100;
            $free_percent_other=$value['other'][0]['percent']/100;
        }else{
            $map['status']='0';
        }
        //获取订单金额
        $res=db('order')->where('order_number',$input['order_number'])->find();
        if($res['help_id']!='0')     
        {    //帮甩
            $map['free_money']=$res['order_amount']*$free_percent_other;
        }else{    //自己甩
            $map['free_money']=$res['order_amount']*$free_percent_own;
        }
        //免单金额返还客户（$map['free_money]）
        $money=$map['free_money'];
        //根据商品设置，获取商品策略配置项
        //增积分
        if($goods_info['point_tactics'])      //商品增积分策略是否配置
        {
            $value2=json_decode($goods_info['point_tactics'],true);
            $zpoint_own=$value2['own'];
            $zpoint_other=$value2['other'];
            $zpoint=$value2['zpoint'];
            if($zpoint=='1')
            {        //增积分开启
                if($res['help_id']!='0')     
                {    //帮甩
                    db('goods_receive')->where(['member_id'=>$res['member_id'],'goods_id'=>$input['goods_id'],'order_type'=>'0'])->setInc('yi_shuai',$zpoint_own);
                }else{    //自己甩
                    db('goods_receive')->where(['member_id'=>$res['member_id'],'goods_id'=>$input['goods_id'],'order_type'=>'0'])->setInc('yi_shuai',$zpoint_other);
                }  
                $re=db('goods_receive')->where(['member_id'=>$res['member_id'],'goods_id'=>$input['goods_id'],'order_type'=>'0'])->find(); 
                if($re['yi_shuai']>=$re['shuai_num'])
                {
                      $where2['order_type']=1;
                      $where2['yi_shuai']=$re['shuai_num'];
                      db('goods_receive')->where(['member_id'=>$res['member_id'],'goods_id'=>$input['goods_id'],'order_type'=>'0'])->update($where2);
                }
            }
        }else{
            $map['status']='0';    //商品未配置策略
        }
        //大满贯策略
        if($goods_info['big_tactics'])      //商品增积分策略是否配置
        {
            $value3=json_decode($goods_info['big_tactics'],true);
            $big_condition=$value3['condition'];      //满足条件
            $big_zpoint=$value3['zpoint'];       //赠送积分
            $big=$value3['big'];                //开启状态
            if($big=='1')
            {        //增积分开启
               //判断是否达到大满贯的策略
                $re2=db('goods_receive')->where(['member_id'=>$res['member_id'],'goods_id'=>$input['goods_id'],'order_type'=>'0'])->find();
                //判断是否满足基本要求
                $big_money=db('order')->where(['member_id'=>$res['member_id'],'goods_id'=>$input['goods_id'],'status'=>'2'])->sum('order_amount');
                $is_money=$big_money-$goods_info['goods_cost']-$goods_info['goods_freight'];
                if($is_money>0)
                {
                    $is_big=$re2['yi_shuai']/$re2['shuai_num']-$big_condition/100;
                    if($is_big>=0)
                    {     //满足第二条件
                        $points_yu=$re2['shuai_num']-$re2['yi_shuai'];
                        $points=$points_yu*$big_zpoint;
                         db('goods')->where(['member_id'=>$res['member_id'],'goods_id'=>$input['goods_id'],'order_type'=>'0'])->setInc('yi_shuai',$points);
                    }

                }
            }
        }else{
            $map['status']='0';
        }
        //其他商品配置策略
        if($res['pay_type']==1){   //微信
            $pay=new pay();
            $data2=$pay->order_refunds($data['order_number'],$money,$res['order_amount']);
            if($data2["return_code"] == "SUCCESS"  ){
                //免单记录
                $info=db('order')->where('order_number',$input['order_number'])->find();
                $where['member_id']=$member['id'];
                $where['help_id']='0';   //帮甩用户id
                $where['goods_id']=$info['goods_id'];
                $where['order_number']= $input['order_number'];
                $where['income']=$map['free_money'];
                $where['pay']='0';
                $where['pay_type']='2';       //weixin   
                $where['order_type']='2';     //免单
                $where['order_status']='0';   //自己甩
                $where['create_time']=time();   //自己甩
                $re=db('captical_record')->insert($where);
            }
        }else{
            //支付宝
            $alipay= new alipay();
            $data3=$alipay->ali_order_refound($money,$res['order_number']);
            $pp['txt']=$data3.date('Y-m-d H:i',time());
            db('text')->insert($pp);
            if($data3==1){
                //退款成功----免单成功
                //免单记录
                $info=db('order')->where('order_number',$input['order_number'])->find();
                $where['member_id']=$member['id'];
                $where['help_id']='0';   //帮甩用户id
                $where['goods_id']=$info['goods_id'];
                $where['order_number']= $input['order_number'];
                $where['income']=$map['free_money'];
                $where['pay']='0';
                $where['pay_type']='1';       //alipay   
                $where['order_type']='2';     //免单
                $where['order_status']='0';   //自己甩
                $where['create_time']=time();   //自己甩
                $re=db('captical_record')->insert($where);
            }
        }
        if($map)
        {
          return ajax_success('获取成功',$map);
        }else{
          return ajax_error('获取失败');
        }

    }
  /**
   * lilu
   * 点击红包，返还红包-----暂时不用
   * token
   * openid
   * order_number
   */
  public function back_bao_money()
  {
      //获取参数
      $input=input();
      if($input){
          $data['order_number']=$input['order_number'];
        //   $re=db('order')->where($data)->find();
          $res=db('order')->where('order_number',$input['order_number'])->find();
          //根据商品设置，获取甩免单和红包的金额以及概率
        $goods_info=db('goods')->where('id',$res['goods_id'])->find();
        //获取红包的概率以及金额
        if($goods_info['bao_tactics'])
        {
            $value2=json_decode($goods_info['bao_tactics'],true);
            ////////自己甩
            $zong=0;
            foreach($value2['own'] as $k=>$v)
            {
               $zong +=$v['probability'];
            }
            //按照probability排序
            $last_names = array_column($value2['own'],'probability');
            array_multisort($last_names,SORT_ASC,$value2['own']);
            $num=mt_rand(1,$zong);   //随机数
            $pro=0;
            foreach($value2['own'] as $k2=>$v2)
            {
                $pro +=$v2['probability'];
                if($num<=$pro){
                    $map1['free_bao_own']=$v2['percent'];
                    break;
                }
            }
            //帮甩红包---other
            $zong2=0;
            $pro2=0;
            foreach($value2['other'] as $k3=>$v3)
            {
               $zong2 +=$v3['probability'];
            }
            //按照probability排序
            $last_names2 = array_column($value2['other'],'probability');
            array_multisort($last_names2,SORT_ASC,$value2['other']);
            $num2=mt_rand(1,$zong2);   //随机数
            foreach($value2['other'] as $k4=>$v4)
            {
                $pro2 +=$v4['probability'];
                if($num2<=$pro2){
                    $map1['free_bao_other']=$v4['percent'];
                    break;
                }
            }
            if($res['help_id']!='0')     
            {    //帮甩
                $map['free_bao']=$map1['free_bao_other'];
            }else{    //自己甩
                $map['free_bao']=$map1['free_bao_own'];
            }
         //获取用户信息
        $member=db('member')->where('token',$this->token)->find();
        $pay=new pay();
        $money=$map['free_bao'];
        db('member')->where('token',$this->token)->setField('openid',$input['openid']);
        $data2=$pay->back_free_money($input['openid'],$money,$member['name']);
        if($data2)
        {

        }
            //记录
            // $xml_data = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
            // $val = json_decode(json_encode($xml_data), true);
         if($data2["return_code"] == "SUCCESS" ){  //成功
                //红包记录
                $info=db('order')->where('order_number',$input['order_number'])->find();
                $where['member_id']=$member['id'];
                $where['help_id']='0';   //帮甩用户id
                $where['goods_id']=$info['goods_id'];
                $where['order_number']= $input['order_number'];
                $where['income']=$map['free_bao'];
                $where['pay']='0';
                $where['pay_type']='2';       //weixin   
                $where['order_type']='3';     //奖励红包
                $where['order_status']='0';   //自己甩
                $re=db('captical_record')->insert($where);
            }
          return ajax_success('获取成功',$map);
      }else{
         return ajax_ERROR('参数错误');
      }
  }

 }
 /**
  * lilu
  * 自动解锁记录
  * token
  * goods_id
  */
  public function unlock()
  {
      $input=input();
      $member=db('member')->where('token',$this->token)->find();
      //更新答题错误记录
      $map['status']=3;
      $map['unlock_time']=time();
      $answer=db('answer_record')->where(['member_id'=>$member['id'],'goods_id'=>$input['goods_id'],'status'=>0])->update($map);
      if($answer !== false)
      {
          return ajax_success('获取成功');
      }else{
          return ajax_error('参数错误');
      }
  }
  /**
   * lilu
   * 扫码分享后，判断用户是否已解锁
   * token 
   * token_help
   * order_number
   */
  public function  member_answer_lock()
  {
      //获取参数
      $input=input();
      //判断是否自动解锁或解锁时间已过
      $answer=db('answer_record')->where(['order_number'=>$input['order_number'],'status'=>0])->find();
      $time=(int)$answer['unlock_time']-time();
      if($answer && $time<0){
          //锁定
          return  ajax_success('用户锁定',1);
      }else{
          //已解锁
          return  ajax_success('用户已解锁',2);
      }
      $res=db('answer_record')->where('order_number',$input['order_number'])->find();
      if($res['status']==0)    //用户锁定
      {
           return  ajax_success('用户锁定',1);
      }else{
           return  ajax_success('用户已解锁',2);
      }
  }
  
  
}