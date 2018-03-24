<?php
namespace Admin\Controller;
use Think\Controller;
use OSS\Core\OssException;
class GuangGaoController extends QXController {

    //广告曲线图数据
    public function getG2Data()
    {
        $id = I('get.id/d');
        $day = I('get.day/d');
        $data = array();
        $req = array(
            'code' => 1,
            'msg' => '数据获取成功',
            'data' => array()
        );
        if ($day == 0 || $day == 1) {
            //按小时取数据
            $date = date("Y-m-d"); //默认今天24h数据
            if ($day == 1) { //昨天24h数据
                $date = date("Y-m-d", strtotime("-1 day"));
            }
            $start_time = strtotime($date . " 00:00:00");
            $end_time = strtotime($date . " 23:59:59");
            $info = M("app_advertising_info")->where("gid = '{$id}' AND add_time > {$start_time} AND add_time < {$end_time}")->select();
            for ($i=0; $i < 24; $i++) {
                $tempB = array(
                    'date' => sprintf("%02d", $i) . ':00',
                    '展示' => 0,
                    '点击' => 0
                );
                foreach ($info as $item) {
                    if ($item['date_h'] == $i) {
                        $tempB['展示'] = $item['show_num'];
                        $tempB['点击'] = $item['click_num'];
                    }
                }
                $data[] = $tempB;
            }
        } else {
            //按天数取数据
            for ($i=$day-1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-" . $i . " day"));
                $tempA = array(
                    'date' => $date,
                    '展示' => 0,
                    '点击' => 0
                );
                $show_num = M("app_advertising_info")->where("gid = '{$id}' AND date = '{$date}'")->sum("show_num");
                $click_num = M("app_advertising_info")->where("gid = '{$id}' AND date = '{$date}'")->sum("click_num");
                if ($show_num) {
                    $tempA['展示'] = $show_num;
                }
                if ($click_num) {
                    $tempA['点击'] = $click_num;
                }
                $data[] = $tempA;
            }
        }
        if ($data) {
            $req['data'] = $data;
        } else {
            $req['code'] = -1;
            $req['msg'] = '数据获取失败';
        }
        $this->ajaxReturn($req);
    }

    //广告数据详情
    public function detail()
    {
        $id = I('get.id/d');
        $ad = M("admin_guanggao")->where("id = '{$id}'")->find();
        if (empty($ad)) {
            $this->error("广告不存在/已删除！");
        }
        $this->assign('ad', $ad);
        $this->display("guanggao/detail");
    }

    //广告列表
    public function liebiao()
    {
        $mod=M('admin_guanggao');
        $count=$mod->Count();
        $pag=new \Think\Page($count,12);
        $pag->setConfig('prev','上一页');
        $pag->setConfig('next','下一页');
        $pag->setConfig('first','首页');
        $pag->setConfig('last','尾页');
        $pag->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
        $fen=$pag->show();
        $list=$mod->limit($pag->firstRow,12)->select();
        $this->assign('fen',$fen);
        $this->assign('list',$list);
        $this->display("guanggao/liebiao");

        //统计相同字段步骤
        // $data=M('admin_user_role')->field('uid')->select();
        // 二维数组转一维数组函数 array_column
        // $uid= array_column($data, 'uid');
        // 统计相同字段函数 array_count_values
        // $count = array_count_values($uid);
        // echo "<pre>";
        // var_dump($count);
    }

    //添加广告
    public function tianjia()
    {
        $ruanwen = M('admin_ruanwen')->field('title,id')->select();
        $fenlei=M("admin_ruanwen_category")->field("name,id")->select();
        $template = M('admin_template')->where("status = 0")->select();
        $this->assign("template",$template);
        $this->assign("ruanwen",$ruanwen);
        $this->assign("fenlei",$fenlei);
        $this->display("guanggao/tianjia");
    }

    //执行插入
    public function insert()
    {
        $upload = new \Think\Upload();
        $upload->maxSize=0;
        $upload->exts=array('jpg','gif','png','jpeg');
        $upload->rootPath='./Public/Uploads/ad/';
        //关闭自动创建日期目录
        $upload->autoSub=false;
        $info=$upload->upload();

        if($info){
            foreach($info as $file){
                    $path=$file['savename'];
                }

        $data=array(
                'title'   => $_POST['title'],
                'url'     => $_POST['url'],
                'zsxg'    => $_POST['zsxg'],
                'tgms'    => $_POST['tgms'],
                'money'   => admin_yuanToFen($_POST['money']),
                'expense' => admin_yuanToFen($_POST['expense']),
                'budget'  => admin_yuanToFen($_POST['budget']),
                'jsfs'    => $_POST['jsfs'],
                'jszq'    => $_POST['jszq'],
                'display' => $_POST['display'],
                'remark'  => $_POST['remark'],
                'status'  => $_POST['status'],
                'type'    => $_POST['type'],
                'addtime' => time(),
                'pic'     => "/Public/Uploads/ad/".$path

            );
                //上传阿里云
                vendor('aliyun.autoload');
                $ossClient = new \OSS\OssClient(C('_OSS_ACCESS_KEY_ID_'), C('_OSS_ACCESS_KEY_SECRET_'), C('_OSS_ENDPOINT_'));
                //oss保存的文件名称
                $object = 'guanggao/' . date('Y-m-d') . '/' . $info['pic']['savename'];
                //文件路径，必须是本地的。
                $file = $upload->rootPath . $info['pic']['savepath'] . $info['pic']['savename'];

                try{
                        //上传文件到oss
                        $ossClient->uploadFile(C('_OSS_BUCKET_'), $object, $file);
                        //删除本地文件
                        unlink($file);
                        //拼接oss地址
                        $data['pic'] = '//' . C('_OSS_BUCKET_ '). '.' . C('_OSS_ENDPOINT_') . '/' . $object;
                        $data['pic'] = '//' . C('_OSS_BUCKET_') . '.' . C('_OSS_ENDPOINT_') . '/' . $object;

                  }catch(OssException $e) {
                        //oss上传失败
                        $this->error("OSS上传错误：" . $e->getMessage());
                }
                        $guanggao = M('admin_guanggao')->add($data);

                    if($guanggao) {
                        for ($i=0; $i < count($_POST['ids']) ; $i++) {
                            $data['wid'] = $_POST['ids'][$i];
                            $data['gid'] = $guanggao;
                            M("admin_guanggaoruanwen")->data($data)->add();
                        }

                        if($guanggao){
                            for ($i=0; $i < count($_POST['idss']) ; $i++) {
                                $data['wid'] = $_POST['idss'][$i];
                                $data['gid'] = $guanggao;
                                M("admin_guanggaofenlei")->data($data)->add();
                         }
                        }

                             $this->success("广告添加成功",U('GuangGao/liebiao'));
                     }else{
                            $this->error('文件上传失败，原因可能是：'.$upload->getError(),U('GuangGao/tianjia'));
                }

        }
    }

    //删除广告及软文GID
    public function delete(){
        $id=$_GET['id'];
        M("admin_guanggao")->startTrans();
        //删除a表gid
        $resa = M('admin_guanggaoruanwen')->where("gid ='{$id}'")->delete();
        $resc = M('admin_guanggaofenlei')->where("gid ='{$id}'")->delete();
        //删除b表id
        $resb=  M("admin_guanggao")->delete($id);
        // 如果同时成功
        if($resa || $resb){
            if($resc || $resb){
                M("admin_guanggao")->commit();
                $this->success("删除成功",U("GuangGao/liebiao"));
            }else{
                M("admin_guanggao")->rollback();
            }
        }
        $this->error("删除失败",U("GuangGao/liebiao"));
    }

    public function edit(){
            $id=$_GET['id'];
            $ruanwen = M('admin_guanggaoruanwen')
                        ->join("LEFT JOIN dz_admin_ruanwen on dz_admin_guanggaoruanwen.wid = dz_admin_ruanwen.id")
                        ->where("dz_admin_guanggaoruanwen.gid = '{$id}'")
                        ->field("dz_admin_ruanwen.id,dz_admin_ruanwen.title")
                        ->select();
            $fenlei = M('admin_guanggaofenlei')
                        ->join("LEFT JOIN dz_admin_ruanwen_category on dz_admin_guanggaofenlei.wid = dz_admin_ruanwen_category.id")
                        ->where("dz_admin_guanggaofenlei.gid = '{$id}'")
                        ->field("dz_admin_ruanwen_category.id,dz_admin_ruanwen_category.name")
                        ->select();
            $res=M('admin_guanggao')->find($id);
            $moban = M("admin_template")->where("status = 0")->select();
            $this->assign('ruanwen',$ruanwen);
            $this->assign('fenlei',$fenlei);
            $this->assign('moban',$moban);
            $this->assign('res',$res);
            $this->display("guanggao/update");
    }
    //修改页面搜索
    public function xiugaisousuo()
    {
            $shousuo = $_POST['val'];
            $users = M("admin_ruanwen")->where("title like '%{$shousuo}%'")->field("title,id")->select();
            $data = array(
                'code' => 1,
                'type' => ok,
                'num' => $users
            );
            $this->ajaxReturn($data);
    }


    public function xiugaifenlei()
    {
            $fenlei = $_POST['v'];
            $users = M("admin_ruanwen_category")->where("name like '%{$fenlei}%'")->field("name,id")->select();
            $data = array(
                'code' => 1,
                'type' => ok,
                'num' => $users
            );
            $this->ajaxReturn($data);
    }



   //执行修改
    public function update(){
        $id=$_POST['id'];
        $data=array(
            'title'    =>  $_POST['title'],
            'url'      =>  $_POST['url'],
            'zsxg'     =>  $_POST['zsxg'],
            'tgms'     =>  $_POST['tgms'],
            'money'    =>  admin_yuanToFen($_POST['money']),
            'jsfs'     =>  $_POST['jsfs'],
            'jszq'     =>  $_POST['jszq'],
            'zsxg'     =>  $_POST['zsxg'],
            'display' =>   $_POST['display'],
            'expense'  =>  admin_yuanToFen($_POST['expense']),
            'budget'   =>  admin_yuanToFen($_POST['budget']),
            'status'   =>  $_POST['status'],
            'remark'   =>  $_POST['remark'],
            'type'     =>  $_POST['type'],
            'pic'      =>  $_FILES['pic']
        );
    if($_FILES['pic']['name']!==""){
            //var_dump($_FILES['pic']['name']);
            //exit;
            $upload = new \Think\Upload();
            $upload->maxSize=0;
            $upload->exts=array('jpg','gif','png','jpeg');
            $upload->rootPath='./Public/Uploads/ad/';
            $upload->autoSub=false;
            $info=$upload->upload();
        if($info){
            vendor('aliyun.autoload');
            $ossClient = new \OSS\OssClient(C('_OSS_ACCESS_KEY_ID_'), C('_OSS_ACCESS_KEY_SECRET_'), C('_OSS_ENDPOINT_'));
            //oss保存的文件名称
            $object = 'guanggao/' . date('Y-m-d') . '/' . $info['pic']['savename'];
            //文件路径，必须是本地的。
            $file = $upload->rootPath . $info['pic']['savepath'] . $info['pic']['savename'];
         try{
                //上传文件到oss
                $ossClient->uploadFile(C('_OSS_BUCKET_'), $object, $file);
                //删除本地文件
                unlink($file);
                //拼接oss地址
                $oo = $data['pic'] = '//' . C('_OSS_BUCKET_') . '.' . C('_OSS_ENDPOINT_') . '/' . $object;
                // var_dump($oo);
                // exit;
            }catch(OssException $e){
                //oss上传失败
                $this->error("OSS上传错误：" . $e->getMessage());
            }
                $result=M('admin_guanggao')->where("id=".$id)->save($data);
            }
            }else{
                $result=M('admin_guanggao')->where("id=".$id)->save($data);
            }
            //所有文章
            if ($_POST['type'] === '0') {
                $ArticlesWid = M('admin_guanggaoruanwen')->where("gid='{$id}'")->delete();
                $BrticlesWid = M('admin_guanggaofenlei')->where("gid='{$id}'")->delete();
                $this->success('修改成功', U("GuangGao/liebiao"));
            }
            //指定文章
            if ($_POST['type'] === '1') {
                $ArticlesWid = M('admin_guanggaoruanwen')->where("gid='{$id}'")->delete();
                $BrticlesWid = M('admin_guanggaofenlei')->where("gid='{$id}'")->delete();
                $ruanwennum = count($_POST['rwid']);
                for ($i=0; $i < $ruanwennum; $i++) {
                    $ruanwenid['wid'] = $_POST['rwid'][$i];
                    $SC['wid'] = $ruanwenid['wid'];
                    $SC['gid'] = $_POST['id'];
                    M("admin_guanggaoruanwen")->data($SC)->add();
                }
                $this->success('修改成功', U("GuangGao/liebiao"));
            }
            //指定分类
            if ($_POST['type'] === '2') {
                $ArticlesWid = M('admin_guanggaoruanwen')->where("gid='{$id}'")->delete();
                $BrticlesWid = M('admin_guanggaofenlei')->where("gid='{$id}'")->delete();
                $ruanwennum = count($_POST['flid']);
                for ($i=0; $i < $ruanwennum; $i++) {
                    $ruanwenid['wid'] = $_POST['flid'][$i];
                    $SC['wid'] = $ruanwenid['wid'];
                    $SC['gid'] = $_POST['id'];
                    M("admin_guanggaofenlei")->data($SC)->add();
                }
                $this->success('修改成功', U("GuangGao/liebiao"));
            }
     }

    public function search()
    {
        $where=array();
        //获取搜索条件
        if(!empty($_POST['aci'])){
            $userForm=M('admin_guanggao'); //实例化数据表
            $where['id | addtime']=array('like',"%{$_POST['aci']}%");//写查询条件
            $results = $userForm->where($where)->select();
            $this->assign("list",$results);
            $this->display("guanggao/liebiao");
        }elseif (!empty($_POST['cci'])) {
            $userForm=M('guanggao_user'); //实例化数据表
            $where['id | account']=array('like',"%{$_POST['cci']}%");//写查询条件
            $results = $userForm->where($where)->select();
            $this->assign("list",$results);
            $this->display("guanggao/advertiser");
        }
    }

    //获取软文
    public function shousuo(){
        $rw = $_POST['rw'];
        // var_dump($rw);
        // exit;
        $ruanwen = M('admin_ruanwen')->where("title like '%{$rw}%'")->field("id,title")->select();
        $this->ajaxReturn($ruanwen);
    }

    //获取分类搜索
    public function fenleishousuo(){
        $fl = $_POST['fl'];
        // var_dump($fl);
        // exit;
        $fenlei = M('admin_ruanwen_category')->where("name like '%{$fl}%'")->field("id,name")->select();

        $this->ajaxReturn($fenlei);
    }

    //广告展示模板
    public function showcase()
    {
        $template = M('admin_template')->select();
        $this->assign("list", $template);
        $this->display('guanggao/showcaselist');
    }

    //添加广告展示模板
    public function showcaseadd()
    {
        $this->display("guanggao/showcaseadd");
    }

    //执行添加广告展示模板
    public function showcasedoadd()
    {
        $data = array(
            'name' => $_POST['mbname'],
            'remark' => $_POST['mbbz'],
            'code' => I('post.mbdm'),
            'status' => $_POST['mbzt'],
            'creat_time' => time(),
            'turnover_time' => time()
        );

        $add = M('admin_template')->data($data)->add();

        if($add){
            $this->success('添加成功',U("GuangGao/showcase"));
        }else{
            $this->error("添加失败");
        }
    }

    //查看广告详细信息
    public function look()
    {
        $GuangGao = M("guanggao_title")->where("id = '{$_GET['id']}'")->find();
        $GuangGao['tgimg'] = explode("wzpt", rtrim($GuangGao['tgimg'],"wzpt"));
        $GuangGao['sqimg'] = explode("wzpt", rtrim($GuangGao['sqimg'],"wzpt"));
        
        $this->assign("guanggao",$GuangGao);
        $this->display("GuangGao/GuangGaolook");
    }
    //删除广告展示模板
    public function showcasedelete()
    {
        $del = M('admin_template')->data($_GET)->delete();

        if($del){
            $this->success("删除成功",U("GuangGao/showcase"));
        }else{
            $this->error("删除失败");
        }
    }

    //修改广告展示模板
    public function showcaseedit()
    {
        $id = $_GET['id'];
        $edit = M('admin_template')->where("id = '{$id}'")->find();
        $tem = M('admin_template') -> where("status = 0")->select();
        $this->assign("edit",$edit);
        $this->display('guanggao/showcaseedit');
    }

    //执行修改广告展示模板
    public function showcasedoedit()
    {
        $id = $_POST['id'];
        $data = array(
            'name' => $_POST['mbname'],
            'remark' => $_POST['mbbz'],
            'code' => I('post.mbdm'),
            'status' => $_POST['mbzt'],
            'turnover_time' => time()
        );
        $doedit = M("admin_template")->where("id = '{$id}'")->data($data)->save();
        if($doedit){
            $this->success('修改成功',U("GuangGao/showcase"));
        }else{
            $this->error("修改失败");
        }
    }

    //广告页面
    public function GuangGaolist()
    {
        $list = M('guanggao_title')->select();
        $this->assign("list",$list);
        $this->display("guanggao/GuangGaolist");
    }

    //删除广告用户
    public function ddodelete()
    {
        $del = M('guanggao_title')->data($_GET)->delete();
        if($del){
            $this->success("删除成功",U("GuangGao/GuangGaolist"));
          }else{
                $this->error("删除失败");
             }
    }



    //快速上下线
    public function Quick()
    {
        $id=$_GET['id'];
        $res=M("admin_guanggao")->field('status')->find($id);
        if ($res['status']=='0') {
             $listA = M("admin_guanggao")->where("id='{$id}'")->data(array("status" => 1))->save();
        }else{
             $listB = M("admin_guanggao")->where("id='{$id}'")->data(array("status" => 0))->save();

        }
        $this->redirect('GuangGao/liebiao');
    }


    //广告主
    public function Advertiser()
    {
        $list=M("guanggao_user")->select();
        $this->assign("list",$list);
        $this->display("guanggao/advertiser");
    }


    //查看广告主消费及充值金额
    public function check()
    {
       $id=$_GET['id'];
       $list=M("guanggao_record")->where("uid={$id}")->order("create_time desc")->limit(0,10)->select();
        foreach ($list as $i => $item) {
            $list[$i]['recharge'] = admin_fenToYuan($list[$i]['recharge']);
        }
       $this->assign("list",$list);
       $this->display("recharge");
    }

    //删除广告主信息
    public function dodelete()
    {
       if(M('guanggao_user')->delete($_GET['id'])){
            $this->success('删除成功',U('GuangGao/Advertiser'));
        }else{
            $this->error();
        }
    }


    //广告主快速上线下
    public function RRapid()
    {
        $id=$_GET['id'];
        $res=M("guanggao_user")->field('status')->find($id);
        if ($res['status']=='0') {
             $listA = M("guanggao_user")->where("id='{$id}'")->data(array("status" => 1))->save();
        }else{
             $listB = M("guanggao_user")->where("id='{$id}'")->data(array("status" => 0))->save();

        }
        $this->redirect('GuangGao/Advertiser');
    }

    //审核广告用户提交的广告 balances余额 bprice预算金额
    public function Rapid()
    {
        $id=$_GET['id'];
        $list=M("guanggao_title")->field("status,uid,bprice")->find($id);
      
        if($list['status']==='0') {
            $listA = M("guanggao_title")->where("id='{$id}'")->data(array("status" => 1))->save();
        }else{
            $listB = M("guanggao_title")->where("id='{$id}'")->data(array("status" => 0))->save();
        }

        if($list['status']==='2'){
            $listE=M("guanggao_user")->where("id='{$list['uid']}'")->setInc('balances', $list['bprice']);
             foreach ($listE as $i => $item) {
               $listE[$i]['balances'] = admin_fenToYuan($listE[$i]['balances']);
               $listE[$i]['bprice'] = admin_fenToYuan($listE[$i]['bprice']);
            }
        }
        $this->redirect("GuangGao/GuangGaolist");
    }


     //修改广告用户
    public function doedit()
    {
        $mod=M('guanggao_title');
        $res=$mod->where("id={$_GET['id']}")->find();
        $ad = M("admin_guanggao")->field("id,title")->select();
        $this->assign('res',$res);
        $this->assign('ad',$ad);
        $this->display('guanggao/doupdate');
    }


    //修改广告
    public function doupdate()
    {
        $ad_id = I('post.bd_id');
        // var_dump($ad_id);
        // exit;
        $id = I('post.id');
        M("guanggao_title")->where("id = {$id}")->save(array("gid" => $ad_id));
        
        $this->success("插入成功",U('GuangGao/GuangGaolist'));
        
    }

    //广告搜索接口
    public function Searchadvertising()
    {
        
        $search = M("admin_guanggao")->where("title like '%{$_POST['val']}%'")->select();
        // var_dump($search);
        // exit;
        $data = array(
            "num" => $search
        );
        $this->ajaxReturn($data);
    }
}