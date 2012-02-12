<?php
/**
 * Class definition file for SLIRRequest
 * 
 * This file is part of SLIR (Smart Lencioni Image Resizer).
 * 
 * SLIR is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * SLIR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with SLIR.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @copyright Copyright © 2010, Joe Lencioni
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public
 * License version 3 (GPLv3)
 * @since 2.0
 * @package SLIR
 */

/* $Id: slirrequest.class.php 129 2010-12-22 19:43:06Z joe.lencioni $ */
 
/**
 * SLIR request class
 * 
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @date $Date: 2010-12-22 13:43:06 -0600 (Wed, 22 Dec 2010) $
 * @version $Revision: 129 $
 * @package SLIR
 */
class SLIRRequest
{

	const CROP_RATIO_DELIMITERS	= ':.';
	
	/**
	 * Path to image
	 *
	 * @since 2.0
	 * @var string
	 */
	private $path;
	
	/**
	 * Maximum width for resized image, in pixels
	 *
	 * @since 2.0
	 * @var integer
	 */
	private $width;

	/**
	 * Maximum height for resized image, in pixels
	 *
	 * @since 2.0
	 * @var integer
	 */
	private $height;

	/**
	 * Ratio of width:height to crop image to.
	 *
	 * For example, if a square shape is desired, the crop ratio should be "1:1"
	 * or if a long rectangle is desired, the crop ratio could be "4:1". Stored
	 * as an associative array with keys being 'width' and 'height'.
	 *
	 * @since 2.0
	 * @var array
	 */
	private $cropRatio;

	/**
	 * Name of the cropper to use, e.g. 'centered' or 'smart'
	 * 
	 * @since 2.0
	 * @var string
	 */
	private $cropper;
	
	/**
	 * Quality of rendered image
	 * 
	 * @since 2.0
	 * @var integer
	 */
	private $quality;
	
	/**
	 * Whether or not progressive JPEG output is turned on
	 * @var boolean
	 * @since 2.0
	 */
	private $progressive;
	
	/**
	 * Color to fill background of transparent PNGs and GIFs
	 * @var string
	 * @since 2.0
	 */
	private $background;
	
	/**
	 * @since 2.0
	 */
	final public function __construct()
	{
		$params	= $this->getParameters();

		// Set image path first
		if (isset($params['i']) && $params['i'] != '' && $params['i'] != '/')
		{
			$this->__set('i', $params['i']);
			unset($params['i']);
		}
		else
		{
			header('HTTP/1.1 400 Bad Request');
			throw new SLIRException('Source image was not specified.');
		} // if

		// Set the rest of the parameters
		foreach($params as $name => $value)
		{
			$this->__set($name, $value);
		} // foreach
	}
	
	/**
	 * @since 2.0
	 * @return void
	 */
	final public function __set($name, $value)
	{
		switch($name)
		{
			case 'i':
			case 'image':
			case 'imagePath':
			case 'path':
				$this->setPath($value);
			break;
			
			case 'w':
			case 'width':
				$this->setWidth($value);
			break;

			case 'h':
			case 'height':
				$this->setHeight($value);
			break;
			
			case 'q':
			case 'quality':
				$this->setQuality($value);
			break;
			
			case 'p':
			case 'progressive':
				$this->setProgressive($value);
			break;
			
			case 'b';
			case 'backgroundFillColor':
				$this->setBackgroundFillColor($value);
			break;
			
			case 'c':
			case 'cropRatio':
				$this->setCrop($value);
			break;
		} // switch
	}

	/**
	 * @since 2.0
	 * @return mixed
	 */
	final public function __get($name)
	{
		return $this->$name;
	}

	/**
	 * @since 2.0
	 * @return void
	 */
	private function setWidth($value)
	{
		$this->width	= (int) $value;
	}

	/**
	 * @since 2.0
	 * @return void
	 */
	private function setHeight($value)
	{
		$this->height	= (int) $value;
	}

	/**
	 * @since 2.0
	 * @return void
	 */
	private function setQuality($value)
	{
		$this->quality	= $value;
		if ($this->quality < 0 || $this->quality > 100)
		{
			header('HTTP/1.1 400 Bad Request');
			throw new SLIRException('Quality must be between 0 and 100: ' . $this->quality);
		}
	}

	/**
	 * @param string $value
	 * @return void
	 */
	private function setProgressive($value)
	{
		$this->progressive	= (bool) $value;
	}

	/**
	 * @param string $value
	 * @return void
	 */
	private function setBackgroundFillColor($value)
	{
		$this->background	= preg_replace('/[^0-9a-fA-F]/', '', $value);

		if(strlen($this->background) == 3)
		{
			$this->background = $this->background[0]
				.$this->background[0]
				.$this->background[1]
				.$this->background[1]
				.$this->background[2]
				.$this->background[2];
		}
		else if (strlen($this->background) != 6)
		{
			header('HTTP/1.1 400 Bad Request');
			throw new SLIRException('Background fill color must be in '
				.'hexadecimal format, longhand or shorthand: '
				. $this->background);
		} // if
	}

	/**
	 * @param string $value
	 * @return void
	 */
	private function setCrop($value)
	{
		$delimiters			= preg_quote(self::CROP_RATIO_DELIMITERS);
		$ratio				= preg_split("/[$delimiters]/", (string) urldecode($value));
		if (count($ratio) >= 2)
		{
			if ((float) $ratio[0] == 0 || (float) $ratio[1] == 0)
			{
				header('HTTP/1.1 400 Bad Request');
				throw new SLIRException('Crop ratio must not contain a zero: ' . (string) $value);
			}
			
			$this->cropRatio	= array(
				'width'		=> (float) $ratio[0],
				'height'	=> (float) $ratio[1],
				'ratio'		=> (float) $ratio[0] / (float) $ratio[1]
			);

			// If there was a third part, that is the cropper being specified
			if (count($ratio) >= 3)
			{
				$this->cropper	= (string) $ratio[2];
			}
		}
		else
		{
			header('HTTP/1.1 400 Bad Request');
			throw new SLIRException('Crop ratio must be in width:height'
				. ' format: ' . (string) $value);
		} // if
	}
	
	/**
	 * Determines the parameters to use for resizing
	 *
	 * @since 2.0
	 * @return array
	 */
	private function getParameters()
	{
		if (!$this->isUsingQueryString()) // Using the mod_rewrite version
		{
			return $this->getParametersFromPath();
		}
		else // Using the query string version
		{
			return $_GET;
		}
	}

	/**
	 * For requests that are using the mod_rewrite syntax
	 *
	 * @since 2.0
	 * @return array
	 */
	private function getParametersFromPath()
	{
		$params	= array();

		// The parameters should be the first set of characters after the
		// SLIR path
		$request	= preg_replace('`.*?' . preg_quote(SLIRConfig::$SLIRDir) . '`', '', (string) $_SERVER['REQUEST_URI']);
		$request	= explode('/', trim($request, '/'));

		if (count($request) < 2)
		{
			header('HTTP/1.1 400 Bad Request');
			throw new SLIRException('Not enough parameters were given.', 'Available parameters:
w = Maximum width
h = Maximum height
c = Crop ratio (width:height)
q = Quality (0-100)
b = Background fill color (RRGGBB or RGB)
p = Progressive (0 or 1)

Example usage:
<img src="' . SLIRConfig::$SLIRDir . '/w300-h300-c1:1/path/to/image.jpg" alt="Don\'t forget '
.'your alt text!" />'
			);

		} // if

		// The parameters are separated by hyphens
		$rawParams	= array_filter(explode('-', array_shift($request)));

		// The image path should be all of the remaining values in the array
		$params['i']	= implode('/', $request);

		foreach ($rawParams as $rawParam)
		{
			// The name of each parameter should be the first character of the
			// parameter string
			$name	= $rawParam[0];
			// The value of each parameter should be the remaining characters of
			// the parameter string
			$value	= substr($rawParam, 1, strlen($rawParam) - 1);

			$params[$name]	= $value;
		} // foreach

		$params	= array_filter($params);

		return $params;
	}
	
	/**
	 * Determines if the request is using the mod_rewrite version or the query
	 * string version
	 *
	 * @since 2.0
	 * @return boolean
	 */
	private function isUsingQueryString()
	{
		if (SLIRConfig::$forceQueryString === TRUE)
		{
			return TRUE;
		}
		else if (isset($_SERVER['QUERY_STRING'])
			&& trim($_SERVER['QUERY_STRING']) != ''
			&& count(array_intersect(array('i', 'w', 'h', 'q', 'c', 'b'), array_keys($_GET)))
			)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * @since 2.0
	 * @param string $path
	 */
	private function setPath($path)
	{
		$this->path	= $this->localizePath((string) urldecode($path));

		// Make sure the image path is secure
		if (!$this->isPathSecure())
		{
			header('HTTP/1.1 400 Bad Request');
			throw new SLIRException('Image path may not contain ":", ".'
				. '.", "<", or ">"');
		}
		// Make sure the image file exists
		else if (!$this->pathExists())
		{
			header('HTTP/1.1 404 Not Found');
			throw new SLIRException('Image does not exist: ' . $this->fullPath());
		}
	}
	
	/**
	 * Strips the domain and query string from the path if either is there
	 * @since 2.0
	 * @return string
	 */
	private function localizePath($path)
	{
		return '/content/' . trim($this->stripQueryString($this->stripProtocolAndDomain($path)), '/');
	}
	
	/**
	 * Strips the protocol and domain from the path if it is there
	 * @since 2.0
	 * @return string
	 */
	private function stripProtocolAndDomain($path)
	{
		return preg_replace('/^(?:s?f|ht)tps?:\/\/[^\/]+/i', '', $path);
	}
	
	/**
	 * Strips the query string from the path if it is there
	 * @since 2.0
	 * @return string
	 */
	private function stripQueryString($path)
	{
		return preg_replace('/\?.*/', '', $path);
	}
	
	/**
	 * Checks to see if the path is secure
	 *
	 * For security, directories may not contain ':' and images may not contain
	 * '..', '<', or '>'.
	 *
	 * @since 2.0
	 * @return boolean
	 */
	private function isPathSecure()
	{
		if (strpos(dirname($this->path), ':') || preg_match('/(\.\.|<|>)/', $this->path))
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 * Determines if the path exists
	 *
	 * @since 2.0
	 * @return boolean
	 */
	private function pathExists()
	{
		return is_file($this->fullPath());
	}
	
	/**
	 * @return string
	 * @since 2.0
	 */
	final public function fullPath()
	{
		return SLIRConfig::$documentRoot . $this->path;
	}
	
	/**
	 * @since 2.0
	 * @return boolean
	 */
	final public function isBackground()
	{
		if ($this->background !== NULL)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * @since 2.0
	 * @return boolean
	 */
	final public function isQuality()
	{
		if ($this->quality !== NULL)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * @since 2.0
	 * @return boolean
	 */
	final public function isCropping()
	{
		if ($this->cropRatio['width'] !== NULL && $this->cropRatio['height'] !== NULL)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
}