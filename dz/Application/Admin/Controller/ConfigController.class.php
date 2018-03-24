<?php
namespace Admin\Controller;
use Think\Controller;
class ConfigController extends QXController {
    public function index() 
    {
        $list = M('admin_config')->select();

        $this ->assign("list", $list);

        $this ->display("config/index");
    }
    //加载添加模板页面
    public function add()
    {
        $this ->display("config/add");
    }

    //执行添加
    public function insert() 
    {
        $id = $_POST['id'];
        $rew = array(
            'key' =>$_POST['key'], 
            'name' =>$_POST['name'], 
            'value' =>$_POST['value'], 
            'status' =>$_POST['status'], 
            'addtime' =>time());

        $add = M('admin_config') ->data($rew) ->add();

        if ($add) {
            $this ->Sync();
            $this ->success("添加模板成功", U("Config/index"));
        } else {

            $this ->error("添加模板失败", U("Config/add"));
        }
    }

    //删除模块
    public function delete() 
    {

        if (M('admin_config') ->delete($_GET['id'])) {
            $this ->Sync();
            $this ->success("删除模板成功", U("Config/index"));
        } else {
            $this ->error("删除模板失败", U("Config/index"));
        }
    }

    //添加修改页面
    public function edit() 
    {
        $mod = M('admin_config');
        $res = $mod ->where("id={$_GET['id']}") ->find();
        // var_dump($res);
        // exit;
        $this ->assign('res', $res);
        $this ->display('config/edit');
    }

    //执行修改
    public function update() 
    {
        $rew = array(
            'key' =>$_POST['key'], 
            'name' =>$_POST['name'], 
            'value' =>$_POST['value'], 
            'status' =>$_POST['status']

        );

        $id = $_POST['id'];

        if (M('admin_config') ->where("id={$id}") ->save($rew)) {
            $this ->Sync();
            $this ->success('修改成功', U('Config/index'));
        } else {
            $this ->error('修改失败', U('Config/edit'));
        }
    }

    //同步数据库
   public function Sync() 
   {
        $mod  = M("admin_config") ->select();
        //文件路径地址
        $path = 'Application/'.Common.'/Conf/text.php';
        //读取配置文件
        $data = array();
        //数组循环，拼接成php文件
        $str  = '<?php return array( ';
                    foreach($mod as $key =>$value) {
                        $tempA = $value['value'];
                        $tempB = strval(intval($value['value']));
                        //|| $tempA == $tempB 是否等于数字
                         if ($value['value'] == "true" || $value['value'] == "false" || $value['value'] == "TRUE" || $value['value'] == "FALSE") {
                                $str.= "'" . $value['key'] . "'=>" . $value['value'] . ", //" . $value['name'] . "\n";
                          } else {
                                $str.= "'" . $value['key'] . "'=>'" . $value['value'] . "', //" . $value['name'] . "\n";
                            }
        }

        $str.=');';

        //写入文件中,更新配置文件
        if (file_put_contents($path, $str)) {
            echo '保存成功！';
            // $this->success('修改成功',U('Config/index'));
        } else {
            echo '保存失败！';
            // $this->error('同步失败',U('Config/index'));
        }
    }
}