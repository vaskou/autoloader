<?php

namespace Vaskou_Autoloader;

class Autoloader {

	private $namespace;

	private $base_dir;

	/**
	 * @param $namespace
	 * @param $base_dir
	 */
	public function __construct( $namespace, $base_dir ) {
		$this->namespace = $namespace;
		$this->base_dir  = $this->trailingslashit( $base_dir );
	}

	public function register() {
		spl_autoload_register( [ $this, 'load_class' ] );
	}

	public function load_class( $class ) {
		if ( empty( $this->namespace ) || 0 !== strpos( $class, $this->namespace ) ) {
			return false;
		}

		$prefix = $class;

		while ( false !== ( $pos = strrpos( $prefix, '\\' ) ) ) { // phpcs:ignore
			$prefix = substr( $class, 0, $pos + 1 );

			$relative_class = substr( $class, $pos + 1 );

			$mapped_file = $this->load_mapped_file( $relative_class );
			if ( $mapped_file ) {
				return $mapped_file;
			}

			$prefix = rtrim( $prefix, '\\' );
		}

		return null;
	}

	private function load_mapped_file( $relative_class ) {

		$relative_class = strtolower( $relative_class );
		$relative_class = strtr( $relative_class, '_', '-' );

		$file = $this->base_dir
		        . str_replace( '\\', '/', $relative_class )
		        . '.php';

		$pos      = strrpos( $file, '/' );
		$filename = 'class-' . substr( $file, $pos + 1 );
		$file     = substr_replace( $file, $filename, $pos + 1 );

		if ( $this->require_file( $file ) ) {
			return $file;
		}

		return null;
	}

	private function require_file( $file ) {
		if ( file_exists( $file ) ) {
			require $file;

			return true;
		}

		return false;
	}

	private function trailingslashit( $string ) {
		return $this->untrailingslashit( $string ) . '/';
	}

	private function untrailingslashit( $string ) {
		return rtrim( $string, '/\\' );
	}

}