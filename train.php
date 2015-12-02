<?php

chdir(__DIR__ . '/../../..');

require('vendor/autoload.php');
mb_internal_encoding('utf-8');

try {
	$classifier = new koe\pld\NGramClassifier();

	$tmp = '/run/shm/corpus.txt';
	foreach (glob(__DIR__ . '/etc/corpus/*') as $langDir) {
		if (is_dir($langDir)) {
			@unlink($tmp);
			$lang = basename($langDir);

			foreach (glob($langDir . '/*.txt') as $langFile)
				file_put_contents($tmp, file_get_contents($langFile), FILE_APPEND);

			echo $lang . '...';
			$classifier->train($lang, $tmp);
			echo "done\n";
		}
	}

	$classifier->save(true);
} catch (Exception $e) {
	die($e->getMessage() . "\n");
}
