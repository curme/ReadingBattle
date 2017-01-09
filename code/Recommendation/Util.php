<?php

/* Note
	Personally, I would name the function names as xxXxxxXxxx(),
but to follow the custom of php name the functions about array process,
I named the following several array about functions as xxx_xxxx_xxxx().
*/
function print_array($data){ 
	echo '<pre>'.print_r($data,true).'</pre>'; 
}

function array_unique_merge($array1, $array2){
	foreach($array2 as &$array2item)
		if (!in_array($array2item, $array1))
			array_push($array1, $array2item);
	sort($array1);
	return $array1;
}

function array_combination($items){
	$combinations = Array(Array());
	foreach($items as &$item){
		$size = sizeof($combinations);
		for ($index = $size-1; $index >= 0; $index--){
			$set_tmp = $combinations[$index];
			array_push($set_tmp, $item);
			array_push($combinations, $set_tmp);
		}
	}
	sort($combinations);
	unset($item);
	return $combinations;
}

function basketize($records){
	$sets = Array();
	foreach($records as &$record) {
		$userid = $record['userid'];
		$bookid = $record['bookid'];
		if ($sets[$userid] == null) $sets[$userid] = Array($bookid);
		else array_push($sets[$userid], $bookid);
	}
	return $sets;
}

function getSetString($set){
	return join(';', $set);
}

?>