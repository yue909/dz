<?php
/**
 * 获取已上传的文件列表
 * User: Jinqn
 * Date: 14-04-09
 * Time: 上午10:17
 */
include "Uploader.class.php";

/* 判断类型 */
switch ($_GET['action']) {
    /* 列出文件 */
    case 'listfile':
        $allowFiles = $CONFIG['fileManagerAllowFiles'];
        $listSize = $CONFIG['fileManagerListSize'];
        $path = $CONFIG['fileManagerListPath'];
        break;
    /* 列出图片 */
    case 'listimage':
    default:
        $allowFiles = $CONFIG['imageManagerAllowFiles'];
        $listSize = $CONFIG['imageManagerListSize'];
        $path = $CONFIG['imageManagerListPath'];
}
$allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

/* 获取参数 */
$size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
$start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
$end = $start + $size;

/* 获取文件列表 */
$path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "":"/") . $path;
$files = getfiles($path, $allowFiles);
if (!count($files)) {
    return json_encode(array(
        "state" => "no match file",
        "list" => array(),
        "start" => $start,
        "total" => count($files)
    ));
}

// OSS 列表
require_once('../../../ThinkPHP/Library/Vendor/aliyun/autoload.php');//引入oss sdk
require_once('../../../Application/Common/Conf/config.php');//引入config
$ossClient = new \OSS\OssClient(_OSS_ACCESS_KEY_ID_, _OSS_ACCESS_KEY_SECRET_, _OSS_ENDPOINT_);//连接oss
$options = array(
    'delimiter' => '/',
    'prefix' => 'ueditor/',
    'max-keys' => 30,
    'marker' => $nextMarker
);
try{
    $listObjectInfo = $ossClient->listObjects(_OSS_BUCKET_, $options); //上传文件到oss
} catch(OssException $e) {
    printf(__FUNCTION__ . ": FAILED\n");
    printf($e->getMessage() . "\n");
}

$listObject = $listObjectInfo->getObjectList();
$list = array();
$temp = array();
for ($i=0; $i < count($listObject); $i++) {
    $temp = array(
        'url' => '//' . _OSS_BUCKET_ . '.' . _OSS_ENDPOINT_ . '/' . $listObject[$i]->getKey(),
        'mtime' => time()
    );
    $list[] = $temp;
}
if (count($list) > 0) {
    return json_encode(array(
        "state" => "SUCCESS",
        "list" => $list,
        "start" => 0,
        "total" => 30
    ));
} else {
    return json_encode(array(
        "state" => "no match file",
        "list" => array(),
        "start" => 0,
        "total" => 30
    ));
}
// OSS 列表

/* 获取指定范围的列表 */
$len = count($files);
for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
    $list[] = $files[$i];
}
//倒序
//for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
//    $list[] = $files[$i];
//}

/* 返回数据 */
$result = json_encode(array(
    "state" => "SUCCESS",
    "list" => $list,
    "start" => $start,
    "total" => count($files)
));

return $result;


/**
 * 遍历获取目录下的指定类型的文件
 * @param $path
 * @param array $files
 * @return array
 */
function getfiles($path, $allowFiles, &$files = array())
{
    if (!is_dir($path)) return null;
    if(substr($path, strlen($path) - 1) != '/') $path .= '/';
    $handle = opendir($path);
    while (false !== ($file = readdir($handle))) {
        if ($file != '.' && $file != '..') {
            $path2 = $path . $file;
            if (is_dir($path2)) {
                getfiles($path2, $allowFiles, $files);
            } else {
                if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
                    $files[] = array(
                        'url'=> substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                        'mtime'=> filemtime($path2)
                    );
                }
            }
        }
    }
    return $files;
}