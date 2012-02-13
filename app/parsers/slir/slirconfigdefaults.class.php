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
 
/* $Id: slirconfigdefaults.class.php 126 2010-12-22 18:43:22Z joe.lencioni $ */

/**
 * SLIR Config Class
 * 
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * $Date: 2010-12-22 12:43:22 -0600 (Wed, 22 Dec 2010) $
 * @version $Revision: 126 $
 * @package SLIR
 */
class SLIRConfigDefaults
{
	/**
	 * How long (in seconds) the web browser should use its cached copy of the image
	 * before checking with the server for a new version
	 * 
	 * @since 2.0
	 * @var integer
	 */
	public static $browserCacheTTL	= 604800; // 7 * 24 * 60 * 60

	/**
	 * Whether we should use the faster, symlink-based request cache as a first
	 * line cache
	 * 
	 * @since 2.0
	 * @var boolean
	 */
	public static $useRequestCache	= TRUE;

	/**
	 * Whether EXIF information should be copied from the source image
	 * 
	 * @since 2.0
	 * @var boolean
	 */
	public static $copyEXIF	= FALSE;

	/**
	 * How much memory (in megabytes) SLIR is allowed to allocate for memory-intensive processes such as rendering
	 *
	 * @since 2.0
	 * @var integer
	 */
	public static $maxMemoryToAllocate	= 100;

	/**
	 * Default quality setting to use if quality is not specified in the request.
	 * Ranges from 0 (worst quality, smaller file) to 100 (best quality, largest
	 * filesize).
	 * 
	 * @since 2.0
	 * @var integer
	 */
	public static $defaultQuality	= 80;

	/**
	 * Default crop mode setting to use if crop mode is not specified in the request.
	 * 
	 * Possible values are:
	 * SLIR::CROP_CLASS_CENTERED
	 * SLIR::CROP_CLASS_TOP_CENTERED
	 * SLIR::CROP_CLASS_SMART
	 * SLIR::CROP_CLASS_FACE (not finished)
	 * 
	 * @since 2.0
	 * @var string
	 */
	public static $defaultCropper	= SLIR::CROP_CLASS_CENTERED;

	/**
	 * Default setting for whether JPEGs should be progressive JPEGs (interlaced)
	 * or not.
	 * 
	 * @since 2.0
	 * @var boolean
	 */
	public static $defaultProgressiveJPEG	= TRUE;

	/**
	 * Whether SLIR should log errors
	 *
	 * @since 2.0
	 * @var boolean
	 */
	public static $logErrors	= TRUE;

	/**
	 * Whether SLIR should generate and output images from error messages
	 * 
	 * @since 2.0
	 * @var boolean
	 */
	public static $errorImages	= TRUE;

	/**
	 * Absolute path to the web root (location of files when visiting
	 * http://domainname.com/) (no trailing slash)
	 * 
	 * @since 2.0
	 * @var string
	 */
	public static $documentRoot	= NULL;

	/**
	 * Path to SLIR (no trailing slash)
	 * 
	 * @since 2.0
	 * @var string
	 */
	public static $SLIRDir	= NULL;

	/**
	 * Name of directory to store cached files in (no trailing slash)
	 * 
	 * @since 2.0
	 * @var string
	 */
	public static $cacheDirName	= '/cache';

	/**
	 * Absolute path to cache directory. This directory must be world-readable,
	 * writable by the web server, and must end with SLIR_CACHE_DIR_NAME (no
	 * trailing slash). Ideally, this should be located outside of the web tree.
	 * 
	 * @var string
	 */
	public static $cacheDir	= NULL;

	/**
	 * Path to the error log file. Needs to be writable by the web server. Ideally,
	 * this should be located outside of the web tree.
	 * 
	 * @since 2.0
	 * @var string
	 */
	public static $errorLogPath	= NULL;

	/**
	 * If TRUE, forces SLIR to always use the query string for parameters instead
	 * of mod_rewrite.
	 *
	 * @since 2.0
	 * @var boolean
	 */
	public static $forceQueryString	= FALSE;

	/**
	 * In conjunction with $garbageCollectDivisor is used to manage probability that the garbage collection routine is started.
	 * 
	 * @since 2.0
	 * @var integer
	 */
	public static $garbageCollectProbability	= 1;

	/**
	 * Coupled with $garbageCollectProbability defines the probability that the garbage collection process is started on every request.
	 * 
	 * The probability is calculated by using $garbageCollectProbability/$garbageCollectDivisor, e.g. 1/100 means there is a 1% chance that the garbage collection process starts on each request.
	 * 
	 * @since 2.0
	 * @var integer
	 */
	public static $garbageCollectDivisor	= 200;

	/**
	 * Specifies the number of seconds after which data will be seen as 'garbage' and potentially cleaned up (deleted from the cache). 
	 * 
	 * @since 2.0
	 * @var integer
	 */
	public static $garbageCollectFileCacheMaxLifetime	= 86400; // 1 day = 1 * 24 * 60 * 60

	/**
	 * Initialize variables that require some dynamic processing.
	 * 
	 * @since 2.0
	 * @return void
	 */
	public static function init()
	{
		if (self::$documentRoot === NULL)
		{
			self::$documentRoot	= preg_replace('/\/$/', '', $_SERVER['DOCUMENT_ROOT']);
		}

		if (self::$SLIRDir === NULL)
		{
			self::$SLIRDir		= dirname($_SERVER['SCRIPT_NAME']);
		}

		if (self::$cacheDir === NULL)
		{
			self::$cacheDir = self::$documentRoot . self::$SLIRDir . self::$cacheDirName;
		}

		if (self::$errorLogPath === NULL)
		{
			self::$documentRoot . self::$SLIRDir . '/slir-error-log';
		}
	}
}