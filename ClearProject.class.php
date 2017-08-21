<?php

class ClearProject{
	private $db_connection;
	private $db_name;
	
	public $backup;
	
	public function __construct($host,$user,$pass,$name){
		$conn=mysqli_connect($host,$user,$pass);
		if(!$conn)
			throw new \Exception('Could not connect to database.');
		
		mysqli_select_db($conn,$name);
		mysqli_query($conn,'SET NAMES utf8');
		
		$this->db_connection=$conn;
		$this->db_name=$name;
	}
	
	private function get_tables($list='*'){
		$tables=array();
		
		if($list!='*'){
			$tables=is_array($tables)?$tables:explode(',',$tables);
		}else{
			$result=mysqli_query($this->db_connection,'SHOW TABLES');
			while($row=mysqli_fetch_row($result)){
				$tables[]=$row[0];
			};
		};
		
		return $tables;
	}
	private function get_files($dir){
		$res=array();
		
		$files=array_diff(scandir($dir),array('..', '.'));
		foreach($files as $file){
			$path=rtrim($dir,'/').'/'.$file;
			if(is_file($path) and is_readable($path)){
				$res[]=$path;
			}elseif(is_dir($path) and is_readable($path)){
				$res=array_merge($res,$this->get_files($path));
			};
		};
		
		return $res;
	}
	
	
	public function get_db_backup_name(){
		$domain=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:null;
		if(is_null($domain)) 
			$domain=isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:null;
		
		return $domain.' -- db-backup - '.date('d-m-Y H-i-s').'.sql';
	}
	public function get_backup($tables='*'){
		if(!empty($this->backup))
			return $this->backup;
		
		$tables=$this->get_tables($tables);
		$link=&$this->db_connection;
		$return='/**
			Date: '.date('Y-m-d H:i:s').'
			Database name: '.$this->db_name.'
		*/';
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
		
		return $this->backup=$return;
	}
	public function clear_database($tables='*'){
		$tables=$this->get_tables($tables);
		if(empty($tables))
			return $this;
		
		mysqli_query('DROP TABLE '.implode(',',array_filter(array_unique($tables))));
		
		return $this;
	}
	public function clear_project(){
		$ROOT=rtrim($_SERVER['DOCUMENT_ROOT'],'/ ').'/';
		
		$files=$this->get_files($ROOT);
		foreach($files as $file)
			@unlick($file);
		
		return $this;
	}
}
