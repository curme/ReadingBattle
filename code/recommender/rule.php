<?php

// include
include 'fpgrowth.php';
include 'apriori.php';

class RuleManager {

	private $rules;
	private $baskets;
	private $frequent_sets;
	private $lift_threshold;
	private $confidence_threshold;

	public function __construct($lift_threshold = 1, $confidence_threshold = 0.1) {
		$this->setLiftThreshold($lift_threshold);
		$this->setConfidenceThreshold($confidence_threshold);
	}

	public function __destruct() {
		unset($this->fpGrowthManager);
	}

	public function setBaskets($baskets){ $this->baskets = $baskets; }
	public function setLiftThreshold($lift_threshold){ $this->lift_threshold = $lift_threshold; }
	public function setConfidenceThreshold($confidence_threshold){ $this->confidence_threshold = $confidence_threshold; }

	public function FPGrowth($baskets = null){
		if ($baskets == null) $baskets = $this->baskets;
		$fp_growth_manager = new FPGrowthManager($baskets);
		$frequent_sets = $fp_growth_manager->getFrequentSets();
		$this->frequent_sets = $frequent_sets;
		unset($fp_growth_manager);
		return $frequent_sets;
	}

	public function Apriori($baskets = null){
		if ($baskets == null) $baskets = $this->baskets;
		$apriori_manager = new AprioriManager($this->baskets);
		$frequent_sets = $apriori_manager->getFrequentSets();
		$this->frequent_sets = $frequent_sets;
		unset($apriori_manager);
		return $frequent_sets;
	}

	public function createRules($frequent_sets = null){
		if ($frequent_sets == null) $frequent_sets = $this->frequent_sets;
		$frequent_sets_param_map = $this->createFreParamMap($frequent_sets);
		$rules = Array();
		foreach($frequent_sets as &$frequent_set){
			$count = $frequent_set['count'];
			$frequency = $frequent_set['frequency'];
			unset($frequent_set['count']);
			unset($frequent_set['frequency']);
			if (sizeof($frequent_set)>1){
				foreach($frequent_set as $key => $element){
					asort($frequent_set);
					$set_string = getSetString($frequent_set);
					$conditions = $frequent_set;
					unset($conditions[$key]);
					$conclusion = Array($element);
					$cond_string = getSetString($conditions);
					$conc_string = getSetString($conclusion);
					$set_frequency = $frequent_sets_param_map[$set_string]['frequency'];
					$cond_frequency = $frequent_sets_param_map[$cond_string]['frequency'];
					$conc_frequency = $frequent_sets_param_map[$conc_string]['frequency'];
					$lift = $this->calLift($set_frequency, $cond_frequency, $conc_frequency);
					$confidence = $this->calConfidence($set_frequency, $conc_frequency);
					$rule = Array('cond'=>$conditions,'conc'=>$conclusion,'lift'=>$lift, 'confidence'=>$confidence);
					if ($lift >= $this->lift_threshold && $confidence >= $this->confidence_threshold)
						array_push($rules, $rule);
				}
			}
		}
		$this->rules = $rules;
		unset($frequent_set);
		unset($key);
		unset($element);
		return $rules;
	}

	private function createFreParamMap($frequent_sets = null){
		if ($frequent_sets == null) $frequent_sets = $this->frequent_sets;
		$frequent_sets_param_map = Array();
		foreach($frequent_sets as &$frequent_set){
			$count = $frequent_set['count'];
			$frequency = $frequent_set['frequency'];
			unset($frequent_set['count']);
			unset($frequent_set['frequency']);
			sort($frequent_set);
			$set_string = getSetString($frequent_set);
			$frequent_sets_param_map[$set_string] = Array();
			$frequent_sets_param_map[$set_string]['count'] = $count;
			$frequent_sets_param_map[$set_string]['frequency'] = $frequency;
		}
		unset($frequent_set);
		return $frequent_sets_param_map;
	}

	public function getRecomBooks($user_read_book_basket, $rules = null){
		// generate recommendation books
		if ($rules == null) $rules = $this->rules;
		$frequent_items = Array();
		foreach($rules as &$rule)
			if (sizeof($rule['cond'])==1 && !in_array($rule['cond'][0], $frequent_items)) 
				array_push($frequent_items, $rule['cond'][0]);
		$user_read_book_basket = array_intersect($frequent_items, $user_read_book_basket);
		$recom_books = Array();
		foreach($rules as &$rule){
			$intersect = array_intersect($rule['cond'], $user_read_book_basket);
			if (sizeof($intersect) == sizeof($rule['cond'])) {
				$bookid = $rule['conc'][0];
				$recom_book = Array();
				$recom_book['bookid'] = $bookid;
				$recom_book['lift'] = $rule['lift'];
				$recom_book['confidence'] = $rule['confidence'];
				$recom_book['cond'] = $rule['cond'];
				if ($recom_books[$bookid] == null || $recom_book['lift'] > $recom_books[$bookid]['lift'])
					$recom_books[$bookid] = $recom_book;
			}
		}
		// check if the recommendation books have already been read
		foreach($user_read_book_basket as &$bookid)
			if (!($recom_books[$bookid] == null)) unset($recom_books[$bookid]);
		// filter top 10 recommendation books
		$book_lifts = Array();
		foreach($recom_books as &$recom_book) $book_lifts[$recom_book['bookid']] = $recom_book['lift'];
		arsort($book_lifts);
		$book_top_lifts = array_chunk($book_lifts, 10, True)[0];
		foreach($recom_books as &$recom_book)
			if ($book_top_lifts[$recom_book['bookid']]==null)
				unset($recom_books[$recom_book['bookid']]);
		sort($recom_books);
		unset($rule);
		unset($bookid);
		unset($recom_book);
		return $recom_books;
	}

	private function calLift($set_frequency, $cond_frequency, $conc_frequency){
		return $set_frequency / ($cond_frequency * $conc_frequency);
	}

	private function calConfidence($set_frequency, $conc_frequency){
		return $set_frequency / $conc_frequency;
	}

}

?>