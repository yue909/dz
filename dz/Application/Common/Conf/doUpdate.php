<?php 
//编辑配置文件 
 
$keyinfo = array("HOST"=>"主机","USER"=>"用户","PASS"=>"密码","DBNAME"=>"库名"); 
 
 
//1 读取配置文件的信息 
$info = file_get_contents("config.php");//将dbconfig.php文件中信息读出并赋给info变量 
//2 使用正则表达式解析配置文件的信息 
preg_match_all("/define\(\"(.*?)\",\"(.*?)\"\)/",$info,$a); //将info中的信息解析到a变量中存储 
 
echo "<pre>"; 
//var_dump($a); 
echo "</pre>"; 
//3 遍历解析后的信息，并输出到修改表单中 
echo "<h2>编辑配置文件</h2>"; 
echo "<form action='doupdate.php' method='post'>"; 
 
foreach($a[1] as $k=>$v){ 
    echo "{$keyinfo[$v]}:<input type='text' name='{$v}' value='{$a[2][$k]}'/><br/><br/>"; 
} 
 
echo "<input type='submit' value='编辑'/>    "; 
echo "<input type='reset' value='重置'/>"; 
echo "</form>";