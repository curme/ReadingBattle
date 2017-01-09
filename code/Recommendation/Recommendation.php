<?php


include 'Util.php';
include 'Data.php';
include 'Rule.php';

function updateBookToBookRules(){

	ini_set('memory_limit', '256M');
	ini_set('max_execution_time', 100);

	// create data manager
	$data_manager = new DataManager();

	// get reading records and preprocess
	$read_book_records = $data_manager->getReadBookRecords();
	$read_book_baskets = basketize($read_book_records);

	// create rule manager
	$lift_threshold = 1.5;
	$confidence_threshold = 0.2;
	$rule_manager = new RuleManager($lift_threshold, $confidence_threshold);
	$rule_manager->setBaskets($read_book_baskets);

	// create rules
	$rule_manager->FPGrowth();
	$rules = $rule_manager->createRules();

	// store rules into database
	$data_manager->storeBookToBookRules($rules);

	// destroy tmp vars
	unset($data_manager);
	unset($rule_manager);

	ini_set('memory_limit', '128M');
	ini_set('max_execution_time', 30);

}

function updateUserRecomBooks($userid){

	ini_set('max_execution_time', 100);

	// get user reading history
	$data_manager = new DataManager();
	$user_read_book_records = $data_manager->getReadBookRecords($userid);
	if ($user_read_book_records == null) return;
	$user_read_book_basket = array_unique(basketize($user_read_book_records)[$userid]);

	// get recommendation rules
	$rules = $data_manager->getBookToBookRules();

	// generate recommendation books for user base on historical reading records
	$rule_manager = new RuleManager();
	$user_recom_books = $rule_manager->getRecomBooks($user_read_book_basket, $rules);

	// store the recom books
	$data_manager->storeUserRecomBooks($userid, $user_recom_books);

	ini_set('max_execution_time', 30);

}

function getUserRecomBooks($userid){

	// get user recommendation books
	$data_manager = new DataManager();
	$user_recom_books = $data_manager->getUserRecomBooks($userid);
	
	return $user_recom_books;
}

?>