<?php
/**
 * Class definition file for the face SLIR cropper
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
 * @copyright Copyright Β© 2010, Joe Lencioni
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public
 * License version 3 (GPLv3)
 * @since 2.0
 * @package SLIR
 * @subpackage Croppers
 */

/* $Id: face.class.php 113 2010-12-21 15:21:32Z joe.lencioni $ */

require_once 'slircropper.interface.php';
 
/**
 * Face SLIR cropper
 * 
 * Calculates the crop offset using face detection
 * 
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * $Date: 2010-12-21 09:21:32 -0600 (Tue, 21 Dec 2010) $
 * @version $Revision: 113 $
 * @package SLIR
 * @subpackage Croppers
 */
class SLIRCropperFace implements SLIRCropper
{
	/**
	 * Determines if the top and bottom need to be cropped
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @return boolean
	 */
	private function shouldCropTopAndBottom(SLIRImage $image)
	{
		if ($image->cropRatio() > $image->ratio())
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Calculates the crop offset using face detection
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @return array Associative array with the keys of x and y that specify the top left corner of the box that should be cropped
	 */
	private function getCrop(SLIRImage $image)
	{
		// This is way too slow to not have apc caching face detection data for us
		if (!function_exists('apc_fetch'))
		{
			return NULL;
		}

		$key		= 'slirface_' . md5($image->path);
		$cached		= (function_exists('apc_fetch')) ? apc_fetch($key) : FALSE;

		if ($cached === FALSE)
		{
			require_once '../facedetector/facedetector.class.php';
			$detector	= new SLIRFaceDetector();
			
			// Make the image smaller for face detection so it will work faster
			// @todo this should be done before resizing
			$a			= $image->width * $image->height;
			$smallerA	= pow(80, 2);
			$ratio		= sqrt($smallerA) / sqrt($a);

			if ($ratio < 1)
			{
				$smallerW	= $image->width * $ratio;
				$smallerH	= $image->height * $ratio;
				$smaller	= imagecreatetruecolor($smallerW, $smallerH);
				imagecopyresampled($smaller, $image->image, 0, 0, 0, 0, $smallerW, $smallerH, $image->width, $image->height);
			}
			else
			{
				$smaller	= $image->image;
				$smallerW	= $image->width;
			}

			// convert to grayscale
			//imagefilter($smaller, IMG_FILTER_GRAYSCALE);

			// for some reason, this grayscale conversion causes the final image to be in
			// grayscale if the size is small. but why?

			// load up our detection data
			$cascade	= json_decode(file_get_contents(SLIR_DOCUMENT_ROOT . SLIR_DIR . '/facedetector/face.json'), TRUE);

			// detect faces
			$faces			= $detector->detect_objects($smaller, $cascade, 5, 1);
			if (function_exists('apc_store'))
			{
				apc_store($key, array('width' => $smallerW, 'faces' => $faces));
			}
		}
		else // Face detection data was cached
		{
			$faces	= $cached['faces'];
			$ratio	= $cached['width'] / $image->width;
		}

		if (count($faces) > 0)
		{
			$confidenceThreshold	= 10;

			if ($this->shouldCropTopAndBottom($image))
			{

				/* // this outlines the faces in red
				$color = imagecolorallocate($image->image, 255, 0, 0); //red
				foreach($faces as $face)
				{
					if ($face['confidence'] > $confidenceThreshold)
					{
						$face['x']		/= $ratio;
						$face['y']		/= $ratio;
						$face['height']	/= $ratio;
						$face['width']	/= $ratio;
						imagerectangle($image->image, $face['x'], $face['y'], $face['x']+$face['width'], $face['y']+ $face['height'], $color);
					}
				}

				header('Content-type: image/jpeg');
				imagejpeg($image->image);
				exit();
				*/
				
				// @todo extract this into its own function (and generalize it for top/bottom cropping as well as left/right cropping)
				$highest	= NULL;
				$lowest		= NULL;
				foreach($faces as $face)
				{
					if ($face['confidence'] > $confidenceThreshold)
					{
						$face['x']		/= $ratio;
						$face['y']		/= $ratio;
						$face['height']	/= $ratio;
						$face['width']	/= $ratio;

						if ($highest === NULL || $face['y'] < $highest)
						{
							$highest	= $face['y'];
						}
						if ($lowest	=== NULL || $face['y'] + $face['height'] > $lowest)
						{
							$lowest		= $face['y'] + $face['height'];
						}
					}

					if ($highest !== NULL && $lowest !== NULL)
					{
						$midpoint		= $highest + (($lowest - $highest) / 2);
						return min($image->height - $image->cropHeight, max(0, $midpoint - ($image->cropHeight / 2)));
					}
				}
				
			}
			else
			{
				$leftest	= NULL;
				$rightest	= NULL;
				foreach($faces as $face)
				{
					if ($face['confidence'] > $confidenceThreshold)
					{
						$face['x']		/= $ratio;
						$face['y']		/= $ratio;
						$face['height']	/= $ratio;
						$face['width']	/= $ratio;

						if ($leftest === NULL || $face['x'] < $leftest)
						{
							$leftest	= $face['x'];
						}
						if ($rightest	=== NULL || $face['x'] + $face['width'] > $rightest)
						{
							$rightest	= $face['x'] + $face['width'];
						}
					}


					if ($leftest !== NULL && $rightest !== NULL)
					{
						$midpoint		= $leftest + (($rightest - $leftest) / 2);
						return min($image->width - $image->cropWidth, max(0, $midpoint - ($image->cropWidth / 2)));
					}
				}
			}
		}
		else
		{
			// @todo fallback to another cropper
			return NULL;
		}
	}
}