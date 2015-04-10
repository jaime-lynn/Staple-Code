<?php
/**
 * The Provider object handles RESTful actions.
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

use Staple\Exception\ProviderException;

abstract class Provider
{
	use Traits\Helpers;
	
	protected $openMethods = array();
	protected $accessLevels = array();
	protected $open = false;

	/**
	 * 
	 * Controller constructor creates an instance of View and saves it in the $view
	 * property. It then calls the overridable method _start() for additional boot time
	 * procedures.
	 */
	public function __construct()
	{
		//Set default access levels
		$methods = get_class_methods(get_class($this));
		foreach($methods as $acMeth)
		{
			if(substr($acMeth, 0,1) != '_')
			{
				$this->accessLevels[$acMeth] = 1;
			}
		}
	}
	
	/*----------------------------------------Auth Functions----------------------------------------*/
	
	/**
	 * Returns a boolean true or false whether the method requires authentication
	 * before being dispatched from the front controller.
	 * @param string $method
	 * @throws ProviderException
	 * @return bool
	 */
	public function auth($method)
	{
		$method = (string)$method;
		if(!ctype_alnum($method))
		{
			throw new ProviderException('Authentication Validation Error', Error::AUTH_ERROR);
		}
		else
		{
			if(method_exists($this,$method))
			{
				//Is the controller completely open?
				if($this->open === true)
				{
					return true;
				}
				elseif(array_search($method, $this->openMethods) !== FALSE)		//Is the requested method open?
				{
					return true;
				}
				elseif(Auth::get()->isAuthed() && Auth::get()->getAuthLevel() >= $this->authLevel($method))	//Does the authed user have the required access level?
				{
					return true;
				}
			}
			else
			{
				throw new ProviderException('Authentication Validation Error', Error::AUTH_ERROR);
			}
		}
		return false;
	}
	
	/**
	 * 
	 * Returns the access level required for this method.
	 * @param string | array $method
	 * @throws ProviderException
	 * @return int
	 */
	public function authLevel($method)
	{
		$method = (string)$method;
		if(!ctype_alnum($method))
		{
			throw new ProviderException('Authentication Validation Error: Invalid Method', Error::AUTH_ERROR);
		}
		else
		{
			if(method_exists($this,$method))
			{
				if(array_key_exists($method, $this->accessLevels) === true)
				{
					return (int)$this->accessLevels[$method];
				}
				else
				{
					//return default auth level if non assigned.
					return 1;
					//throw new Exception('Authentication Validation Error: No Auth Level', Error::AUTH_ERROR);
				}
			}
			else
			{
				throw new ProviderException('Authentication Validation Error: Method Not Found', Error::AUTH_ERROR);
			}
		}
	}
	
	/**
	 * 
	 * Replaces the default permission level with the specified permission level. All method
	 * specific access levels will be overwritten. This should be called in controller startup.
	 * @param int $level
	 */
	protected function requiredProviderAccessLevel($level)
	{
		$methods = get_class_methods(get_class($this));
		foreach($methods as $acMeth)
		{
			if(substr($acMeth, 0,1) != '_')
			{
				$this->accessLevels[$acMeth] = (int)$level;
			}
		}
		$this->openMethods = array();
	}
	
	/**
	 * Specifies the required access level for the specified action. This should be called
	 * in the Controller startup.
	 * @param string $for
	 * @param int $level
	 * @throws ProviderException
	 */
	protected function requiredActionAccessLevel($for,$level)
	{
		$level = (int)$level;
		$for = (string)$for;
		if(!ctype_alnum($for))
		{
			throw new ProviderException('Cannot change method permissions.', Error::AUTH_ERROR);
		}
		else
		{
			if(method_exists($this, $for))
			{
				if($level < 0)
				{
					throw new ProviderException('Cannot change method permissions.', Error::AUTH_ERROR);
				}
				else
				{
					if($level == 0)
					{
						$this->openMethod($for);
						if(array_key_exists($for, $this->accessLevels))
						{
							unset($this->accessLevels[$for]);
						}
					}
					else
					{
						$this->accessLevels[$for] = $level;
					}
				}
			}
			else 
			{
				throw new ProviderException('Cannot change method permissions on a non-existant method.', Error::AUTH_ERROR);
			}
		}
	}
	
	/**
	 * Sent a string it allows one method to be accessed without authentication. When sent
	 * an array, it allows all the values method names without authentication.
	 * @param string | array $method
	 * @throws ProviderException
	 * @return bool
	 */
	protected function openMethod($method)
	{
		if(is_array($method))
		{
			foreach($method as $mName)
			{
				if(!ctype_alnum($mName))
				{
					throw new ProviderException('Cannot change method permissions.', Error::AUTH_ERROR);
				}
				else
				{
					if(array_search($mName, $this->openMethods) === false)
					{
						$this->openMethods[] = $mName;
						$this->accessLevels[$mName] = 0;
					}
				}
			}
			return true;
		}
		else
		{
			if(!ctype_alnum($method))
			{
				throw new ProviderException('Cannot change method permissions.', Error::AUTH_ERROR);
			}
			else
			{
				if(array_search($method, $this->openMethods) === false)
				{
					$this->openMethods[] = $method;
					$this->accessLevels[$method] = 0;
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * This function opens the entire controller to be accessed without authentication.
	 */
	protected function openAll()
	{
		$methods = get_class_methods(get_class($this));
		foreach($methods as $acMeth)
		{
			if(substr($acMeth, 0,1) != '_')
			{
				$this->accessLevels[$acMeth] = 0;
			}
		}
		$this->open = true;
	}
}