<?php
/** 
 * A container to reference config settings without having to re-read the config file.
 * 
 * @author Ironpilot
 * @copyright Copyright (c) 2011, STAPLE CODE
 * 
 * This file is part of the STAPLE Framework.
 * 
 * The STAPLE Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by the 
 * Free Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 * 
 * The STAPLE Framework is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public License for 
 * more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with the STAPLE Framework.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Staple;

use \Exception;
use \Staple\Exception\ConfigurationException;
use \stdClass;

/**
* Added to include Singleton Trait once so Config class will load with out autoloader being started.
*/
require_once STAPLE_ROOT.'Traits'.DIRECTORY_SEPARATOR.'Singleton.trait.php';
require_once STAPLE_ROOT.'Exception'.DIRECTORY_SEPARATOR.'ConfigurationException.class.php';

class Config
{
	use \Staple\Traits\Singleton;
	
	/**
	 * Variable to specify whether the config has already been read from the filesystem.
	 * @var boolean
	 */
	protected $read = false;
	
	/**
	 * The name of the configset to load.
	 * @var string
	 */
	protected $configSet = 'application';
	
	/**
	 * The configuration set store.
	 * @var array
	 */
	protected $store = array();
	
	/**
	 * Disable construction of this object.
	 */
	public function __construct($configName = NULL)
	{
		$this->read = false;
		if(isset($configName))
			$this->setConfigSet($configName);
		$this->read();
	}
	
	/**
	 * Get a config set by header.
	 * @param string $name
	 * @throws ConfigurationException
	 * @return mixed
	 */
	public function __get($name)
	{
		if(!$this->$read)
		{
			$this->read();
		}
		
		//Check for the existence of
		if(array_key_exists($name, $this->store))
		{
			return $this->store[$name];
		}
		else
		{
			throw new ConfigurationException('Configuration value does not exist in the current scope.');
		}
	}
	
	/**
	 * Setting config values during runtime are not allowed.
	 * @throws Exception
	 */
	public function __set($name,$value)
	{
		throw new ConfigurationException('Config changes are not allowed at execution',Error::APPLICATION_ERROR);
	}
	
	/**
	 * Get a config set by header.
	 * @todo this doesn't really work as a static method and needs to be refactored.
	 * @param string $name
	 * @param bool $getAsObject
	 * @throws ConfigurationException
	 * @return array
	 */
	public static function get($name, $getAsObject = false)
	{
		//Get the config instance
		$inst = static::getInstance();
		
		//Check that the config file has been read.
		if(!$inst->read)
		{
			$inst->read();
		}
		
		//Look for the requested key in the data store.
		if(array_key_exists($name, $inst->store))
		{
			if($getAsObject === true)
			{
				$object = new stdClass();
				foreach ($inst->store[$name] as $key => $value)
				{
					$object->$key = $value;
				}
				return $object;
			}
			else
			{
				return $inst->store[$name];
			}
		}
		else
		{
			throw new ConfigurationException('Configuration value does not exist in the current scope.');
		}
	}
	
	/**
	 * Returns the entire config array.
	 * @return array
	 */
	public static function getAll()
	{
		return static::getInstance()->store;
	}
	
	/**
	 * Returns a single value from the configuration file.
	 * 
	 * @param string $set
	 * @param string $key
	 * @param boolean $throwOnError
	 * @throws ConfigurationException
	 * @return mixed
	 */
	public static function getValue($set,$key, $throwOnError = true)
	{
		//Get the config instance
		$inst = static::getInstance();
		
		//Check that the config file has been read.
		if(!$inst->read)
		{
			$inst->read();
		}
		
		//Look for the requested key in the data store.
		if(array_key_exists($set, $inst->store))
		{
			if(array_key_exists($key, $inst->store[$set]))
			{
				return $inst->store[$set][$key];
			}
			else
			{
				if($throwOnError)
					throw new ConfigurationException('Configuration value does not exist in the current scope.');
				else
					return null;
			}
		}
		else
		{
			if($throwOnError)
				throw new ConfigurationException('Configuration value does not exist in the current scope.');
			else
				return null;
		}
	}
	
	/**
	 * Sets a configuration value at runtime. Returns a true or false on success or failure.
	 * 
	 * @param string $set
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	/*public static function setValue($set,$key,$value)
	{
		if(!self::$read)
		{
			self::read();
		}
		if(array_key_exists($set, self::$store))
		{
			if(array_key_exists($key, self::$store[$set]))
			{
				self::$store[$set][$key] = $value;
				return true;
			}
		}
		return false;
	}*/
	
	/**
	 * Read and store the application.ini config file.
	 */
	private function read()
	{
		if($this->read == false)
		{
			if(defined('CONFIG_ROOT'))
			{
				if(file_exists(CONFIG_ROOT.$this->getConfigSet().'.ini'))
				{
					$this->store = parse_ini_file(CONFIG_ROOT.$this->getConfigSet().'.ini',true);
				}
			}
			$this->read = true;
		}
	}
	
	/**
	 * @return string $configSet
	 */
	public function getConfigSet()
	{
		return $this->configSet;
	}

	/**
	 * @param string $configSet
	 * @return $this
	 */
	public function setConfigSet($configSet)
	{
		$this->configSet = $configSet;
		return $this;
	}

}