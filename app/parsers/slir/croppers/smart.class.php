<?php
/**
 * Class definition file for the smart SLIR cropper
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

/* $Id: smart.class.php 113 2010-12-21 15:21:32Z joe.lencioni $ */

require_once 'slircropper.interface.php';
 
/**
 * Smart SLIR cropper
 * 
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * $Date: 2010-12-21 09:21:32 -0600 (Tue, 21 Dec 2010) $
 * @version $Revision: 113 $
 * @package SLIR
 * @subpackage Croppers
 */
class SLIRCropperSmart implements SLIRCropper
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
	 * Determines the optimal number of rows in from the top or left to crop
	 * the source image
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @return integer|boolean
	 */
	private function cropSmartOffsetRows(SLIRImage $image)
	{
		// @todo Change this method to resize image, determine offset, and then extrapolate the actual offset based on the image size difference. Then we can cache the offset in APC (all just like we are doing for face detection)

		if ($this->shouldCropTopAndBottom($image))
		{
			$length				= $image->cropHeight;
			$lengthB			= $image->cropWidth;
			$originalLength		= $image->height;
		}
		else
		{
			$length				= $image->cropWidth;
			$lengthB			= $image->cropHeight;
			$originalLength		= $image->width;
		}
		
		// To smart crop an image, we need to calculate the difference between
		// each pixel in each row and its adjacent pixels. Add these up to
		// determine how interesting each row is. Based on how interesting each
		// row is, we can determine whether or not to discard it. We start with
		// the closest row and the farthest row and then move on from there.
		
		// All colors in the image will be stored in the global colors array.
		// This array will also include information about each pixel's
		// interestingness.
		// 
		// For example (rough representation):
		// 
		// $colors = array(
		//   x1	=> array(
		//   	x1y1	=> array(
		//			'lab'	=> array(l, a, b),
		//			'dE'	=> array(TL, TC, TR, LC, LR, BL, BC, BR),
		//			'i'		=> computedInterestingness
		//   	),
		//		x1y2	=> array( ... ),
		//		...
		//   ),
		//   x2	=> array( ... ),
		//   ...
		// );
		global $colors;
		$colors	= array();
		
		// Offset will remember how far in from each side we are in the
		// cropping game
		$offset	= array(
			'near'	=> 0,
			'far'	=> 0,
		);
		
		$rowsToCrop	= $originalLength - $length;
		
		// $pixelStep will sacrifice accuracy for memory and speed. Essentially
		// it acts as a spot-checker and scales with the size of the cropped area
		$pixelStep	= round(sqrt($rowsToCrop * $lengthB) / 10);
		
		// We won't save much speed if the pixelStep is between 4 and 1 because
		// we still need to sample adjacent pixels
		if ($pixelStep < 4)
		{
			$pixelStep = 1;
		}
		
		$tolerance	= 0.5;
		$upperTol	= 1 + $tolerance;
		$lowerTol	= 1 / $upperTol;
		
		// Fight the near and far rows. The stronger will remain standing.
		$returningChampion	= NULL;
		$ratio				= 1;
		for ($rowsCropped = 0; $rowsCropped < $rowsToCrop; ++$rowsCropped)
		{
			$a	= $this->rowInterestingness($image, $offset['near'], $pixelStep, $originalLength);
			$b	= $this->rowInterestingness($image, $originalLength - $offset['far'] - 1, $pixelStep, $originalLength);
			
			if ($a == 0 && $b == 0)
			{
				$ratio = 1;
			}
			else if ($b == 0)
			{
				$ratio = 1 + $a;
			}
			else
			{
				$ratio	= $a / $b;
			}
			
			if ($ratio > $upperTol)
			{
				++$offset['far'];
				
				// Fightback. Winning side gets to go backwards through fallen rows
				// to see if they are stronger
				if ($returningChampion == 'near')
				{
					$offset['near']	-= ($offset['near'] > 0) ? 1 : 0;
				}
				else
				{
					$returningChampion	= 'near';
				}
			}
			else if ($ratio < $lowerTol)
			{
				++$offset['near'];
				
				if ($returningChampion == 'far')
				{
					$offset['far']	-= ($offset['far'] > 0) ? 1 : 0;
				}
				else
				{
					$returningChampion	= 'far';
				}
			}
			else
			{
				// There is no strong winner, so discard rows from the side that
				// has lost the fewest so far. Essentially this is a draw.
				if ($offset['near'] > $offset['far'])
				{
					++$offset['far'];
				}
				else // Discard near
				{
					++$offset['near'];
				}
					
				// No fightback for draws
				$returningChampion	= NULL;
			} // if
			
		} // for
		
		// Bounceback for potentially important details on the edge.
		// This may possibly be better if the winning side fights a hard final
		// push multiple-rows-at-stake battle where it stands the chance to gain
		// ground.
		if ($ratio > (1 + ($tolerance * 1.25)))
		{
			$offset['near'] -= round($length * .03);
		}
		else if ($ratio < (1 / (1 + ($tolerance * 1.25))))
		{
			$offset['near']	+= round($length * .03);
		}
			
		return min($rowsToCrop, max(0, $offset['near']));
	}
	
	/**
	 * Calculate the interestingness value of a row of pixels
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @param integer $row 
	 * @param integer $pixelStep Number of pixels to jump after each step when comparing interestingness
	 * @param integer $originalLength Number of rows in the original image
	 * @return float
	 */
	private function rowInterestingness(SLIRImage $image, $row, $pixelStep, $originalLength)
	{
		$interestingness	= 0;
		$max				= 0;
		
		if ($this->shouldCropTopAndBottom($image))
		{
			for ($totalPixels = 0; $totalPixels < $image->width; $totalPixels += $pixelStep)
			{
				$i	= $this->pixelInterestingness($image, $totalPixels, $row);
				
				// Content at the very edge of an image tends to be less interesting than
				// content toward the center, so we give it a little extra push away from the edge
				//$i					+= min($row, $originalLength - $row, $originalLength * .04);
				
				$max				= max($i, $max);
				$interestingness	+= $i;
			}
		}
		else
		{
			for ($totalPixels = 0; $totalPixels < $image->height; $totalPixels += $pixelStep)
			{
				$i	= $this->pixelInterestingness($image, $row, $totalPixels);
				
				// Content at the very edge of an image tends to be less interesting than
				// content toward the center, so we give it a little extra push away from the edge
				//$i					+= min($row, $originalLength - $row, $originalLength * .04);
				
				$max				= max($i, $max);
				$interestingness	+= $i;
			}
		}
		
		return $interestingness + (($max - ($interestingness / ($totalPixels / $pixelStep))) * ($totalPixels / $pixelStep));
	}
	
	/**
	 * Get the interestingness value of a pixel
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @param integer $x x-axis position of pixel to calculate
	 * @param integer $y y-axis position of pixel to calculate
	 * @return float
	 */
	private function pixelInterestingness(SLIRImage $image, $x, $y)
	{
		global $colors;
		
		if (!isset($colors[$x][$y]['i']))
		{
			// Ensure this pixel's color information has already been loaded
			$this->loadPixelInfo($image, $x, $y);
			
			// Calculate each neighboring pixel's Delta E in relation to this
			// pixel
			$this->calculateDeltas($image, $x, $y);
			
			// Calculate the interestingness of this pixel based on neighboring
			// pixels' Delta E in relation to this pixel
			$this->calculateInterestingness($x, $y);
		} // if
		
		return $colors[$x][$y]['i'];
	}
	
	/**
	 * Load the color information of the requested pixel into the $colors array
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @param integer $x x-axis position of pixel to calculate
	 * @param integer $y y-axis position of pixel to calculate
	 * @return boolean
	 */
	private function loadPixelInfo(SLIRImage $image, $x, $y)
	{
		if ($x < 0 || $x >= $image->width
			|| $y < 0 || $y >= $image->height)
			{
				return FALSE;
			}
				
		global $colors;
		
		if (!isset($colors[$x]))
		{
			$colors[$x]	= array();
		}
			
		if (!isset($colors[$x][$y]))
		{
			$colors[$x][$y]	= array();
		}
		
		if (!isset($colors[$x][$y]['i']) && !isset($colors[$x][$y]['lab']))
		{
			$colors[$x][$y]['lab']	= $this->evaluateColor(imagecolorat($image->image, $x, $y));
		}
			
		return TRUE;
	}
	
	/**
	 * Calculates each adjacent pixel's Delta E in relation to the pixel requested
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @param integer $x x-axis position of pixel to calculate
	 * @param integer $y y-axis position of pixel to calculate
	 * @return boolean
	 */
	private function calculateDeltas(SLIRImage $image, $x, $y)
	{
		// Calculate each adjacent pixel's Delta E in relation to the current
		// pixel (top left, top center, top right, center left, center right,
		// bottom left, bottom center, and bottom right)
		
		global $colors;
		
		if (!isset($colors[$x][$y]['dE']['d-1-1']))
		{
			$this->calculateDelta($image, $x, $y, -1, -1);
		}
		if (!isset($colors[$x][$y]['dE']['d0-1']))
		{
			$this->calculateDelta($image, $x, $y, 0, -1);
		}
		if (!isset($colors[$x][$y]['dE']['d1-1']))
		{
			$this->calculateDelta($image, $x, $y, 1, -1);
		}
		if (!isset($colors[$x][$y]['dE']['d-10']))
		{
			$this->calculateDelta($image, $x, $y, -1, 0);
		}
		if (!isset($colors[$x][$y]['dE']['d10']))
		{
			$this->calculateDelta($image, $x, $y, 1, 0);
		}
		if (!isset($colors[$x][$y]['dE']['d-11']))
		{
			$this->calculateDelta($image, $x, $y, -1, 1);
		}
		if (!isset($colors[$x][$y]['dE']['d01']))
		{
			$this->calculateDelta($image, $x, $y, 0, 1);
		}
		if (!isset($colors[$x][$y]['dE']['d11']))
		{
			$this->calculateDelta($image, $x, $y, 1, 1);
		}
		
		return TRUE;
	}
	
	/**
	 * Calculates and stores requested pixel's Delta E in relation to comparison pixel
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @param integer $x1 x-axis position of pixel to calculate
	 * @param integer $y1 y-axis position of pixel to calculate
	 * @param integer $xMove number of pixels to move on the x-axis to find comparison pixel
	 * @param integer $yMove number of pixels to move on the y-axis to find comparison pixel
	 * @return boolean
	 */
	private function calculateDelta(SLIRImage $image, $x1, $y1, $xMove, $yMove)
	{
		$x2	= $x1 + $xMove;
		$y2 = $y1 + $yMove;
		
		// Pixel is outside of the image, so we cant't calculate the Delta E
		if ($x2 < 0 || $x2 >= $image->width
			|| $y2 < 0 || $y2 >= $image->height)
			{
				return NULL;
			}
		
		global $colors;
		
		if (!isset($colors[$x1][$y1]['lab']))
		{
			$this->loadPixelInfo($image, $x1, $y1);
		}
		if (!isset($colors[$x2][$y2]['lab']))
		{
			$this->loadPixelInfo($image, $x2, $y2);
		}
		
		$delta	= $this->deltaE($colors[$x1][$y1]['lab'], $colors[$x2][$y2]['lab']);
		
		$colors[$x1][$y1]['dE']["d$xMove$yMove"]	= $delta;
		
		$x2Move	= $xMove * -1;
		$y2Move	= $yMove * -1;
		$colors[$x2][$y2]['dE']["d$x2Move$y2Move"]	=& $colors[$x1][$y1]['dE']["d$xMove$yMove"];
		
		return TRUE;
	}
	
	/**
	 * Calculates and stores a pixel's overall interestingness value
	 * 
	 * @since 2.0
	 * @param integer $x x-axis position of pixel to calculate
	 * @param integer $y y-axis position of pixel to calculate
	 * @return boolean
	 */
	private function calculateInterestingness($x, $y)
	{
		global $colors;
		
		// The interestingness is the average of the pixel's Delta E values
		$colors[$x][$y]['i']	= array_sum($colors[$x][$y]['dE'])
			/ count(array_filter($colors[$x][$y]['dE'], 'is_numeric'));
		
		return TRUE;
	}
	
	/**
	 * @since 2.0
	 * @param integer $int
	 * @return array
	 */
	private function evaluateColor($int)
	{
		$rgb	= $this->colorIndexToRGB($int);
		$xyz	= $this->RGBtoXYZ($rgb);
		$lab	= $this->XYZtoHunterLab($xyz);
		
		return $lab;
	}
	
	/**
	 * @since 2.0
	 * @param integer $int
	 * @return array
	 */
	private function colorIndexToRGB($int)
	{
		$a	= (255 - (($int >> 24) & 0xFF)) / 255;
		$r	= (($int >> 16) & 0xFF) * $a;
		$g	= (($int >> 8) & 0xFF) * $a;
		$b	= ($int & 0xFF) * $a;
		return array('r' => $r, 'g' => $g, 'b' => $b);
	}
	
	/**
	 * @since 2.0
	 * @param array $rgb
	 * @return array XYZ
	 * @link http://easyrgb.com/index.php?X=MATH&H=02#text2
	 */
	private function RGBtoXYZ($rgb)
	{
		$r	= $rgb['r'] / 255;
		$g	= $rgb['g'] / 255;
		$b	= $rgb['b'] / 255;
		
		if ($r > 0.04045)
		{
			$r	= pow((($r + 0.055) / 1.055), 2.4);
		}
		else
		{
			$r	= $r / 12.92;
		}
		
		if ($g > 0.04045)
		{
			$g	= pow((($g + 0.055) / 1.055), 2.4);
		}
		else
		{
			$g	= $g / 12.92;
		}
		
		if ($b > 0.04045)
		{
			$b	= pow((($b + 0.055) / 1.055), 2.4);
		}
		else
		{
			$b	= $b / 12.92;
		}
			
		$r	*= 100;
		$g	*= 100;
		$b	*= 100;

		//Observer. = 2Β°, Illuminant = D65
		$x = $r * 0.4124 + $g * 0.3576 + $b * 0.1805;
		$y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
		$z = $r * 0.0193 + $g * 0.1192 + $b * 0.9505;
		
		return array('x' => $x, 'y' => $y, 'z' => $z);
	}
	
	/**
	 * @link http://www.easyrgb.com/index.php?X=MATH&H=05#text5
	 */ 
	private function XYZtoHunterLab($xyz)
	{
		if ($xyz['y'] == 0)
		{
			return array('l' => 0, 'a' => 0, 'b' => 0);
		}
		
		$l	= 10 * sqrt($xyz['y']);
		$a	= 17.5 * ( ( ( 1.02 * $xyz['x'] ) - $xyz['y']) / sqrt( $xyz['y'] ) );
		$b	= 7 * ( ( $xyz['y'] - ( 0.847 * $xyz['z'] ) ) / sqrt( $xyz['y'] ) );
		
		return array('l' => $l, 'a' => $a, 'b' => $b);
	}
	
	/**
	 * Converts a color from RGB colorspace to CIE-L*ab colorspace
	 * @since 2.0
	 * @param array $xyz
	 * @return array LAB
	 * @link http://www.easyrgb.com/index.php?X=MATH&H=05#text5
	 */
	private function XYZtoCIELAB($xyz)
	{
		$refX	= 100;
		$refY	= 100;
		$refZ	= 100;
		
		$X = $xyz['x'] / $refX;
		$Y = $xyz['y'] / $refY;
		$Z = $xyz['z'] / $refZ;
		
		if ( $X > 0.008856 )
		{
			$X = pow($X, 1/3);
		}
		else
		{
			$X = ( 7.787 * $X ) + ( 16 / 116 );
		}
			
		if ( $Y > 0.008856 ) 
		{
			$Y = pow($Y, 1/3);
		}
		else
		{
			$Y = ( 7.787 * $Y ) + ( 16 / 116 );
		}
			
		if ( $Z > 0.008856 )
		{
			$Z = pow($Z, 1/3);
		}
		else
		{
			$Z = ( 7.787 * $Z ) + ( 16 / 116 );
		}

		$l = ( 116 * $Y ) - 16;
		$a = 500 * ( $X - $Y );
		$b = 200 * ( $Y - $Z );
		
		return array('l' => $l, 'a' => $a, 'b' => $b);
	}
	
	private function deltaE($lab1, $lab2)
	{
		return sqrt( ( pow( $lab1['l'] - $lab2['l'], 2 ) )
               + ( pow( $lab1['a'] - $lab2['a'], 2 ) )
               + ( pow( $lab1['b'] - $lab2['b'], 2 ) ) );
	}
	
	/**
	 * Compute the Delta E 2000 value of two colors in the LAB colorspace
	 * 
	 * @link http://en.wikipedia.org/wiki/Color_difference#CIEDE2000
	 * @link http://easyrgb.com/index.php?X=DELT&H=05#text5
	 * @since 2.0
	 * @param array $lab1 LAB color array
	 * @param array $lab2 LAB color array
	 * @return float
	 */
	private function deltaE2000($lab1, $lab2)
	{
		$weightL	= 1; // Lightness
		$weightC	= 1; // Chroma
		$weightH	= 1; // Hue
		
		$xC1 = sqrt( $lab1['a'] * $lab1['a'] + $lab1['b'] * $lab1['b'] );
		$xC2 = sqrt( $lab2['a'] * $lab2['a'] + $lab2['b'] * $lab2['b'] );
		$xCX = ( $xC1 + $xC2 ) / 2;
		$xGX = 0.5 * ( 1 - sqrt( ( pow($xCX, 7) ) / ( ( pow($xCX, 7) ) + ( pow(25, 7) ) ) ) );
		$xNN = ( 1 + $xGX ) * $lab1['a'];
		$xC1 = sqrt( $xNN * $xNN + $lab1['b'] * $lab1['b'] );
		$xH1 = $this->LABtoHue( $xNN, $lab1['b'] );
		$xNN = ( 1 + $xGX ) * $lab2['a'];
		$xC2 = sqrt( $xNN * $xNN + $lab2['b'] * $lab2['b'] );
		$xH2 = $this->LABtoHue( $xNN, $lab2['b'] );
		$xDL = $lab2['l'] - $lab1['l'];
		$xDC = $xC2 - $xC1;
		
		if ( ( $xC1 * $xC2 ) == 0 )
		{
		   $xDH = 0;
		}
		else
		{
			$xNN = round( $xH2 - $xH1, 12 );
			if ( abs( $xNN ) <= 180 )
			{
				$xDH = $xH2 - $xH1;
			}
			else
			{
				if ( $xNN > 180 )
				{
					$xDH = $xH2 - $xH1 - 360;
				}
				else
				{
					$xDH = $xH2 - $xH1 + 360;
				}
			} // if
		} // if

		$xDH = 2 * sqrt( $xC1 * $xC2 ) * sin( rad2deg( $xDH / 2 ) );
		$xLX = ( $lab1['l'] + $lab2['l'] ) / 2;
		$xCY = ( $xC1 + $xC2 ) / 2;

		if ( ( $xC1 *  $xC2 ) == 0 )
		{
			$xHX = $xH1 + $xH2;
		}
		else
		{
			$xNN = abs( round( $xH1 - $xH2, 12 ) );
			if ( $xNN >  180 )
			{
				if ( ( $xH2 + $xH1 ) <  360 )
				{
					$xHX = $xH1 + $xH2 + 360;
				}
				else
				{
					$xHX = $xH1 + $xH2 - 360;
				}
			}
			else
			{
				$xHX = $xH1 + $xH2;
			} // if
			$xHX /= 2;
		} // if

		$xTX = 1 - 0.17 * cos( rad2deg( $xHX - 30 ) )
			+ 0.24 * cos( rad2deg( 2 * $xHX ) )
			+ 0.32 * cos( rad2deg( 3 * $xHX + 6 ) )
			- 0.20 * cos( rad2deg( 4 * $xHX - 63 ) );
					   
		$xPH = 30 * exp( - ( ( $xHX  - 275 ) / 25 ) * ( ( $xHX  - 275 ) / 25 ) );
		$xRC = 2 * sqrt( ( pow($xCY, 7) ) / ( ( pow($xCY, 7) ) + ( pow(25, 7) ) ) );
		$xSL = 1 + ( ( 0.015 * ( ( $xLX - 50 ) * ( $xLX - 50 ) ) )
			/ sqrt( 20 + ( ( $xLX - 50 ) * ( $xLX - 50 ) ) ) );
		$xSC = 1 + 0.045 * $xCY;
		$xSH = 1 + 0.015 * $xCY * $xTX;
		$xRT = - sin( rad2deg( 2 * $xPH ) ) * $xRC;
		$xDL = $xDL / $weightL * $xSL;
		$xDC = $xDC / $weightC * $xSC;
		$xDH = $xDH / $weightH * $xSH;

		$delta	= sqrt( pow($xDL, 2) + pow($xDC, 2) + pow($xDH, 2) + $xRT * $xDC * $xDH );
		return (is_nan($delta)) ? 1 : $delta / 100;
	}
	
	/**
	 * Compute the Delta CMC value of two colors in the LAB colorspace
	 * 
	 * @since 2.0
	 * @param array $lab1 LAB color array
	 * @param array $lab2 LAB color array
	 * @return float
	 * @link http://easyrgb.com/index.php?X=DELT&H=06#text6
	 */
	private function deltaCMC($lab1, $lab2)
	{
		// if $weightL is 2 and $weightC is 1, it means that the lightness
		// will contribute half as much importance to the delta as the chroma
		$weightL	= 2; // Lightness
		$weightC	= 1; // Chroma
		
		$xC1	= sqrt( ( pow($lab1['a'], 2) ) + ( pow($lab1['b'], 2) ) );
		$xC2	= sqrt( ( pow($lab2['a'], 2) ) + ( pow($lab2['b'], 2) ) );
		$xff	= sqrt( ( pow($xC1, 4) ) / ( ( pow($xC1, 4) ) + 1900 ) );
		$xH1	= $this->LABtoHue( $lab1['a'], $lab1['b'] );
		
		if ( $xH1 < 164 || $xH1 > 345 )
		{
			$xTT	= 0.36 + abs( 0.4 * cos( deg2rad(  35 + $xH1 ) ) );
		}
		else
		{
			$xTT	= 0.56 + abs( 0.2 * cos( deg2rad( 168 + $xH1 ) ) );
		}
		
		if ( $lab1['l'] < 16 )
		{
			$xSL	= 0.511;
		}
		else
		{
			$xSL	= ( 0.040975 * $lab1['l'] ) / ( 1 + ( 0.01765 * $lab1['l'] ) );
		}
			
		$xSC = ( ( 0.0638 * $xC1 ) / ( 1 + ( 0.0131 * $xC1 ) ) ) + 0.638;
		$xSH = ( ( $xff * $xTT ) + 1 - $xff ) * $xSC;
		$xDH = sqrt( pow( $lab2['a'] - $lab1['a'], 2 ) + pow( $lab2['b'] - $lab1['b'], 2 ) - pow( $xC2 - $xC1, 2 ) );
		$xSL = ( $lab2['l'] - $lab1['l'] ) / $weightL * $xSL;
		$xSC = ( $xC2 - $xC1 ) / $weightC * $xSC;
		$xSH = $xDH / $xSH;
		
		$delta = sqrt( pow($xSL, 2) + pow($xSC, 2) + pow($xSH, 2) );
		return (is_nan($delta)) ? 1 : $delta;
	}
	
	/**
	 * @since 2.0
	 * @param integer $a
	 * @param integer $b
	 * @return CIE-HΒ° value
	 */
	private function LABtoHue($a, $b)
	{
		$bias	= 0;
		
		if ($a >= 0 && $b == 0) return 0;
		if ($a <  0 && $b == 0) return 180;
		if ($a == 0 && $b >  0) return 90;
		if ($a == 0 && $b <  0) return 270;
		if ($a >  0 && $b >  0) $bias = 0;
		if ($a <  0           ) $bias = 180;
		if ($a >  0 && $b <  0) $bias = 360;
		
		return (rad2deg(atan($b / $a)) + $bias);
	}

	/**
	 * Calculates the crop offset using an algorithm that tries to determine
	 * the most interesting portion of the image to keep.
	 * 
	 * @since 2.0
	 * @param SLIRImage $image
	 * @return array Associative array with the keys of x and y that specify the top left corner of the box that should be cropped
	 */
	public function getCrop(SLIRImage $image)
	{
		// Try contrast detection
		$o	= $this->cropSmartOffsetRows($image);

		$crop	= array(
			'x'	=> 0,
			'y'	=> 0,
		);

		if ($o === FALSE)
		{
			return TRUE;
		}
		else if ($this->shouldCropTopAndBottom($image))
		{
			$crop['y']	= $o;
		}
		else
		{
			$crop['x']	= $o;
		}
		
		return $crop;
	}

}