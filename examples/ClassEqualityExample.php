<?php

require '../src/MonkeyLoader/RootException.php';
require '../src/MonkeyLoader/NotSingletonException.php';
require '../src/MonkeyLoader/MonkeyLoader.php';

use ElvenSpellmaker\MonkeyLoader\MonkeyLoader;

$className = 'Foo';
$filename = "../src/$className.iphp";

define( 'ROOT_DIR', __DIR__ .'/..' );

$icl = new MonkeyLoader;

file_put_contents( $filename, "meta:\r\nclass:\r\npublic function __construct()
	{ echo \"Constructing a Foo!\r\n\";}" );

echo "Getting a Foo object.\r\n";
// Should give me a Foo object.
$foo1 = $icl->get( 'Foo' );
var_dump( $foo1 );
echo 'Memory Usage: ', memory_get_usage(), "\r\n";

// Modify the Class file by replacing Foo with Bar.
$contents = file_get_contents( $filename );
$contents = str_replace( 'Foo', 'Bar', $contents );
file_put_contents( $filename, $contents );
unset( $contents );

echo "Getting a Foo1 object.\r\n";
// This should now construct a Foo1 Object!
$foo2 = $icl->get( 'Foo' );
var_dump( $foo2 );
echo 'Memory Usage: ', memory_get_usage(), "\r\n";

echo ( $icl->isSameClass( $foo1, $foo2 ) ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";

// Remove the test file.
unlink( $filename );