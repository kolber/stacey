<?php
/**
 * Interface definition file for SLIR croppers
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

/* $Id: slircropper.interface.php 113 2010-12-21 15:21:32Z joe.lencioni $ */
 
/**
 * SLIR cropper interface
 * 
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * $Date: 2010-12-21 09:21:32 -0600 (Tue, 21 Dec 2010) $
 * @version $Revision: 113 $
 * @package SLIR
 * @subpackage Croppers
 */
interface SLIRCropper
{
	/**
	 * @since 2.0
	 * @param SLIRImage $image
	 * @return array Associative array with the keys of x, y, width, and height that specify the box that should be cropped
	 */
	public function getCrop(SLIRImage $image);
}