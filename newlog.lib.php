<?php

/*
外部定义常量，一般在频道下面的onload.config.php里面定义
NEW_LOG_HOST 日记服务器ip
NEW_LOG_PORT 日记服务器端口
NEW_LOG_CHANNEL	当前频道，比如mapi,www,up等
NEW_LOG_EXECUTE_TIME 设置超过多长时间才会写执行时间日记，比如写0.2，那么只有执行时间大于这个数值的日记会被记录
NEW_LOG_REQUEST 是否开启单个请求的日记
NEW_LOG_REQUEST_PARAM 是否开启单个请求日记的时候记录get跟post信息 单个请求开启的时候才有用

调用例子：
独立调用写日记 
	new_log::log(msg,unique_key='');//msg为日记信息，unique_key为此次记录的唯一标识，可不填
记录执行过程时间
	$new_log = new_log(log_type='');//log_type为调用类型，比如mysql，memcache，也可以自定义
	$new_log->start(unique_key='');//unique_key为此次记录的唯一标识
	//这里为执行过程
	$new_log->end(unique_key='',msg='');//这里unique_key于之前start的一致，msg为附带自定义的日记内容，可不填
*/



class new_log{
	public $log_host = '58.215.161.204';
	public $log_port = '5152';
	const FALSE_LOG = '/tmp/new_log_5152.log';

	public static $socket;
	public $channel = 'default';
	public $log_type = 'default';

	public $unique_key = '';
	public $unique_key_arr = array();
	public $log_use_time = 0;
	public $log_request = false;
	public $log_request_param = false;
	public $log_connect = false;
	public $max_length = 2000;

	public static $request;
	public static $request_start;

	public function __construct($log_type='default')
	{
		if(defined('NEW_LOG_HOST'))
		{
			$this->log_host = NEW_LOG_HOST;
		}
		if(defined('NEW_LOG_PORT'))
		{
			$this->log_port = NEW_LOG_PORT;
		}
		if(!self::$socket)
		{
			$socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			if(!$socket)
			{
				//error_log(time().':new_log:create socket false:'.socket_strerror(socket_last_error())."\n",3,self::FALSE_LOG);
				self::$socket = null;
				return false;
			}
			self::$socket = $socket;
		}
		if(defined('NEW_LOG_CHANNEL'))
		{
			$this->channel = NEW_LOG_CHANNEL;
		}
		$this->log_type = $log_type;
		if(defined('NEW_LOG_EXECUTE_TIME'))
		{
			$this->log_use_time = NEW_LOG_EXECUTE_TIME;
		}
	}


	public function start($unique_key='')
	{
		if(empty($unique_key))
		{
			$unique_key = uniqid();
		}
		$this->unique_key = $unique_key;
		$unique_key_key = md5($unique_key);
		$this->unique_key_arr[$unique_key_key] = array(
			'unique_key'=>$unique_key,
			'start_time'=>$this->microtime_float()
		);
	}

	public function end($unique_key='',$msg='')
	{
		$log_unique_key = true;
		if(empty($unique_key))
		{
			$unique_key = $this->unique_key;
			$log_unique_key = false;
		}
		$end_microtime = $this->microtime_float();
		$unique_key_key = md5($unique_key);
		$use_time = $end_microtime - $this->unique_key_arr[$unique_key_key]['start_time'];
		if(is_array($msg))
		{
			$msg = var_export($msg,true);
		}
		$msg_tail = $log_unique_key ? ' ['.$unique_key.']'.'['.$use_time.']' : ' ['.$use_time.']';
		if($use_time > $this->log_use_time || $this->log_use_time==0)
		{
			$this->_log($msg,$msg_tail);
		}
		unset($this->unique_key_arr[$unique_key]);
	}

	public function _log($msg,$msg_tail='')
	{
		$socket = self::$socket;
		if(is_array($msg))
		{
			$msg = var_export($msg,true);
		}
		if(strlen($msg)>$this->max_length)
		{
			$msg = substr($msg,0,$this->max_length).'......'.$msg_tail;
		}else
		{
			$msg = $msg.$msg_tail;
		}
		$msg = "[".$this->channel."][".$this->log_type."][".intval($_SESSION['uid'])."] ".$msg." @".session_id();
		$result = @socket_sendto($socket, $msg,strlen($msg),0,$this->log_host,$this->log_port);
		if(!$result)
		{
			//error_log(time().':new_log:write server false:'.socket_strerror(socket_last_error())."\n",3,self::FALSE_LOG);
		}
	}

	/**
	*供外部单独调用
	*/
	public static function log($msg,$unique_key='')
	{
		if(defined('NEW_LOG_HOST'))
		{
			$log_host = NEW_LOG_HOST;
		}else
		{
			$log_host = '10.1.1.84';
		}
		if(defined('NEW_LOG_PORT'))
		{
			$log_port = NEW_LOG_PORT;
		}
		if(self::$socket)
		{
			$socket = self::$socket;
		}else
		{
			$socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			if(!$socket)
			{
				//error_log(time().':new_log:socket create false:'.socket_strerror(socket_last_error())."\n",3,self::FALSE_LOG);
				return false;
			}
		}
		
		if(is_array($msg))
		{
			$msg = var_export($msg,true);
		}
		if(strlen($msg)>2000)
		{
			$msg = substr($msg,0,2000).'......';
		}
		$msg = "[".NEW_LOG_CHANNEL."][manual][".intval($_SESSION['uid'])."] ".$msg." [".$unique_key."] @".session_id();
		$result = @socket_sendto($socket, $msg,strlen($msg),0,$log_host,$log_port);
		if(!$result)
		{
			//error_log(time().':new_log:write server false:'.socket_strerror(socket_last_error())."\n",3,self::FALSE_LOG);
		}
	}

	public static function request_start($request)
	{
		if(defined('NEW_LOG_REQUEST'))
		{
			if(!NEW_LOG_REQUEST)
			{
				return false;
			}
		}
		if(empty($request))
		{
			return false;
		}
		$ob = new self();
		self::$request = $request;
		self::$request_start = $ob->microtime_float();
	}

	public static function request_end()
	{
		if(defined('NEW_LOG_REQUEST'))
		{
			if(!NEW_LOG_REQUEST)
			{
				return false;
			}
		}
		$ob = new self();
		if(empty(self::$request))
		{
			return false;
		}
		$request_end = $ob->microtime_float();
		$use_time = $request_end-self::$request_start;
		$ob->channel = defined('NEW_LOG_CHANNEL') ? NEW_LOG_CHANNEL : $ob->channel;
		$ob->log_type = 'request';
		$msg = '';
		if(defined('NEW_LOG_REQUEST_PARAM'))
		{
			if(NEW_LOG_REQUEST_PARAM)
			{
				$get_str = json_encode($_GET);
				$post_str = json_encode($_POST);
				$msg = "(".$get_str.")(".$post_str.") ";
			}
		}
		$ob->_log($msg.'['.self::$request.']'.'['.$use_time.']');
		self::$request = false;
	}

	public function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
   		return ((float)$usec + (float)$sec);
	}

	public function close()
	{
		if(self::$socket)
		{
			socket_close(self::$socket);
			self::$socket = null;
		}
				
	}

}
