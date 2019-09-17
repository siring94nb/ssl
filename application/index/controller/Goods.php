<?php
namespace  app\index\controller;

use think\Controller;
use think\Console;
use think\Db;

/**
 * lilu
 * Class Goods
 * @package app\index\controller
 */
class Goods extends Controller
{
    /**
     **************lilu*******************
     * @param Request $request
     * Notes:前端获取商品信息
     * token
     * id
     **************************************
     */
     public  function goods_info()
     {
       //获取参数
       $input=input();
         //检索条件
         $where['label']=1;          //上架
         $where['goods_setting']=0;  //帮甩不限制
         $id=input();
         if($id['id']=='0'){
           
         }else{
          $where['id']=$id['id'];
         }
         $where['end_date']=array('gt',time());
        //获取商品
        $goods = db("goods")->where($where)->order("id asc")->select();
        foreach ($goods as $k=>$v)
        {
          //判断token是否存在
          if($input['token']=='0')
          { 

          }else{
             $member=db('member')->where('token',$input['token'])->find();
             $num=db('order')->where(['goods_id'=>$v['id'],'member_id'=>$member['id'],'status'=>2])->count();
             $goods[$k]['goods_shuai_num']=$num;     //商品已甩次数
          }
           if($v['goods_standard']=='1')
           {         //特殊规格
             $data=db('special')->where('goods_id',$v['id'])->select();
             $goods[$k]['attr']=$data;
           }else{
            //    $goods[$k]['attr']='';
           }
           //获取当前商品甩客数量
           $shuai_num=db('order')->where('goods_id',$v['id'])->group('member_id')->count();
           $goods[$k]['person_num']=$shuai_num;
        }
//        $num = db("goods")->count();        //获取商品总数
//         $all_idents = $goods;               //获取分页的数据
//         $curPage = input('get.page') ? input('get.page') : 1;//接收前端分页传值
//         $listRow = 10;//每页10行记录
//         $showdata = array_slice($all_idents, ($curPage - 1) * $listRow, $listRow, true);// 数组中根据条件取出一段值，并返回
//         $goods = Bootstrap::make($showdata, $listRow, $curPage, count($all_idents), false, [
//             'var_page' => 'page',
//             'path' => url('admin/Goods/goods_index'),//这里根据需要修改url
//             'query' => [],
//             'fragment' => '',
//         ]);
//         $goods->appends($_GET);
//         $this->assign('listpage', $goods->render());
//        return view("goods_index", ["goods" => $goods]);
          return ajax_success('获取成功',$goods);
     }
     /**
      * lilu
      * 领取商品列表
      * goods_id
      * member_id
      */
      public function goods_receive_list()
      {
        //获取参数
        $input=input();    //goods_id  member_id
        $where['status']=array(gt,0);
        $re=db('goods_reveive')->where($where)->select();
        if($re){
            return ajax_success('获取成功',$re);
        }else{
            return  ajax_error('获取失败');
        }
      }
     /**
      * lilu
      * 领取商品
      * id    记录id
      */
      public function is_goods_receive()
      {
        //获取参数
        $input=input();    //goods_id  member_id
        $re=db('goods_reveive')->where('id',$input['id'])->setField('status',2);
        if($re){
          return    ajax_success('获取成功');
        }else{
            return  ajax_error('获取失败');
        }
      }
}