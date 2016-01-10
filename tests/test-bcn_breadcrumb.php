<?php
/**
 * This file contains tests for the bcn_breadcrumb class
 *
 * @group bcn_breadcrumb
 * @group bcn_core
 */
class BreadcrumbTest extends WP_UnitTestCase {
	public $breadcrumb;
	function setUp() {
		parent::setUp();
		$this->breadcrumb = new bcn_breadcrumb('test', bcn_breadcrumb::get_default_template(), array('page', 'current-item'), 'http://flowissues.com/test', 101);
	}
	public function tearDown() {
		parent::tearDown();
	}
	function test_assemble_linked() {
		//First test a linked breadcrumb
		$breadcrumb_string_linked1 = $this->breadcrumb->assemble(true, 81);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked1);
		//Check that our position was populated
		$this->assertContains('content="81"', $breadcrumb_string_linked1);
		
		//Now test a breadcrumb that didn't have a template passed in
		$breadcrumb2 = new bcn_breadcrumb('test', '', array('page', 'current-item'), 'http://flowissues.com/test', 101);
		$breadcrumb_string_linked2 = $breadcrumb2->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked2);
		
	}
	function test_assemble_unlinked() {
		//Test a breadcrumb that can be linked, but is unlinked from the assemble function
		$breadcrumb_string_unlinked1 = $this->breadcrumb->assemble(false, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><span property="name">%s</span><meta property="position" content="%d"></span>', $breadcrumb_string_unlinked1);
		
		//Now test a breadcrumb that shouldn't be linked (both assemble and creation)
		$breadcrumb_unlinked = new bcn_breadcrumb('test', bcn_breadcrumb::default_template_no_anchor, array('page', 'current-item'), NULL, 102);
		$breadcrumb_string_unlinked2 = $breadcrumb_unlinked->assemble(false, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><span property="name">%s</span><meta property="position" content="%d"></span>', $breadcrumb_string_unlinked2);
		
		//Now test a breadcrumb that shouldn't be linked (just creation)
		$breadcrumb_unlinked = new bcn_breadcrumb('test', bcn_breadcrumb::default_template_no_anchor, array('page', 'current-item'), NULL, 103);
		$breadcrumb_string_unlinked3 = $breadcrumb_unlinked->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><span property="name">%s</span><meta property="position" content="%d"></span>', $breadcrumb_string_unlinked3);
	}
	function test_get_title() {
		//Test to see if we get back the title we expect
		$this->assertSame('test', $this->breadcrumb->get_title());
	}
	function test_set_title() {
		//Update the title and check that the update took
		$this->breadcrumb->set_title('Hello');
		$this->assertSame('Hello', $this->breadcrumb->get_title());
	}
	function test_bad_title() {
		$source = "'penn & teller' & at&t";
		$resa = "&#039;penn &amp; teller&#039; &amp; at&amp;t";
		//Set the title
		$this->breadcrumb->set_title($source);
		//Ensure the title hasn't changed yet (escape later)
		$this->assertSame($source, $this->breadcrumb->get_title());
		//Assemble the breadcrumb
		$breadcrumb_string_linked1 = $this->breadcrumb->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked1);
		//Check that our titles are escaped as expected
		$this->assertContains('title="Go to ' . $resa . '."', $breadcrumb_string_linked1);
		$this->assertContains('<span property="name">' . $source . '</span>', $breadcrumb_string_linked1);
	}
	function test_set_url() {
		//Start with an unlinked breadcrumb trail assembly
		$breadcrumb_unlinked = new bcn_breadcrumb('test', bcn_breadcrumb::default_template_no_anchor, array('page', 'current-item'), NULL, 101);
		$breadcrumb_string_unlinked1 = $breadcrumb_unlinked->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><span property="name">%s</span><meta property="position" content="%d"></span>', $breadcrumb_string_unlinked1);
		
		//Now set a URL
		$breadcrumb_unlinked->set_url('http://flowissues.com/code');
		$breadcrumb_string_linked1 = $breadcrumb_unlinked->assemble(true, 1);
		//Make sure we changed automatically to a linked template
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked1);
		//Make sure we have the expected URL in the output
		$this->assertContains('http://flowissues.com/code', $breadcrumb_string_linked1);
		
		//Now change the URL
		$breadcrumb_unlinked->set_url('http://flowissues.com/food');
		$breadcrumb_string_linked2 = $breadcrumb_unlinked->assemble(true, 1);
		//Make sure we changed automatically to a linked template
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked2);
		//Make sure we have the expected URL in the output
		$this->assertContains('http://flowissues.com/food', $breadcrumb_string_linked2);
		
		//Now pass in a blank URL
		$breadcrumb_unlinked->set_url('');
		$breadcrumb_string_unlinked2 = $breadcrumb_unlinked->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><span property="name">%s</span><meta property="position" content="%d"></span>', $breadcrumb_string_unlinked2);
		
		//Now pass in a NULL URL
		$breadcrumb_unlinked->set_url(NULL);
		$breadcrumb_string_unlinked3 = $breadcrumb_unlinked->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><span property="name">%s</span><meta property="position" content="%d"></span>', $breadcrumb_string_unlinked3);
	}
	function test_bad_url() {	
		//First test a linked breadcrumb
		$breadcrumb_string_linked1 = $this->breadcrumb->assemble(true, 81);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked1);
		//Now change the URL to a bad URL
		$this->breadcrumb->set_url('feed:javascript:alert(1)');
		$breadcrumb_string_linked2 = $this->breadcrumb->assemble(true, 1);
		//Make sure we changed automatically to a linked template, though the link should be empty
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked2);
		//Make sure we do not have the bad URL items in the output
		$this->assertRegExp('/^((?!feed\:javascript\:alert\(1\)).)*$/s', $breadcrumb_string_linked2);
	}
	function test_set_template() {
		//Ensure the raw setup is as expected
		$breadcrumb_string_linked1 = $this->breadcrumb->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked1);
		
		//Now change the template
		$this->breadcrumb->set_template('<a href="%link%">%htitle%</a>');
		$breadcrumb_string_linked2 = $this->breadcrumb->assemble(true, 1);
		$this->assertStringMatchesFormat('<a href="%s">%s</a>', $breadcrumb_string_linked2);
	}
	function test_get_id() {
		//Test to see if we get back the ID we expect
		$this->assertSame(101, $this->breadcrumb->get_id());
	}
	function test_set_id() {
		//Update the ID and check that the update took
		$this->breadcrumb->set_id(92);
		$this->assertSame(92, $this->breadcrumb->get_id());
	}
	function test_add_type() {
		//First test a linked breadcrumb
		$breadcrumb_string_linked1 = $this->breadcrumb->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked1);
		$this->assertContains('page', $breadcrumb_string_linked1);
		$this->assertContains('current-item', $breadcrumb_string_linked1);
		
		//Now add another type to the mix
		$this->breadcrumb->add_type('somethingelse');
		$breadcrumb_string_linked2 = $this->breadcrumb->assemble(true, 1);
		$this->assertStringMatchesFormat('<span property="itemListElement" typeof="ListItem"><a property="item" typeof="WebPage" title="Go to %s." href="%s" class="%s"><span property="name">%s</span></a><meta property="position" content="%d"></span>', $breadcrumb_string_linked2);
		$this->assertContains('page', $breadcrumb_string_linked2);
		$this->assertContains('current-item', $breadcrumb_string_linked2);
		$this->assertContains('somethingelse', $breadcrumb_string_linked2);
	}
}