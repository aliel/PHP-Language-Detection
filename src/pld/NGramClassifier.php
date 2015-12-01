<?php

namespace koe\pld;

class NGramClassifier extends Classifier {

	const DB = 'etc/ngrams.json';

	private $defaults = array(
		'depth' => 1000,
		'ngMin' => 1,
		'ngMax' => 4,
	);

	public function __construct($file = '', $defaults = array()) {
		parent::__construct($file ? : self::DB);

		$this->defaults = array_merge($this->defaults, $defaults);
	}

	public function ngrams($text) {
		//Split text into "words"
		$words = preg_split('~[\P{L}]+~u', mb_strtolower($text));

		$ngMin = $this->defaults['ngMin'];
		$ngMax = $this->defaults['ngMax'];

		//Split words into ngramgs
		$freq = array();
		foreach ($words as $word) {
			$l = mb_strlen($word);
			for ($i = 0; $i < $l; $i++) {
				for ($n = $ngMin; $n <= $ngMax; $n++) {
					$ngram = mb_substr($word, $i, $n);
					if (mb_strlen($ngram) < $n)
						continue;

					@$freq[$ngram]++;
				}
			}
		}

		arsort($freq);
		//We only need top ngrams. Freqs have no use
		return array_flip(array_slice(array_keys($freq), 0, $this->defaults['depth']));
	}

	public function model($class, $text) {
		$this->db[$class] = $this->ngrams($text);
	}

	public function predict($text) {
		$ngrams = $this->ngrams($text);
		$depth = $this->defaults['depth'];
		$max = count($ngrams) * $depth;
		$result = array();
		foreach ($this->db as $class => $model) {
			$i = 0;
			$delta = 0;
			foreach ($ngrams as $ngram => $j) {
				$delta += isset($model[$ngram]) ? abs($i - $j) : $depth;
				$i++;
			}

			if ($delta < $max)
				$result[$class] = $delta / $max;
		}

		asort($result);
		return $result;
	}

	public function predictOne($text) {
		$result = $this->predict($text);
		return count($result) ? current($result) : null;
	}

}
