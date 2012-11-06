<?php

include "common.inc.php";

/*

A selenium test to do the following:

- Direct link a movie page from nowehere.  Repeat 1000 times.
- leave.

*/

$minimum_movie_id = 1;			// There might be a bias towards new numbers?
$maximum_movie_id = 1543720;

$iterations		  = 1000;

for($i=0;$i<$iterations;$i++) {

	$movie_id = rand($minimum_movie_id, $maximum_movie_id);
	
	// open_browser

}

?>
