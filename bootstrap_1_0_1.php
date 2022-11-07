<?php
if ( ! class_exists( 'Vaskou_Autoloader_Bootstrap_1_0_1' ) ) {

	class Vaskou_Autoloader_Bootstrap_1_0_1 {

		const VERSION = '1.0.1';

		private static $_instance;

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		private function __construct() {

			if ( ! class_exists( 'Vaskou_Autoloader\\VersionHandler' ) ) {
				include 'VersionHandler.php';
			}

			$version = Vaskou_Autoloader\VersionHandler::instance();

			if ( empty( $version->getVersion() ) ) {
				$version->setVersion( self::VERSION );
			}

			if ( version_compare( $version->getVersion(), self::VERSION, '<=' ) ) {
				$version->setVersion( self::VERSION );
				spl_autoload_register( array( $this, 'class_loader' ), true, true );
			}
		}

		public function class_loader( $class_name ) {

			$namespace = 'Vaskou_Autoloader\\';

			$length = strlen( $namespace );

			if ( 0 !== strpos( $class_name, $namespace ) ) {
				return;
			}

			$filename = substr( $class_name, $length );

			include_once( "{$filename}.php" );
		}
	}

	Vaskou_Autoloader_Bootstrap_1_0_1::instance();
}