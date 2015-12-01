<?php

namespace koe\pld;

abstract class Classifier {

	private static function raise($m, $s) {
		throw new \Exception($m . ': ' . $s);
	}

	protected $file;
	protected $db = array();

	public function __construct($file) {
		if (!$file)
			self::raise(__METHOD__, 'filename is empty');

		$this->file = $file;
	}

	public function exists() {
		return file_exists($this->file);
	}

	public function save() {
		$data = json_encode($this->db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		if (file_put_contents($this->file, $data) === false)
			self::raise(__METHOD__, 'cant write to ' . $this->file);
	}

	public function load() {
		if (!$this->exists())
			self::raise(__METHOD__, $this->file . ' doesnt exist');

		$data = file_get_contents($this->file);
		if ($data === false)
			self::raise(__METHOD__, 'cant read from ' . $this->file);

		$this->db = json_decode($data, true);
	}

	public function train($class, $file) {
		$text = file_get_contents($file);
		if ($text === false)
			self::raise(__METHOD__, 'cant read from ' . $file);

		$this->model($class, $text);
	}

	abstract function model($class, $text);

	abstract function predict($text);
}
