<?php
/**
 * Created by PhpStorm.
 * User: CHEN
 * Date: 2018/7/10
 * Time: 18:20
 */
namespace app\admin\controller;

use think\Controller;
use think\Config;
use think\captcha\Captcha;
use think\Request;
use think\Session;
use \traits\controller\Jump;
class Index extends Controller
{

    /**
     **************lilu*******************
     * @param Request $request
     * Notes:后台首页
     **************************************
     * @param Request $request
     * @return \think\response\View
     */
    public function index()
    {
        $user_info = Session::get("user_info");
            $menu_list = db("menu")->where('status','<>',0)->select();
            $role = db("role")->where("id",$user_info[0]['role_id'])->field("menu_role_id")->select();
            $role = explode(",",$role[0]["menu_role_id"]);
            //在控制台获取当前的url地址
            $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URI'];
            //var_dump($url);

            $explode = explode("/",$url);
            if(count($explode) > 3){
                $url = "/".$explode[1]."/".$explode[2];
            }

            $if_url = 0;
            if($user_info[0]['id'] != 1) {
                foreach ($menu_list as $key => $values) {
                    if (!in_array($values['id'], $role)) {
                        unset($menu_list[$key]);
                    } else {
                        if ($values['url'] == $url) {
                            $if_url = 1;
                        }
                    }
                }
            }
            $menu_list1 = _tree_hTree(_tree_sort($menu_list,"sort_number"));
            config("menu_list",$menu_list1);//节点信息
        $menu_list = Config::get("menu_list");
        $user_info =Session::get("user_info");
        $account =$user_info[0]["account"];
        return view("index", ["menu_list" => $menu_list,"account"=>$account]);
    }

    /**
     **************lilu*******************
     * @param Request $request
     * Notes:通过菜单栏id获取二级下面的三级权限内的信息
     **************************************
     */
    public function get_id_return_info(Request $request)
    {
        if ($request->isPost()) {
            $id = $request->only(['id'])['id'];//当前id
            //查找当前账号权限
            if (!empty($id)) {
                $arr = request()->routeInfo();
                if (!preg_match("/admin\/Login/", $arr["route"])) {
                    $data = Session::get("user_id");
                    if (empty($data)) {
                        $this->error("请登录", url("/admin/index", "", false));
                        exit();
                    }
                    $user_info = Session::get("user_info");
                    $where['pid']=$id;
                     $where['status']=1;
                    $menu_list = db("menu")->where($where)->order("sort_number asc")->select();
                    $role = db("role")->where("id", $user_info[0]['role_id'])->field("menu_role_id")->select();
                    $role = explode(",", $role[0]["menu_role_id"]);
                    //在控制台获取当前的url地址
                    $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URI'];
                    $explode = explode("/", $url);

                    if (count($explode) > 3) {
                        $url = "/" . $explode[1] . "/" . $explode[2];
                    }
                    $if_url = 0;
                    if ($user_info[0]['id'] != 1) {
                        foreach ($menu_list as $key => $values) {
                            if (!in_array($values['id'], $role)) {
                                unset($menu_list[$key]);
                            } else {
                                if ($values['url'] == $url) {
                                    $if_url = 1;
                                }
                            }
                        }
                    }
//                  $menu_list = _tree_hTree(_tree_sort($menu_list, "sort_number"));
                    if(!empty($menu_list)){
                        foreach ($menu_list as $val_data){
                            $menu_lists[] =$val_data;
                        }
                    }
                    return ajax_success("成功获取", $menu_lists);
                } else {
                    return ajax_error("没有获取到id");
                }

            }
        }

    }
}
