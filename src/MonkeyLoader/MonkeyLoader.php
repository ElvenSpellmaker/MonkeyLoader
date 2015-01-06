<?php

namespace ElvenSpellmaker\MonkeyLoader;

use ElvenSpellmaker\MonkeyLoader\RootException;
use ElvenSpellmaker\MonkeyLoader\NotSingletonException;

/**
 * MonkeyLoader is a class designed to load Incomplete PHP Files (*.iphp) and
 * attempt to scope them in with an eval()` call. It attempts to mimic class
 * re-loading in PHP by reloading classes and appending a number to their class
 * name.
 *
 * See the README.md for more details.
 *
 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
 */
class MonkeyLoader
{
	/**
	 * Holds information about loaded classes.
	 *
	 * @var []
	 */
	protected $loadedClasses = [];

	/**
	 * Holds an inverse class Map such that Foo\Bar and Foo\Bar1 if loaded via
	 * Foo\Bar both will point to the `$loadedClasses['Foo\Bar']`.
	 *
	 * @var []
	 */
	protected $loadedClassMap = [];

	/**
	 * Holds the path to the class files.
	 *
	 * @var string
	 */
	protected $filePath;

	/**
	 * The root directory of the project.
	 *
	 * @var string
	 */
	protected $rootDir;

	public function __construct($rootDir = '', $sourcePrefix = 'src')
	{
		if( strlen( $rootDir ) )
			$this->rootDir = $rootDir;
		elseif( defined( 'ROOT_DIR' ) )
			$this->rootDir = ROOT_DIR;
		else
			throw new RootException;

		$this->filePath = $this->rootDir ."/$sourcePrefix/";
	}

	/**
	 * Attempts to return a singleton, this will not check for new revisions
	 * but will load a class for the first time if unloaded.
	 *
	 * @param string $class A valid class name. (The filepath)
	 * @param array $params The parameters needed to instantiate this singleton
	 * which will be ignored if the singleton is already initialised.
	 *
	 * @throws NotSingletonException
	 *
	 * @return Object|null
	 */
	public function getCachedSingleton($class, array $params = [])
	{
		return $this->getSingleton($class, $params, false );
	}

	/**
	 * Attempts to return a singleton, this will check for new revisions of the
	 * class and reloads the singleton object if need-be.
	 *
	 * @param string $class A valid class name. (The filepath)
	 * @param array $params The parameters needed to instantiate this singleton
	 * which will be ignored if the singleton is already initialised and a new
	 * version not needed.
	 *
	 * @throws NotSingletonException
	 *
	 * @return Object|null
	 */
	public function getSingleton($class, $params = [], $checkForNew = true)
	{
		$new_singleton_needed = false;

		// If we have created an object before as a normal object but now want
		// it as a singleton throw an error as multiple could easily exist.
		if( isset( $this->loadedClasses[$class] ) && ! isset( $this->loadedClasses[$class]['singleton'] ) )
			throw new NotSingletonException( $class );

		if( isset( $this->loadedClasses[$class]['singleton'] ) )
			$checkForNew and $new_singleton_needed = $this->hasNew( $class );
		else $new_singleton_needed = true;

		if( $new_singleton_needed )
		{
			$obj = $this->getNew( $class, $params );

			// Eventually something like the below would be desirable...
			// $obj->setMonkeyData( $this->loadedClasses[$class]['singleton']->getMonkeyData() );

			$this->loadedClasses[$class]['singleton'] = $obj;
		}
		else $obj = $this->loadedClasses[$class]['singleton'];

		return $obj;
	}

	/**
	 * Attempts to return an instance of a class for a given name. This will
	 * load a class for the first time if not loaded, but will not check for
	 * new revisions.
	 * If the class has been created as a singleton the singleton will be
	 * returned.
	 *
	 * @param string $class A valid class name. (The filepath)
	 * @param array $params The parameters needed to instantiate this new
	 * object. If the class is a singleton they will be ignored if the object is
	 * already created.
	 *
	 * @throws NotSingletonException
	 *
	 * @return Object|null
	 */
	public function getCached($class, array $params = [])
	{
		return $this->get( $class, $params, false );
	}


	/**
	 * Attempts to return an instance of a class for a given name. This will
	 * check for newer revisions of the class and will re-load it and return
	 * an instance of the newer class.
	 * If the class has been created as a singleton the singleton will be
	 * returned and the same new checks will be applied.
	 *
	 * @param string $class A valid class name. (The filepath)
	 * @param array $params The parameters needed to instantiate this new
	 * object. If the class is a singleton they will be ignored if the object is
	 * already created and no new revisions are available.
	 *
	 * @throws NotSingletonException
	 *
	 * @return Object|null
	 */
	public function get($class, array $params = [], $checkForNew = true)
	{
		// If it's a singleton, then return it like so.
		if( isset( $this->loadedClasses[$class]['singleton'] ) )
			return $this->getSingleton( $class, $params, $checkForNew );

		return ( ! $checkForNew && isset( $this->loadedClasses[$class] ) )
				? $this->createNewObject( $class, $params )
				: $this->getNew( $class, $params );
	}

	/**
	 * Given a class name, try to load an IPHP file and return the object if
	 * needed.
	 *
	 * @param string $class A valid class name. (The filepath)
	 * @param array $params The parameters needed to instantiate the object.
	 *
	 * @return Object|null
	 */
	protected function getNew($class, array $params = [])
	{
		$exploded_class = explode( '\\', $class );

		$filename = $this->filePath . join( '/', $exploded_class ) .'.iphp';
		$class_name = array_pop( $exploded_class );
		$namespace = join( '\\', $exploded_class );
		$is_class_loaded = isset( $this->loadedClasses[$class] );

		$obj = null;

		if( file_exists( $filename ) )
		{
			clearstatcache( true, $filename );

			if( ! $is_class_loaded
				|| ( $is_class_loaded && $this->hasNew( $class, $filename ) )
			  )
			{
				$number = $is_class_loaded ? $this->loadedClasses[$class]['current_number'] + 1 : '';

				if( eval( $this->process( $filename, $class_name, $namespace,
					$number ) ) !== false )
				{
					$class_names = $is_class_loaded
						? $this->loadedClasses[$class]['class_names']
						: [];

					$class_names[] = $class . $number;

					$this->loadedClasses[$class] = [
						'current_number' => $number,
						'mtime' => filemtime( $filename ),
						'hash' => hash_file( 'sha256', $filename ),
						'class_names' => $class_names,
					];

					$this->loadedClassMap[$class . $number] = &$this->loadedClasses[$class];

					$is_class_loaded = true;
				}
			}
		}

		if( $is_class_loaded )
		{
			$obj = $this->createNewObject( $class, $params );
		}

		return $obj;
	}

	/**
	 * Given a class name and a list of parameters for a loadedClass, create an
	 * instance of the object and return it.
	 *
	 * @param string $class A valid class name, without the number increment.
	 * @param array $params The paramaters to create the object.
	 *
	 * @return Object
	 */
	protected function createNewObject($class, array $params = [])
	{
		$class .= $this->loadedClasses[$class]['current_number'];

		return (new \ReflectionClass( $class ))->newInstanceArgs( $params );

		// Replace with the below line when PHP 5.6 becomes more prevalent.
		// return new $class(...$params);
	}

	/**
	 * Checks if the given classname has a new revision to load. If a filename
	 * is passed this will speed things up.
	 *
	 * @param string $class
	 * @param string|null $filename The filename to check, this can be worked
	 * out from the class if null is passed.
	 *
	 * @return boolean True if a new revision is available.
	 */
	public function hasNew($class, $filename = null)
	{
		if( ! $filename )
			$filename = $this->filePath . join( '/', explode( '\\', $class ) ) .'.iphp';

		if( ! file_exists( $filename ) || ! isset( $this->loadedClasses[$class] ) )
			return false;

		clearstatcache( true, $filename );

		return
			filemtime( $filename ) !== $this->loadedClasses[$class]['mtime']
			|| hash_file( 'sha256', $filename ) !== $this->loadedClasses[$class]['hash'];
	}

	/**
	 * Checks if two objects or class strings (or a mix) are the same class as
	 * loaded by
	 *
	 * @param string|object $class_string1
	 * @param string|object $class_string2
	 *
	 * @return boolean True if the classes are the "same".
	 */
	public function isSameClass($class_string1, $class_string2)
	{
		is_object( $class_string1 ) and $class_string1 = get_class( $class_string1 );
		is_object( $class_string2 ) and $class_string2 = get_class( $class_string2 );

		return isset( $this->loadedClassMap[$class_string1] )
			&& isset( $this->loadedClassMap[$class_string2] )
			&& $this->loadedClassMap[$class_string1] === $this->loadedClassMap[$class_string2];
	}

	/**
	 * Processes an IPHP file into "valid" PHP code ready for loading using
	 * eval().
	 *
	 * @param string $filename The filename to load.
	 * @param string $class_name The class name to be loaded.
	 * @param string $namespace The namespace the class should be in.
	 * @param string $number The current number of the class to be loaded.
	 *
	 * @return string The PHP code from an IPHP file.
	 */
	protected function process($filename, $class_name, $namespace, $number)
	{
		// Deal with the namespace of the class to be loaded.
		$namespace = ($namespace !== '')
			? $namespace = "namespace $namespace {"
			: $namespace = '';

		$iphp = file_get_contents( $filename );

		if( strpos( $iphp, "meta:\r\n") !== 0 )
			return 'return false;';

		if( ( $class_start = strpos( $iphp, "class:\r\n" ) ) === false )
			return 'return false;';

		// String legnth of "meta:\r\n" is 7.
		// String legnth of "class:\r\n" is 7.

		$meta = substr( $iphp, 7, $class_start - 7 );

		$class = substr( $iphp, $class_start + 8 );

		$class_string = "$namespace class $class_name$number $meta { $class}";

		// Finish off the namespace section.
		($namespace != '') and $class_string .= '}';

		return $class_string;
	}
}
