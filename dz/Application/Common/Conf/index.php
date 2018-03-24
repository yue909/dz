<html>
	<head>
		<title>修改配置</title>
		<meta charset='utf-8' />
	</head>
	
	<body>
		<form action='doUpdate.php' method='post'>
			<table border='1' width='300'>
				<?php
					//读取文件
					$info=file_get_contents("config.php");
					//var_dump($info);
					
					//正则
					preg_match_all('/define\(\"(.*?)\",\"(.*?)\"\)/',$info,$arr);
					//var_dump($arr);
					
					//遍历
					foreach($arr[1] as $k=>$v){
						echo "<tr>";
							echo "<td>{$v}</td>";
							echo "<td><input type='text' name='{$v}' value='{$arr[2][$k]}' /></td>";
						echo "</tr>";
					}
				?>
				<tr>
					<td colspan='2' align='center' >
						<input type='submit' value='保存' />
						<input type='reset'  />
					</td>
				</tr>
			</table>
		</form>
	</body>
</html>