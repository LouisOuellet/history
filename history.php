<?php

class History extends Database{

	public $Status = TRUE;
	public $Level = 4;

  public function __construct($host,$username,$password,$database){
    parent::__construct($host,$username,$password,$database);
  }

	public function disable($status = TRUE){
		$this->Status = $status;
	}

	public function level($level = 4){
		$this->Level = $level;
	}

	private function get_client_ip() {
	  $ipaddress = '';
	  if(getenv('HTTP_CLIENT_IP')){
	    $ipaddress = getenv('HTTP_CLIENT_IP');
	  } elseif(getenv('HTTP_X_FORWARDED_FOR')){
	    $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	  } elseif(getenv('HTTP_X_FORWARDED')){
	    $ipaddress = getenv('HTTP_X_FORWARDED');
	  } elseif(getenv('HTTP_FORWARDED_FOR')){
	    $ipaddress = getenv('HTTP_FORWARDED_FOR');
	  } elseif(getenv('HTTP_FORWARDED')){
	    $ipaddress = getenv('HTTP_FORWARDED');
	  } elseif(getenv('REMOTE_ADDR')){
	    $ipaddress = getenv('REMOTE_ADDR');
	  } else {
	    $ipaddress = 'UNKNOWN';
		}
	  return $ipaddress;
	}

	private function saveTransaction($table, $action, $before, $after){
		$run = FALSE;
		switch($action){
			case"read":
				if($this->Level >= 1){ $run = TRUE; }
				break;
			case"create":
				if($this->Level >= 2){ $run = TRUE; }
				break;
			case"update":
				if($this->Level >= 3){ $run = TRUE; }
				break;
			case"delete":
				if($this->Level >= 4){ $run = TRUE; }
				break;
		}
		if($run){
			$query = [
				'before' => json_encode($before),
				'after' => json_encode($after),
				'action' => $action,
				'table' => $table,
				'ip' => $this->get_client_ip(),
			];
			parent::create('history',$query);
		}
	}

	public create($table,$fields){
		$results = parent::create($table,$field);
		if($this->Status){ $this->saveTransaction($table, 'create', [], $fields); }
		return $results;
	}

	public read($table, $id = null, $field = 'id'){
		$results = parent::read($table,$id,$field);
		if($this->Status){
			if($results->numRows() == 1){ $before = $results->fetchArray(); } else { $before = $results->fetchAll(); }
			$this->saveTransaction($table, 'read', $before, []);
		}
		return $results;
	}

	public update($table, $fields, $id, $field = 'id'){
		$results = parent::update($table,$field,$id,$field);
		if($this->Status){
			$before = parent::read($table,$id,$field)->fetchArray();
			$this->saveTransaction($table, 'save', $before, $fields);
		}
		return $results;
	}

	public delete($table,$id,$field = 'id'){
		if($this->Status){
			$before = parent::read($table,$id,$field)->fetchArray();
			$this->saveTransaction($table, 'delete', $before, []);
		}
		$results = parent::delete($table,$id,$field);
		return $results;
	}
}
