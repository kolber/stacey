<?php
/**
 * Class definition file for SLIRException
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

/* $Id: slirexception.class.php 123 2010-12-21 18:58:03Z joe.lencioni $ */
 
/**
 * Exception and error handler
 * 
 * @since 2.0
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * @date $Date: 2010-12-21 12:58:03 -0600 (Tue, 21 Dec 2010) $
 * @version $Revision: 123 $
 * @package SLIR
 */
class SLIRException extends Exception
{
	/**
	 * Max number of characters to wrap error message at
	 *
	 * @since 2.0
	 * @var integer
	 */
	const WRAP_AT		= 65;
	
	/**
	 * Text size to use in imagestring(). Possible values are 1, 2, 3, 4, or 5
	 *
	 * @since 2.0
	 * @var integer
	 */
	const TEXT_SIZE		= 4;
	
	/**
	 * Height of one line of text, in pixels
	 *
	 * @since 2.0
	 * @var integer
	 */
	const LINE_HEIGHT	= 16;
	
	/**
	 * Width of one character of text, in pixels
	 *
	 * @since 2.0
	 * @var integer
	 */
	const CHAR_WIDTH	= 8;
	
	/**
	 * @since 2.0
	 * @param Exception $exception
	 * @param string $explanationText
	 */
	public function __construct($exception, $explanationText = NULL)
	{
		parent::__construct($exception);
		
		/* @todo fix this
		if (defined('SLIR_ERROR_LOG_PATH') && (!defined('SLIR_LOG_ERRORS') || SLIR_LOG_ERRORS !== FALSE))
		{
			$log	= $this->log();
			if (!$log)
			{
				$explanationText .= "\n\nAlso could not log error to file. "
					. 'Please create a file at \'' . SLIR_ERROR_LOG_PATH . '\' '
					. 'and give the web server permissions to write to it.';
			}
		} // if
		*/
		
		if (SLIRConfig::$errorImages === TRUE)
		{
			$this->errorImage($explanationText);
		}
		else
		{
			$this->errorText($explanationText);
		}
	}
	
	/**
	 * Determines if a constant is not defined
	 * 
	 * @since 2.0
	 * @param string $constantName
	 * @return boolean
	 */
	public function isNotDefined($constantName)
	{
		return (!defined($constantName));
	}
		
	/**
	 * Logs the error to a file
	 * 
	 * @since 2.0
	 */
	private function log()
	{
		$userAgent	= (isset($_SERVER['HTTP_USER_AGENT'])) ? " {$_SERVER['HTTP_USER_AGENT']}" : '';
		$referrer	= (isset($_SERVER['HTTP_REFERER'])) ? "Referrer: {$_SERVER['HTTP_REFERER']}\n\n" : '';
		$request	= (isset($_SERVER['REQUEST_URI'])) ? "Request: {$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}\n" : '';
		
		$message	= "\n[" . @gmdate('D M d H:i:s Y') . '] ['
			. $_SERVER['REMOTE_ADDR'] . $userAgent . '] ' . $this->getMessage()
			. "\n\n" . $referrer . $request . $this->getTraceAsString() . "\n";
		
		return @error_log($message, 3, SLIRConfig::$errorLogPath);
	}
	
	/**
	 * @since 2.0
	 * @param string $explanationText
	 * @return string Error message
	 */
	private function errorMessage($explanationText = NULL)
	{
		$text	= $this->getMessage();
		if ($explanationText)
		{
			$text	.= "\n\n$explanationText";
		}
		
		return $text;
	}
	
	/**
	 * Create and output an image with an error message
	 * 
	 * @since 2.0
	 * @param string $explanationText
	 */
	private function errorImage($explanationText = NULL)
	{
		$text	= $this->errorMessage($explanationText);

		$text	= wordwrap($text, SLIRException::WRAP_AT);
		$text	= explode("\n", $text);
		
		// determine width
		$characters	= 0;
		foreach($text as $line)
		{
			if (($temp = strlen($line)) > $characters)
			{
				$characters = $temp;
			}
		} // foreach
		
		// set up the image
		$image	= imagecreatetruecolor(
			$characters * SLIRException::CHAR_WIDTH,
			count($text) * SLIRException::LINE_HEIGHT
		);
		$white	= imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $white);
		
		// set text color
		$textColor	= imagecolorallocate($image, 200, 0, 0); // red
		
		// write the text to the image
		$i	= 0;
		foreach($text as $line)
		{
			imagestring(
				$image,
				SLIRException::TEXT_SIZE,
				0,
				$i * SLIRException::LINE_HEIGHT,
				$line,
				$textColor
			);
			++$i;
		}
		
		// output the image
		header('Content-type: image/png');
		imagepng($image);
		
		// clean up for memory
		imagedestroy($image);
	}
	
	/**
	 * @since 2.0
	 * @param string $explanationText
	 */
	private function errorText($explanationText = NULL)
	{
		echo nl2br($this->errorMessage($explanationText) . "\n");
	}
	
	/**
	 * Error handler
	 * 
	 * @since 2.0
	 * @param integer $errno Level of the error raised
	 * @param string $errstr Error message
	 * @param string $errfile Filename that the error was raised in
	 * @param integer $errline Line number the error was raised at,
	 * @param array $errcontext Points to the active symbol table at the point the error occurred
	 */
	public static function error($errno, $errstr, $errfile = NULL, $errline = NULL, $errcontext = array())
	{
		// if error has been supressed with an @
		if (error_reporting() == 0)
		{
			return;
		}
			
		$message	= $errno . ' ' .$errstr;
		if ($errfile !== NULL)
		{
			$message	.= "\n\nFile: $errfile";
			if ($errline !== NULL)
			{
				$message	.= "\nLine $errline";
			}
		}
			
		throw new SLIRException($message);
	}
} // SLIRException