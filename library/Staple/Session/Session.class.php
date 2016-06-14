<?php
/**
 * Staple Session management main class.
 *
 * Configuration options [session]:
 * max_lifetime = 20 		The length in minutes that the session lives for.
 * handler = [class name]	The handler class that is used to handle the session.
 * 				Built in options: Staple\Session\DatabaseHandler,
 * 				Staple\Session\FileHandler, Staple\Session\RedisHandler
 *
 * @author Ironpilot
 * @copyright Copyright (c) 2016, STAPLE CODE
 *
 * This file is part of the STAPLE Framework.
 *
 * The STAPLE Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * The STAPLE Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the STAPLE Framework.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Staple\Session;

use Staple\Config;
use Staple\Exception\SessionException;
use Staple\Traits\Singleton;

class Session
{
	use Singleton;
	/**
	 * @var Handler
	 */
	protected $handler;
	/**
	 * The session ID
	 * @var string
	 */
	protected $sessionId;
	/**
	 * The session name;
	 * @var string
	 */
	protected $sessionName;
	/**
	 * The number of seconds for session life.
	 * @var int
	 */
	protected $maxLifetime = 1440;

	/**
	 * Session constructor. Optional session handler object parameter.
	 *
	 * @param Handler $handler
	 * @param string $name
	 * @throws SessionException
	 */
	public function __construct(Handler $handler = NULL, $name = NULL)
	{
		//Setup the session handler
		if(isset($handler))
			$this->setHandler($handler);
		elseif (($configHandler = Config::getValue('session','handler',false)) != NULL)
			$this->setHandler(new $configHandler());
		else
			$this->setHandler(new FileHandler());

		//Set the optional session name
		if(isset($name))
		{
			session_name($name);
			$this->setSessionName($name);
		}
		elseif (($configName = Config::getValue('session','name',false)) != NULL)
		{
			session_name($name);
			$this->setSessionName($name);
		}

		//Set the session max lifetime
		if(Config::exists('session','max_lifetime'))
			$this->setMaxLifetime(Config::getValue('session','max_lifetime'));
		else
			$this->setMaxLifetime(ini_get('session.gc_maxlifetime'));


		//Setup session handler functions
		$handlerSetup = session_set_save_handler($this->handler, true);
		if(!$handlerSetup)
			throw new SessionException('Failed to setup session save handler: '.get_class($this->handler));
	}

	/**
	 * Get the session handler object
	 * @return Handler | FileHandler
	 */
	public function getHandler()
	{
		return $this->handler;
	}

	/**
	 * Set the session handler object. Must be an instance of Staple\Session\Handler abstract class.
	 * @param Handler $handler
	 * @return $this
	 */
	public function setHandler(Handler $handler)
	{
		$this->handler = $handler;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSessionId()
	{
		return $this->sessionId;
	}

	/**
	 * @param string $sessionId
	 */
	public function setSessionId($sessionId)
	{
		$this->sessionId = $sessionId;
	}

	/**
	 * @return string
	 */
	public function getSessionName()
	{
		return $this->sessionName;
	}

	/**
	 * @param string $sessionName
	 * @return $this
	 */
	public function setSessionName($sessionName)
	{
		//Don't allow digit only session names
		if(!ctype_digit($sessionName) && ctype_alnum($sessionName))
		{
			$this->sessionName = (string)$sessionName;
		}
		return $this;
	}

	/**
	 * Get the session max lifetime
	 * @return int
	 */
	public function getMaxLifetime()
	{
		return $this->maxLifetime;
	}

	/**
	 * Set the max session lifetime
	 * @param int $maxLifetime
	 * @return $this
	 */
	public function setMaxLifetime($maxLifetime)
	{
		$this->maxLifetime = (int)$maxLifetime;
		return $this;
	}



	/**
	 * Set a session variable value.
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($key, $value)
	{
		$_SESSION[(string)$key]	= $value;
	}

	/**
	 * Retrieve a value from the session object.
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		if(array_key_exists($key, $_SESSION))
		{
			return $_SESSION[$key];
		}
		return NULL;
	}

	/**
	 * Start the session.
	 * @param string $sessionId
	 * @param bool $suppressThrow
	 * @return $this
	 * @throws SessionException
	 */
	public static function start($sessionId = NULL, $suppressThrow = false)
	{
		$session = self::getInstance();
		if(!headers_sent())
		{
			if (isset($sessionId))
			{
				session_id($sessionId);
				$session->setSessionId($sessionId);
				if(!session_start()) throw new SessionException('Failed to start session.');
			}
			else
			{
				if(!session_start()) throw new SessionException('Failed to start session.');
				$session->setSessionId(session_id());
			}
		}
		else
		{
			if(!$suppressThrow)
				throw new SessionException('Session headers have already been sent by output.');
		}
		return $session;
	}

	/**
	 * Destroy the session.
	 */
	public static function destroy()
	{
		session_destroy();
	}

	/**
	 * Regenerate the session ID
	 * @param bool $deleteOldSession
	 */
	public static function regenerate($deleteOldSession = true)
	{
		session_regenerate_id($deleteOldSession);
	}
}