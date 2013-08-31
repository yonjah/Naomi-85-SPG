<?php
	$random_api = "http://he.wiktionary.org/w/api.php?action=query&list=random&rnlimit=10&format=json&rnnamespace=0";

	function curl_get($url, $json = true) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response = curl_exec($ch);
		curl_close ($ch);
		if ($response && $json) {
			try {
				return json_decode($response);
			}  catch (Exception $e) {
				echo $e;
				return false;
			}
		} else {
			return $response;
		}
	}

	$mysqli = new mysqli("localhost", "words", NULL, "words");

	if ($mysqli->connect_errno) {
		printf("Connect failed: %s\n", $mysqli->connect_error);
		exit();
	}

	if (!$mysqli->set_charset("utf8")) {
		printf("Error loading character set utf8: %s\n", $mysqli->error);
	}


	for ($i=0; $i < 5000; $i++) {
		$random_data = curl_get($random_api);
		if ($random_data && isset($random_data->query->random)) {
			$page_ids = array();
			foreach ($random_data->query->random as $page) {
				preg_match_all('/[א-ת]{2,}/u', $page->title, $matches);
				foreach ($matches[0] as $word) {
					$word = $mysqli->real_escape_string($word);
					$mysqli->query("INSERT into words (word, count) VALUES ('$word', '1') ON DUPLICATE KEY UPDATE count=count+1;");
				}
			}
		}
	}

	$words = $mysqli->query(
		"SELECT * from words where
			(
				word LIKE 'ה%' OR
				word LIKE 'ב%' OR
				word LIKE 'ו%' OR
				word LIKE 'כ%' OR
				word LIKE 'ל%' OR
				word LIKE 'ת%' OR
				word LIKE 'ש%'
			)
		 AND count > 0"
	);
	while ($word = $words->fetch_assoc()) {
		$source = $mysqli->real_escape_string(mb_substr($word['word'], 1, strlen($word['word']), 'UTF-8'));
		$source = $mysqli->query("SELECT * from words where word = '$source'")->fetch_assoc();
		if ($source) {
			printf("%s = %s\n", $source['word'], $word['word']);
			$mysqli->real_query("UPDATE words SET count = count + {$source['count']} where word_id = {$source['word_id']}");
			$mysqli->real_query("UPDATE words SET count = 0 where word_id = {$word['word_id']}");
		}
	}
?>