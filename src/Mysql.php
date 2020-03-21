<?php
##############################################
#           名称：mysql操作类
#           作者：crazycat
#           Q Q : 27012508
#           时间：2016-10-09
##############################################

namespace Shf;

class Mysql
{
	private $conn;
	private $_table;
	private $_where;
	private $_order;
	private $_limit;
	private $_field = '*';
    public  $sql;
	public function __construct($table='')
	{

		//是否加载了mysqli扩展
		$findExt = get_extension_funcs("mysqli");


		if(empty($findExt))
		{
			die('mysqli extension error');
		}

		if(!defined('DB_USER') or !defined('DB_PASW') or !defined('DB_NAME') or !defined('DB_HOST'))
		{
			die('mysql params error');
		}

		$port = defined('DB_PORT')?DB_PORT:3306;

		$this->conn = new \mysqli(DB_HOST,DB_USER,DB_PASW,DB_NAME,$port);

		if ($this->conn->connect_error)
		{
    		die('Connect Error (' . $this->conn->connect_errno . ') '. $this->conn->connect_error);
		}


		//设置连接字符集
		if(defined('DB_CSET')){
			$this->conn->query("set names ".DB_CSET);
		}else{
			$this->conn->query("set names utf8");

		}

		//设置表
		if(!empty($table))
		{
			$this->table($table);
		}

		/* 对用户传入的变量进行转义操作。*/
		if (!get_magic_quotes_gpc())
		{
		    if (!empty($_GET))
		    {
		        $_GET  = $this->addslashes_deep($_GET);
		    }
		    if (!empty($_POST))
		    {
		        $_POST = $this->addslashes_deep($_POST);
		    }

		    $_COOKIE   = $this->addslashes_deep($_COOKIE);
		    $_REQUEST  = $this->addslashes_deep($_REQUEST);
		}

	}




	//兼容大小写
	public function __call($methodName,$args)
	{


		$methodName=strtolower($methodName);


		if(method_exists($this, $methodName))
		{
			if(!empty($args[0]))
			{
				return $this->$methodName($args[0]);
			}else
			{
				return $this->$methodName();
			}

		}else
		{
			die('调用类'.get_class($this).'中的'.$methodName.'()方法不存在');
		}

	}

	private function addslashes_deep($value)
	{
	    if (empty($value))
	    {
	        return $value;
	    }
	    else
	    {
	        return is_array($value) ? array_map(array($this,'addslashes_deep'), $value) : addslashes($value);
	    }
	}


	public function table($table='')
	{
		if(!empty($table))
		{
			$this->_table = defined('DB_PREFIX')?DB_PREFIX.addslashes($table):addslashes($table);
		}

		return $this;
	}



	public function where($where='')
	{


		if(!empty($where))
		{
			if(is_string($where))
			{
				$array[] = ' ('.$where.' )';

			}else
			{
				$array=array();

				foreach($where as $k => $v)
				{
					if(is_string($v))
					{
						$array[] = '(`'.$k.'` = "'.addslashes($v).'" )';
					}elseif(is_array($v))
					{
						$array[] = '(`'.$k.'` '.addslashes(implode(' ',$v)).' )';
					}elseif(is_int($v))
					{
						$array[] = '(`'.$k.'` = '.addslashes($v).' )';
					}
				}
			}


			$this->_where = ' where '.implode(' and ', $array);

		}



		return $this;
	}

	public function field($field='')
	{

		if(!empty($field))
		{
			$this->_field = addslashes($field);
		}

		return $this;
	}


	public function order($orderby='')
	{

		if(!empty($orderby))
		{
			$this->_order = ' order by '.$orderby;
		}

		return $this;
	}

	public function limit($limit='')
	{

		if(!empty($limit))
		{
			$this->_limit = ' limit '.$limit;
		}

		return $this;
	}

	public function delete()
	{
		$sql = "delete from {$this->_table} {$this->_where}";
        $this->sql=$sql;
		$result = $this->conn->query($sql);
		$this->initPara();
		return $result;
	}

	public function select()
	{
		$sql = "select {$this->_field} from {$this->_table} {$this->_where} {$this->_order} {$this->_limit}";

		return $this->query($sql);

	}
	public function count()
	{
		$sql = "select {$this->_field} from {$this->_table} {$this->_where} {$this->_order} {$this->_limit}";
        $this->sql=$sql;
        $result = $this->conn->query($sql);
		$this->initPara();


		return $result?$result->num_rows:0;

	}
	public function find()
	{
		$sql = "select {$this->_field} from {$this->_table} {$this->_where} {$this->_order} {$this->_limit}";

		$result = $this->query($sql);

		return $result?$result[0]:$result;

	}

	public function add($data=array())
	{
		if(empty($data) and !is_array($data))
		{
			return false;
		}

		$field = array_keys($data);
		$value = array_values($data);


		$sql = "insert into {$this->_table}(".implode(',',$field).")values('".implode("','", $value)."')";
        $this->sql=$sql;
		$result = $this->conn->query($sql);
		$this->initPara();

		return $result;
	}


	public function save($data=array())
	{
		if(empty($data) and !is_array($data))
		{
			return false;
		}

		$array = array();

		foreach($data as $k => $v)
		{
			$array[] = '`'.$k.'` = "'.addslashes($v).'"';
		}


		$sql = "update {$this->_table} set ".implode(',', $array)." {$this->_where} ";
        $this->sql=$sql;
		$result = $this->conn->query($sql);
		$this->initPara();

		return $result;
	}

	public function insert_id()
	{
		return $this->conn->insert_id;
	}

	//sql语句注意过滤非法字符
	public function query($sql='')
	{

		if($this->conn and !empty($sql))
		{
            $this->sql=$sql;
			$result = $this->conn->query($sql);

			$this->initPara();

			return $this->getRes($result);

		}else
		{
			return false;
		}
	}

	private function initPara()
	{
		$this->_where='';
		$this->_order='';
		$this->_limit='';
		$this->_field = '*';
	}

	private function getRes($result=0)
	{

		if($result && isset($result->num_rows))
		{
			$re = array();
			while ( $row  =  $result->fetch_assoc())
			{
	    		$re[]=$row;
			}
			return $re;
		}else
		{
			return $result;
		}

	}




}
?>
