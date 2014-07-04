<?php

/** 
 * This class will be a container for routes generated from link strings.
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
class Staple_Route
{
	/**
	 * The name of the controller being executed.
	 * @var string
	 */
	protected $controller;
	/**
	 * Name of the action being executed.
	 * @var string
	 */
	protected $action;
	/**
	 * The parameters that are being sent to the action
	 * @var array[mixed]
	 */
	protected $params = array();
	
	public function __construct($link = NULL)
	{
		if(isset($link))
		{
			if(is_array($link))
			{
				$this->processArrayRoute($link);
			}
			else
			{
				$this->processStringRoute($link);
			}
		}
	}
	
	/**
	 * Returns the route as a link.
	 */
	public function __toString()
	{
		//Website Base
		//$link = Staple_Config::getValue('application', 'public_location');
		
		//Add Controller
		$link = Staple_Link::urlCase($this->getController()).'/';
		
		//Add Action
		$link .= Staple_Link::urlCase($this->getAction());
		
		//Add Parameters
		if(count($this->params) >= 1)
		{
			$link .= '/'.implode('/', $this->params);
		}
		
		return $link;
	}
	
	/**
	 * Dispatch the route
	 */
	public function Execute()
	{
		//@todo incomplete method
	}
	
	/**
	 * @return the $controller
	 */
	public function getController()
	{
		return $this->controller;
	}

	/**
	 * @return the $action
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @return the $params
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @param string $controller
	 */
	public function setController($controller)
	{
		$this->controller = $controller;
		return $this;
	}

	/**
	 * @param string $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
		return $this;
	}

	/**
	 * @param array[mixed] $params
	 */
	public function setParams(array $params)
	{
		$this->params = $params;
		return $this;
	}

	/**
	 * Process an array route
	 * @param array $route
	 */
	protected function processArrayRoute(array $route)
	{
		//Set the Controller
		if(array_key_exists(0, $route))
		{
			$this->setController($route[0]);
			unset($route[0]);
		}
		else
		{
			$this->setController('index');
		}
		
		//Set the Action
		if(array_key_exists(1, $route))
		{
			$this->setAction($route[1]);
			unset($route[1]);
		}
		else
		{
			$this->setAction('index');
		}
		
		//Set Parameters
		if(count($route) >= 1)
		{
			$this->setParams($route);
		}
	}
	
	protected function processStringRoute($route)
	{
		//Run some route cleaning operations.
		
		//Convert backslashes to forward slashes
		$route = str_replace('\\','/',$route);
		
		//Remove a starting forward slash
		if(substr($route, 0, 1) == '/')	$route = substr($route, 1, strlen($route)-1);
		
		//Remove trailing forward slash
		if(substr($route, (strlen($route)-1), 1) == '/') $route = substr($route, 0, strlen($route)-1);
		
		//End routing information on the first "." occurance
		if(($end = strpos($route,'.')) !== false)
		{
			$route = substr($route, 0, $end);
		}
		
		//Split the route into it's component elements.
		$splitRoute = explode('/',$route);
		$routeCount = count($splitRoute);
		
		//If the route only contains a controller add the index action
		if($routeCount == 0)
		{
			array_push($splitRoute, 'index');
			array_push($splitRoute, 'index');
		}
		elseif($routeCount == 1)
		{
			array_push($splitRoute, 'index');
		}
		elseif($routeCount >= 2)
		{
			//If the action is numeric, it is not the action. Insert the index action into the route.
			if(is_numeric($splitRoute[1]))
			{
				$shift = array_shift($splitRoute);
				array_unshift($splitRoute, $shift, 'index');
			}
		}
		
		//Check the Controller value and Set a valid value
		$controller = array_shift($splitRoute);
		if(ctype_alnum(str_replace('-', '', $controller)) && ctype_alpha(substr($controller, 0, 1)))
		{
			$this->setController(Staple_Link::methodCase($controller));
		}
		else
		{
			//Bad info in the route, error out.
			throw new Exception('Invalid Route', Staple_Error::PAGE_NOT_FOUND);
		}
		
		//Check the Action Value and Set a valid value
		$action = array_shift($splitRoute);
		if(ctype_alnum(str_replace('-', '', $action)) && ctype_alpha(substr($action, 0, 1)))
		{
			$this->setAction(Staple_Link::methodCase($action));
		}
		else
		{
			//Bad info in the route, error out.
			throw new Exception('Invalid Route', Staple_Error::PAGE_NOT_FOUND);
		}
		
		//Set the parameters
		$this->setParams($splitRoute);
	}
}

?>