<?php

require "helpers.php";

$x = '';

if (!empty($_GET['q'])) {
	$x = "WHERE name like '%{$_GET['q']}%'";
}

$jobs = fetch("SELECT * FROM jobs LIMIT 2 " . $x);


$total = count(fetch("SELECT * FROM jobs"));


echo json_encode(['total' => $total, 'jobs'=>$jobs]);