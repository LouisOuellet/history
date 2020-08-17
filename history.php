<?php

class History extends Database{

  public function __construct($host,$username,$password,$database){
    parent::__construct($host,$username,$password,$database);
  }

	private function get_client_ip() {
	  $ipaddress = '';
	  if (getenv('HTTP_CLIENT_IP'))
	    $ipaddress = getenv('HTTP_CLIENT_IP');
	  else if(getenv('HTTP_X_FORWARDED_FOR'))
	    $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	  else if(getenv('HTTP_X_FORWARDED'))
	    $ipaddress = getenv('HTTP_X_FORWARDED');
	  else if(getenv('HTTP_FORWARDED_FOR'))
	    $ipaddress = getenv('HTTP_FORWARDED_FOR');
	  else if(getenv('HTTP_FORWARDED'))
	    $ipaddress = getenv('HTTP_FORWARDED');
	  else if(getenv('REMOTE_ADDR'))
	    $ipaddress = getenv('REMOTE_ADDR');
	  else
	    $ipaddress = 'UNKNOWN';
	  return $ipaddress;
	}

	private function saveTransaction($table, $action, $before, $after){
		$query = [
			'before' => json_encode($before),
			'after' => json_encode($after),
			'action' => $action,
			'table' => $table,
			'ip' => $this->get_client_ip(),
		];
		parent::create('history',$query);
	}

	public create($table,$fields){
		$results = parent::create($table,$field);
		$this->saveTransaction($table, 'create', [], $fields);
		return $results;
	}

	public read($table, $id = null, $field = 'id'){
		$results = parent::read($table,$id,$field);
		if($results->numRows() == 1){ $before = $results->fetchArray(); } else { $before = $results->fetchAll(); }
		$this->saveTransaction($table, 'read', $before, []);
		return $results;
	}

	public update($table, $fields, $id, $field = 'id'){
		$before = parent::read($table,$id,$field)->fetchArray();
		$results = parent::update($table,$field,$id,$field);
		$this->saveTransaction($table, 'save', $before, $fields);
		return $results;
	}

	public delete($table,$id,$field = 'id'){
		$before = parent::read($table,$id,$field)->fetchArray();
		$results = parent::delete($table,$id,$field);
		$this->saveTransaction($table, 'delete', $before, []);
		return $results;
	}
}
