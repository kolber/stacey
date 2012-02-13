<?php
/**
 * Class definition file for SLIRFaceDetector
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
 */

/* $Id: facedetector.class.php 112 2010-12-20 21:13:56Z joe.lencioni $ */
 
/**
 * Face detector class
 * 
 * This code was originally written by Liu Liu <ll2ef@virginia.edu> in JavaScript
 * and was ported to PHP by Joe Lencioni <joe@shiftingpixel.com>
 * 
 * @link https://github.com/liuliu/ccv
 * 
 * @since 2.0
 * @author Liu Liu <ll2ef@virginia.edu>
 * @author Joe Lencioni <joe@shiftingpixel.com>
 * $Date: 2010-12-20 15:13:56 -0600 (Mon, 20 Dec 2010) $
 * @version $Revision: 112 $
 * @package SLIR
 */
class SLIRFaceDetector
{

	/**
	 * @return void
	 */
	public function __construct()
	{
	}

	/**
	 * @param array $seq
	 * @return array
	 */
	protected function array_group($seq)
	{
		$i		= NULL;
		$j		= NULL;
		$node	= array(); // array_fill(0, count($seq), NULL);

		for ($i = 0; $i < count($seq); ++$i)
		{
			$node[$i]	= array(
					'parent'	=> -1,
					'element'	=> $seq[$i],
					'rank'		=> 0,
				);
		}

		for ($i = 0; $i < count($seq); ++$i)
		{
			if (!$node[$i]['element'])
			{
				continue;
			}

			$root = $i;
			while ($node[$root]['parent'] != -1)
			{
				$root	= $node[$root]['parent'];
			}

			for ($j = 0; $j < count($seq); ++$j)
			{
				if ($i != $j && $node[$j]['element'] && $this->gfunc($node[$i]['element'], $node[$j]['element']))
				{
					$root2	= $j;

					while ($node[$root2]['parent'] != -1)
					{
						$root2	= $node[$root2]['parent'];
					}

					if ($root2 != $root)
					{
						if ($node[$root]['rank'] > $node[$root2]['rank'])
						{
							$node[$root2]['parent']	= $root;
						}
						else
						{
							$node[$root]['parent']	= $root2;
							if ($node[$root]['rank'] == $node[$root2]['rank'])
							{
								++$node[$root2]['rank'];
							}
							$root	= $root2;
						}

						// Compress path from node2 to the root:
						$temp	= NULL;
						$node2	= $j;

						while ($node[$node2]['parent'] != -1)
						{
							$temp	= $node2;
							$node2	= $node[$node2]['parent'];
							$node[$temp]['parent']	= $root;
						}

						// Compress path from node to the root: 
						$node2	= $i;
						while ($node[$node2]['parent'] != -1)
						{
							$temp	= $node2;
							$node2	= $node[$node2]['parent'];
							$node[$temp]['parent']	= $root;
						}
					} // if
				}
			} // for
		} // for

		$idx		= array(); //array_fill(0, count($seq), NULL);
		$class_idx	= 0;

		for ($i = 0; $i < count($seq); ++$i)
		{
			$j		= -1;
			$node1	= $i;
			if ($node[$node1]['element'])
			{
				while ($node[$node1]['parent'] != -1)
				{
					$node1 = $node[$node1]['parent'];
				}

				if ($node[$node1]['rank'] >= 0)
				{
					// JS: ~class_idx++;
					$node[$node1]['rank'] = (0 - $class_idx) + 1;
					++$class_idx;
				}

				$j = (0 - $node[$node1]['rank']) + 1; // JS: ~node[node1].rank;
			}
			$idx[$i] = $j;
		}
		
		return array('index' => $idx, 'cat' => $class_idx);
	}

	/**
	 * @param array $r1
	 * @param array $r2
	 * @return boolean
	 */
	protected function gfunc($r1, $r2)
	{
		$distance	= floor($r1['width'] * 0.25 + 0.5);
		return  $r2['x'] <= $r1['x'] + $distance &&
				$r2['x'] >= $r1['x'] - $distance &&
				$r2['y'] <= $r1['y'] + $distance &&
				$r2['y'] >= $r1['y'] - $distance &&
				$r2['width'] <= floor($r1['width'] * 1.5 + 0.5) &&
				floor($r2['width'] * 1.5 + 0.5) >= $r1['width'];
	}

	/**
	 * @param resource $canvas GD Image resource
	 * @param array $cascade
	 * @param integer $interval
	 * @param integer $min_neighbors
	 * @return array
	 */
	public function detect_objects($canvas, $cascade, $interval, $min_neighbors)
	{
		$scale		= pow(2, 1 / ($interval + 1));
		$next		= $interval + 1;
		$scale_upto	= floor(log(min($cascade['width'], $cascade['height'])) / log($scale));
		$pyr		= array_fill(0, ($scale_upto + $next * 2) * 4, NULL);
		$pyr[0]		= $canvas;
		$i = $j = $k = $x = $y = $q = NULL;

		$baseWidth	= imagesx($pyr[0]);
		$baseHeight	= imagesy($pyr[0]);

		for ($i = 1; $i <= $interval; ++$i)
		{
			$newWidth		= floor($baseWidth / pow($scale, $i));
			$newHeight		= floor($baseHeight / pow($scale, $i));
			$pyr[$i * 4]	= imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($pyr[$i * 4], $pyr[0], 0, 0, 0, 0, $newWidth, $newHeight, $baseWidth, $baseHeight);
		}

		for ($i = $next; $i < $scale_upto + $next * 2; ++$i)
		{
			$baseImage		= $pyr[$i * 4 - $next * 4];
			$baseWidth		= imagesx($baseImage);
			$baseHeight		= imagesy($baseImage);
			$newWidth		= max(1, floor($baseWidth / 2));
			$newHeight		= max(1, floor($baseHeight / 2));
			$pyr[$i * 4]	= imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($pyr[$i * 4], $baseImage, 0, 0, 0, 0, $newWidth, $newHeight, $baseWidth, $baseHeight);
		}
		
		for ($i = $next * 2; $i < $scale_upto + $next * 2; ++$i)
		{
			$baseImage			= $pyr[$i * 4 - $next * 4];
			$baseWidth			= imagesx($baseImage);
			$baseHeight			= imagesy($baseImage);
			$newWidth			= max(1, floor($baseWidth / 2));
			$newHeight			= max(1, floor($baseHeight / 2));

			$pyr[$i * 4 + 1]	= imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($pyr[$i * 4 + 1], $baseImage, 0, 0, 1, 0, $newWidth-2, $newHeight, $baseWidth-1, $baseHeight);

			$pyr[$i * 4 + 2]	= imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($pyr[$i * 4 + 2], $baseImage, 0, 0, 0, 1, $newWidth, $newHeight-2, $baseWidth, $baseHeight-1);

			$pyr[$i * 4 + 3]	= imagecreatetruecolor($newWidth, $newHeight);
			imagecopyresampled($pyr[$i * 4 + 3], $baseImage, 0, 0, 1, 1, $newWidth-2, $newHeight-2, $baseWidth-1, $baseHeight-1);
		}

		for ($j = 0; $j < count($cascade['stage_classifier']); ++$j)
		{
			$cascade['stage_classifier'][$j]['orig_feature']	= $cascade['stage_classifier'][$j]['feature'];
		}

		$scale_x	= 1;
		$scale_y	= 1;
		$dx			= array(0, 1, 0, 1);
		$dy			= array(0, 0, 1, 1);
		$seq		= array();

		for ($i = 0; $i < $scale_upto; ++$i)
		{
			$qw		= imagesx($pyr[$i * 4 + $next * 8]) - floor($cascade['width'] / 4);
			$qh		= imagesy($pyr[$i * 4 + $next * 8]) - floor($cascade['height'] / 4);
			$step	= array(
					imagesx($pyr[$i * 4]) * 4,
					imagesx($pyr[$i * 4 + $next * 4]) * 4,
					imagesx($pyr[$i * 4 + $next * 8]) * 4,
				);

			$paddings	= array(
					imagesx($pyr[$i * 4]) * 16 - $qw * 16,
					imagesx($pyr[$i * 4 + $next * 4]) * 8 - $qw * 8,
					imagesx($pyr[$i * 4 + $next * 8]) * 4 - $qw * 4,
				);

			for ($j = 0; $j < count($cascade['stage_classifier']); ++$j)
			{
				$orig_feature	= $cascade['stage_classifier'][$j]['orig_feature'];
				$feature		= array_fill(0, $cascade['stage_classifier'][$j]['count'], NULL);

				for ($k = 0; $k < $cascade['stage_classifier'][$j]['count']; ++$k)
				{
					$feature[$k]	= array(
							'size'	=> $orig_feature[$k]['size'],
							'px'	=> array_fill(0, $orig_feature[$k]['size'], NULL),
							'pz'	=> array_fill(0, $orig_feature[$k]['size'], NULL),
							'nx'	=> array_fill(0, $orig_feature[$k]['size'], NULL),
							'nz'	=> array_fill(0, $orig_feature[$k]['size'], NULL),
						);

					for ($q = 0; $q < $orig_feature[$k]['size']; ++$q)
					{
						if ($orig_feature[$k]['pz'][$q] === -1 || $orig_feature[$k]['nz'][$q] === -1)
						{
							continue;
						}
						
						$feature[$k]['px'][$q]	= $orig_feature[$k]['px'][$q] * 4 + $orig_feature[$k]['py'][$q] * $step[$orig_feature[$k]['pz'][$q]];
						$feature[$k]['pz'][$q]	= $orig_feature[$k]['pz'][$q];
						$feature[$k]['nx'][$q]	= $orig_feature[$k]['nx'][$q] * 4 + $orig_feature[$k]['ny'][$q] * $step[$orig_feature[$k]['nz'][$q]];
						$feature[$k]['nz'][$q]	= $orig_feature[$k]['nz'][$q];
					}
				}

				$cascade['stage_classifier'][$j]['feature']	= $feature;
			}

			
			for ($q = 0; $q < 4; ++$q)
			{
				$u8		= array(
						$pyr[$i * 4],
						$pyr[$i * 4 + $next * 4],
						$pyr[$i * 4 + $next * 8 + $q],
					);
				$u8w	= array(
						imagesx($u8[0]),
						imagesx($u8[1]),
						imagesx($u8[2]),
					);
				$u8o	= array(
						$dx[$q] * 8 + $dy[$q] * $u8w[0] * 8,
						$dx[$q] * 4 + $dy[$q] * $u8w[1] * 4,
						0,
					);
				
				// Color cache saves time
				$colors	= array(array(), array(), array());

				for ($y = 0; $y < $qh; ++$y)
				{
					for ($x = 0; $x < $qw; ++$x)
					{
						$sum	= 0;
						$flag	= TRUE;

						for ($j = 0; $j < count($cascade['stage_classifier']); ++$j)
						{
							$sum		= 0;
							$alpha		= $cascade['stage_classifier'][$j]['alpha'];
							$feature	= $cascade['stage_classifier'][$j]['feature'];

							for ($k = 0; $k < $cascade['stage_classifier'][$j]['count']; ++$k)
							{
								$feature_k	= $feature[$k];
								$p		= NULL;
								
								$pos	= ($u8o[$feature_k['pz'][0]] + $feature_k['px'][0]) / 4;

								if (!isset($colors[$feature_k['pz'][0]][$pos]))
								{
									$posx	= $pos % $u8w[$feature_k['pz'][0]];
									$posy	= floor($pos / $u8w[$feature_k['pz'][0]]);
									$colors[$feature_k['pz'][0]][$pos]	= imagecolorat($u8[$feature_k['pz'][0]], $posx, $posy);
								}
								$pmin	= $colors[$feature_k['pz'][0]][$pos];
								

								$n		= NULL;
								$pos	= ($u8o[$feature_k['nz'][0]] + $feature_k['nx'][0]) / 4;

								if (!isset($colors[$feature_k['nz'][0]][$pos]))
								{
									$posx	= $pos % $u8w[$feature_k['nz'][0]];
									$posy	= floor($pos / $u8w[$feature_k['nz'][0]]);
									$colors[$feature_k['nz'][0]][$pos]	= imagecolorat($u8[$feature_k['nz'][0]], $posx, $posy);
								}
								$nmax		= $colors[$feature_k['nz'][0]][$pos];
								
								if ($pmin <= $nmax)
								{
									$sum	+= $alpha[$k * 2];
								}
								else
								{
									$f			= NULL;
									$shortcut	= TRUE;

									for ($f = 0; $f < $feature_k['size']; ++$f)
									{
										if ($feature_k['pz'][$f] >= 0 && $feature_k['pz'][$f] !== NULL)
										{
											$pos	= ($u8o[$feature_k['pz'][$f]] + $feature_k['px'][$f]) / 4;
											if (!isset($colors[$feature_k['pz'][$f]][$pos]))
											{
												$posx	= $pos % $u8w[$feature_k['pz'][$f]];
												$posy	= floor($pos / $u8w[$feature_k['pz'][$f]]);
												$colors[$feature_k['pz'][$f]][$pos] = imagecolorat($u8[$feature_k['pz'][$f]], $posx, $posy);
											}
											$p	= $colors[$feature_k['pz'][$f]][$pos];

											if ($p < $pmin)
											{
												if ($p <= $nmax)
												{
													$shortcut	= FALSE;
													break;
												}
												$pmin	= $p;
											}
										}
										
										if ($feature_k['nz'][$f] >= 0 && $feature_k['nz'][$f])
										{
											$pos	= ($u8o[$feature_k['nz'][$f]] + $feature_k['nx'][$f]) / 4;

											if (!isset($colors[$feature_k['nz'][$f]][$pos]))
											{
												$posx	= $pos % $u8w[$feature_k['nz'][$f]];
												$posy	= floor($pos / $u8w[$feature_k['nz'][$f]]);
												$colors[$feature_k['nz'][$f]][$pos] = imagecolorat($u8[$feature_k['nz'][$f]], $posx, $posy);
											}
											$n	= $colors[$feature_k['nz'][$f]][$pos];

											if ($n > $nmax)
											{
												if ($pmin <= $n)
												{
													$shortcut	= FALSE;
													break;
												}
												$nmax	= $n;
											}
										}
									}

									$sum += ($shortcut) ? $alpha[$k * 2 + 1] : $alpha[$k * 2];
								}
							}

							if ($sum < $cascade['stage_classifier'][$j]['threshold'])
							{
								$flag = FALSE;
								break;
							}
						}

						if ($flag)
						{
							$seq[]	= array(
									'x'				=> ($x * 4 + $dx[$q] * 2) * $scale_x,
									'y'				=> ($y * 4 + $dy[$q] * 2) * $scale_y,
									'width'			=> $cascade['width'] * $scale_x,
									'height'		=> $cascade['height'] * $scale_y,
									'neighbor'		=> 1,
									'confidence'	=> $sum,
								);
						}
						$u8o[0] += 16;
						$u8o[1] += 8;
						$u8o[2] += 4;
					}
					$u8o[0] += $paddings[0];
					$u8o[1] += $paddings[1];
					$u8o[2] += $paddings[2];
				}
			}
			$scale_x *= $scale;
			$scale_y *= $scale;

		} // for scale_upto

		for ($j = 0; $j < count($cascade['stage_classifier']); ++$j)
		{
			$cascade['stage_classifier'][$j]['feature']	= $cascade['stage_classifier'][$j]['orig_feature'];
		}

		if (!($min_neighbors > 0))
		{
			return $seq;
		}
		else
		{
			$result	= $this->array_group($seq);

			$ncomp		= $result['cat'];
			$idx_seq	= $result['index'];
			$comps		= array_fill(0, $ncomp + 1, array(
					'neighbors'		=> 0,
					'x'				=> 0,
					'y'				=> 0,
					'width'			=> 0,
					'height'		=> 0,
					'confidence'	=> 0,
				));
			
			
			// count number of neighbors
			for ($i = 0; $i < count($seq); ++$i)
			{
				$r1		= $seq[$i];
				$idx	= $idx_seq[$i];

				if ($comps[$idx]['neighbors'] == 0)
				{
					$comps[$idx]['confidence'] = $r1['confidence'];
				}

				++$comps[$idx]['neighbors'];

				$comps[$idx]['x']			+= $r1['x'];
				$comps[$idx]['y']			+= $r1['y'];
				$comps[$idx]['width']		+= $r1['width'];
				$comps[$idx]['height']		+= $r1['height'];
				$comps[$idx]['confidence']	= max($comps[$idx]['confidence'], $r1['confidence']);
			}

			$seq2	= array();
			// calculate average bounding box
			for ($i = 0; $i < $ncomp; ++$i)
			{
				$n = $comps[$i]['neighbors'];
				if ($n >= $min_neighbors)
				{
					$seq2[]	= array(
							'x'				=> ($comps[$i]['x'] * 2 + $n) / (2 * $n),
							'y'				=> ($comps[$i]['y'] * 2 + $n) / (2 * $n),
							'width'			=> ($comps[$i]['width'] * 2 + $n) / (2 * $n),
							'height'		=> ($comps[$i]['height'] * 2 + $n) / (2 * $n),
							'neighbors'		=> $comps[$i]['neighbors'],
							'confidence'	=> $comps[$i]['confidence']
						);
				}
			}

			$result_seq	= array();
			// filter out small face rectangles inside large face rectangles
			for ($i = 0; $i < count($seq2); ++$i)
			{
				$r1		= $seq2[$i];
				$flag	= TRUE;

				for ($j = 0; $j < count($seq2); ++$j)
				{
					$r2	= $seq2[$j];
					$distance = floor($r2['width'] * 0.25 + 0.5);

					if ($i != $j &&
						$r1['x'] >= $r2['x'] - $distance &&
						$r1['y'] >= $r2['y'] - $distance &&
						$r1['x'] + $r1['width'] <= $r2['x'] + $r2['width'] + $distance &&
						$r1['y'] + $r1['height'] <= $r2['y'] + $r2['height'] + $distance &&
						($r2['neighbors'] > max(3, $r1['neighbors']) || $r1['neighbors'] < 3))
					{
						$flag = FALSE;
						break;
					}
				}

				if ($flag)
				{
					$result_seq[]	= $r1;
				}
			} // for
			return $result_seq;
		} // else
	} // detect_objects()
}