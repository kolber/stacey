<?php

Class Stacey {

	static $version = '1.0b';
	
	function __construct($get) {
		$this->php_fixes();
		// it's easier to handle some redirection through php rather than relying on a more complex .htaccess file to do all the work
		if($this->handle_redirects()) return;
		// parse get request
		$r = new Renderer($get);
		// handle rendering of the page
		$r->render();
	}
	
	function php_fixes() {
		// in PHP/5.3.0 they added a requisite for setting a default timezone, this should be handled via the php.ini, but as we cannot rely on this, we have to set a default timezone ourselves
		if(function_exists('date_default_timezone_set')) date_default_timezone_set('Australia/Melbourne');
	}
	
	function handle_redirects() {
		// rewrite any calls to /index or /app back to /
		if(preg_match('/index|app\/?$/', $_SERVER['REQUEST_URI'])) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: ../');
			return true;
		}
		// add trailing slash if required
		if(!preg_match('/\/$/', $_SERVER['REQUEST_URI'])) {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location:'.$_SERVER['REQUEST_URI'].'/');
			return true;
		}
		return false;
	}
	
}

Class Helpers {
	
	static function sort_by_length($a,$b){
		if($a == $b) return 0;
		return (strlen($a) > strlen($b) ? -1 : 1);
	}
	
	static function list_files($dir, $regex) {
		if(!is_dir($dir)) return false;
		if(!$dh = opendir($dir)) return false;
		$files = array();
		// if file matches regex (and doesn't begin with a .), push it to the files array
		while (($file = readdir($dh)) !== false) {
			if(!preg_match('/^\./', $file) && preg_match($regex, $file)) $files[] = $file;
		}
		closedir($dh);
		// sort list of files reverse-numerically (10, 9, 8, etc)
		rsort($files, SORT_NUMERIC);
		// return list of files
		return $files;
	}
	
}

Class Cache {

	var $page;
	var $cachefile;
	var $hash;
	
	function __construct($page) {
		// store reference to current page
		$this->page = $page;
		// turn a base64 of the full path to the page's content file into the name of the cache file
		$this->cachefile = './cache/'.base64_encode($this->page->content_file);
		//collect an md5 of all files
		$this->hash = $this->create_hash();
	}
	
	function check_expired() {
		// if cachefile doesn't exist, we need to create one
		if(!file_exists($this->cachefile)) return true;
		// compare new m5d to existing cached md5
		elseif($this->hash !== $this->get_current_hash()) return true;
		else return false;
	}
	
	function get_current_hash() {
		preg_match('/Stacey.*: (.+?)\s/', file_get_contents($this->cachefile), $matches);
		return $matches[1];
	}
	
	function write_cache() {
		echo "\n".'<!-- Stacey('.Stacey::$version.'): '.$this->hash.' -->';
		$fp = fopen($this->cachefile, 'w');
		fwrite($fp, ob_get_contents());
		fclose($fp);
	}

	function create_hash() {
		// create a collection of every file inside the content folder
		$content = $this->collate_files('../content/');
		// create a collection of every file inside the templates folder
		$templates = $this->collate_files('../templates/');
		// create an md5 of the two collections
		return $this->hash = md5($content.$templates);
	}
	
	function collate_files($dir) {
		if(!isset($files_modified)) $files_modified = '';
		if(!is_dir($dir)) return false;
		if(!$dh = opendir($dir)) return false;
		$files = array();
		while (($file = readdir($dh)) !== false) {
			if(!preg_match('/^\./', $file)) {
				if(is_dir($dir.'/'.$file)) {
					$files_modified .= $file.':'.filemtime($dir.'/'.$file);
					$files_modified .= $this->collate_files($dir.'/'.$file);
				} else {
					$files_modified .= $file.':'.filemtime($dir.'/'.$file);
				}
			}
		}
		closedir($dh);
		return $files_modified;
	}
	
}


Class Renderer {
	
	var $page;
	
	function __construct($get) {
		// take the passed url ($get) and turn it into an object
		$this->page = $this->handle_routes($get);
	}
	
	function is_category($name) {
		// find folder name from $name
		$dir = '';
		$folders = Helpers::list_files('../content', '/^\d+?\.[^\.]+$/');
		foreach($folders as $folder) {
			if(preg_match('/'.$name.'$/', $folder)) {
				$dir = '../content/'.$folder;
				break;
			}
		}
		// check if this folder contains inner folders - if it does, then it is a category
		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(is_dir($dir.'/'.$file) && !preg_match('/^\./', $file)) return true;
				}
				closedir($dh);
			}
		}
		// if the folder doesn't contain any inner folders, then it is not a category
		return false;
	}
	
	function handle_routes($get) {
		// if key is empty, we're looking for the index page
		if(key($get) == '') {
			// creating a new page without passing through a name creates the index page
			return new Page();
		}
		// if key does contain slashes, it must be a category/page
		else if(preg_match('/\//', key($get))) {
			// explode key, [0] => category, [1] => name
			$path = explode('/', key($get));
			// if key contains more than one /, return a 404 as the app doesn't handle more than 2 levels of depth
			if(count($path) > 2) return key($get);
			else return new PageInCategory($path[0], $path[1]);
		}
		// if key contains no slashes, it must be a page or a category
		else {
			// check whether we're looking for a category or a page
			if($this->is_category(key($get))) return new Category(key($get));
			else return new Page(key($get));
		}
	}
	
	function render_404() {
		// return correct 404 header
		header('HTTP/1.0 404 Not Found');
		// if there is a 404 page set, use it
		if(file_exists('../public/404.html')) echo file_get_contents('../public/404.html');
		// otherwise, use this text as a default
		else echo '<h1>404</h1><h2>Page could not be found.</h2><p>Unfortunately, the page you were looking for does not exist here.</p>';
	}
	
	function render() {
		// if page doesn't contain a content file or have a matching template file, redirect to it or return 404
		if(!$this->page || !$this->page->template_file) {
			// if a static html page with a name matching the current route exists in the public folder, serve it 
			if($this->page->public_file) echo file_get_contents($this->page->public_file);
			// serve 404
			else $this->render_404();
		} else {
			// create new cache object
			$cache = new Cache($this->page);
			// check etags
			header ('Etag: "'.$cache->hash.'"');
			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == '"'.$cache->hash.'"') {
				// local cache is still fresh, so return 304
				header ("HTTP/1.0 304 Not Modified");
				header ('Content-Length: 0');
			} else {
				// check if cache needs to be expired
				if($cache->check_expired()) {
					// start output buffer
					ob_start();
						// render page
						$t = new TemplateParser;
						$c = new ContentParser;
						echo $t->parse($this->page, $c->parse($this->page));
						// cache folder is writable, write to it
						if(is_writable('./cache')) $cache->write_cache();
						else echo "\n".'<!-- Stacey('.Stacey::$version.'). -->';
					// end buffer
					ob_end_flush();
				} else {
					// else cache hasn't expired, so use existing cache
					echo file_get_contents($cache->cachefile)."\n".'<!-- Cached. -->';
				}
			}
		}
	}
}

Class Page {
	var $name;
	
	var $content_file;
	var $template_file;
	var $public_file;
	
	var $i;
	var $name_unclean;
	var $unclean_names = array();
	var $image_files = array();
	var $link_path;
	
	function __construct($name = 'index') {
		$this->name = $name;
		$this->store_unclean_names('../content/');
		$this->name_unclean = $this->unclean_name($this->name);

		$this->content_file = $this->get_content_file();
		$this->template_file = $this->get_template_file();
		$this->public_file = $this->get_public_file();
		$this->image_files = $this->get_images(preg_replace('/\/[^\/]+$/', '', $this->content_file));
		$this->link_path = $this->construct_link_path();
	}
	
	function construct_link_path() {
		$link_path = '';
		if(!preg_match('/index/', $this->content_file)) {
			$link_path .= '../';
			preg_match_all('/\//', $this->content_file, $slashes);
			for($i = 3; $i < count($slashes[0]); $i++) $link_path .= '../';
		}
		return $link_path;
	}
	
	function store_unclean_names($dir) {
		// store a list of folder names
		$this->unclean_names = Helpers::list_files($dir, '/^\d+?\.[^\.]+$/');
	}
	
	function clean_name($name) {
		// strip leading digit and dot from filename (1.xx becomes xx)
		return preg_replace('/^\d+?\./', '', $name);
	}

	function unclean_name($name) {
		// loop through each unclean page name looking for a match for $name
		foreach($this->unclean_names as $key => $file) {
			if(preg_match('/'.$name.'$/', $file)) {
				// store current number of this page
				$this->i = ($key + 1);
				// return match
				return $file;
			}
		}
		return false;
	}
	
	function get_images($dir) {
		$image_files = array();
		if(is_dir($dir)) {
		 	if($dh = opendir($dir)) {
		 		while (($file = readdir($dh)) !== false) {
		 			if(!preg_match('/^\./', $file) && preg_match('/\.(gif|jpg|png|jpeg)/i', $file) && !preg_match('/thumb\./i', $file)) {
						$image_files[] = $file;
					}
				}
				closedir($dh);
			}
		}
		return $image_files;
	}
	
	function get_template_file() {
		// check folder exists, if not, return 404
		if(!$this->name_unclean) return false;
		// find the name of the text file
		preg_match('/\/([^\/]+?)\.txt/', $this->content_file, $template_name);
		// if template exists, return it
		if(!empty($template_name) && file_exists('../templates/'.$template_name[1].'.html')) return '../templates/'.$template_name[1].'.html';
		// return content.html as default template (if it exists)
		elseif(file_exists('../templates/content.html')) return '../templates/content.html';
		else return false;
	}
	
	function get_content_file() {
		// check folder exists
		if($this->name_unclean && file_exists('../content/'.$this->name_unclean)) {
			// look for a .txt file
			$txts = Helpers::list_files('../content/'.$this->name_unclean, '/\.txt$/');
			// if $txts contains a result, return it
			if(count($txts) > 0) return '../content/'.$this->name_unclean.'/'.$txts[0];
			else return '../content/'.$this->name_unclean.'/none';
		} else return '../content/'.$this->name_unclean.'/none';
	}
	
	function get_public_file() {
		// see if a static html file with $name exists in the public folder
		if(file_exists('../public/'.$this->name.'.html')) return '../public/'.$this->name.'.html';
		else return false;
	}
	
}

Class Category extends Page {
	function __construct($name) {
		$this->name = $name;
		$this->store_unclean_names('../content/');
		$this->name_unclean = $this->unclean_name($this->name);

		$this->content_file = $this->get_content_file();
		$this->template_file = $this->get_template_file();
		$this->public_file = '';
		$this->link_path = $this->construct_link_path();
	}
	
	function get_template_file() {
		// check folder exists, if not, return 404
		if(!$this->name_unclean) return false;
		// find the name of the text file
		preg_match('/\/([^\/]+?)\.txt/', $this->content_file, $template_name);
		// if template exists, return it
		if(!empty($template_name) && file_exists('../templates/'.$template_name[1].'.html')) return '../templates/'.$template_name[1].'.html';
		// return category.html as default template (if it exists)
		elseif(file_exists('../templates/category.html')) return '../templates/category.html';
		else return false;
	}
	
}

Class PageInCategory extends Page {
	
	var $category;
	var $category_unclean;
	var $sibling_pages;
	
	function __construct($category, $name) {
		$this->name = $name;
		$this->category = $category;
		$this->store_unclean_names('../content/');
		$this->category_unclean = $this->unclean_name($this->category);
		$this->store_unclean_names('../content/'.$this->category_unclean);
		$this->name_unclean = $this->unclean_name($this->name);
		$this->sibling_pages = $this->get_sibling_pages();

		$this->content_file = $this->get_content_file();
		$this->template_file = $this->get_template_file();
		$this->public_file = $this->get_public_file();
		$this->image_files = $this->get_images(preg_replace('/\/[^\/]+$/', '', $this->content_file));
		$this->link_path = $this->construct_link_path();
	}
	
	function get_sibling_pages() {
		// if current page is a MockPageInCategory, escape this function (to prevent infinite loop)
		if(get_class($this) == 'MockPageInCategory') return array(array(), array());
		// loop through each unclean name looking for a match
		foreach($this->unclean_names as $key => $name) {
			// if match found...
			if($name == $this->name_unclean) {
				// store the names of the next/previous pages
				$previous_name = ($key >= 1) ? $this->unclean_names[$key-1] : $this->unclean_names[(count($this->unclean_names)-1)];
				$next_name = ($key + 1 < count($this->unclean_names)) ? $this->unclean_names[$key+1] : $this->unclean_names[0];
				//store the urls of the next/previous pages
				$previous = array('/@url/' => '../'.$this->clean_name($previous_name));
				$next = array('/@url/' => '../'.$this->clean_name($next_name));
				// create MockPageInCategory objects so we can access the variables of the pages
				$previous_page = new MockPageInCategory($this->category, $previous_name);
				$next_page = new MockPageInCategory($this->category, $next_name);
				
				$c = new ContentParser;
				return array(
					array_merge($previous, $c->parse($previous_page)),
					array_merge($next, $c->parse($next_page)),
				);
				// kill loop
				break;
			}
		}
		
		return array(array(), array());
	}
	
	function get_content_file() {
		// check folder exists
		if($this->name_unclean && $this->category_unclean && file_exists('../content/'.$this->category_unclean.'/'.$this->name_unclean)) {
			// look for a .txt file
			$txts = Helpers::list_files('../content/'.$this->category_unclean.'/'.$this->name_unclean, '/\.txt$/');
			// if $txts contains a result, return it
			if(count($txts) > 0) return '../content/'.$this->category_unclean.'/'.$this->name_unclean.'/'.$txts[0];
			else return '../content/'.$this->category_unclean.'/'.$this->name_unclean.'/none';
		}
		else return '../content/'.$this->category_unclean.'/'.$this->name_unclean.'/none';
	}
	
	function get_template_file() {
		// check folder exists, if not, return 404
		if(!$this->name_unclean) return false;
		// find the name of the text file
		preg_match('/\/([^\/]+?)\.txt/', $this->content_file, $template_name);
		// if template exists, return it
		if(!empty($template_name) && file_exists('../templates/'.$template_name[1].'.html')) return '../templates/'.$template_name[1].'.html';
		// return page-in-category.html as default template (if it exists)
		elseif(file_exists('../templates/page-in-category.html')) return '../templates/page-in-category.html';
		else return false;
	}
	
}

Class MockPageInCategory extends PageInCategory {
	
	var $folder_name;
	
	function __construct($category, $folder_name) {
		$this->folder_name = $folder_name;
		$this->store_unclean_names('../content/');
		$this->category_unclean = $this->unclean_name($category);
		$this->store_unclean_names('../content/'.$this->category_unclean);
		$this->name_unclean = $this->unclean_name(preg_replace('/^\d+?\./', '', $folder_name));
		
		$this->content_file = $this->get_content_file();
		$this->image_files = $this->get_images(preg_replace('/\/[^\/]+$/', '', $this->content_file)); 
		$this->link_path = $this->construct_link_path();
	}

	function get_content_file() {
		// check folder exists
		if($this->folder_name && $this->category_unclean && file_exists('../content/'.$this->category_unclean.'/'.$this->folder_name)) {
			// look for a .txt file
			$txts = Helpers::list_files('../content/'.$this->category_unclean.'/'.$this->folder_name, '/\.txt$/');
			// if $txts contains a result, return it
			if(count($txts) > 0) return '../content/'.$this->category_unclean.'/'.$this->folder_name.'/'.$txts[0];
			else return '../content/'.$this->category_unclean.'/'.$this->folder_name.'/none';
		}
		else return '../content/'.$this->category_unclean.'/'.$this->folder_name.'/none';
	}
	
}

Class ContentParser {
	
	var $page;
	
	function preparse($text) {
		$patterns = array(
			// replace inline colons
			'/(?<=\n)([a-z0-9_-]+?):(?!\/)/',
			'/:/',
			'/\\\x01/',
			// replace inline dashes
			'/(?<=\n)-/',
			'/-/',
			'/\\\x02/',
			// automatically link http:// websites
			'/(?<![">])\bhttp&#58;\/\/([\S]+\.[\S]*\.?[A-Za-z0-9]{2,4})/',
			// automatically link email addresses
			'/(?<![;>])\b([A-Za-z0-9.-]+)@([A-Za-z0-9.-]+\.[A-Za-z]{2,4})/',
			// convert lists
			'/\n?-(.+?)(?=\n)/',
			'/(<li>.*<\/li>)/',
			// replace doubled lis
			'/<\/li><\/li>/',
			// wrap multi-line text in paragraphs
			'/([^\n]+?)(?=\n)/',
			'/<p>(.+):(.+)<\/p>/',
			'/: (.+)(?=\n<p>)/',
			// replace any keys that got wrapped in ps
			'/(<p>)([a-z0-9_-]+):(<\/p>)/',
		);
		$replacements = array(
			// replace inline colons
			'$1\\x01',
			'&#58;',
			':',
			// replace inline dashes
			'\\x02',
			'&#45;',
			'-',
			// automatically link http:// websites
			'<a href="http&#58;//$1">http&#58;//$1</a>',
			// automatically link email addresses
			'<a href="mailto&#58;$1&#64;$2">$1&#64;$2</a>',
			// convert lists
			'<li>$1</li>',
			'<ul>$1</ul>',
			// replace doubled lis
			'</li>',
			// wrap multi-line text in paragraphs
			'<p>$1</p>',
			'$1:$2',
			':<p>$1</p>',
			// replace any keys that got wrapped in ps
			'$2:',
		);
		$parsed_text = preg_replace($patterns, $replacements, $text);
		return $parsed_text;
	}
	
	function create_replacement_rules($text) {
		// push additional useful values to the replacement pairs
		$replacement_pairs = array(
			'/@Images_Count/' => count($this->page->image_files),
			'/@Pages_Count/' => count($this->page->unclean_names),
		);
		
		// if the page is a Category, push category-specific variables
		if(get_class($this->page) == 'Category') {
			$c = new CategoryListPartial;
			// look for a partial file matching the categories name, otherwise fall back to using the category partial
			$partial_file = file_exists('../templates/partials/'.$this->page->name.'.html') ? '../templates/partials/'.$this->page->name.'.html' : '../templates/partials/category-list.html';
			// create a dynamic category list variable
			$replacement_pairs['/@Category_List/'] = $c->render($this->page, $this->page->name_unclean, $partial_file);
		}
		
		// if the page is a PageInCategory, push pageincategory-specific variables
		if(get_class($this->page) == 'PageInCategory' || get_class($this->page) == 'MockPageInCategory') {
			$np = new NextPagePartial;
			$pp = new PreviousPagePartial;
			$replacement_pairs['/@Page_Number/'] = $this->page->i;
			$replacement_pairs['/@Previous_Page/'] = $pp->render($this->page->sibling_pages[0]);
			$replacement_pairs['/@Next_Page/'] = $np->render($this->page->sibling_pages[1]);
		}
		
		// pull out each key/value pair from the content file
		preg_match_all('/[\w\d_-]+?:[\S\s]*?\n\n/', $text, $matches);
		foreach($matches[0] as $match) {
			$colon_split = explode(':', $match);
			$replacement_pairs['/@'.$colon_split[0].'/'] = trim($colon_split[1]);
		}
		// sort keys by length, to ensure replacements are made in the correct order (ie. @page does not partially replace @page_name)
		uksort($replacement_pairs, array('Helpers', 'sort_by_length'));
		return $replacement_pairs;
	}
	
	function parse($page) {
		// store page and parse its content file
		$this->page = $page;
		// store contents of content file (if it exists, otherwise, pass back an empty string)
		$text = (file_exists($this->page->content_file)) ? file_get_contents($this->page->content_file) : '';
		// include shared variables for each page
		$shared = (file_exists('../content/_shared.txt')) ? file_get_contents('../content/_shared.txt') : '';
		// run preparsing rules to clean up content files (the newlines are added to ensure the first and last rules have their double-newlines to match on)
		$parsed_text = $this->preparse("\n\n".$text."\n\n".$shared."\n\n");
		// create the replacement rules
		return $this->create_replacement_rules($parsed_text);
	}
	
}

Class TemplateParser {

	var $page;
	var $replacement_pairs;
	
	function find_categories() {
		$dir = '../content/';
		$categories = array();
		// loop through each top-level folder to check if it contains other folders (in which case it is a category);
		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(is_dir($dir.'/'.$file) && !preg_match('/^\./', $file)) {
						if($idh = opendir($dir.'/'.$file)) {
							while (($inner_file = readdir($idh)) !== false) {
								if(is_dir($dir.'/'.$file.'/'.$inner_file) && !preg_match('/^\./', $inner_file)) {
									// strip leading digit and dot from filename (1.xx becomes xx)
									$file_clean = preg_replace('/^\d+?\./', '', $file);
									$categories[$file] = array(
										'name' => $file,
										'name_clean' => $file_clean,
										// look for a partial file matching the categories name, otherwise fall back to using the category partial
										'partial_file' => file_exists('../templates/partials/'.$file_clean.'.html') ? '../templates/partials/'.$file_clean.'.html' : '../templates/partials/category-list.html'
									);
									break;
								}
							}
							closedir($idh);
						}
					}
				}
				closedir($dh);
			}
		}
		// sort categories in reverse-numeric order
		krsort($categories);
		return $categories;
	}
	
	function create_replacement_partials() {
		// constructs a partial for each category within the content folder
		$c = new CategoryListPartial;
		// constructs a partial containing each image on the page
		$i = new ImagesPartial;
		// constructs a partial containing all of the top level pages & categories, excluding the index
		$n = new NavigationPartial;
		// constructs a partial containing all of the top level pages, excluding any categories and the index
		$p = new PagesPartial;
		
		// construct a special variable which will hold all of the category lists
		$partials['/@Category_Lists/'] = '';
		// find all categories
		$categories = $this->find_categories();
		// category lists will become available as a variable as: '$.projects-folder' => @Projects_Folder
		foreach($categories as $category) {
			// store the output of the CategoryListPartial
			$category_list = $c->render($this->page, $category['name'], $category['partial_file']);
			// create a partial that matches the name of the category
			$partials['/@'.ucfirst(preg_replace('/-(.)/e', "'_'.strtoupper('\\1')", $category['name_clean'])).'/'] = $category_list;
			// append to the @Category_Lists variable
			$partials['/@Category_Lists/'] .= $category_list;
		}
		// construct the rest of the special variables
		$partials['/@Images/'] = $i->render($this->page);
		$partials['/@Navigation/'] = $n->render($this->page);
		$partials['/@Pages/'] = $p->render($this->page);
		$partials['/@Year/'] = date('Y');
		return $partials;
	}
	
	function parse($page, $rules) {
		// store reference to current page
		$this->page = $page;
		// create all the replacement pairs that rely on partials
		$this->replacement_pairs = array_merge($rules, $this->create_replacement_partials());
		// store template file content
		$text = file_get_contents($this->page->template_file);
		// sort keys by length, to ensure replacements are made in the correct order (ie. @page does not partially replace @page_name)
		uksort($this->replacement_pairs, array('Helpers', 'sort_by_length'));
		// run replacements on the template
		return preg_replace(array_keys($this->replacement_pairs), array_values($this->replacement_pairs), $text);
	}
}

Class Partial {
	
	var $page;
	
	function check_thumb($dir, $file) {
		if($dh = opendir($dir.'/'.$file)) {
			while (($inner_file = readdir($dh)) !== false) {
				// check for an image named thumb
				if(!preg_match('/^\./', $inner_file) && preg_match('/thumb\.(gif|jpg|png|jpeg)/i', $inner_file, $file_type)) {
					return preg_replace('/\.\.\//', $this->page->link_path, $dir).'/'.$file.'/thumb.'.$file_type[1];
				}
			}
			closedir($dh);
		}
		return '';
	}

	function parse($file) {
		$partial = (file_exists($file)) ? file_get_contents($file) : '<p>! '.$file.' not found.</p>';
		// split the template file by loop code
		preg_match('/([\S\s]*)foreach[\S\s]*?:([\S\s]*)endforeach;([\S\s]*)/', $partial, $matches);
		// if partial file found, return array containing the markup: before loop, inside loop & after loop (in that order)
		if(count($matches) > 0) return array($matches[1], $matches[2], $matches[3]);
		// if partial file not found, return warning string
		else return array($partial, '', '');
	}
	
}

Class CategoryListPartial extends Partial {
	
	var $dir;
	var $partial_file;

	function render($page, $dir, $partial_file) {
		// store reference to current page
		$this->page = $page;
		// store correct partial file
		$this->partial_file = $partial_file;
		$this->dir = '../content/'.$dir;
		// pull out html wrappers from partial file
		$wrappers = $this->parse($this->partial_file);
		$html = '';
		
		// for each page within this category...
		$files = array();
		if(is_dir($this->dir)) {
		 	if($dh = opendir($this->dir)) {
		 		while (($file = readdir($dh)) !== false) {
					if(is_dir($this->dir.'/'.$file) && !preg_match('/^\./', $file)) {
						// store filename
						$files[] = $file;
						// store url and thumb
						$vars = array(
							'/@url/' => $this->page->link_path.preg_replace('/^\d+?\./', '', $dir).'/'.preg_replace('/^\d+?\./', '', $file).'/',
							'/@thumb/' => $this->check_thumb($this->dir, $file)
						);
						// create a MockPageInCategory to give us access to all the variables inside this PageInCategory
						$c = new ContentParser;
						$category_page = new MockPageInCategory($dir, $file);
						$file_vars[] = array_merge($vars, $c->parse($category_page));
					}
				}
				closedir($dh);
			}

			// sort files in reverse-numeric order
			arsort($files, SORT_NUMERIC);
			// add opening outer wrapper
			$html .= $wrappers[0];
			
			foreach($files as $key => $file) $html .= preg_replace(array_keys($file_vars[$key]), array_values($file_vars[$key]), $wrappers[1]);
			// add closing outer wrapper
			$html .= $wrappers[2];

		}
		
		return $html;
	}
	
}

Class NavigationPartial extends Partial {
	
	var $dir = '../content/';
	var $partial_file = '../templates/partials/navigation.html';

	function render($page) {
		// store reference to current page
		$this->page = $page;
		$html = '';
		// pull out html wrappers from partial file
		$wrappers = $this->parse($this->partial_file);
		
		// collate navigation set
		$files = array();
		$file_vars = array();
		if($dh = opendir($this->dir)) {
			while (($file = readdir($dh)) !== false) {
				// if file is a folder and is not /index, add it to the navigation list
				if(!preg_match('/^\./', $file) && !preg_match('/index/', $file) && !preg_match('/^_/', $file) && !preg_match('/\.txt$/', $file)) {
					$files[] = $file;
					$file_name_clean = preg_replace('/^\d+?\./', '', $file);
					// store the url and name of the navigation item
					$file_vars[] = array(
						'/@url/' => $this->page->link_path.$file_name_clean.'/',
						'/@name/' => ucfirst(preg_replace('/-/', ' ', $file_name_clean)),
					);
				}
			}
			closedir($dh);
		}
		// sort files in reverse-numeric order
		arsort($files, SORT_NUMERIC);
		// add opening outer wrapper
		$html .= $wrappers[0];
		
		foreach($files as $key => $file) {
			$html .= preg_replace(array_keys($file_vars[$key]), array_values($file_vars[$key]), $wrappers[1]);
		}
		// add closing outer wrapper
		$html .= $wrappers[2];
		
		return $html;
	}
	
}

Class PagesPartial extends Partial {
	
	var $dir = '../content';
	var $partial_file = '../templates/partials/pages.html';

	function is_category($dir) {
		if(is_dir($dir)) {
			if($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if(is_dir($dir.'/'.$file) && !preg_match('/^\./', $file)) return true;
				}
				closedir($dh);
			}
		}
		return false;
	}

	function render($page) {
		// store reference to current page
		$this->page = $page;
		$html = '';
		// pull out html wrappers from partial file
		$wrappers = $this->parse($this->partial_file);
		
		// collate navigation set
		$files = array();
		$file_vars = array();
		if($dh = opendir($this->dir)) {
			while (($file = readdir($dh)) !== false) {
				// if file is a folder and is not /index, add it to the navigation list
				if(!preg_match('/^\./', $file) && !preg_match('/index/', $file) && !preg_match('/^_/', $file) && !preg_match('/\.txt$/', $file)) {
					// check if this folder contains inner folders - if it does, then it is a category and should be excluded from this list
					if(!$this->is_category($this->dir.'/'.$file)) {
						$files[] = $file;
						$file_name_clean = preg_replace('/^\d+?\./', '', $file);
						// store the url and name of the navigation item
						$file_vars[] = array(
							'/@url/' => $this->page->link_path.$file_name_clean.'/',
							'/@name/' => ucfirst(preg_replace('/-/', ' ', $file_name_clean)),
						);
					}
				}
			}
			closedir($dh);
		}
		// sort files in reverse-numeric order
		arsort($files, SORT_NUMERIC);
		// add opening outer wrapper
		$html .= $wrappers[0];
		
		foreach($files as $key => $file) {
			$html .= preg_replace(array_keys($file_vars[$key]), array_values($file_vars[$key]), $wrappers[1]);
		}
		// add closing outer wrapper
		$html .= $wrappers[2];
		
		return $html;
	}
	
}

Class ImagesPartial extends Partial {

	var $dir;
	var $partial_file = '../templates/partials/images.html';

	function render($page) {
		// store reference to current page
		$this->page = $page;
		
		// strip out the name of the content file (ie content.txt) to create the path to the folder
		$dir = preg_replace('/\/[^\/]+$/', '', $this->page->content_file);
		$html = '';
		// pull out html wrappers from partial file
		$wrappers = $this->parse($this->partial_file);
		
		// loop through directory looking for images
		$files = array();
		$file_vars = array();
		if(is_dir($dir)) {
		 	if($dh = opendir($dir)) {
		 		while (($file = readdir($dh)) !== false) {
					// if images isn't a thumb, add it to the files array
		 			if(!preg_match('/^\./', $file) && preg_match('/\.(gif|jpg|png|jpeg)/i', $file) && !preg_match('/^thumb\./i', $file)) {
						$files[] = $file;
						$file_vars[] = array(
							// store url to this image, appending the correct link path
							'/@url/' => $this->page->link_path.preg_replace('/\.\.\//', '', $dir).'/'.$file,
						);
					}
				}
				closedir($dh);
			}
			
			if(count($files) > 0) {
				// sort files in reverse-numeric order
				arsort($files, SORT_NUMERIC);
				// add opening outer wrapper
				$html .= $wrappers[0];
				// loop through inner wrapper, replacing any variables contained within
				foreach($files as $key => $file) $html .= preg_replace(array_keys($file_vars[$key]), array_values($file_vars[$key]), $wrappers[1]);
				// add closing outer wrapper
				$html .= $wrappers[2];
			}
		}
		return $html;
	}

}

Class NextPagePartial extends Partial {
	var $partial_file = '../templates/partials/next-page.html';
	
	function render($page_sibling) {
		// replace html with @vars
		$html = (count($page_sibling) > 0) ? preg_replace(array_keys($page_sibling), array_values($page_sibling), file_get_contents($this->partial_file)) : '';
		return $html;
	}
}

Class PreviousPagePartial extends NextPagePartial {
	var $partial_file = '../templates/partials/previous-page.html';
}

?>