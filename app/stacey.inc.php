<?php

class ContentParser {
	
	static function sort_by_length($a,$b){
		if($a == $b) return 0;
		return (strlen($a) > strlen($b) ? -1 : 1);
	}
	
	function parse($file) {
		$file = file_get_contents($file);
		$parsed_text = $this->preparse("\n\n".$file."\n\n");
		preg_match_all('/[\w\d_-]+?:[\S\s]+?\n\n/', $parsed_text, $matches);
		foreach($matches[0] as $match) {
			$colon_split = split(":", $match);
			$replacement_pairs[$colon_split[0]] = $colon_split[1];
		}
		// sort keys by length, to ensure replacements are made in the correct order
		uksort($replacement_pairs, array("ContentParser", "sort_by_length"));
		return $replacement_pairs;
	}
	
	function preparse($text) {
		$patterns = array(
			# replace inline colons
			'/(?<=\n)([\w\d_-]+?):/',
			'/:/',
			'/\\\x01/',
			# replace inline dashes
			'/(?<=\n)-/',
			'/-/',
			'/\\\x02/',
			# convert lists
			'/\n?-(.+?)(?=\n)/',
			'/(<li>.*<\/li>)/',
			# wrap multi-line text in paragraphs
			'/([^\n]+?)(?=\n)/',
			'/<p>(.+):(.+)<\/p>/',
			'/: (.+)(?=\n<p>)/',
			# automatically link email addresses
			'/([A-Za-z0-9.-]+)@([A-Za-z0-9.-]+\.[A-Za-z]{2,4})/',
			# automatically link http:// websites
			'/http&#58;\/\/([A-Za-z0-9.-]+\.[A-Za-z]{2,4})/',
		);
		$replacements = array(
			# replace inline colons
			'$1\\x01',
			'&#58;',
			':',
			# replace inline dashes
			'\\x02',
			'&#45;',
			'-',
			# convert lists
			'<li>$1</li>',
			'<ul>$1</ul>',
			# wrap multi-line text in paragraphs
			'<p>$1</p>',
			'$1:$2',
			':<p>$1</p>',
			# automatically link email addresses
			'<a href="mailto&#58;$1&#64;$2">$1&#64;$2</a>',
			# automatically link http:// websites
			'<a href="http&#58;//$1">http&#58;//$1</a>',
		);
		$parsed_text = preg_replace($patterns, $replacements, $text);
		return $parsed_text;
	}
}

class TemplateParser {
	
	var $stacey;
	var $stored_file_names = array();
	
	function __construct($s) {
		$this->stacey = $s;
	}
	
	function store_file_names() {
		$dir = "../content/projects/";
		// read contents of projects directory
		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(!is_dir($file)) $this->stored_file_names[] = $file;
				}
				closedir($dh);
			}
		}
	}
	
	function get_folder_name() {
		return (!$this->stacey->project_name) ? "../content/".$this->stacey->page : "../content/projects/".$this->unclean_project_name($this->stacey->project_name);
	}
	
	function clean_project_name($project_name) {
		return preg_replace("/[0-9]+?\./", "", $project_name);
	}
	
	function unclean_project_name($project_name) {
		$this->store_file_names();
		$match = $project_name;
		foreach($this->stored_file_names as $key => $file) {
			if(preg_match("/$project_name$/", $file)) $match = $file;
		}
		return $match;
	}
	
	function check_thumb($dir, $file) {
			$file_types = array("jpg", "gif", "png");
			foreach($file_types as $file_type) {
				if(file_exists($dir."/".$file."/thumb.".$file_type)) {
					return '<img src="'.$dir.'/'.$file.'/thumb.'.$file_type.'" alt="Project Thumbnail">';
				}
			}
			return "";
	}
	
	function parse_partial($file) {
		$file = file_get_contents($file);
		preg_match('/([\S\s]*)foreach[\S\s]*{([\S\s]*)}([\S\s]*)/', $file, $matches);
		return array(preg_replace('/\t/', '', $matches[1]), $matches[2], preg_replace('/\t/', '', $matches[3]));
	}
	
	function find_projects() {
		$dir = "../content/projects";
		$projects_html = "";
		// read contents of projects directory
		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(!is_dir($file)) {
						if(file_exists($dir."/".$file."/information.txt")) {
							// store file
							$files[] = $file;
							$file_clean = $this->clean_project_name($file);
							preg_match("/project_title:(.+)/", file_get_contents($dir."/".$file."/information.txt"), $matches);
							$file_vars[] = array(
								"/@project_title/" => $matches[1],
								"/@url/" => $file_clean,
								"/@thumb/" => $this->check_thumb($dir, $file),
							);
						}
					}
				}
				
				$this->project_wrappers = $this->parse_partial('./templates/partials/projects.html');
				
				asort($files,SORT_NUMERIC);
				$projects_html = $this->project_wrappers[0];
				foreach($files as $key => $file) {
					$projects_html .= preg_replace(array_keys($file_vars[$key]), array_values($file_vars[$key]), $this->project_wrappers[1]);
				}
				closedir($dh);
				$projects_html .= $this->project_wrappers[2];
			}
		}
		
		return $projects_html;
	}
	
	function get_navigation() {
		$dir = "../content/";
		
		$navigation = $this->navigation_wrappers[0];
		
		// list projects
		$navigation .= $this->navigation_wrappers[1];
		$navigation .= '<a href="../projects">Projects</a>';
		$navigation .= $this->find_projects();
		$navigation .= $this->navigation_wrappers[2];
		
		// read contents of projects directory
		if($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if(!is_dir($file) && $file != ".DS_Store" && $file != "projects" && $file != "index.txt") {
					// remove any file extenstions
					$filename = (preg_match('/\./', $file)) ? explode(".", $file) : $file;
					if(is_array($filename)) $filename = $filename[0];
					$navigation .= $this->navigation_wrappers[1];
					$navigation .= '<a href="../'.$filename.'">'.ucfirst($filename).'</a>';
					$navigation .= $this->navigation_wrappers[2];
				}
			}
		}
		$navigation .= $this->project_wrappers[3];
		
		return ($navigation == $this->navigation_wrappers[0].$this->navigation_wrappers[3]) ? "" : $navigation;
	}
	
	function find_images() {
		$dir = $this->get_folder_name();
		$prefix = (preg_match('/projects/', $dir)) ? "../../" : (preg_match('/index/', $dir) ? "./" : "../");
		$images_html = "";
		// read contents of project's directory looking for images
		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(!is_dir($file)) {
						if(!is_dir($file) && preg_match("/\.[gif|jpg|png|jpeg]/i", $file) && !preg_match("/thumb\.[gif|jpg|png|jpeg]/i", $file)) {
							$files[] = $file;
							$image_vars[] = array(
								"/@url/" => $prefix.preg_replace('/\.\.\//', '', $dir)."/".$file,
							);
						} 
					}
				}
				if(count($files) > 0) {
					$images_html .= $this->image_wrappers[0];
					$this->image_wrappers = $this->parse_partial('./templates/partials/images.html');
					asort($files, SORT_NUMERIC);
					foreach($files as $key => $file) {
						$images_html .= preg_replace(array_keys($image_vars[$key]), array_values($image_vars[$key]), $this->image_wrappers[1]);
					}
					closedir($dh);
					$images_html .= $this->image_wrappers[2];
				}
			}
		}
		return $images_html;
	}
	
	function parse($file, $rules) {
		$file = file_get_contents($file);
		foreach($rules as $key => $value) {
			$keys[] = '/@'.$key.'/';
			$values[] = $value;
		}
		$parsed_text = preg_replace($keys, $values, $file);
		// special page vars
		$matches = array(
			'/@Projects/',
			'/@Images/',
			'/@Year/',
			'/@Navigation/',
		);
		$replacements = array(
			$this->find_projects(),
			$this->find_images(),
			date('Y'),
			$this->get_navigation(),
		);
		$parsed_text = preg_replace($matches, $replacements, $parsed_text);
		
		return $parsed_text;
	}
}

class Stacey {
	
	var $page = false;
	var $project_name = false;
	
	var $content_parser;
	var $template_parser;
	
	function __construct($get) {
		
		$this->content_parser = new ContentParser;
		$this->template_parser = new TemplateParser($this);
		
		$parsed_get = $this->parse_get($get);
		
		$cachefile = './cache/'.base64_encode('index');
		if ($_SERVER['QUERY_STRING']!='') $cachefile .= '_'.base64_encode($_SERVER['QUERY_STRING']);
			$folder = preg_replace('/[\w\d-.]+$/', '', $parsed_get["content_file"]);
			
			if (file_exists($cachefile) 
					&& (filemtime($parsed_get["content_file"]) < filemtime($cachefile) 
					&& filemtime($parsed_get["template_file"]) < filemtime($cachefile)) 
					&& $this->hash_images($folder) == $this->get_cached_images_hash($cachefile)
					&& (filemtime('./templates/partials/images.html') < filemtime($cachefile) 
					&& filemtime('./templates/partials/projects.html') < filemtime($cachefile))) {
				include($cachefile);
				echo "\n<!-- Cached. -->";
			} else { 
				ob_start();
				// parse content and template
				$this->parse($parsed_get["content_file"], $parsed_get["template_file"]);
				echo "\n<!-- Cache: ".$this->hash_images($folder)." -->";
				
				$fp = fopen($cachefile, 'w');
				fwrite($fp, ob_get_contents());
				fclose($fp);
				ob_end_flush();
			}
	}
	
	function hash_images($dir) {
		$images_modified = "";
		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(!is_dir($file)) {
						if(!is_dir($file) && preg_match("/\.[gif|jpg|png|jpeg]/i", $file) && !preg_match("/thumb\.[gif|jpg|png|jpeg]/i", $file)) {
							$images_modified .= $file.":".filemtime($dir."/".$file);
						} 
					}
				}
			}
		}
		return md5($images_modified);
	}
	
	function get_cached_images_hash($cachefile) {
		preg_match('/Cache: (.+?)\s/', file_get_contents($cachefile), $matches);
		return $matches[1];
	}
	
	function parse_get($get) {
		$this->page = (key($get)) ? key($get) : "index";
		$this->project_name = $get['name'];
		// work out which template to use
		$template_file = $this->get_template_file();
		// if page is not found in /content/projects or /content
		if(!$this->check_content_file_exists()) {
			// check if the page exists in the public directory
			echo $this->render_public_file();
			exit;
		}
		$content_file = $this->get_content_file();
		
		return array(
			'content_file' => $content_file,
			'template_file' => $template_file,
		);
	}
	
	function get_content_file() {
		// work out whether to go looking for the page's information.txt, or the standard page content
		$project_name_true = $this->template_parser->unclean_project_name($this->project_name);
		return ($this->project_name) ? "../content/projects/$project_name_true/information.txt" : (file_exists("../content/$this->page/information.txt") ? "../content/$this->page/information.txt" : "../content/$this->page.txt");
	}
	
	function check_content_file_exists() {
		// strip any number prefixes from the filename
		$project_name_true = $this->template_parser->unclean_project_name($this->project_name);
		if($this->project_name) return file_exists("../content/projects/$project_name_true/information.txt");
		else return (file_exists("../content/$this->page.txt") || file_exists("../content/$this->page/information.txt"));
	}
	
	function get_template_file() {
		return ($this->project_name) ? "./templates/project.html" : (file_exists("./templates/$this->page.html") ? "./templates/$this->page.html" : "./templates/content.html");
	}
	
	function render_public_file() {
		if(file_exists("../public/$this->page.html")) {
			return file_get_contents('../public/'.$this->page.'.html');
		} else {
			// return 404 if necessary
			header("HTTP/1.0 404 Not Found");
			return file_get_contents('../public/404.html');
		}
	}

	function parse($content, $template) {
		// parse content
		$rules = $this->content_parser->parse($content);
		// parse template
		echo $this->template_parser->parse($template, $rules);
	}

}

?>