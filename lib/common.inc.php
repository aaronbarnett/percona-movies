<?php

function get_memcached(){
	global $memcache;

	if(!$memcahce) {
		$memcache = new Memcached();
		$memcache->addServer('localhost', 11211);
	}

	return $memcache;
}

function cache_get($key, Closure $f, $time) {
	$val = get_memcached()->get($key);
	if(!$val){
		$val = $f();
		echo "<!-- Caching " . $key . " => " . $val . " -->\n";
		cache_set($key, $val, $time);
	}else{
		echo "<!-- Using Cached " . $key . " => " . $val . " -->\n";
	}
	return $val;
}

function cache_set($key, $value, $time) {
	get_memcached()->set($key, $value, $time);
}


function mysql_query_wrapper($query) {
	global $__queries;
	
	/*
	 This is a very light-weight wrapper around mysql_query() that
	 understands:
	 
	 - The performance instrumentation class
	 - Writing to a debug log if a query was invalid.
	 - Logging all queries.  If debug mode is on, the logged
	   queries display at the end of the page.
 
	*/
	
	if (!isset($__queries)) $__queries = array();
	
	$__queries[] = $query;

	$return = MySQL_perf::mysql_query($query);
	
	if (!$return) {
		debug_write($query);
	}
	
	return $return;
}

function debug_write($string) {
	
	if (DEBUG_MODE) {
		print $string;
	} else {
		error_log("mymovies: " . $string);
	}
}

function get_number_of_users() {
	return cache_get("NUM_USERS", function(){
		$result = mysql_query_wrapper("SELECT count(*) as c FROM users");
		return mysql_result($result,0,'c');
	}, 60);
}
function get_number_of_movies() {
	return cache_get("NUM_MOVIES", function(){
		$result = mysql_query_wrapper("SELECT count(*) as c FROM title");
		return mysql_result($result,0,'c');
	}, 60);
}
function get_number_of_actors() {
	return cache_get("NUM_ACTORS", function(){
		$result = mysql_query_wrapper("SELECT count(*) as c FROM name");
		return mysql_result($result,0,'c');
	}, 60);
}

function get_random_movie() {

	$result = mysql_query_wrapper("SELECT * FROM title WHERE title != '' 
	AND kind_id = 1
	ORDER BY RAND() LIMIT 1");
	return mysql_fetch_assoc($result);

}

function get_random_actor() {

	$result = mysql_query_wrapper("SELECT * FROM name ORDER BY RAND() LIMIT 1");
	return mysql_fetch_assoc($result);

}

function get_random_user() {

	$result = mysql_query_wrapper("SELECT * FROM users ORDER BY RAND() LIMIT 1");
	return mysql_fetch_assoc($result);

}

function redirect_to($url) {

	header("Location: $url");
	die();

}

function get_comments() {
	return cache_get("COMMENTS", function(){

		$return = array();
		$result = mysql_query_wrapper("SELECT * FROM comments ORDER BY id DESC limit 10");
		while($row = mysql_fetch_assoc($result)) {
			$return[] = $row;
		}

		return $return;
	}, 30);

}


function get_being_viewed($limit=5) {
	return cache_get("BEING_VIEWED", function(){
		$return = array();
		$result = mysql_query_wrapper("SELECT DISTINCT type, viewed_id FROM page_views ORDER BY id DESC LIMIT $limit");
		while($row = mysql_fetch_assoc($result)) {
			$return[] = $row;
		}
		
		return $return;
	}, 10);
}


function get_users_online() {
	return cache_get("USERS_ONLINE", function(){
		$return = array();
		$result = mysql_query_wrapper("SELECT * FROM users WHERE last_login_date > NOW()-INTERVAL 10 MINUTE ORDER BY last_login_date DESC LIMIT 10");
		while($row = mysql_fetch_assoc($result)) {
			$return[] = $row['id'];
		}
		
		return $return;
	}, 60);
}

function update_page_views($type, $id) {
	
	global $me;
	
	$my_id = is_object($me) ? $me->id : 0;	
	mysql_query_wrapper("INSERT INTO page_views (type, viewed_id, viewed, user_id)
		VALUES ('$type', $id, NOW(), $my_id)") or mysql_error();
	

}

function require_valid_user($fail_location=BASE_URI) {

	if (!is_logged_in()) {
		redirect_to($fail_location);
	}
}

function is_logged_in() {
	global $me;
	return isset($me);
}


function h($text) {
	return htmlentities($text, ENT_QUOTES,"UTF-8");
}

function create_new_user($first_name, $last_name, $email) {

	mysql_query_wrapper(sprintf("INSERT INTO users (first_name, last_name, email_address)
		VALUES ('%s', '%s', '%s')",
		mysql_real_escape_string($first_name),
		mysql_real_escape_string($last_name),
		mysql_real_escape_string($email)
	));
	
	return mysql_insert_id();

}


?>
