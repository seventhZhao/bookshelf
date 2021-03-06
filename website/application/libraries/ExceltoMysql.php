<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 *从Excel批量导入数据到MySql
 *注意Excel必须先转换为CSV格式，首行为字段名且与数据库中的顺序一致
 * @access public
 * @param $table,$file
 * @author chile
 * @return 成功导入的行数$n
 */
class ExceltoMysql
{
	protected $table;
	protected $file;
	protected $field;
	protected $fieldNum;
	public function __construct()
	{
		switch(func_num_args())
		{
			case 2:$this->ExceltoMysql2(func_get_arg(0),func_get_arg(1));break;
			default:$this->ExceltoMysql();break;
		}
	}
	protected function ExceltoMysql() 
	{
		
	}
	protected function ExceltoMysql2($table,$file)
	{
		$this->table = $table;
		$this->file = $file;
		$this->get_field();
	}
	public function set($table,$file)
	{
		$this->table = $table;
		$this->file = $file;
		$this->get_field();
	}
	protected function get_field()
	{
		if($this->table == '') exit("Please set tablename!");
		$query = mysql_query("SHOW COLUMNS FROM $this->table");
		$this->fieldNum = mysql_num_rows($query);
		while($fieldArray = mysql_fetch_assoc($query))
		{
			$this->field[] = $fieldArray['Field'];
		}
	}
	protected function get_bookid($bookname)
	{
		$sql = "SELECT `id` from `allbook` where `name` = trim(both from '$bookname')";
		$query = mysql_query($sql);
		return mysql_fetch_assoc($query);
	}
	public function InsertToMysql()
	{
		mysql_query("SET NAMES gbk");
	    $handle = fopen($this->file,'r') or exit("Unable to open $this->file file!");
	    //验证字段数目
	    $firstRow = fgetcsv($handle);
	    if(count($firstRow)!=$this->fieldNum)
	    {
	    	exit("Unmatch fields' number!");
		}
		if(count($diff = array_diff($firstRow,$this->field))) 
		{
			$error = "";
	    	foreach ($diff as $key => $value) 
	    	{
	    		$error .= " '$value' ";
	    	}
	    	exit('Please check field:'.$error."in the file $this->file!");
		}
		//END
		$sql = "INSERT INTO $this->table VALUES (";	
		$n = 2;
	    while($charArray = fgetcsv($handle))//这里是第二行读取了    
	    {
	    	if($this->table=='allbook_mg')	//如果是'allbook_mg'表则进行字段转换
    		{
    			$result = $this->get_bookid($charArray[3]);
    			if($result)
    			{
    				$charArray[3] = $result['id'];
    			}
    			else
    			{
    				exit("已成功导入".(int)($n-2)."行错误发生在".$n."行<br/>错误信息：《".$charArray[3]."》课本不存在！");
    			} 				
    		}
	    	$sql2 = "";
	    	foreach ($charArray as $key => $value) 
	    	{	
	    		if($key!=$this->fieldNum-1)
	    		{
	    			$sql2 .= "trim(both from '".$value."'),";
	    		}
	    		else
	    		{
	    			$sql2 .= "trim(both from '".$value."'));";
	    		}
	    	}
	    	$sql3 = $sql.$sql2;
	    	mysql_query($sql3) or exit("已成功导入".(int)($n-2)."行错误发生在".$n."行<br/>错误信息：".mysql_error());
	    	$n++;
	    }
	    return $n-2;   	
	}
}

/* End of file ExceltoMysql.php */