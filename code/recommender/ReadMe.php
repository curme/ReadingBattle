<br><h2>NOTE</h2><br>

<p>1. UPDATE RECOMMENDATION RULES<br>
// Call this function every day once in free time.<br>
<font color='blue'>updateBookToBookRules();</font></p><br>

<p>2. UPDATE USER RECOMMENDATION BOOKS<br>
// Call this function when user generate new read records.<br>
<font color='blue'>updateUserRecomBooks($userid);</font></p><br>

<p>* 2.1 UPDATE FOR ALL USERS (resources wasting)<br>
<font color='blue'>$data_manager = new DataManager();<br>
$userid_list = $data_manager->getUseridList();<br>
foreach($userid_list as &$userid) updateUserRecomBooks($userid);</font></p><br>


<p>3. GET USER RECOMMENDATION BOOKS<br>
// Call this function when need getting user recommendation books.<br>
<font color='blue'>getUserRecomBooks($userid);</font></p><br>



<?php

echo '<h2>RUNNING RESULT EXAMPLE</h2><br>';


include 'Recommendation.php';

// 1. UPDATE RECOMMENDATION RULES
// updateBookToBookRules();


// 2. UPDATE USER RECOMMENDATION BOOKS
// $userid = '1024'; // example
// updateUserRecomBooks($userid);


// * 2.1 UPDATE FOR ALL USERS
// $data_manager = new DataManager();
// $userid_list = $data_manager->getUseridList();
// foreach($userid_list as &$userid) updateUserRecomBooks($userid);


// 3. GET USER RECOMMENDATION BOOKS
echo "Example of 3.: ";
echo "<font color='blue'>getUserRecomBooks('1024');</font>";
$userid = '1024'; // example
$recom_books = getUserRecomBooks($userid);
print_array($recom_books);

?>