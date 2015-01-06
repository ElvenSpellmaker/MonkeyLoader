<?php

namespace ElvenSpellmaker\MonkeyLoader;

/**
 * This exception is thrown when a non-Singleton loaded class is trying to be
 * loaded as a singleton.
 *
 * @author Jack Blower <Jack@elvenspellmaker.co.uk>
 */
class NotSingletonException extends \Exception
{
	public function __construct($class_name)
	{
		$this->message = "Class '$class_name' has previously been used as a normal class!";
	}
}