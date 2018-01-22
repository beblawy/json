<?php
session_start();

function query($sql){
	$con = mysqli_connect('localhost','root','','besho');

	if (!$con) {
		echo mysqli_connect_error();
		die;
	}

	$result = mysqli_query($con, $sql);

	if (!empty(mysqli_error($con))) {
		echo mysqli_error($con);
		die;
	}
	return $result;
}


function fetch($sql){
	$result = query($sql);

	$count = mysqli_num_rows($result);
	$data = [];
	for ($i=0; $i < $count; $i++) { 
		$data[] = mysqli_fetch_assoc($result);
	}
	return $data;
}

function one($sql){
	$res = fetch($sql);
	if (!empty($res)) {
		return $res[0];
	}else{
		return false;
	}
}

function find($table, $id){
	return one("SELECT * FROM {$table} WHERE id={$id} LIMIT 1");

}

function all($table){
	return fetch("SELECT * FROM {$table}");
}


function create($table, $data){
	$sql  = "INSERT INTO {$table} (";
	$sql .= implode(',', array_keys($data) );
	$sql .= ") VALUES('";
	$sql .= implode("','", array_values($data));
	$sql .= "')";

	query($sql);
}

function validator($data, $rules){
	$errors = [];

	foreach ($rules as $k => $v) {

		$v = explode('|', $v);

		if (in_array('required', $v) && !isset($data[$k]) ){
			$errors[] = $k.' field is required.';
		}

		if (in_array('required', $v) && empty($data[$k]) ){
			$errors[] = $k.' field has not a value.';
		}

		if ( !empty($data[$k]) ) {
			foreach ($v as $x => $y) {

				if ($y == 'string' && !is_string($data[$k]) ){
					$errors[] = $k.' field must be a .';
				}
				
				if ($y == 'number' && !is_numeric($data[$k]) ){
					$errors[] = $k.' field must be a number.';
				}

				if ($y == 'array' && !is_array($data[$k]) ){
					$errors[] = $k.' field must be an array.';
				}

				if ($y == 'email' && !filter_var($data[$k], FILTER_VALIDATE_EMAIL) ){
					$errors[] = $k.' field must be a valid email.';
				}

				if ($y == 'confirmed' && !isset($data[ "{$k}_confirmarion" ]) ){
					$errors[] = $k.' field must be confirmed.';
				}

				if ($y == 'confirmed' && !empty($data[ "{$k}_confirmarion" ]) && $data[$k] != $data["{$k}_confirmarion"]){
					$errors[]=$k." field confirmation doesn't match.";
				}

				if (substr($y,0,3) == 'max'){

					$length = substr($y,4);

					if( strlen($data[$k]) > $length){
						$errors[] = $k.' field must not bigger than '.$length;
					}
				}

				if (substr($y,0,3) == 'min'){

					$length = substr($y,4);

					if( strlen($data[$k]) < $length){
						$errors[] = $k.' field must not less than '.$length;
					}
				}

				/*
				if (substr($y,0,2) == 'in'){

					$arr = explode(',', substr($y,3));
					if (!in_array($data[$k], $arr)) {
						# code...
					}
				}
				*/

				if (substr($y ,0 ,6) == 'unique'){
					$t = explode( ',', substr($y, 7) );
	
					if(empty($t[0]) || empty($t[1])){
						throw new Exception("Your Unique parameters is not valid");
					}
					$exist = fetch("SELECT * FROM {$t[0]} WHERE $t[1]='{$data[$k]}' LIMIT 1");

					if ($exist) {
						$errors[] = $data[$k].' has been taken.';
					}					
				}

				if (substr($y, 0, 6) == 'exists'){
					$t = explode( ',', substr($y, 7) );
	
					if(empty($t[0]) || empty($t[1])){
						throw new Exception("Your Exists parameters is not valid");
					}
					$exist = fetch("SELECT * FROM {$t[0]} WHERE $t[1]='{$data[$k]}' LIMIT 1");

					if (!$exist) {
						$errors[] = $data[$k]." doesn't match our records.";
					}					
				}
			}
		}
	}
	return $errors;
}

function redirect($location){
	header("Location: {$location}");
    exit();
}

$auth = false;

if (isset($_SESSION['user_id'])) {
	$auth = find("users", $_SESSION['user_id']);
}


function middleware($params){
	global $auth;

	if (in_array('auth', $params)) {
		if (!$auth) {
			redirect('login.php');
		}
	}

	if (in_array('guest', $params)) {
		if ($auth) {
			redirect('search.php');
		}
	}

	if (in_array('admin', $params)) {
		if ($auth['role'] != 'admin') {
			redirect('search.php');
		}
	}


}
