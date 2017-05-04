<?php
namespace db;

/**
 * pdoHelper [Pdo 简单封装]
 * 
 * @uses PDO 
 * @version ${Id}$
 * @author Shaowei Pu <pushaowei@sporte.cn>
 * @license sporte.cn
 * @copyright 2016-12-26 19:42:48   体创(上海)云科技有限公司 
 * 
 */
# [-select-]
#
# $manage->getRow('SELECT * FROM test');
# $manage->getRow('SELECT * FROM test WHERE id=:id', [':id'=>10]);
# $manage->getRow('SELECT * FROM test WHERE id=:id', [':id'=>10], [':id'=>$manage::PARAM_INT]);
# $manage->table('test')->all();
# $manage->table('test')->field('id,name')->limit(1)->order('id DESC')->all();
# $manage->table('test')->where('id>100')->find();
# 
# [-create-]
#
#  $manage->table('test')->create(['name'=>1]);
# 
# [-update-]
# 
# $manage->table('test')->update(['name'=>'test2']);
# $manage->table('test')->where('id=5')->update(['content'=>'test', 'name'=>'test2']);
# $manage->table('test')->where('id=:id', [':id'=>3])->update(['content'=>'test', 'name'=>'test2']);
# $manage->table('test')->where('id=:id', [':id'=>2], [':id'=>$manage::PARAM_INT])->update(['content'=>'test', 'name'=>'test2']);
#
# [-delete-] 
# 
# $manage->table('test')->delete();
# $manage->table('test')->where('id=2')->delete();
# $manage->table('test')->where('id=:id', [':id'=>3])->delete();
# $manage->table('test')->where('id=:id', [':id'=>3], [':id'=>$manage::PARAM_INT])->delete();

class pdoHelper
{
    private $pdo = NULL;
	private $errorInfo = [];
	private $whereParms = [];
	private $whereSql = NULL;
	private $whereDataType = [];
	private $table = NULL;
	private $limit = '';
	private $order = '';
	private $field = '*';
	private static $_instance;
    private static $_pdohelper;
	
    const PARAM_BOOL = \PDO::PARAM_BOOL;
	const PARAM_NULL = \PDO::PARAM_NULL;
	const PARAM_INT  = \PDO::PARAM_INT;
	const PARAM_STR  = \PDO::PARAM_STR;
	const PARAM_LOB  = \PDO::PARAM_LOB;
	const PARAM_STMT = \PDO::PARAM_STMT;
	/**
	 * [__clone 禁止克隆]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T20:18:36+0800
	 * @return                              [type] [description]
	 */
	private function __clone() {}

	/**
	 * [__construct 初始化连接]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T20:00:51+0800
	 */
    private  function __construct()
	{
	    try{
	        $this->pdo = new \PDO('mysql:dbname='.M_DB_NAME.';host='.M_DB_HOST,M_DB_USER,M_DB_PWD);
	        $this->pdo->exec('SET NAMES utf8');//设置通信编码
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	    }catch(PDOException $e){
	        die('error:'.$e->getMessage());
	    }   
    }
	/**
	 * [__destruct 析构函数]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T20:19:02+0800
	 */
    public function __destruct(){
		if(!empty($this->errorInfo[0]) && $this->errorInfo[0] != '00000'){
	        throw new \Exception(implode('  ', $this->errorInfo));
		}
    }

    /**
     * [getInstance 小单例]
     * @author 		Shaowei Pu <pushaowei@sporte.cn>
     * @CreateTime	2016-12-26T20:00:36+0800
     * @return                              [type] [description]
     */
    public static function getInstance()
	{
      if (!(self::$_instance instanceof self))            
      {   
          self::$_instance = new self;
      }
      return self::$_instance;
	}

	/**
	 * [getRow 获取一条数据]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:43:28+0800
	 * @param                               [type] $sql      [description]
	 * @param                               array  $parms    [description]
	 * @param                               array  $dataType [description]
	 * @return                              [type]           [description]
	 */
    public function getRow($sql, $parms = [], $dataType = []){
		$stmt = $this->prepareBindParms($sql, $parms, $dataType);
		$stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    /**
     * [getAll 获取所有数据]
     * @author 		Shaowei Pu <pushaowei@sporte.cn>
     * @CreateTime	2016-12-26T19:43:42+0800
     * @param                               [type] $sql      [description]
     * @param                               array  $parms    [description]
     * @param                               array  $dataType [description]
     * @return                              [type]           [description]
     */
    public function getAll($sql, $parms = [], $dataType = []){
		$stmt = $this->prepareBindParms($sql, $parms, $dataType);
		$stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
	

	/**
	 * [exec 执行一段带预处理的SQL]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:44:04+0800
	 * @param                               [type] $sql      [description]
	 * @param                               array  $parms    [description]
	 * @param                               array  $dataType [description]
	 * @return                              [type]           [description]
	 */
    public function exec($sql, $parms = [], $dataType = []){
		$stmt = $this->prepareBindParms($sql, $parms, $dataType);
		$status = $stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $status;
    }
	


	/**
	 * [getLastInsertId 获取最后一次写入的ID]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:44:11+0800
	 * @return                              [type] [description]
	 */
	public function getLastInsertId(){
		return $this->pdo->lastInsertId();
	}

	/**
	 * [prepareBindParms 预处理参数]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:45:11+0800
	 * @param                               [type] $sql      [description]
	 * @param                               array  $parms    [description]
	 * @param                               array  $dataType [description]
	 * @return                              [type]           [description]
	 */
    private function prepareBindParms($sql, $parms = [], $dataType = []){
        $stmt = $this->pdo->prepare($sql);
		if($stmt === FALSE){
	        throw new \Exception('SQL PrePare ERROR');
		}
		if(empty($parms)){
			return $stmt;
		}
		array_walk($parms, function($v, $k) use($stmt, $dataType){
			$callParms = [];
			$callParms[] = $k;
			$callParms[] = $v;
			if(isset($dataType[$k])){
				$callParms[] = $dataType[$k];
			}
			call_user_func_array([$stmt, 'bindValue'], $callParms);
		});
		return $stmt;
    }
	
	/**
	 * [create 带有预处理的创建，需要使用前置table方法设置表名]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:45:31+0800
	 * @param                               array  $parms [description]
	 * @return                              [type]        [description]
	 */
    public function create($parms = []){
		if(empty($parms)){
			return false;
		}
		$keys = array_keys($parms);
		$values = array_values($parms);
		$sql = 'INSERT INTO `'.$this->table.'` ('.implode(',', $keys).') VALUES ('.implode(',', array_fill(0, count($keys), '?')).')';
        $stmt = $this->pdo->prepare($sql);
		$stmt->execute($values);
		$this->errorInfo = $stmt->errorInfo();
		return $this->getLastInsertId();
    }
	/**
	 * [insertMore 用于插入多条数据]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T20:32:27+0800
	 * @param                               array  $param [description]
	 * @return                              [type]        [description]
	 */
	public function insertMore(array $param)
	{
		$keys    = [];
		$values  = [];
		$fill    = '';
		$data    = [];
		foreach ($param as $param_key => $param_val) {
			foreach($param_val as $child_key => $child_val){
					array_push($values,$child_val);
			}
			if( is_array($param_val)){
				$fill.= '('.implode(',', array_fill(0, count($param_val), '?')).'),';
			}
		}

		$sql  = 'INSERT INTO `'.$this->table.'` ('.implode(',', array_keys($param[0])).') VALUES '.trim($fill,',');
        $stmt = $this->pdo->prepare($sql);

		$stmt->execute($values);
		$this->errorInfo = $stmt->errorInfo();
		return $this->getLastInsertId();
	}

	/**
	 * [table 设置表名称，为下一步执行进行操作]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:45:46+0800
	 * @param                               [type] $table [description]
	 * @return                              [type]        [description]
	 */
	public function table($table)
	{
		$this->table = $table;
		return $this;
	}
	


	/**
	 * [where 设置一个where条件]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:45:54+0800
	 * @param                               [type] $whereSql [description]
	 * @param                               array  $parms    [description]
	 * @param                               array  $dataType [description]
	 * @return                              [type]           [description]
	 */
	public function where($whereSql, $parms = [], $dataType = [])
	{
		$this->whereSql = $whereSql;
		$this->whereParms = $parms;
		$this->whereDataType = $dataType;
		return $this;
	}
	

	/**
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:46:09+0800
	 * @param                               [type] $value [description]
	 * @return                              [type]        [description]
	 */
    public function update($value){
		$keys = array_keys($value);
		$values = array_values($value);
		$sets = [];
		$newParms = [];
		foreach ($keys as $v) {
			$k = ":__{$v}";
			$sets[] = "`{$v}`={$k}";
			$newParms[$k] = $value[$v];
		}
		$whereSql = '';
		if(!empty($this->whereSql)){
			$whereSql = ' WHERE '. $this->whereSql;
		}
		
		$sql = 'UPDATE '. $this->table .'  SET ' . implode(',', $sets) . $whereSql . $this->order . $this->limit;
		$newParms = array_merge($newParms, $this->whereParms);
		$stmt = $this->prepareBindParms($sql, $newParms, $this->whereDataType);
		$execStatus = $stmt->execute();
		$this->defaultWhere();
		$this->errorInfo = $stmt->errorInfo();
		return $execStatus;
    }

	/**
	 * [delete 删除操作，需要前置条件TABLE]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:46:25+0800
	 * @return                              [type] [description]
	 */
    public function delete(){
		$whereSql = '';
		if(!empty($this->whereSql)){
			$whereSql = ' WHERE '. $this->whereSql;
		}
		$sql = 'DELETE FROM '.$this->table. ' ' . $whereSql . $this->order . $this->limit;
		$stmt = $this->prepareBindParms($sql, $this->whereParms, $this->whereDataType);
		$execStatus = $stmt->execute();
		$this->defaultWhere();
		$this->errorInfo = $stmt->errorInfo();
		return $execStatus;
    }
	
	/**
	 * [all 依据前置条件获取所有数据]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:46:46+0800
	 * @return                              [type] [description]
	 */
	public function all()
	{
		$whereSql = '';
		if(!empty($this->whereSql)){
			$whereSql = ' WHERE '. $this->whereSql;
		}
		
		$sql = 'SELECT '.$this->field.' FROM '.$this->table. ' ' . $whereSql . $this->order . $this->limit;
		$stmt = $this->prepareBindParms($sql, $this->whereParms, $this->whereDataType);
		$stmt->execute();
		$this->defaultWhere();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
	
	/**
	 * [find 依据前置条件获取一条数据]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:46:56+0800
	 * @return                              [type] [description]
	 */
	public function find()
	{
		$whereSql = '';
		if(!empty($this->whereSql)){
			$whereSql = ' WHERE '. $this->whereSql;
		}
		
		$sql = 'SELECT '.$this->field.' FROM '.$this->table. ' ' . $whereSql . $this->order . $this->limit;
		$stmt = $this->prepareBindParms($sql, $this->whereParms, $this->whereDataType);
		$stmt->execute();
		$this->defaultWhere();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	/**
	 * [field 设置要查询的字段名称]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:47:05+0800
	 * @param                               string $field [description]
	 * @return                              [type]        [description]
	 */
	public function field($field = '*')
	{
		$this->field = $field;
		return $this;
	}

	/**
	 * [limit 设置查询语句的数量条件]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:47:12+0800
	 * @param                               [type]  $offset [description]
	 * @param                               integer $limit  [description]
	 * @return                              [type]          [description]
	 */
	public function limit($offset, $limit = 0)
	{
		if (empty($limit)) {
			$this->limit = " LIMIT {$offset} ";
		} else {
			$this->limit = " LIMIT {$offset},{$limit} ";
		}
		return $this;
	}
	
	/**
	 * [order 设置排序条件]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T19:47:21+0800
	 * @param                               string $order [description]
	 * @return                              [type]        [description]
	 */
	public function order($order = '')
	{
		if (!empty($order)) {
			$this->order = ' ORDER BY '.$order.' ';
		}
		return $this;
	}
	
	/**
	 * [defaultWhere 默认的where条件]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T20:19:36+0800
	 * @return                              [type] [description]
	 */
	private function defaultWhere()
	{
		$this->whereSql = NULL;
		$this->whereParms = [];
		$this->whereDataType = [];
		$this->table = NULL;
		$this->limit = '';
		$this->order = '';
		$this->field = '*';
		return $this;
	}
	/**
	 * [getLastErrorInfo 获取错误提示]
	 * @author 		Shaowei Pu <pushaowei@sporte.cn>
	 * @CreateTime	2016-12-26T20:19:49+0800
	 * @return                              [type] [description]
	 */
	public function getLastErrorInfo()
	{
		return $this->errorInfo;
	}
}
