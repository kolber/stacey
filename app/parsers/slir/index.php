<?php
/**
 * Main file for SLIR (Smart Lencioni Image Resizer)
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
 * @date $Date: 2010-11-11 12:36:47 -0600 (Thu, 11 Nov 2010) $
 * @version $Revision: 107 $
 */
 
 /* $Id: index.php 107 2010-11-11 18:36:47Z joe.lencioni $ */

// define('SLIR_CONFIG_FILENAME', 'slir-config-alternate.php');
function __autoload($className)
{
	require_once strtolower($className) . '.class.php';
}

new SLIR();