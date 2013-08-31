<?php
	$mysqli = new mysqli("localhost", "words", NULL, "words");
	if ($mysqli->connect_errno) {
		printf("Connect failed: %s\n", $mysqli->connect_error);
		exit();
	}
	if (!$mysqli->set_charset("utf8")) {
		printf("Error loading character set utf8: %s\n", $mysqli->error);
	}

	$words = $mysqli->query("SELECT * FROM `words`  where count > 0 and CHAR_LENGTH(word) between 4 and 5 ORDER BY `words`.`count` DESC limit 16384");
	$response = array();
	while ($word = $words->fetch_assoc()) {
		$response[] = '"'.$word['word'].'"';
	}
	asort($response);
	echo '[',implode(",", $response),']';
?>