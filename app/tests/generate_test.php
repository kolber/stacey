<?php

require_once('./simpletest/autorun.php');
require_once('./simpletest/mock_objects.php');
require_once('../generate/generate.php');

error_reporting(E_ALL & ~E_DEPRECATED);
Mock::generate('GenerateFile');

class TestOfErrors extends UnitTestCase {
	
	function testErrorHaltsImportWhenErrorSuppressionOff() {
		Error::set_error_suppression(false);
		$result = Error::raise('Testing error.');
		$this->assertFalse($result);
	}
	
	function testErrorReturnsStringWhenErrorSuppressionOn() {
		Error::set_error_suppression(true);
		$result = Error::raise('Testing error.');
		$this->assertIsA($result, 'String');
	}
	
}

class TestOfGenerateFile extends UnitTestCase {
	
	var $location;
	
	function setUp() {
		$this->location = dirname(__FILE__);
		Error::set_error_suppression(true);
		$this->generate_file = new GenerateFile;
	}
	
	function testErrorIfTextFileNonexistent() {
		$this->assertTrue(!file_exists($this->location.'/../generate/non-existant-file.txt'));
		$result = $this->generate_file->store_file($this->location.'/../generate/non-existant-file.txt');
		$this->assertIsA($result, 'String');
		$this->assertPattern('/non-existant-file\.txt.*was.not.found/', $result);
	}
	
}

class TestOfGenerate extends UnitTestCase {
	
	var $generate_file;
	
	function setUp() {
		$this->location = dirname(__FILE__);
		Error::set_error_suppression(true);
		$this->generate_file = &new MockGenerateFile();
		$this->generate_file->setReturnValue('modified_date', 0);
	}
	
	function tearDown() {
	}
	
	function testErrorIfOutdatedTextFile() {
		$generate = new Generate();
		$result = $generate->parse($this->generate_file, $this->location.'/../../content');
		$this->assertIsA($result, 'String');
		$this->assertPattern('/newer.than.generate\.txt.file/', $result);
	}
	
	function testFalseReturnIfTextFileContainsNoRules() {
		$generate = new Generate();
		$this->generate_file = &new MockGenerateFile();
		$this->generate_file->setReturnValue('get_contents', '');
		$result = $generate->contains_creation_rules($this->generate_file->get_contents());
		$this->assertFalse($result);
	}
	
	function testParentFolderRuleMatches() {
		$generate = new Generate();
		$this->generate_file = &new MockGenerateFile();
		$this->generate_file->setReturnValue('get_contents', 'projects/category.txt');
		$result = $generate->contains_creation_rules($this->generate_file->get_contents());
		$this->assertTrue($result);
	}
	
	function testChildFolderRuleMatches() {
		$generate = new Generate();
		$this->generate_file = &new MockGenerateFile();
		$this->generate_file->setReturnValue('get_contents', '- project-1/project.txt: The Test Project 1');
		$result = $generate->contains_creation_rules($this->generate_file->get_contents());
		$this->assertTrue($result);
	}
	
	function testSyntaxErrorRuleFails() {
		$generate = new Generate();
		$this->generate_file = &new MockGenerateFile();
		$this->generate_file->setReturnValue('get_contents', 'project-1=project.txt: The Test Project 1');
		$result = $generate->contains_creation_rules($this->generate_file->get_contents());
		$this->assertFalse($result);
	}
	
}

?>