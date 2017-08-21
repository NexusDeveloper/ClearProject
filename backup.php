<?php
function __get_database_tables($db_conn){
	$res=array();
	$result=mysqli_query($db_conn,'SHOW TABLES');
	while($row=mysqli_fetch_row($result)){
		$res[]=$row[0];
	};
	
	return $res;
};
function __get_database_backup($host,$user,$pass,$name,$tables='*'){
	$link=mysqli_connect($host,$user,$pass);
	mysqli_select_db($link,$name);
	mysqli_query($link,'SET NAMES utf8');
	
	if($tables=='*'){
		$tables=__get_database_tables($link);
	}else{
		$tables=is_array($tables)?$tables:explode(',',$tables);
	}
	
	foreach($tables as $table){
			$result=mysqli_query($link,'SELECT * FROM '.$table);
			$num_rows=mysqli_num_rows($result);
			
			$row2=mysqli_fetch_row(mysqli_query($link,'SHOW CREATE TABLE '.$table));
			$row2=str_ireplace(
				'Create Table',
				'CREATE TABLE IF NOT EXISTS',
				$row2);
				
			$return.="\n\n".$row2[1].";";
			$index=0;
			while($row=mysqli_fetch_row($result)){
				if(++$index==1 or $index%10==1){
					$return.='INSERT INTO `'.$table."` \n\t";
				};
				
				$return.='VALUES(';
				for($j=0;$j<count($row);$j++){
					$row[$j]=addslashes($row[$j]);
					$row[$j]=str_replace("\n","\\n",$row[$j]);
					if(isset($row[$j])){
						$return.='"'.$row[$j].'"';
					}else{
						$return.='""';
					};
					
					if($j<(count($row)-1)){
						$return.= ',';
					};
				};
				
				if($index==$num_rows or $index%10==0){
					$return.=');'."\n";
				}else{
					$return.='),'."\n\t";
				};
			};
				
			$return.="\n\n\n";
	};
	
	return $return;
};
function __backup_database($host,$user,$pass,$name,$tables='*'){
	$return=__get_database_backup($host,$user,$pass,$name,$tables);
	
	$file_name=rtrim($_SERVER['DOCUMENT_ROOT'],'/ ').'/'.__get_backup_file_name().'.gz';
	file_put_contents($file_name,gzencode($return));
	
	return $file_name;
};
function __get_backup_file_name(){
	$domain=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:null;
	if(is_null($domain)) 
		$domain=isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:null;
	
	return $domain.' -- db-backup - '.date('d-m-Y H-i-s').'.sql';
};
