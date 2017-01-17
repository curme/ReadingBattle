<?php

class FPGrowthManager {

	private $sets;
	private $min_support;
	private $frequent_sets;

	public function __construct($sets, $min_support=50){
		$this->sets = $this->preproSets($sets);
		$this->min_support = $min_support;
		$this->frequent_sets = $this->generateFrequentSets($this->sets);
		unset($this->frequent_sets[0]);
		$this->calFrequency();


		// Abandoned Support count method
		// $frequent_sets_count = $this->setsSupportCount($this->frequent_sets);
		// foreach($frequent_sets_count as $index => $frequent_set_count)
		// 	$this->frequent_sets[$index]['count'] = $frequent_set_count;
	}

	public function __destruct(){
		unset($this->sets);
		unset($this->frequent_sets);
	}

	private function preproSets($raw_sets){
		foreach($raw_sets as &$raw_set) {
			$raw_set = array_unique($raw_set);
			$raw_set['count'] = 1;
		}
		unset($raw_set);
		return $raw_sets;
	}

	private function generateFrequentSets($sets){
		$frequent_sets = Array();

		// create root fp tree
		$fptree = new FPTree($sets, $this->min_support);
		$f1 = $fptree->getF1();
		$headers = $fptree->getHeaders();

		// check end
		if ($fptree->checkNull()) return Array(Array());
		elseif ($fptree->checkSingleBranch()) {
			$frequent_sets = array_combination(array_keys($f1));
			foreach($frequent_sets as &$frequent_set){
				for ($index = sizeof($fptree->getTree())-1; $index > 0; $index--) {
					$element = $fptree->getTree()[$index];
					if (in_array($element->getContent(), $frequent_set)) {
						$frequent_set['count'] = $element->getCount();
						asort($frequent_set);
						break;
					}
				}
			}
			unset($frequent_set);
			return $frequent_sets;
		}

		// generate frequent sets for sub tree
		else {
			for ($header = sizeof($f1)-1; $header >= 0; $header--){
				$element = array_keys($f1)[$header];
				$element_sets = $fptree->getElementSets($element);
				$sub_frequent_sets = $this->generateFrequentSets($element_sets);
				foreach($sub_frequent_sets as &$sub_frequent_set){
					array_push($sub_frequent_set, $element);
					asort($sub_frequent_set);
				}
				$sub_frequent_sets[0]['count'] = $f1[$element];
				$frequent_sets = $this->frequentSetsUniqueMerge($frequent_sets, $sub_frequent_sets);
				if (!$frequent_sets[0] == Array()) $frequent_sets = array_merge(Array(Array()), $frequent_sets);
			}
			unset($sub_frequent_set);
			return $frequent_sets;
		}
	}

	private function frequentSetsUniqueMerge($array1, $array2){
		$array1_tmp = $array1;
		foreach($array1_tmp as &$array1item_tmp) 
			unset($array1item_tmp['count']);
		foreach($array2 as &$array2item){
			$array2item_tmp = $array2item;
			unset($array2item_tmp['count']);
			$check_in_array1 = False;
			for ($index = 0; $index < sizeof($array1); ++$index){
				if ($array1_tmp[$index] == $array2item_tmp) {
					$array1[$index]['count'] += $array2item['count'];
					$check_in_array1 = True;
					break;
				}
			}
			if (!$check_in_array1){
				array_push($array1, $array2item);
				array_push($array1_tmp, $array2item_tmp);
			}
		}
		sort($array1);
		unset($array1item_tmp);
		unset($array2item);
		return $array1;
	}

	// Abandoned method
	// private function setsSupportCount($sets){
	// 	$count_table = Array();
	// 	foreach($sets as $key => $cand_set)
	// 		$count_table[$key] = $this->setSupportCount($cand_set);
	// 	unset($key);
	// 	unset($cand_set);
	// 	return $count_table;
	// }

	// Abandoned method
	// private function setSupportCount($cand_set){
	// 	$set_count = 0;
	// 	unset($cand_set['count']);
	// 	foreach($this->sets as &$set) {
	// 		$check_in_set = True;
	// 		foreach($cand_set as &$element){
	// 			if (!in_array($element, $set)) { $check_in_set = False; break; }
	// 		}
	// 		$set_count += $check_in_set ? $set['count'] : 0;
	// 	}
	// 	unset($set);
	// 	return $set_count;
	// }

	private function calFrequency(){
		$sets_count = sizeof($this->sets);
		foreach($this->frequent_sets as &$frequent_set)
			$frequent_set['frequency'] = $frequent_set['count'] / $sets_count;
		unset($frequent_set);
	}

	public function getSets(){ return $this->sets; }
	public function getFrequentSets(){ return $this->frequent_sets; }

}

class FPTree {

	private $min_support;
	private $sets;
	private $F1;
	private $tree;
	private $headers;

	public function __construct($sets, $min_support){
		$this->min_support = $min_support;
		$this->sets = $sets;
		$this->preproSets();
		$this->buildTree();
		$this->createHeaderTable();
	}

	public function __destruct(){
		unset($this->sets);
		unset($this->F1);
		unset($this->tree);
		unset($this->headers);
	}

	public function getSets(){ return $this->sets; }
	public function getF1(){ return $this->F1; }
	public function getTree(){ return $this->tree; }
	public function getHeaders(){ return $this->headers; }
	public function getElementSets($element){
		$header_queue = $this->headers[$element];
		$element_sets = Array();
		foreach($header_queue as &$header){
			$set_tmp = Array();
			$set_count = $this->tree[$header]->getCount();
			$set_tmp['count'] = $set_count;
			$parent_index = $this->tree[$header]->getParent();
			while(!($this->tree[$parent_index]->getContent() == null)){
				array_push($set_tmp, $this->tree[$parent_index]->getContent());
				$parent_index = $this->tree[$parent_index]->getParent();
			}
			array_push($element_sets, $set_tmp);
		}
		unset($header);
		return $element_sets;
	}

	private function preproSets(){

		// count items
		$count_table = Array();
		foreach($this->sets as &$set){
			$count = $set['count'];
			foreach($set as $key => $value){
				if ($key === 'count') continue;
				if ($count_table[$value] == null) $count_table[$value] = $count;
				else $count_table[$value] += $count;
			}
		}

		// filter under support items out
		foreach($count_table as $key => $value){
			if ($value < $this->min_support) unset($count_table[$key]);
		}

		// sort the items by count and store F1
		arsort($count_table);
		$this->F1 = $count_table;

		// filter sets, sort and store
		$order = array_keys($this->F1);
		foreach($this->sets as &$set){
			foreach($set as $key => $value){
				if ($key === 'count') continue;
				if (!in_array($value, array_keys($this->F1))){ unset($set[$key]); }
			}
			uasort($set, function ($a, $b) use ($order) {
			    $pos_a = array_search($a, $order);
			    $pos_b = array_search($b, $order);
			    return $pos_a - $pos_b;
			});
		}

		// destroy temp var
		unset($key);
		unset($value);
		unset($set);
	}

	private function buildTree(){
		
		// create new tree with a root node
		$this->tree = Array();
		$root_node = new FPNode(0, null, -1, null);
		array_push($this->tree, $root_node);

		// add record into tree one by one
		foreach($this->sets as &$set){
			$set_tmp = $set;
			$count = $set_tmp['count'];
			unset($set_tmp['count']);
			$set_tmp = array_values($set_tmp);
			$size = sizeof($set_tmp);

			$parent_node = $root_node;
			for ($index = 0; $index < $size; $index++){
				$content = $set_tmp[$index];
				$parent_index = $parent_node->getIndex();
				// check if current node in the children list
				if (in_array($content, array_keys($this->tree[$parent_index]->getChildren()))){
					$parent_children = $this->tree[$parent_index]->getChildren();
					$child_index = $parent_children[$content];
					$this->tree[$child_index]->addCount($count);
					$child_node = $this->tree[$child_index];
					$parent_node = $child_node;
				}
				else {
					$new_node_index = sizeof($this->tree);
					$new_node = new FPNode($new_node_index, $content, $count, $parent_index);
					array_push($this->tree, $new_node);
					$this->tree[$parent_index]->addChild($content, $new_node_index);
					$parent_node = $new_node;
				}
			}
		}

		// destroy temp var
		unset($set);
	}

	private function createHeaderTable(){
		$this->headers = Array();
		foreach($this->tree as $node){
			$node_content = $node->getContent();
			if ($node_content == null) continue;
			$node_index = $node->getIndex();
			if (!in_array($node_content, array_keys($this->headers)))
				$this->headers[$node_content] = Array();
			array_push($this->headers[$node_content], $node_index);
		}
		unset($node);
	}

	public function checkNull(){ return sizeof($this->tree) === 1 ? True : False;}
	public function checkSingleBranch(){
		foreach($this->tree as &$node)
			if (sizeof($node->getChildren()) > 1){ unset($node); return False; }
		unset($node);
		return True;
	}

}

class FPNode {

	private $index;
	private $content;
	private $count;
	private $parent;
	private $children;

	public function __construct($index, $content, $count, $parent){
		$this->setIndex($index);
		$this->setContent($content);
		$this->setCount($count);
		$this->setParent($parent);
		$this->children = Array();
	}

	public function __destruct(){ unset($this->children); }

	public function setIndex($index){ $this->index = $index; }
	public function setContent($content){ $this->content = $content; }
	public function setCount($count){ $this->count = $count; }
	public function setParent($parent){ $this->parent = $parent; }

	public function getIndex(){ return $this->index; }
	public function getContent(){ return $this->content; }
	public function getCount(){ return $this->count; }
	public function getParent(){ return $this->parent; }
	public function getChildren(){ return $this->children; }

	public function addCount($count){ $this->count += $count; }
	public function addChild($child_content, $child_index){ $this->children[$child_content] = $child_index; }

}

?>