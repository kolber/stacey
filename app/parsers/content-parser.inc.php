<?php

Class ContentParser {
	
	static function parse($text) {
		$patterns = array(
			# replace inline colons
			'/(?<=\n)([a-z0-9_-]+?):(?!\/)/u',
			'/:/u',
			'/\\\x01/u',
			# replace inline dashes
			'/(?<=\n)-/u',
			'/-/u',
			'/\\\x02/u',
			# automatically link http:// websites
			'/(?<![">])\bhttps?&#58;\/\/([-A-Za-z0-9+&@#\/%\?\=~_\(\)|!:,.;]*[-A-Za-z0-9+&@#\/%=~_|])/u',
			# automatically link email addresses
			'/(?<![;>])\b([A-Za-z0-9.-]+)@([A-Za-z0-9.-]+\.[A-Za-z]{2,4})/u',
			# convert lists
			'/\n?-(.+?)(?=\n)/u',
			'/(<li>.*<\/li>)/u',
			# replace doubled lis
			'/<\/li><\/li>/u',
			# replace headings h1. h2. etc
			'/h([0-5])\.\s?(.*)/u',
			# wrap multi-line text in paragraphs
			'/([^\n]+?)(?=\n)/u',
			'/<p>(.+):(.+)<\/p>/u',
			'/: (.+)(?=\n<p>)/u',
			# replace any keys that got wrapped in ps
			'/(<p>)([a-z0-9_-]+):(<\/p>)/u',
			# replace any headings that got wrapped in ps
			'/<p>(<h[0-5]>.*<\/h[0-5]>)<\/p>/u'
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
			# automatically link http:// websites
			'<a href="http&#58;//$1">http&#58;//$1</a>',
			# automatically link email addresses
			'<a href="mailto&#58;$1&#64;$2">$1&#64;$2</a>',
			# convert lists
			'<li>$1</li>',
			'<ul>$1</ul>',
			# replace doubled lis
			'</li>',
			# replace headings h1. h2. etc
			'<h$1>$2</h$1>',
			# wrap multi-line text in paragraphs
			'<p>$1</p>',
			'$1:$2',
			':<p>$1</p>',
			# replace any keys that got wrapped in ps
			'$2:',
			'$1'
		);
		$parsed_text = preg_replace($patterns, $replacements, $text);
		return $parsed_text;
	}
	
}

?>