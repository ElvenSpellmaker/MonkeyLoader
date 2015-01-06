<?php

require '../src/MonkeyLoader/RootException.php';
require '../src/MonkeyLoader/NotSingletonException.php';
require '../src/MonkeyLoader/MonkeyLoader.php';

use ElvenSpellmaker\MonkeyLoader\MonkeyLoader;
use ElvenSpellmaker\MonkeyLoader\NotSingletonException;

$className = 'Foo';
$filename = "../src/MonkeyLoader/$className.iphp";

define( 'ROOT_DIR', __DIR__ .'/..' );

$icl = new MonkeyLoader;

file_put_contents( $filename, "meta:\r\nclass:\r\npublic function __construct()
	{ echo \"Constructing a Foo!\r\n\";}" );

echo "Getting a Foo object.\r\n";
// Should give me a Foo object.
$foo1 = $icl->getSingleton( 'MonkeyLoader\Foo' );
var_dump( $foo1 );
echo ( get_class( $foo1 ) === 'MonkeyLoader\Foo' ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";

echo "Getting a Foo object and checking it's the SAME object as the previous Foo.\r\n";
// Should give me another Foo object.
$foo2 = $icl->get( 'MonkeyLoader\Foo' );
var_dump( $foo2 );
echo ( $foo2 === $foo1 ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";
unset( $foo2 );

// Modify the Class file by replacing Foo with Bar.
$contents = file_get_contents( $filename );
$contents = str_replace( 'Foo', 'Bar', $contents );
file_put_contents( $filename, $contents );
unset( $contents );

echo "Getting a Foo object from Cache, should be the old Foo from the beginning still!\r\n";
// This should *still* construct a Foo Object!
$foo3 = $icl->getCached( 'MonkeyLoader\Foo' );
var_dump( $foo3 );
echo ( $foo3 === $foo1 ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";
unset( $foo3 );

echo "Getting a Foo1 object as a singleton.\r\n";
// This should now construct a Foo1 Object!
$foo4 = $icl->get( 'MonkeyLoader\Foo' );
var_dump( $foo4 );
echo ( $foo4 !== $foo1 ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";
unset( $foo1 );

echo "Getting a Foo1 object which should be the same object as before.\r\n";
// Another Foo1 here!
$foo5 = $icl->get( 'MonkeyLoader\Foo' );
var_dump( $foo5 );
echo ( $foo5 === $foo4 ) ? "\e[0;32mPASS\e[0m\n" : "\e[0;31mFAIL\e[0m\r\n";
echo 'Memory Usage: ', memory_get_usage(), "\r\n";
unset( $foo5, $foo4 );

// Remove the test file.
unlink( $filename );

$filename = '../src/MonkeyLoader/Bar.iphp';

file_put_contents( $filename, "meta:\r\nclass:\r\npublic function __construct()
	{ echo \"Constructing a Bar!\r\n\";}" );

// Get a normal Bar object.
$icl->get( 'MonkeyLoader\Bar' );

echo "Trying to get Bar as a Singleton after already creating it as a normal object.\r\n";
try {
	$icl->getSingleton( 'MonkeyLoader\Bar' );
	echo "\e[0;31mFAIL\e[0m\r\n";
} catch (NotSingletonException $e) {
	echo "$e\r\n\e[0;32mPASS\e[0m\r\n";
}
finally
{
	unlink( $filename );
}
