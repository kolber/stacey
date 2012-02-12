<?php
/**
 * Class definition file for SLIRImage
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

/* $Id: slirimage.class.php 129 2010-12-22 19:43:06Z joe.lencioni $ */
 
/**
 * SLIR image class
 * 
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * $Date: 2010-12-22 13:43:06 -0600 (Wed, 22 Dec 2010) $
 * @version $Revision: 129 $
 * @package SLIR
 */
class SLIRImage
{
	/**
	 * Path to this image file
	 * @var string
	 * @since 2.0
	 */
	private $path;
	
	/**
	 * Image data
	 * @var string
	 * @since 2.0
	 */
	private $data;
	
	/**
	 * Image identifier
	 * @var resource
	 * @since 2.0
	 */
	private $image;
	
	/**
	 * MIME type of this image
	 * @var string
	 * @since 2.0
	 */
	private $mime;
	
	/**
	 * Width of image in pixels
	 * @var integer
	 * @since 2.0
	 */
	private $width;
	
	/**
	 * Height of image in pixels
	 * @var integer
	 * @since 2.0
	 */
	private $height;
	
	/**
	 * Width of cropped image in pixels
	 * @var integer
	 * @since 2.0
	 */
	private $cropWidth;
	
	/**
	 * Height of cropped image in pixels
	 * @var integer
	 * @since 2.0
	 */
	private $cropHeight;

	/**
	 * Name of the cropper to use
	 * @var string
	 * @since 2.0
	 */
	private $cropper;
	
	/**
	 * IPTC data embedded in image
	 * @var array
	 * @since 2.0
	 */
	private $iptc;
	
	/**
	 * Quality of image
	 * @var integer
	 * @since 2.0
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
	public $background;
	
	/**
	 * @since 2.0
	 */
	final public function __construct()
	{
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 * @since 2.0
	 */
	final public function __set($name, $value)
	{
		switch ($name)
		{
			case 'path':
				$this->setPath($value);
			break;
			
			default:
				if (property_exists($this, $name))
				{
					$this->$name	= $value;
				}
			break;
		} // switch
	}
	
	/**
	 * @since 2.0
	 */
	final public function __get($name)
	{
		switch($name)
		{
			case 'data':
				if ($this->data === NULL)
				{
					$this->data	= $this->getData();
				}
				return $this->data;
			break;
			
			default:
				if (property_exists($this, $name))
				{
					return $this->$name;
				}
			break;
		}
	}
	
	/**
	 * @param string $path
	 * @param boolean $loadImage
	 * @since 2.0
	 */
	public function setPath($path, $loadImage = TRUE)
	{
		$this->path	= $path;

		if ($loadImage === TRUE)
		{
			// Set the image info (width, height, mime type, etc.)
			$this->setImageInfoFromFile();

			// Make sure the file is actually an image
			if (!$this->isImage())
			{
				header('HTTP/1.1 400 Bad Request');
				throw new SLIRException('Requested file is not an '
					. 'accepted image type: ' . $this->fullPath());
			} // if
		}
	}
	
	/**
	 * @return float
	 * @since 2.0
	 */
	final public function ratio()
	{
		return $this->width / $this->height;
	}
	
	/**
	 * @return float
	 * @since 2.0
	 */
	final public function cropRatio()
	{
		if ($this->cropHeight != 0)
		{
			return $this->cropWidth / $this->cropHeight;
		}
		else
		{
			return 0;
		}
	}
	
	/**
	 * @return integer
	 * @since 2.0
	 */
	final public function area()
	{
		return $this->width * $this->height;
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
	 * Checks the mime type to see if it is an image
	 *
	 * @since 2.0
	 * @return boolean
	 */
	final public function isImage()
	{
		if (substr($this->mime, 0, 6) == 'image/')
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
	 * @param string $type Can be 'JPEG', 'GIF', or 'PNG'
	 * @return boolean
	 */
	final public function isOfType($type = 'JPEG')
	{
		$method	= "is$type";
		if (method_exists($this, $method) && isset($imageArray['mime']))
		{
			return $this->$method();
		}
	}
	
	/**
	 * @since 2.0
	 * @return boolean
	 */
	final public function isJPEG()
	{
		if ($this->mime == 'image/jpeg')
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
	final public function isGIF()
	{
		if ($this->mime == 'image/gif')
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
	final public function isPNG()
	{
		if (in_array($this->mime, array('image/png', 'image/x-png')))
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
	final public function isAbleToHaveTransparency()
	{
		if ($this->isPNG() || $this->isGIF())
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
	private function isCroppingNeeded()
	{	
		if ($this->cropWidth !== NULL && $this->cropHeight != NULL
			&& ($this->cropWidth < $this->width || $this->cropHeight < $this->height)
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
	 * @return boolean
	 */
	private function isSharpeningDesired()
	{
		if ($this->isJPEG())
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
	 */
	private function setImageInfoFromFile()
	{
		$info = $this->getImageInfoFromFile();
		
		$this->mime		= $info['mime'];
		$this->width	= $info['width'];
		$this->height	= $info['height'];
		if (isset($info['iptc']))
		{
			$this->iptc		= $info['iptc'];
		}
	}
	
	/**
	 * Retrieves information about the image such as width, height, and IPTC info
	 *
	 * @since 2.0
	 * @return array
	 */
	private function getImageInfoFromFile()
	{
		$info	= getimagesize($this->fullPath(), $extraInfo);

		if ($info == FALSE)
		{
			header('HTTP/1.1 400 Bad Request');
			throw new SLIRException('getimagesize failed (source file may not '
				. 'be an image): ' . $this->fullPath());
		}
		
		$info['width']	=& $info[0];
		$info['height']	=& $info[1];
		
		// IPTC
		if(is_array($extraInfo) && isset($extraInfo['APP13']))
		{
			$info['iptc']	= iptcparse($extraInfo['APP13']);
		}

		return $info;
	}
	
	/**
	 * @since 2.0
	 */
	final public function createBlankImage()
	{
		$this->image	= imagecreatetruecolor($this->width, $this->height);
	}
	
	/**
	 * @since 2.0
	 */
	final public function createImageFromFile()
	{
		if ($this->isJPEG())
		{
			$this->image	= ImageCreateFromJpeg($this->fullPath());
		}
		else if ($this->isGIF())
		{
			$this->image	= ImageCreateFromGif($this->fullPath());
		}
		else if ($this->isPNG())
		{
			$this->image	= ImageCreateFromPng($this->fullPath());
		}
	}
	
	/**
	 * Turns on transparency for image if no background fill color is
	 * specified, otherwise, fills background with specified color
	 *
	 * @param boolean $isBackgroundFillOn
	 * @since 2.0
	 */
	final public function background($isBackgroundFillOn, $image = NULL)
	{
		if (!$this->isAbleToHaveTransparency())
		{
			return;
		}
		
		if ($image === NULL)
		{
			$image	= $this->image;
		}
		
		if (!$isBackgroundFillOn)
		{
			// If this is a GIF or a PNG, we need to set up transparency
			$this->transparency($image);
		}
		else
		{
			// Fill the background with the specified color for matting purposes
			$this->fillBackground($image);
		} // if
	}
	
	/**
	 * @since 2.0
	 */
	private function transparency($image)
	{
		imagealphablending($image, FALSE);
		imagesavealpha($image, TRUE);
	}
	
	/**
	 * @since 2.0
	 */
	private function fillBackground($image)
	{
		$background	= imagecolorallocate(
			$image,
			hexdec($this->background[0].$this->background[1]),
			hexdec($this->background[2].$this->background[3]),
			hexdec($this->background[4].$this->background[5])
		);
		
		imagefilledrectangle($image, 0, 0, $this->width, $this->height, $background);
	}

	/**
	 * @since 2.0
	 */
	final public function interlace()
	{
		if ($this->progressive)
		{
			imageinterlace($this->image, 1);
		}
	}

	/**
	 * Gets the name of the class that will be used to determine the crop offset for the image
	 * 
	 * @since 2.0
	 * @param string $className Name of the cropper class name to get
	 * @return string
	 */
	private function getCropperClassName($className = NULL)
	{
		if ($className !== NULL)
		{
			return $className;
		}
		else if ($this->cropper !== NULL)
		{
			return $this->cropper;
		}
		else
		{
			return SLIRConfig::$defaultCropper;
		}
	}

	/**
	 * Gets the class that will be used to determine the crop offset for the image
	 * 
	 * @since 2.0
	 * @param string $className Name of the cropper class to get
	 * @return SLIRCropper
	 */
	final public function getCropperClass($className = NULL)
	{
		$cropClass	= strtolower($this->getCropperClassName($className));
		$fileName	= "croppers/$cropClass.class.php";
		$class		= 'SLIRCropper' . ucfirst($cropClass);
		require_once $fileName;
		return new $class();
	}
	
	/**
	 * Crops the image
	 * 
	 * @since 2.0
	 * @param boolean $isBackgroundFillOn
	 * @return boolean
	 * @todo improve cropping method preference (smart or centered)
	 */
	final public function crop($isBackgroundFillOn)
	{
		if (!$this->isCroppingNeeded())
		{
			return TRUE;
		}
		
		$cropper	= $this->getCropperClass();
		$offset		= $cropper->getCrop($this);
		return $this->cropImage($offset['x'], $offset['y'], $isBackgroundFillOn);
	}
	
	/**
	 * Performs the actual cropping of the image
	 * 
	 * @since 2.0
	 * @param integer $leftOffset Number of pixels from the left side of the image to crop in
	 * @param integer $topOffset Number of pixels from the top side of the image to crop in
	 * @param boolean $isBackgroundFillOn
	 * @return boolean
	 */
	private function cropImage($leftOffset, $topOffset, $isBackgroundFillOn)
	{
		// Set up a blank canvas for our cropped image (destination)
		$cropped	= imagecreatetruecolor(
						$this->cropWidth,
						$this->cropHeight
						);
		
		$this->background($isBackgroundFillOn, $cropped);
						
		// Copy rendered image to cropped image
		ImageCopy(
			$cropped,
			$this->image,
			0,
			0,
			$leftOffset,
			$topOffset,
			$this->width,
			$this->height
		);

		// Replace pre-cropped image with cropped image
		imagedestroy($this->image);
		$this->image	= $cropped;
		unset($cropped);
		
		return TRUE;
	}
	
	/**
	 * Sharpens the image
	 *
	 * @param integer $sharpness
	 * @since 2.0
	 */
	final public function sharpen($sharpness)
	{
		if ($this->isSharpeningDesired())
		{
			imageconvolution(
				$this->image,
				$this->sharpenMatrix($sharpness),
				$sharpness,
				0
			);
		}
	}
	
	/**
	 * @param integer $sharpness
	 * @return array
	 * @since 2.0
	 */
	private function sharpenMatrix($sharpness)
	{
		return array(
			array(-1, -2, -1),
			array(-2, $sharpness + 12, -2),
			array(-1, -2, -1)
		);
	}
	
	/**
	 * @since 2.0
	 * @return array
	 */
	final public function cacheParameters()
	{
		return array(
			'path'			=> $this->fullPath(),
			'width'			=> $this->width,
			'height'		=> $this->height,
			'cropWidth'		=> $this->cropWidth,
			'cropHeight'	=> $this->cropHeight,
			'iptc'			=> $this->iptc,
			'quality'		=> $this->quality,
			'progressive'	=> $this->progressive,
			'background'	=> $this->background,
			'cropper'		=> $this->getCropperClassName(),
		);
	}
	
	/**
	 * @since 2.0
	 * @return string
	 */
	private function getData()
	{
		ob_start(NULL);
			if (!$this->output())
			{
				return FALSE;
			}
			$data	= ob_get_contents();
		ob_end_clean();
		
		return $data;
	}
	
	/**
	 * @since 2.0
	 * @return boolean
	 */
	private function output($filename = NULL)
	{
		if ($this->isJPEG())
		{
			return imagejpeg($this->image, $filename, $this->quality);
		}		
		else if ($this->isPNG())
		{
			return imagepng($this->image, $filename, $this->quality);
		}			
		else if ($this->isGIF())
		{
			return imagegif($this->image, $filename, $this->quality);
		}			
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * @since 2.0
	 * @return integer
	 */
	final public function fileSize()
	{
		return strlen($this->data);
	}
	
	/**
	 * @since 2.0
	 * @return boolean
	 */
	final public function destroyImage()
	{
		return imagedestroy($this->image);
	}
	
}