<?php

// Import Librairies
require_once dirname(__FILE__,3) . '/src/lib/database.php';

class History extends Database{

	public $Status = TRUE; // Is history enabled
	public $Level = 4; // Level of logging. 4=READ,3=CREATE,2=UPDATE,1=DELETE

	public function disable($status = FALSE){
		$this->Status = $status;
	}

	public function level($level = 4){
		$this->Level = $level;
	}

	public function getClientIP() {
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

	protected function saveTransaction($table, $action, $before, $after, $id, $status){
		$run = FALSE;
		switch($action){
			case"read":
				if($this->Level >= 4){ $run = TRUE; }
				break;
			case"create":
				if($this->Level >= 3){ $run = TRUE; }
				break;
			case"update":
				if($this->Level >= 2){ $run = TRUE; }
				break;
			case"delete":
				if($this->Level >= 1){ $run = TRUE; }
				break;
		}
		if($run){
			if((is_int($status))&&($status > 0)){ $status = 'Success'; } else { $status = 'Error'; }
			$query = [
				'before' => json_encode($before, JSON_PRETTY_PRINT),
				'after' => json_encode($after, JSON_PRETTY_PRINT),
				'action' => $action,
				'table' => $table,
				'of' => $id,
				'status' => $status,
				'ip' => $this->getClientIP(),
			];
			$results = $this->query('INSERT INTO `history` (created,modified) VALUES (?,?)', date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
			$id = $this->lastInsertID();
			$headers = $this->getHeaders('history');
	    foreach($query as $key => $val){
	      if((in_array($key,$headers))&&($key != 'id')){
	        $this->query('UPDATE `history` SET `'.$key.'` = ? WHERE `id` = ?',$val,$id);
	      }
	    }
		}
	}

	public function create($table,$fields){
		$history = $this->Status;
		$this->Status = FALSE;
		$results = parent::create($table,$fields);
		$this->Status = $history;
		if($this->Status){ $this->saveTransaction($table, 'create', [], $fields, $results, $results); }
		return $results;
	}

	public function read($table, $id = null, $field = 'id'){
		$results = parent::read($table,$id,$field);
		if($this->Status){
			if($results->numRows() == 1){ $before = $results->fetchArray(); } else { $before = $results->fetchAll(); }
			$this->saveTransaction($table, 'read', $before, [], $id, $results->numRows());
		}
		return $results;
	}

	public function update($table, $fields, $id, $field = 'id'){
		$results = parent::update($table,$fields,$id,$field);
		if($this->Status){
			$before = $this->read($table,$id,$field)->fetchArray();
			$this->saveTransaction($table, 'save', $before, $fields, $id, $results->affectedRows());
		}
		return $results;
	}

	public function delete($table,$id,$field = 'id'){
		if($this->Status){
			$before = $this->read($table,$id,$field)->fetchArray();
			$this->saveTransaction($table, 'delete', $before, [], $id, $results->affectedRows());
		}
		$results = parent::delete($table,$id,$field);
		return $results;
	}
}
