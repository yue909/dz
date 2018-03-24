<?php
namespace Admin\Controller;
use Think\Controller;
class DaiLiController extends QXController {

    //代理列表
    public function liebiao()
    {
        //待优化
        $select = M()
                ->table("dz_app_user")
                ->join("LEFT JOIN dz_home_commission on dz_app_user.id = dz_home_commission.uid")
                ->join("LEFT JOIN dz_app_commission on dz_app_user.id = dz_app_commission.uid")
                ->field("dz_app_user.*,dz_home_commission.stair,dz_home_commission.second_level,sum(dz_app_commission.commission) as yj")
                ->group("dz_app_user.id")
                ->select();
        $count = count($select);

        //实例化分页类 传入总记录数和每页显示的记录数(25)
        $Page  = new \Think\Page($count, 25);
        $show  = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = M()
                ->table("dz_app_user")
                ->join("LEFT JOIN dz_home_commission on dz_app_user.id = dz_home_commission.uid")
                ->join("LEFT JOIN dz_app_commission on dz_app_user.id = dz_app_commission.uid")
                ->field("dz_app_user.*,dz_home_commission.stair,dz_home_commission.second_level,sum(dz_app_commission.commission) as yj,dz_app_user.phone")
                ->group("dz_app_user.id")
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();

        foreach ($list as $i => $item) {
            $list[$i]['yj'] = haoToYuan($item['yj']);

        }
        $this->assign('fen',$show);
        $this->assign('list',$list);
        //加载后台模板
        $this->display("daili/liebiao");
    }

    //佣金转换为公共
    public function transition()
    {
        $list = M()->table("dz_home_commission")->where("uid = '{$_GET['id']}'")->data(array("stair" => null,"second_level"=> null))->save();
        if($list){
            $this->success("修改成功",U("DaiLi/liebiao"));
        }else{
            $this->error("修改失败");
        }
    }

    //搜索
    public function search()
     {
            //获取搜索条件
            if(!empty($_POST['aci'])){
                if(preg_match("/^1[34578]{1}\d{9}$/",$_POST['aci'])){

                    $search = M('app_user')
                            ->join("LEFT JOIN dz_home_commission on dz_app_user.id = dz_home_commission.uid")
                            ->join("LEFT JOIN dz_app_commission on dz_app_user.id = dz_app_commission.uid")
                            ->field("dz_app_user.*,dz_home_commission.stair,dz_home_commission.second_level,sum(dz_app_commission.commission) as yj")->group("dz_app_user.id")->where("dz_app_user.phone = '{$_POST['aci']}'")->select();
                }else{
                    $search = M('app_user')
                            ->join("LEFT JOIN dz_home_commission on dz_app_user.id = dz_home_commission.uid")
                            ->join("LEFT JOIN dz_app_commission on dz_app_user.id = dz_app_commission.uid")
                            ->field("dz_app_user.*,dz_home_commission.stair,dz_home_commission.second_level,sum(dz_app_commission.commission) as yj")
                            ->group("dz_app_user.id")
                            ->where("dz_app_user.username like '%{$_POST['aci']}%'")
                            ->select();
                }
                $this->assign("list",$search);
                $this->display("daili/liebiao");
            }
    }

    //用户佣金比例
    public function usercommission()
    {
        if($_POST){
            $user = M('home_commission')->where("uid = '{$_GET['id']}'")->find();
            if($user){
                $data = array(
                    'stair' => rtrim($_POST['stair'],"%"),
                    'second_level' => rtrim($_POST['second_level'],"%")
                );
                $upd = M('home_commission')->where("uid = '{$_GET['id']}'")->data($data)->save();
                if($upd){
                    $this->success('用户比例修改成功',U("DaiLi/liebiao"));
                }else{
                    $this->error('用户比例修改失败');
                }
            }else{
                $data = array(
                    'uid' => $_GET['id'],
                    'stair' => rtrim($_POST['stair'],"%"),
                    'second_level' => rtrim($_POST['second_level'],"%")
                );
                $add = M('home_commission')->data($data)->add();
                if($add){
                    $this->success('用户比例添加成功',U("DaiLi/liebiao"));
                }else{
                    $this->error("用户比例添加失败");
                }
            }

        }else{
            $usercommission = M('home_commission')->where("uid = '{$_GET['id']}'")->find();
            $this->assign("usercommission",$usercommission);
            $this->assign("id",$_GET['id']);
            $this->display("daili/usercommission");
        }
    }
}
