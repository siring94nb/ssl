<?php
namespace app\index\controller;

use think\Controller;
use app\index\controller\Base;
use think\Session;
use think\Request;
use think\Image;

/**
 * lilu
 * 会员模块
 */
class Member extends Base
{

    /**
     * lilu
     * 会员基本信息
     * @parsm $this->token
     */
    public function member_info()
    {
        //获取参数信息
        $input=input('');
        $res=db('member')->where('token',$this->token)->find();
        //获取用户默认地址
        $map['is_use']='1';
        $map['member_id']=$res['id'];
        $address=db('member_address')->where($map)->find();
        if($address)
        {
            $is_address=$address['address'].$address['detail_address'];
        }else{
            $is_address='';
        }
        $res['address']=$is_address;
        //当前客户正在甩的商品
        $goods_num=db('goods_receive')->where('member_id',$res['id'])->count();
        $res['goods_num']=$goods_num;
        //总推送金额
        $money_zong=db('order')->where(['member_id'=>$res['id'],'status'=>2])->sum('order_amount');
        //统计总的免单金额和退款金额
        $money_free=db('captical_record')->where(['member_id'=>$res['id'],'order_type'=>2])->sum('income');
        $money_free2=db('captical_record')->where(['member_id'=>$res['id'],'order_type'=>4])->sum('income');
        $money=$money_zong-$money_free-$money_free2;
        //保留二位小数
        $money=number_format($money, 2);
        $res['money']=$money;
        unset($res['token']);
        unset($res['token_time']);
        if($res){
           return ajax_success('success',$res);
        }else{
           return ajax_error('error');
        }
    }
    /**
     * lilu
     * 会员排行榜（按照帮甩人数）
     * token
     */
    public function member_ranking()
    {
        //根据token获取会员的信息
        $info2=db('member')->where('token',$this->token)->find();
        $where['help_num']=array('gt',$info2['help_num']);
        $member_num=db('member')->where($where)->count();
        $rank=$member_num+1;    //排名
        //获取所有的会员列表-按照帮甩人数排列
        $member_list=db('member')->select();
        $info=[];
        $con=mysqli_connect("rm-wz9l3z92630ora5wjwo.mysql.rds.aliyuncs.com","siring","ei%&#w9l3z3",'ssl');  //连接数据库
        foreach($member_list as $k =>$v){
            $info[$k]['id']=$v['id'];
            $info[$k]['name']=$v['name'];
            $info[$k]['head_pic']=$v['head_pic'];
            //判断数据表是否存在
            if($con)
            {     //数据库连接成功
                mysqli_select_db($con,'tb_'.$v['id']);
                $table='tb_'.$v['id'];
                $result = mysqli_query($con,"SELECT * FROM $table");
                if($result)
                {   //表存在
                    $num=db($v['id'])->count();
                }else{    //表不存在
                    $num=0;
                }
            }               
            //获取当前用户的好友
            $info[$k]['help_num']=$num;
            $info[$k]['star_value']=$v['star_value'];
        }
        //排序
        $help_num = array_column($info,'help_num');
        array_multisort($help_num,SORT_DESC,$info);
        mysqli_close($con);
        if($info){
            return ajax_success($rank,$info);
        }else{
            return ajax_error('获取失败');
        }
    }
    /**
     * lilu
     * 排行榜界面---个人信息
     * token
     */
    public function rank_member_info()
    {
            //根据token获取会员的信息
            $info=db('member')->where('token',$this->token)->find();
            $where['help_num']=array('gt',$info['help_num']);
            $member_num=db('member')->where($where)->count();
            $data['rank']=$member_num+1;    //排名
            $data['id']=$info['id'];
            $data['head_pic']=$info['head_pic'];
            $data['star_value']=$info['star_value'];
            $data['exchange_star_value']=$info['exchange_star_value'];
            if($data)
            { 
                return ajax_success('获取成功',$data);
            }else{
                return ajax_error('获取失败');
            }

    }

    /**
     * lilu
     * Notes:前端会员修改手机号
     */
    public function member_exchange_phone()
    {
        //获取前端传递的参数
        $input=input('');
        if(!$input['account'])
        {
            return ajax_error('保存失败');
        }
        if(!$input['code']){
             return ajax_error('保存失败');
        }
        if(!$input['new_account'])
        {
            return ajax_error('保存失败');
        }
        $code=Session::get('code');
        //判断code是否正确
        if($input['code']==$code || $input['code']=='000'){
           $re=db('member')->where('token',$this->token)->setField('account',$input['new_account']);
           Session::delete('code');
           return ajax_success('保存成功');
        }else{
            return ajax_error('验证码错误');
        }


    }
    /**
     * lilu
     * Notes:会员头像路径保存
     */
    public function member_pic_save( Request $request)
    {
        //获取会员的信息（图片路径）
        $input=$request->file('pic_url');
        if (!empty($input)) {
            $info = $input->move(ROOT_PATH . 'public' . DS . 'static'.DS.'index'.DS.'img');
            $head_pic['pic_url'] = '/static/index/img/'.str_replace("\\", "/", $info->getSaveName());
            //更改头像路径
             $re=db('member')->where('token',$this->token)->setField('head_pic',$head_pic['pic_url']);
             if($re){
                 return ajax_success('保存成功');
             }else{
                 return ajax_error('上传失败');
             }
        }else{
            return ajax_error('上传文件为空');
        }
       
    }
    /**
     * lilu
     * Notes:会员地址添加
     */
    public function member_address_add()
    {
        //获取会员的ID
        $input=input();
        $member=db('member')->where('token',$this->token)->find();
        $data['member_id']=$member['id'];
        $data['phone']=$input['phone'];
        $data['address']=$input['address'];
        $data['detail_address']=$input['detail_address'];
        $data['is_use']=$input['is_use'];
        $data['name']=$input['name'];
       if($input['is_use']=='1')     //添加的是默认地址
       {
           $address=db('member_address')->where('member_id',$member['id'])->select();
           foreach($address as $k =>$v)
           {
               db('member_address')->where('id',$v['id'])->setField('is_use',0);
           }
       }
        $re=db('member_address')->insert($data);
        if($re){
            return ajax_success('添加成功');
        }else{
            return ajax_error('添加失败');
        }
    }
    /**
     * lilu
     * 会员地址列表
     */
    public function member_address()
    {
        if($this->token)
        {
           //根据会员id获取用户的地址列表
           $member_id=db('member')->where('token',$this->token)->find();
           $re=db('member_address')->where('member_id',$member_id['id'])->select();
           if($re){
               return ajax_success('获取成功',$re);
           }else{
               return ajax_error('获取失败');
           }
        }else{
            return ajax_error('获取失败');
        }
    }
    /**
     * lilu
     * Notes:会员地址编辑
     */
    public function member_address_edit()
    {
        //获取会员的ID
        $input=input();
        $member=db('member')->where('token',$this->token)->find(); //会员信息
        $data['member_id']=$member['id'];
        $data['address']=$input['address'];
        $data['detail_address']=$input['detail_address'];
        $data['phone']=$input['phone'];
        $data['is_use']=$input['is_use'];
        $data['name']=$input['name'];
       if($input['is_use']=='1')     //添加的是默认地址
       {
           $address=db('member_address')->where('member_id',$member['id'])->select();
           foreach($address as $k =>$v)
           {
               db('member_address')->where('id',$v['id'])->setField('is_use',0);
           }
       }
        $re=db('member_address')->where('id',$input['id'])->update($data);
        if($re){
            return ajax_success('编辑成功');
        }else{
            return ajax_error('编辑失败');
        }
    }
    /**
     * lilu 
     * 开甩界面----自己甩
     * token
     * goods_id
     */
    public function shuai_start()
    {
       //获取信息
       $input=input();   //商品id
       $goods_info=db('goods')->where('id',$input['goods_id'])->find();
       //获取当前甩客信息
       $info=db('member')->where('token',$this->token)->find();
       //获取当前用户的地址
        $map['is_use']='1';
        $map['member_id']=$info['id'];
        $address=db('member_address')->where($map)->find();
        if($address)
        {
            $is_address=$address['address'].$address['detail_address'];
        }else{
            $is_address='';
        }
       $data['address']=$is_address;
       $data['id']=$info['id'];
       $data['name']=$info['name'];
       $data['head_pic']=$info['head_pic'];
       $data['member_type']=$info['member_type'];
       //统计客户甩品数量
       $num_goods=db('goods_receive')->where('member_id',$info['id'])->group('goods_id')->count();
       $data['goods_num']=$num_goods;    //客户甩品数量
       $data['member_type']=$info['member_type'];  //会员等级
       $repertory=db('goods')->where('id',$input['goods_id'])->find();
       $data['image']=$repertory['goods_images_two'];
       $data['goods_repertory']=$repertory['goods_repertory'];    //商品库存
       //当前商品帮甩人数
       $helper_num=db('order')->where('goods_id',$input['goods_id'])->group('member_id')->count();
       $data['helper_num']=$helper_num;
       $data['end_date']=$repertory['end_date'];    //商品结束日期
       $data['shuai_num']=$repertory['points'];     //商品甩次
       //当前商品已甩次数
       $goods_num=db('goods_receive')->where(['goods_id'=>$input['goods_id'],'member_id'=>$info['id']])->value('yi_shuai');
       $data['yi_goods_num']=$goods_num;
       //获取当前用户当前甩品的免单金额
       $free_money=db('captical_record')->where(['member_id'=>$info['id'],'goods_id'=>$input['goods_id'],'help_id'=>0,'order_type'=>2])->count();
       $free_money=$free_money*$goods_info['goods_price'];
       $free_money=number_format($free_money, 2);
       $data['free_money']=$free_money;
       //获取当前用户当前甩品的免单金额
       $free_money2=db('captical_record')->where(['member_id'=>$info['id'],'goods_id'=>$input['goods_id'],'order_type'=>2])->count();
       $free_money2=$free_money2*$goods_info['goods_price'];
       $free_money2=number_format($free_money2, 2);
       $data['free_money2']=$free_money2;    //本商品免单金额（自己甩+帮甩）
       //获取当前用户当前甩品的红包金额
    //    $bao_money=db('help_record')->where(['member_id'=>$info['id'],'goods_id'=>$input['goods_id']])->sum('income');
    //    $data['bao_money']=$bao_money;
        //获取当前商品的甩费--新增
        $data['good_price']=$repertory['goods_price'];
        $data['goods_bottom_money']=$repertory['goods_bottom_money'];
        //甩购零钱
        $pp['member_id']=$info['id'];
        $pp['goods_id']=$input['goods_id'];
        $pp['status']=array('between',array(2,8));
        $shuai_zong=db('order')->where($pp)->count();   //该客户该商品总甩费
        $shuai_zong=$shuai_zong*$goods_info['goods_price'];
        $shuai_zong=number_format($shuai_zong, 2);
        $ling_money=$shuai_zong-$free_money2;   //甩购零钱
        $ling_money=number_format($ling_money, 2);
        $data['ling_money']=$ling_money;
        //库存
        $ku_zong=db('goods')->where('id',$input['goods_id'])->value('goods_repertory');    //总库存
        $status['goods_id']=$input['goods_id'];
        $status['order_type']=array('gt',0);
        $sell_num=db('goods_receive')->where($status)->count();   //该客户该商品总甩费
        $yu_num=$ku_zong-$sell_num;
        $data['sell_num']=$sell_num;
        $data['yu_num']=$yu_num;
        $data['ku_zong']=$ku_zong;
       if($data)
       {
           return ajax_success('获取成功',$data);
       }else{
           return ajax_error('获取信息失败');
       }
    }
    /**
     * lilu 
     * 开甩界面----帮甩
     * token
     */
    public function shuai_start_help()
    {
       //获取信息
       $input=input();   //商品id
       //获取当前甩客信息
       $info=db('member')->where('token',$this->token)->find();
       $data['id']=$info['id'];
       $data['name']=$info['name'];
       $data['head_pic']=$info['head_pic'];
       $data['member_type']=$info['member_type'];
       $data['goods_num']=$info['goods_num'];    //客户甩品数量
       $data['member_type']=$info['member_type'];  //会员等级
       $repertory=db('goods')->where('id',$input['goods_id'])->find();
       $data['image']=$repertory['goods_images_two'];
       $data['goods_repertory']=$repertory['goods_repertory'];    //商品库存
       //当前商品帮甩人数
       $helper_num=db('order')->where('goods_id',$input['goods_id'])->group('member_id')->count();
       $data['helper_num']=$helper_num;
       $data['end_date']=$repertory['end_date'];    //商品结束日期
       $data['shuai_num']=$repertory['points'];     //商品甩次
       //当前商品已甩次数
       $goods_num=db('order')->where(['goods_id'=>$input['goods_id'],'member_id'=>$info['id']])->count();
       $data['yi_goods_num']=$goods_num;
       //获取当前用户当前甩品的免单金额
       $free_money=db('help_record')->where(['member_id'=>$info['id'],'goods_id'=>$input['goods_id']])->sum('income');
       $data['free_money']=$free_money;
       //获取当前用户当前甩品的红包金额
       $bao_money=db('help_record')->where(['member_id'=>$info['id'],'goods_id'=>$input['goods_id']])->sum('income');
       $data['bao_money']=$bao_money;
       if($data)
       {
           return ajax_success('获取成功',$data);
       }else{
           return ajax_error('获取信息失败');
       }
    }
    /**
     * lilu
     * Notes:会员地址删除
     */
    public function member_address_del()
    {
        //获取会员的ID
        $input=input();
        if($input['id']){
            $member=db('member_address')->delete($input['id']); //会员信息
            if($member){
                $id=db('member')->where('token',$this->token)->find();
                $list=db('member_address')->where(['member_id'=>$id['id'],'is_use'=>1])->select();
                if(!$list){
                    $re=db('member_address')->where('member_id',$id['id'])->select();
                    if($re){
                        $re=db('member_address')->where('member_id',$id['id'])->find();
                        $re2=db('member_address')->where('member_id',$re['member_id'])->setField('is_use','1');
                    }
                }
                return ajax_success('编辑成功');
            }else{
                return ajax_error('编辑失败');
            }
        }
        
    }
    /**
     * lilu
     * 会员昵称修改
     */
    public function member_name_edit()
    {
        //获取修改的新昵称
        $input=input();
        $data['name']=$input['name'];
        $re=db('member')->where('token',$this->token)->update($data);
        if($re)
        {
           return ajax_success('昵称修改成功');
        }else{
            return ajac_eror('昵称修改失败');
        }
    }
    /**
     * lilu
     * 获取当前客户当前商品的帮甩记录
     * token
     * goods_id
     * 
     */
    public function get_helper_record()
    {
        //获取参数   token,goods_id
        $input=input();
        if($input){
            //获取用户该商品的每日增加次数
            // $goods=db('goods')->where('id',$input['goods_id'])->find();
            // if($goods['new_tactics'])    //新人策略
            // {
            //    $tactics=json_decode($goods['new_tactics'],true);
            //    $goods_num=$tactics['help_num'];
            // }else{
            //     $goods_num='0';
            // }
            // if($goods['old_tactics'])    //新人策略
            // {
            //    $tactics2=json_decode($goods['old_tactics'],true);
            //    $goods_num2=$tactics2['help_num'];
            // }else{
            //     $goods_num2='0';
            // }
             $re=db('member')->where('token',$this->token)->find();   //获取会员信息
            //当前用户当前商品的甩品记录
            $where['member_id']=$re['id'];
            $where['goods_id']=$input['goods_id'];
            $where['status']=array('gt',1);
            $where['person_type']=array('lt',2);
             $list=db('order')->where($where)->order('create_time desc')->select();
             $list_num=db('order')->where($where)->order('create_time desc')->count();
             if(!$list)
             {
                 return ajax_error('数据获取错误');
             }
            //获取帮甩头像以及帮甩次数
             foreach($list as $k=>$v){
                 $data[$k]['rank']=$list_num-$k;
                 //自己甩
                 if($v['help_id']==0)
                 {  
                    $data[$k]['id']=$v['member_id'];
                    $data[$k]['order_type']='0';      //自己甩
                    // $data[$k]['num']='1';
                    $head_pic=db('member')->where('id',$v['member_id'])->value('head_pic');
                    $data[$k]['head_pic']='';
                 }else{    
                     //帮甩
                    // if($v['person_type']=='0')
                    // {    //旧人帮甩
                    //     $data[$k]['order_type']='3';
                    //     $data[$k]['num']=$goods_num2;
                    // }elseif($v['person_type']=='1'){    //新人帮甩
                    //     $data[$k]['order_type']='2';
                    //     $data[$k]['num']=$goods_num;
                    // }else{                   //帮甩
                    //     $data[$k]['num']='1';
                    //     $data[$k]['order_type']='1';
                    // }
                     $data[$k]['order_type']='1';
                     $head_pic=db('member')->where('id',$v['help_id'])->value('head_pic');
                     $data[$k]['id']=$v['help_id'];
                     $data[$k]['head_pic']=$head_pic;

                }
                    $data[$k]['create_time']=$v['create_time'];

             }
             //获取答题记录
             $answer_record=db('answer_record')->where(['member_id'=>$re['id'],'goods_id'=>$input['goods_id']])->select();
             if(!$answer_record)
             {
                 return ajax_error('数据获取错误');
             }
             foreach($answer_record as $k=>$v){
                   if($v['status']=='0')
                   {    //自己答题错误被锁
                        $data2[$k]['id']=$v['member_id'];
                        $data2[$k]['order_type']='5';  
                        $head_pic=db('member')->where('id',$v['member_id'])->value('head_pic');
                        // $data2[$k]['num']='0';
                        $data2[$k]['head_pic']='';
                        $data2[$k]['create_time']=$v['create_time'];
                   }elseif($v['status']=='3'){    //自动解锁
                        //创建假数据---被锁定
                        $data2[$k]['id']=$v['member_id'];
                        $data2[$k]['order_type']='6';
                        $data2[$k]['unlock_time']=$v['unlock_time'];
                        $head_pic=db('member')->where('id',$v['member_id'])->value('head_pic');
                        $data2[$k]['head_pic']='';
                        // $data2[$k]['num']='0';
                        $data2[$k]['create_time']=$v['create_time'];
                   }elseif($v['status']=='4'){     //助力解锁
                        $head_pic2=db('member')->where('id',$v['help_id'])->value('head_pic');
                        $data2[$k]['head_pic']=$head_pic2;    //帮答题的头像
                        $data2[$k]['help_id']=$v['help_id'];
                        $data2[$k]['id']=$v['member_id'];
                        $data2[$k]['unlock_time']=$v['unlock_time'];
                        // $data2[$k]['num']='0';
                        $data2[$k]['order_type']='7';
                        $head_pic=db('member')->where('id',$v['member_id'])->value('head_pic');
                        // $data2[$k]['head_pic']='';
                        $data2[$k]['create_time']=$v['create_time'];
                   }elseif($v['status']=='5'){        //帮答题失败 
                        $data2[$k]['id']=$v['help_id']; 
                        $data2[$k]['order_type']='10';
                        $head_pic=db('member')->where('id',$v['help_id'])->value('head_pic');
                        // $data2[$k]['num']='0';
                        $data2[$k]['head_pic']=$head_pic;
                        $data2[$k]['create_time']=$v['create_time'];
                   }elseif($v['status']=='1'){         //答题正确
                        $data2[$k]['id']=$v['member_id'];
                        $data2[$k]['order_type']='4';
                        $head_pic=db('member')->where('id',$v['member_id'])->value('head_pic');
                        // $data2[$k]['num']='0';
                        $data2[$k]['head_pic']='';
                        $data2[$k]['create_time']=$v['create_time'];
                   }
                   $data2[$k]['rank']=-1;
            }
            //获取免单记录
            // $free_dan=db('captical_record')->where(['member_id'=>$re['id'],'goods_id'=>$input['goods_id']])->select();
            // foreach($free_dan as $k =>$v)
            // {
            //     if($v['help_id']=='0')
            //     {    //自己答对，获得免单
            //          $data3[$k]['id']=$v['member_id'];
            //          $data3[$k]['order_type']='8';
            //          $data3[$k]['head_pic']=db('member')->where('id',$v['member_id'])->value('head_pic');
            //     }else{
            //         //帮甩答对，获得免单
            //         $data3[$k]['id']=$v['help_id'];
            //         $data3[$k]['order_type']='9';
            //         $data3[$k]['head_pic']=db('member')->where('id',$v['help_id'])->value('head_pic');
            //     }
            //     $data3[$k]['num']='0';
            //     $data3[$k]['create_time']=date('Y-m-d H:i',$v['create_time']);
            // }
            $res=array_merge($data,$data2);
            //排序
            $create_time = array_column($res,'create_time');
            array_multisort($create_time,SORT_DESC,$res);
             if($res){
                 return ajax_success('获取成功',$res);
             }else{
                 return ajax_error('获取失败');
             }
        }else{
            return ajax_error('获取失败');
        }
    }
    /**
     * lilu
     * 获取openid
     * token
     */
    public function get_openid()
    {
        //获取参数
        $input=input();
        $re=$member=db('member')->where('token',$this->token)->update($input);
        if($re)
        {
          return  ajax_success('获取成功');
        }else
        {
           return ajax_error('获取失败');
        }

    }
    /**
     * lilu
     * 获取帮甩档案配置
     * token
     * goods_id
     */
    public function help_setting()
    {
        $input=input();
        //获取key
        $key1="goods_limit";    // 商品限制
        $key2="goods_limit_own";   //自己甩限制
        $key3="help_goods_limit";   //帮甩限制
        $value1=db('sys_setting')->where('key',$key1)->find();
        $info['goods_limit']=json_decode($value1['value'],true);
        $value2=db('sys_setting')->where('key',$key2)->find();
        $info['goods_limit_own']=json_decode($value2['value'],true);
        $value3=db('sys_setting')->where('key',$key3)->find();
        $info['help_goods_limit']=json_decode($value3['value'],true);
        $data['add1']='本品甩'.$info['goods_limit']['limit_num'].'次/天';       //商品限制
        $data['add2']='可邀请朋友帮甩';       //商品限制
        $data['add3']='帮甩可+1次机会';       //商品限制
        $goods=db('goods')->where('id',$input['goods_id'])->find();
        if($goods['new_tactics'])
        {
           $tactics=json_decode($goods['new_tactics'],true);
           $goods_num=$tactics['help_num'];
        }else{
            $goods_num='0';
        }
        $data['add4']='新人帮甩可+'.$goods_num.'甩/每天';
        $data2[]['shuai_limit']=$data['add1'];
        $data2[]['shuai_limit']=$data['add2'];
        $data2[]['shuai_limit']=$data['add3'];
        $data2[]['shuai_limit']=$data['add4'];
        if($data2)
        {
           return ajax_success('获取成功',$data2);
        }else{
            return ajax_error('获取失败');
        }
    }
    /**
     * lilu
     * 好友接口
     * token
     */
    public function friend()
    {
      //获取会员信息
      $member=db('member')->where('token',$this->token)->find();
      //获取当前用户的好友
      $friend=db($member['id'])->order('join_time desc')->select();
      foreach($friend as $k =>$v)
      {
          //获取好友的信息
          $info=db('member')->where('id',$v['id'])->find();
          $data[$k]['id']=$v['id'];
          $data[$k]['name']=$info['name'];
          $data[$k]['star_value']=$info['star_value'];
          $data[$k]['head_pic']=$info['head_pic'];
          $data[$k]['token']=$info['token'];
          if($info['member_type']=='1')
          {
              $data[$k]['member_type']='vip';
          }else{
              $data[$k]['member_type']='普通会员';
          }
          $id=db('member')->where('account',$info['account'])->find();  //当前好友信息
          //获取好友甩的商品
          $goods=db('goods_receive')->where(['member_id'=>$id['id'],'order_type'=>0])->select();
          if($goods){
              foreach($goods as $k2 =>$v2)
              {
                  //获取商品信息
                  $goods2=db('goods')->where('id',$v2['goods_id'])->find();
                  $map[$k2]['id']=$goods2['id'];
                  $map[$k2]['goods_image']=$goods2['goods_show_image'];
              }
              $data[$k]['shuai_goods']=$map;
          }else{
              
          }
        }
        if($data)
        {
            return ajax_success('获取成功',$data);
        }else{
            return ajax_error('获取失败');
        }

    }
    /**
     * lilu
     * 个人中心----商品展示
     * token
     */
    public function goods_show()
    {
        //获取参数信息
        $input=input();
        $member=db('member')->where('token',$this->token)->find();     //获取会员信息
        $goods=db('goods_receive')->where('member_id',$member['id'])->order('create_time desc')->select();
        foreach($goods as $k =>$v)
        {
            //获取商品信息
            $goods2=db('goods')->where('id',$v['goods_id'])->find();
            if($goods2['goods_repertory']=='0')
            {
                $map[$k]['type']='2';           //订单状态    1  正在甩   2 已抢光    3 待确定   4 未发货   5 已发货  6  已完成
            }
            elseif($v['order_type']=='1')       //可以领取----待确定
            { 
              $map[$k]['type']='3';
            }elseif($v['order_type']=='2'){      //未发货
                $map[$k]['type']='4';
            }elseif($v['order_type']=='3')       //已发货
            {
                $map[$k]['type']='5';
            }elseif($v['order_type']=='4')       //已完成
            {
                $map[$k]['type']='6';
            }else{
                $map[$k]['type']='1';            //正在甩
            }
            $map[$k]['id']=$goods2['id'];
            $map[$k]['goods_image']=$goods2['goods_images_three'];
            $map[$k]['points']=$goods2['points'];
            $map[$k]['goods_name']=$goods2['goods_name'];
            //当前商品的帅次
             $num=db('order')->where(['goods_id'=>$v['goods_id'],'member_id'=>$member['id'],'status'=>2])->count();
             $map[$k]['goods_shuai_num']=$num;
             $map[$k]['confirm_time']=$v['confirm_time'];
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
     * 分享添加好友关系-----暂时屏蔽
     * token
     * token_help
     */
    public function friend_add()
    {
        //获取token
        $input=input();
        $member1=db('member')->where('token',$this->token)->find();
        $member2=db('member')->where('token',$input['token_help'])->find();
        //判断是否是新人
        $is_new=db('order')->where('member_id',$member1['id'])->find();
        if(!$is_new)    //新人   添加上级id
        {
             db('member')->where('id',$member1['id'])->setField('pid',$member2['id']);
        }
        //判断是否为好友关系
        $is1=db($member1['id'])->where('account',$member2['account'])->find();   
        $is2=db($member2['id'])->where('account',$member1['account'])->find();
        if(!$is1)
        {
            db($member1['id'])->insert($member2);
        }
        if(!$is2)
        {
            db($member2['id'])->insert($member1);
        }
        return ajax_success('添加成功');
    }
    /**
     * lilu
     * 个人中心---账单
     * token
     */
    public function person_record()
    {
        $input=input();
        if($input['token']=='0')   //没有传递token
        {
            return ajax_error('获取失败');
        }
        //获取用户信息
        $member=db('member')->where('token',$this->token)->find();        
        //获取用户免单
        $free_dan=db('captical_record')->where(['member_id'=>$member['id'],'order_type'=>2])->order('order_number desc')->group('goods_id')->select();
        $arr=[];
        foreach($free_dan as $k=>$v)
        {
              $list=db('captical_record')->where(['member_id'=>$v['member_id'],'goods_id'=>$v['goods_id'],'order_type'=>2])->select();
              //单个商品处理
              foreach($list as $k2=>$v2)
              {
                   $list3[$k][$k2]['rank']=$k2+1;
                   $goods_name=db('goods')->where('id',$v2['goods_id'])->find();
                   if($goods_name)
                   {
                       $list3[$k][$k2]['goods_name']=$goods_name['goods_name'];
                   }
                   $list3[$k][$k2]['income']=$v2['income'];
                   $list3[$k][$k2]['goods_show_image']=$goods_name['goods_images_three'];
                   $list3[$k][$k2]['create_time']=$v2['create_time'];
                   if($v2['help_id']==0){

                       $list3[$k][$k2]['type']=1;      //甩免单---自己甩
                    }else{
                        $list3[$k][$k2]['type']=4;      //甩免单---帮甩
                   }
                   $list3[$k][$k2]['goods_id']=$v2['goods_id'];      //甩免单
              }
        }
        $record=[];
        foreach($list3 as $k=>$v)
        {
            foreach($v as $k3=>$v3)
            {
               $record[]=$v3;
            }
        }
        //获取退款账单---未到账
        $refund_dan=db('captical_record')->where(['member_id'=>$member['id'],'order_type'=>4])->order('order_number desc')->group('goods_id')->select();
        $arr2=[];
        foreach($refund_dan as $k=>$v)
        {
              $list2[$k]=db('captical_record')->where(['member_id'=>$v['member_id'],'goods_id'=>$v['goods_id'],'order_type'=>4])->select();
              //单个商品处理
              foreach($list2[$k] as $k2=>$v2)
              {
                   $list4[$k2]['rank']=$k2+1;
                   $goods_name=db('goods')->where('id',$v2['goods_id'])->find();
                   if($goods_name)
                   {
                       $list4[$k2]['goods_name']=$goods_name['goods_name'];
                   }
                   $list4[$k2]['income']=$v2['income'];
                   $list4[$k2]['goods_show_image']=$goods_name['goods_images_three'];
                   $list4[$k2]['create_time']=$v2['create_time'];
                   $list4[$k2]['type']=2;     //退款记录
                   $list4[$k2]['goods_id']=$v2['goods_id'];     //退款记录
              }
              $arr2[]=$list4;
        }
        foreach($arr2 as $k5=>$v5)
        {
            foreach($v5 as $k6=>$v6)
            {
               $record[]=$v6;
            }
        }
        // halt($record);
        //获取退款账单---已到账
        $refund_dan2=db('captical_record')->where(['member_id'=>$member['id'],'order_type'=>6])->order('order_number desc')->group('goods_id')->select();
        $arr3=[];
        foreach($refund_dan2 as $k=>$v)
        {
              $list3[$k]=db('captical_record')->where(['member_id'=>$v['member_id'],'goods_id'=>$v['goods_id'],'order_type'=>6])->select();
              //单个商品处理
              foreach($list3[$k] as $k2=>$v2)
              {
                   $list5[$k2]['rank']=$k2+1;
                   $goods_name=db('goods')->where('id',$v2['goods_id'])->find();
                   if($goods_name)
                   {
                       $list5[$k2]['goods_name']=$goods_name['goods_name'];
                   }
                   $list5[$k2]['income']=$v2['income'];
                   $list5[$k2]['goods_show_image']=$goods_name['goods_images_three'];
                   $list5[$k2]['create_time']=$v2['create_time'];
                   $list5[$k2]['type']=3;     //退款记录
                   $list5[$k2]['goods_id']=$v2['goods_id'];     //退款记录
              }
              if($list5){
                  $arr3[]=$list5;
              }
        }
        foreach($arr3 as $k5=>$v5)
        {
            foreach($v5 as $k6=>$v6)
            {
               $record[]=$v6;
            }
        }
        // $record3=array_merge($record,$record2);
        //根据字段last_name对数组$data进行降序排列
        $create_time = array_column($record,'create_time');
        array_multisort($create_time,SORT_DESC,$record);
        if($record)
        {
            return ajax_success('获取成功',$record);
        }else{
            return ajax_error('获取失败');
        }

    }
    /**
     * lilu
     * 领取已甩的商品
     * token
     * goods_id
     */
    public function goods_get()
    {
        //获取参数
        $input=input();
        if($input)
        {
            //获取会员id
            $member=db('member')->where('token',$this->token)->find();
            //获取参数
            $re=db('goods_receive')->where(['goods_id'=>$input['goods_id'],'member_id'=>$member['id']])->find();
            if($re)
            {
                //商品的库存减少
                $res1=db('goods')->where('id',$input['goods_id'])->setDec('goods_repertory');
                //修改商品订单的状态
                $res2=db('goods_receive')->where(['goods_id'=>$input['goods_id'],'member_id'=>$member['id']])->setField('order_type',2);   //未发货
                if($res1 && $res2)
                {
                    return ajax_success('领取成功');
                }
            }
        }else{
            return ajax_success('领取失败');
        }
    }
    /**
     * lilu
     * 确认收货
     * token
     * goods_id
     */
    public function goods_confirm()
    {
        //获取信息
        $input=input();
        if($input)
        {
            //获取用户的信息
            $member=db('member')->where('token',$this->token)->find();
            $re=db('goods_receive')->where(['member_id'=>$member['id'],'goods_id'=>$input['goods_id']])->setField('order_type',4);
            if($re)
            {
               return ajax_success('确认收货成功');
            }else{
                return alax_error('确认收获失败');
            }
        }else{
            return alax_error('参数错误');
        }

    }
    /**
     * lilu
     * 个人信息-当前商品甩次排行榜
     * token
     * goods_id
     */
    public function rank_three()
    {
        //获取参数
        $input=input();
        if($input)
        {
             //获取前三
             $list=db('goods_receive')->where('goods_id',$input['goods_id'])->order('yi_shuai desc')->limit('3')->select();
             foreach($list as $k =>$v)
             {
                 $member=db('member')->where('id',$v['member_id'])->find();
                 $map[$k]['id']=$member['id'];
                 $map[$k]['head_pic']=$member['head_pic'];
             }
             if($map)
             {
                return ajax_success('获取成功',$map);
             }else{
                 return ajax_error('获取失败');

             }
        }else{
             return ajax_error('获取参数错误');
        }
    }
    /**
     * lilu
     * 甩甩乐档案--帮甩新旧人头衔展示
     * token
     * goods_id
     */
    public function get_new_old_record()
    {
        //获取参数
        $input=input();
        if($input)
        {
            //获取用户的信息
            $member=db('member')->where('token',$this->token)->find();
            //获取当前商品的帮甩记录
            $where['help_id']=array('gt',0);
            $where['person_type']=array('lt',2);
            $where['goods_id']=$input['goods_id'];
            $where['member_id']=$member['id'];
            $order_list=db('order')->where($where)->order('create_time desc')->group('help_id')->select();
            if(!$order_list){
                return ajax_error('参数错误');
            }
            //获取当前商品的新旧人帮甩策略
            $goods=db('goods')->where('id',$input['goods_id'])->find();
            
               $tactics=json_decode($goods['new_tactics'],true);
               if($tactics['new']==1)    //新人策略开启
               {
                   $goods_num=$tactics['help_num'];
               }else{
                   return  ajax_error('获取失败');
               }
               $value=json_decode($goods['old_tactics'],true);
                if($value['old']==1)
                {    //开启
                     $old=$value['help_num'];
                }else{
                    return  ajax_error('获取失败');
                }
            foreach($order_list as $k=>$v)
            {
                if($v['person_type']==1)
                {   //新人   每天
                    $data[$k]['type']=1;  
                    $data[$k]['num']=$goods_num;
                }else{
                    $data[$k]['type']=2;     //当天
                    $data[$k]['num']=$old;
                }
                $member_help=db('member')->where('id',$v['help_id'])->find();
                $data[$k]['id']=$member_help['id'];
                $data[$k]['head_pic']=$member_help['head_pic'];
            }
            if($data)
            {
               return ajax_success('获取成功',$data);
            }else{
              return  ajax_error('获取失败');
            }
        }else{
             return ajax_error('参数错误');
        }
    }
     


}