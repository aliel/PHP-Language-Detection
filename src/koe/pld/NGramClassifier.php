<?php

namespace koe\pld;

class NGramClassifier extends Classifier {
	//Relative to the package's root

	const DB = 'etc/ngrams.json';

	private $defaults = array(
		'depth' => 1000,
		'ngMin' => 1,
		'ngMax' => 4,
	);

	public function __construct($file = '', $defaults = array()) {
		parent::__construct($file ? : $this->packagePath(self::DB));

		$this->defaults = array_merge($this->defaults, $defaults);
	}

	public function save($force = false) {
		if (!$force) {
			if (realpath($this->file) === realpath($this->packagePath(self::DB)))
				self::raise(__METHOD__, 'The bundled db is read only');
		}

		//Pack and save
		$a = array();
		foreach ($this->db as $class => $model)
			$a[$class] = array_keys($model);

		$_ = & $this->db;
		$this->db = & $a;
		parent::save();
		$this->db = & $_;
	}

	public function load() {
		parent::load();

		//Unpack
		foreach ($this->db as $class => $model)
			$this->db[$class] = array_flip($model);
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

	public function model($class, $data) {
		$this->db[$class] = $this->ngrams($data);
	}

	//Here the classifier is.
	//Returns an array of language=>distance sorted by distance.
	//Distances returned are [0,1). The less the distance the higher the similarity.
	//Use predictOne() method to only get the top language detected.
	public function predict($data) {
		$ngrams = $this->ngrams($data);
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

}
