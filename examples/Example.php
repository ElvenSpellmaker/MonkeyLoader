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
echo ( get_class( $foo1 ) === 'Foo' ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";
unset( $foo1 );

echo "Getting a Foo object.\r\n";
// Should give me another Foo object.
$foo2 = $icl->get( 'Foo' );
var_dump( $foo2 );
echo ( get_class( $foo2 ) === 'Foo' ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";
unset( $foo2 );

// Modify the Class file by replacing Foo with Bar.
$contents = file_get_contents( $filename );
$contents = str_replace( 'Foo', 'Bar', $contents );
file_put_contents( $filename, $contents );
unset( $contents );

echo "Getting a Foo object.\r\n";
// This should *still* construct a Foo Object!
$foo3 = $icl->getCached( 'Foo' );
var_dump( $foo3 );
echo ( get_class( $foo3 ) === 'Foo' ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";
unset( $foo3 );

echo "Getting a Foo1 object.\r\n";
// This should now construct a Foo1 Object!
$foo4 = $icl->get( 'Foo' );
var_dump( $foo4 );
echo ( get_class( $foo4 ) === 'Foo1' ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";
unset( $foo4 );

echo "Getting a Foo1 object.\r\n";
// Another Foo1 here!
$foo5 = $icl->get( 'Foo' );
var_dump( $foo5 );
echo ( get_class( $foo5 ) === 'Foo1' ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";

// Remove the test file.
unlink( $filename );