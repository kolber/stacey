<?php
/**
 * Configuration file for SLIR (Smart Lencioni Image Resizer)
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
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License version 3 (GPLv3)
 * @since 2.0
 * @package SLIR
 */
 
/* $Id: slirconfig-sample.class.php 123 2010-12-21 18:58:03Z joe.lencioni $ */

require_once 'slirconfigdefaults.class.php';
require_once '../../../extensions/config.php';

/**
 * SLIR Config Class
 * 
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * $Date: 2010-12-21 12:58:03 -0600 (Tue, 21 Dec 2010) $
 * @version $Revision: 123 $
 * @package SLIR
 */
class SLIRConfig extends SLIRConfigDefaults
{
	// override configuration values here

  public static $SLIRDir = 'render';

	public static function init()
	{
    self::$cacheDir = '../../../'.Config::$cache_folder.'/images';
    self::$documentRoot	= '../../..';
		// This must be the last line of this function
		parent::init();
	}
}

SLIRConfig::init();
