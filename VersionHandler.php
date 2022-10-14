<?php

namespace Vaskou_Autoloader;

class VersionHandler {

	private $version;
	private static $_instance;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {


	}

	/**
	 * @return mixed
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @param mixed $version
	 */
	public function setVersion( $version ) {
		$this->version = $version;
	}
}