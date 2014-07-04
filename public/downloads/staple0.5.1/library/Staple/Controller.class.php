<?php
/**
 * The controller object handles user actions.
 * 
 * @author Ironpilot
 * @copyright Copywrite (c) 2011, STAPLE CODE
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
abstract class Staple_Controller
{
	//private $authMethods = array();
	protected $openMethods = array();
	protected $accessLevels = array();
	protected $open = false;
	//private $protect = false;
	
	/**
	 * 
	 * The $view property holds the view object for the controller. 
	 * @var Staple_View
	 */
	public $view;
	
	/**
	 * 
	 * Controller constructor creates an instance of Staple_View and saves it in the $view
	 * property. It then calls the overridable method _start() for additional boot time
	 * procedures.
	 */
	public final function __construct()
	{
		$methods = get_class_methods(get_class($this));
		foreach($methods as $acMeth)
		{
			if(substr($acMeth, 0,1) != '_')
			{
				$this->accessLevels[$acMeth] = 1;
			}
		}
		$this->view = new Staple_View();
		$this->_start();
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function __wakeup()
	{
		$methods = get_class_methods(get_class($this));
		foreach($methods as $acMeth)
		{
			if(substr($acMeth, 0,1) != '_')
			{
				if(!array_key_exists($acMeth, $this->accessLevels))
				{
					$this->accessLevels[$acMeth] = 1;
				}
			}
		}
		$this->view = new Staple_View();
		$this->_start();
	}
	
	/**
	 * Overridable and empty method allows for additional boot time procedures.
	 */
	public function _start()
	{
		
	}
	
	/**
	 * Returns a boolean true or false whether the method requires authentication
	 * before being dispatched from the front controller.
	 * @todo Incomplete function and procedure.
	 * @param string $method
	 * @return bool
	 */
	public function _auth($method)
	{
		$method = (string)$method;
		if(!ctype_alnum($method))
		{
			throw new Exception('Authentication Validation Error', Staple_Error::AUTH_ERROR);
		}
		else
		{
			if(method_exists($this,$method))
			{
				if($this->open === true)
				{
					return true;
				}
				else
				{
					if(array_search($method, $this->openMethods) !== FALSE)
					{
						return true;
					}
				}
			}
			else
			{
				throw new Exception('Authentication Validation Error', Staple_Error::AUTH_ERROR);
			}
		}
		return false;
	}
	
	/**
	 * 
	 * Returns the access level required for this method.
	 * @param string | array $method
	 * @throws Exception
	 */
	public function _authLevel($method)
	{
		$method = (string)$method;
		if(!ctype_alnum($method))
		{
			throw new Exception('Authentication Validation Error', Staple_Error::AUTH_ERROR);
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
					throw new Exception('Authentication Validation Error', Staple_Error::AUTH_ERROR);
				}
			}
			else
			{
				throw new Exception('Authentication Validation Error', Staple_Error::AUTH_ERROR);
			}
		}
		return 1;
	}
	
	/**
	 * 
	 * Replaces the default permission level with the specified permission level. All method
	 * specific access levels will be overwritten. This should be called in controller startup.
	 * @param int $level
	 */
	protected function _requiredControllerAccessLevel($level)
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
	 * @throws Exception
	 */
	protected function _requiredActionAccessLevel($for,$level)
	{
		$level = (int)$level;
		$for = (string)$for;
		if(!ctype_alnum($for))
		{
			throw new Exception('Cannot change method permissions.', Staple_Error::AUTH_ERROR);
		}
		else
		{
			if(method_exists($this, $for))
			{
				if($level < 0)
				{
					throw new Exception('Cannot change method permissions.', Staple_Error::AUTH_ERROR);
				}
				else
				{
					if($level == 0)
					{
						$this->_openMethod($for);
					}
					else
					{
						$this->accessLevels[$for] = $level;
					}
				}
			}
			else 
			{
				throw new Exception('Cannot change method permissions.', Staple_Error::AUTH_ERROR);
			}
		}
	}
	
	/**
	 * Sent a string it allows one method to be accessed without authentication. When sent
	 * an array, it allows all the values method names without authentication.
	 * @param string | array $method
	 * @throws Exception
	 * @return bool
	 */
	protected function _openMethod($method)
	{
		if(is_array($method))
		{
			foreach($method as $mName)
			{
				if(!ctype_alnum($mName))
				{
					throw new Exception('Cannot change method permissions.', Staple_Error::AUTH_ERROR);
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
				throw new Exception('Cannot change method permissions.', Staple_Error::AUTH_ERROR);
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
	protected function _openAll()
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
	
	/**
	 * 
	 * This function accepts a routing string to redirect the application internally. A
	 * redirect of this sort clears the output buffer and redraws the header, proceeding
	 * as if the redirected controller/action was called directly. 
	 * @param string $to
	 */
	public function _redirect($to)
	{
		Staple_Main::get()->redirect($to);
		$this->view->noRender();
	} 
	
	/**
	 * The default action on any controller must be defined.
	 */
	abstract public function index();
}