<?php
namespace Admin\Controller;

use Think\Controller;
use OSS\Core\OssException;

class RuanWenController extends QXController {

    //文章转移

    public function transfer_article()
    {
        $before_id = I('get.before_id/d');
        $after_id = I('get.after_id/d');
        if ($before_id > 0 && $after_id > 0) {
            M("admin_ruanwen")->where("category = '{$before_id}'")->save(array("category" => $after_id));
            $req = array('code' => 1, 'msg' => '转移成功！');
        } else {
            $req = array('code' => 0, 'msg' => '转移失败,请稍后再试！');
        }
        $this->ajaxReturn($req);
    }

    //分类列表
    public function category()
    {
        //获取操作类型
        $op = I('get.op');
        if (empty($op)) {
            $op = 'display';
        }

        if ($op == 'display') {
            //查询分类
            $category = M('admin_ruanwen_category');
            $count    = $category->count();
            $Page     = new \Think\Page($count, 10);
            $show     = $Page->show();
            $list = $category->order('level desc')->limit($Page->firstRow.','.$Page->listRows)->select();
            $list_all = $category->select();
            $this->assign('list', $list);
            $this->assign('list_all', $list_all);
            $this->assign('page', $show);
            $this->display('ruanwen/category');
        }
    }

    //分类添加
    public function category_add()
    {
        //获取操作类型
        $op = I('get.op');
        if (empty($op)) {
            $op = 'display';
        }
        if ($op == 'display') {
            $this->display('ruanwen/category_add');
        } elseif ($op == 'save') {
            $name = I('post.name');
            if (empty($name)) {
                $this->error("名称不能为空！");
            } else {
                //获取提交的数据
                $data['name'] = $name;
                $data['create_time'] = time();
                $data['update_time'] = time();
                //插入数据库
                $insert = M('admin_ruanwen_category')->add($data);
                if ($insert) {
                    $this->success("添加成功！", U('RuanWen/category'));
                } else {
                    $this->error("添加失败，请稍后再试！");
                }
            }
        }
    }

    //删除分类
    public function category_delete()
    {
        $req = array('code' => -1, 'msg' => '删除异常！');
        //获取id
        $id = intval(I('get.id'));
        if ($id <= 0) {
            $this->error("id 不能为空！");
        }
        //删除指定id文章
        $delete = M('admin_ruanwen_category')->where("id = {$id}")->delete();
        if ($delete) {
            $req = array('code' => 1, 'msg' => '删除成功！');
        } else {
            $req = array('code' => 0, 'msg' => '删除失败,请稍后再试！');
        }
        $this->ajaxReturn($req);
    }

    //编辑分类
    public function category_edit()
    {
        //获取操作类型
        $op = I('get.op');
        if (empty($op)) {
            $op = 'display';
        }

        //获取分类id
        $id = intval(I('get.id'));
        if ($id <= 0) {
            $this->error("id 不能为空！");
        }

        if ($op == 'display') {
            //根据id获取分类信息
            $category = M('admin_ruanwen_category')->where("id = {$id}")->find();
            if ($category) {
                $this->assign('category', $category);
                $this->display("ruanwen/category_edit");
            } else {
                $this->error("分类不存在！");
            }
        } elseif ($op == 'save') {
            //获取分类名称
            $name = I('post.name');
            if (empty($name)) {
                $this->error("名称不能为空！");
            } else {
                $data['name'] = $name;
                $data['update_time'] = time();
                //存入数据库
                $update = M('admin_ruanwen_category')->where("id = {$id}")->save($data);
                if ($update) {
                    $this->success("修改成功！", U('RuanWen/category'));
                } else {
                    $this->error("修改失败，请稍后再试！");
                }
            }
        }
    }

    //查询分类下有多少篇文章
    public function category_inquire()
    {
        $id = intval(I('get.id'));
        if ($id <= 0) {
            //不存在的id
            echo "0";
            exit();
        } else {
            //查询出文章的数量
            $num = M('admin_ruanwen')->where("category = {$id}")->count();
            echo $num;
            exit();
        }
    }

    //分类往上一个位置移动
    public function category_move()
    {
        $id = intval(I('get.id'));
        //查询分类信息
        $info = M('admin_ruanwen_category')->where("id = {$id}")->find();
        if (empty($info)) {
            $req = array('code' => 1, 'msg' => '分类不存在!');
            $this->ajaxReturn($req);
        }
        //查询比当前分类前一级的信息
        $before = M('admin_ruanwen_category')->where("level > {$info['level']}")->order('level desc')->find();
        if ($before) {
            $level = $before['level'] + 1;
        } else {
            $level = $info['level'] + 1;
        }
        $save = M('admin_ruanwen_category')->where("id = {$id}")->save(array('level' => $level));
        if ($save) {
            $req = array('code' => 1, 'msg' => '保存成功！');
        } else {
            $req = array('code' => 0, 'msg' => '保存失败,请稍后再试！');
        }
        $this->ajaxReturn($req);
    }

    //删除分类下全部文章
    public function article_delete()
    {
        $id = intval(I('get.id'));
        $delete = M('admin_ruanwen')->where("category = {$id}")->delete();
        if ($delete) {
            $req = array('code' => 1, 'msg' => '删除成功！');
        } else {
            $req = array('code' => 0, 'msg' => '删除失败,请稍后再试！');
        }
        $this->ajaxReturn($req);
    }

    //文章预览
    public function preview() {
        $id = intval(I('get.id'));
        if ($id <= 0) {
            $this->error("id 不能为空！");
        }
        $ruanwen = M('admin_ruanwen')->where("id = {$id}")->find();
        if ($ruanwen) {
            $ruanwen['content'] = htmlspecialchars_decode($ruanwen['content']);
            $this->assign('ruanwen', $ruanwen);
            $this->display("ruanwen/preview");
        } else {
            $this->error("文章不存在！");
        }
    }

    //文章列表
    public function lists()
    {
        //获取操作类型
        $op = I('get.op');
        if (empty($op)) {
            $op = 'display';
        }

        //初始化条件
        $where = "";
        $search = I('get.search');
        if (!empty($search)) {
            $where = "title LIKE '%" . $search . "%'";
            $this->assign('search', $search);
        }
        $category_val = intval(I('get.category'));
        if ($category_val > 0) {
            if (empty($where)) {
                $where = "category = '" . $category_val . "'";
            } else {
                $where .= " AND category = '" . $category_val . "'";
            }
            $this->assign('category_val', $category_val);
        }

        $ruanwen = M('admin_ruanwen');

        if (I('get.submit_type') == "删除") {
            $delete = $ruanwen->where($where)->delete();
            if ($delete) {
                $this->success("删除成功");
            } else {
                $this->error("删除失败");
            }
            exit();
        }

        $count   = $ruanwen->where($where)->count();
        $Page    = new \Think\Page($count, 10);
        if (!empty($search)) {
            $Page->parameter['search'] = $search;
        }
        if ($category_val > 0) {
            $Page->parameter['category_val'] = $category_val;
        }
        $show = $Page->show();
        $list = $ruanwen->field('dz_admin_ruanwen.id,dz_admin_ruanwen.title,dz_admin_ruanwen.money,dz_admin_ruanwen.thumb,dz_admin_ruanwen.describe,dz_admin_ruanwen.update_time,dz_admin_ruanwen.status,dz_admin_ruanwen.read_num,dz_admin_ruanwen.share_num,dz_admin_ruanwen_category.name')->join('dz_admin_ruanwen_category ON dz_admin_ruanwen.category = dz_admin_ruanwen_category.id')->where($where)->order('dz_admin_ruanwen.create_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        //获取分类信息
        $category = M('admin_ruanwen_category')->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('category', $category);
        $this->display("ruanwen/lists");
    }

    //删除文章
    public function delete()
    {
        $req = array('code' => -1, 'msg' => '删除异常！');
        //获取id
        $id = intval(I('get.id'));
        if ($id <= 0) {
            $this->error("id 不能为空！");
        }
        //删除指定id文章
        $delete = M('admin_ruanwen')->where("id = {$id}")->delete();
        if ($delete) {
            $req = array('code' => 1, 'msg' => '删除成功！');
        } else {
            $req = array('code' => 0, 'msg' => '删除失败,请稍后再试！');
        }
        $this->ajaxReturn($req);
    }

    //编辑文章
    public function edit()
    {
        //获取操作类型
        $op = I('get.op');
        if (empty($op)) {
            $op = 'display';
        }
        //获取编辑的文章id
        $id = intval(I('get.id'));
        if ($id <= 0) {
            $this->error("id 不能为空！");
        }
        if ($op == 'display') {
            //获取文章内容
            $ruanwen = M('admin_ruanwen')
                        ->join("LEFT JOIN dz_admin_guanggaoruanwen on dz_admin_ruanwen.id = dz_admin_guanggaoruanwen.wid")
                        ->join("LEFT JOIN dz_admin_guanggao on dz_admin_guanggaoruanwen.gid = dz_admin_guanggao.id")
                        ->where("dz_admin_ruanwen.id = {$id}")
                        ->field("dz_admin_ruanwen.*,dz_admin_guanggao.title,dz_admin_guanggao.id as gid")
                        ->select();
            $ruanwenlist = array();
            for($i = 0;$i< count($ruanwen);$i ++){
                    $ruanwenlist[$i]['title'] = $ruanwen[$i]['title'];
                    $ruanwenlist[$i]['gid'] = $ruanwen[$i]['gid'];
            }
            $ruanwen = M('admin_ruanwen')->where("id = {$id}")->find();
            if ($ruanwen) {
                $ruanwen['content'] = htmlspecialchars_decode($ruanwen['content']);
                //获取分类信息
                $category = M('admin_ruanwen_category')->select();
                $this->assign('category', $category);
                $this->assign("list",$ruanwenlist);
                $this->assign('ruanwen', $ruanwen);
                $this->display("ruanwen/edit");
            } else {
                $this->error("文章不存在！");
            }
        } elseif ($op == 'save') {
            //获取提交数据
            $data = array(
                'title' => I('post.title'),
                'describe' => I('post.describe'),
                'category' => I('post.category'),
                'content' => I('post.content'),
                'read_num' => intval(I('post.read_num')),
                'share_num' => intval(I('post.share_num')),
                'money' => intval(I('post.money')),
                'status' => intval(I('post.status'))
            );
            //正文/标题判断
            if (empty($data['title']) || empty($data['content'])) {
                $this->error("标题/正文不能为空！");
            }
            //描述为空取正文前100字！
            if (empty($data['describe'])) {
                $data['describe'] = substr($this->html2text($data['content']), 0, 100);
            }
            //更新缩略图标
            if (!empty($_FILES['thumb']['name'])) {
                //上传缩略图
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize = 3145728 ;// 设置附件上传大小
                $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath = './Public/Uploads/'; // 设置附件上传根目录
                // 上传单个文件
                $info = $upload->uploadOne($_FILES['thumb']);
                if(!$info) {// 上传错误提示错误信息
                    $this->error($upload->getError());
                }else{//本地上传成功，转存到阿里云OSS
                    //加载aliyun oss
                    vendor('aliyun.autoload');
                    //连接oss
                    $ossClient = new \OSS\OssClient(C('_OSS_ACCESS_KEY_ID_'), C('_OSS_ACCESS_KEY_SECRET_'), C('_OSS_ENDPOINT_'));
                    $object = 'article/' . date('Y-m-d') . '/' . $info['savename'];//oss保存的文件名称
                    $file = $upload->rootPath . $info['savepath'] . $info['savename'];//文件路径，必须是本地的。
                    try{
                        //上传文件到oss
                        $ossClient->uploadFile(_OSS_BUCKET_, $object, $file);
                        //删除本地文件
                        unlink($file);
                        //拼接oss地址
                        $data['thumb'] = '//' . _OSS_BUCKET_ . '.' . C('_OSS_ENDPOINT_') . '/' . $object;
                    } catch(OssException $e) {
                        //oss上传失败
                        $this->error("OSS上传错误：" . $e->getMessage());
                    }
                    //本地路径
                    /*$data['thumb'] = '/Public/Uploads/' . $info['savepath'] . $info['savename'];*/
                }
            }
            //更新时间
            $data['update_time'] = time();
            //是否关联广告
            if (!empty($_POST['ids'])) {
                //存入数据库
                $updateguanggao = M("admin_guanggaoruanwen")->where("wid = {$id}")->delete();
                foreach ($_POST['ids'] as $key => $value) {
                    $addguanggao = M("admin_guanggaoruanwen")->data(array("wid"=> $id,"gid" => $value))->add();
                }
                $update = M('admin_ruanwen')->where("id = {$id}")->save($data);
            }
            if ($update) {
                $this->success("修改成功！", U('RuanWen/lists'));
            } else {
                $this->error("修改失败,请稍后再试！");
            }
        }
    }

    //添加文章
    public function add()
    {

        //获取操作类型
        $op = I('get.op');
        $ruanwen = M('admin_ruanwen')->field('title,id')->select();
        $this->assign("ruanwen",$ruanwen);
        if (empty($op)) {
            $op = 'display';
        }
        if ($op == 'display') {
            //获取分类信息
            $category = M('admin_ruanwen_category')->select();
            $this->assign('category', $category);
            //渲染添加页面
            $this->display("ruanwen/add");
        } elseif ($op == 'save') {
            //获取提交数据
            $data = array(
                'title' => I('post.title'),
                'describe' => I('post.describe'),
                'category' => I('post.category'),
                'content' => I('post.content'),
                'read_num' => intval(I('post.read_num')),
                'share_num' => intval(I('post.share_num')),
                'money' => intval(I('post.money')),
                'status' => intval(I('post.status')),
                'thumb' => ''
            );

            if (empty($data['title']) || empty($data['content'])) {
                $this->error("标题/正文不能为空！");
            }
            if (empty($_FILES['thumb']['name'])) {
                $this->error("缩略图不能为空！");
            }
            //描述为空取正文前100字！
            if (empty($data['describe'])) {
                $data['describe'] = substr($this->html2text($data['content']), 0, 100);
            }
            //上传缩略图
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize = 3145728 ;// 设置附件上传大小
            $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath = './Public/Uploads/'; // 设置附件上传根目录
            // 上传单个文件
            $info = $upload->uploadOne($_FILES['thumb']);
            if(!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            } else {//本地上传成功，转存到阿里云OSS
                //加载aliyun oss
                vendor('aliyun.autoload');
                //连接oss
                $ossClient = new \OSS\OssClient(C('_OSS_ACCESS_KEY_ID_'), C('_OSS_ACCESS_KEY_SECRET_'), C('_OSS_ENDPOINT_'));
                $object = 'article/' . date('Y-m-d') . '/' . $info['savename'];//oss保存的文件名称
                $file = $upload->rootPath . $info['savepath'] . $info['savename'];//文件路径，必须是本地的。
                try{
                    //上传文件到oss
                    $ossClient->uploadFile(_OSS_BUCKET_, $object, $file);
                    //删除本地文件
                    unlink($file);
                    //拼接oss地址
                    $data['thumb'] = '//' . _OSS_BUCKET_ . '.' . C('_OSS_ENDPOINT_') . '/' . $object;
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    //插入数据库
                    $insert = M('admin_ruanwen')->add($data);

                    if ($insert) {
                        for ($i = 0; $i < count($_POST['ids']); $i++) {
                            $data['gid'] = $_POST['ids'][$i];
                            $data['wid'] = $insert;
                            M("admin_guanggaoruanwen")->data($data)->add();
                        }
                        $this->success("添加成功！", U('RuanWen/lists'));
                    } else {
                        $this->error("添加失败！");
                    }
                } catch(OssException $e) {
                    //oss上传失败
                    $this->error($e->getMessage());
                }
                //本地路径
                /*$data['thumb'] = '/Public/Uploads/' . $info['savepath'] . $info['savename'];*/
            }
        }
    }

    //html转txt文本
    public function html2text($str)
    {
        $str = preg_replace("/<style .*?<\/style>/is", "", $str);  $str = preg_replace("/<script .*?<\/script>/is", "", $str);
        $str = preg_replace("/<br \s*\/?\/>/i", "\n", $str);
        $str = preg_replace("/<\/?p>/i", "\n\n", $str);
        $str = preg_replace("/<\/?td>/i", "\n", $str);
        $str = preg_replace("/<\/?div>/i", "\n", $str);
        $str = preg_replace("/<\/?blockquote>/i", "\n", $str);
        $str = preg_replace("/<\/?li>/i", "\n", $str);
        $str = preg_replace("/\&nbsp\;/i", " ", $str);
        $str = preg_replace("/\&nbsp/i", " ", $str);
        $str = preg_replace("/\&amp\;/i", "&", $str);
        $str = preg_replace("/\&amp/i", "&", $str);
        $str = preg_replace("/\&lt\;/i", "<", $str);
        $str = preg_replace("/\&lt/i", "<", $str);
        $str = preg_replace("/\&ldquo\;/i", '"', $str);
        $str = preg_replace("/\&ldquo/i", '"', $str);
        $str = preg_replace("/\&lsquo\;/i", "'", $str);
        $str = preg_replace("/\&lsquo/i", "'", $str);
        $str = preg_replace("/\&rsquo\;/i", "'", $str);
        $str = preg_replace("/\&rsquo/i", "'", $str);
        $str = preg_replace("/\&gt\;/i", ">", $str);
        $str = preg_replace("/\&gt/i", ">", $str);
        $str = preg_replace("/\&rdquo\;/i", '"', $str);
        $str = preg_replace("/\&rdquo/i", '"', $str);
        $str = strip_tags($str);
        $str = html_entity_decode($str, ENT_QUOTES, $encode);
        $str = preg_replace("/\&\#.*?\;/i", "", $str);
        return $str;
    }

    //获取软文
    public function shousuo(){
        $rw = $_POST['rw'];
        $ruanwen = M('admin_guanggao')->where("title like '%{$rw}%'")->field("id,title")->select();
        // var_dump($ruanwen);
        // exit;
        $this->ajaxReturn($ruanwen);
    }

}