WP Plugin Uninstall Tester
==========================

A testcase class for testing plugin install and uninstall, with related tools.

# Background #

The purpose of this testcase is to allow you to make plugin uninstall testing as
realistic as possible. WordPress uninstalls plugins when they aren't active, and
these tools allow you simulate that. The installation is performed remotely, so the
plugin is not loaded when the tests are being run.

I created these tools after finding that there was a fatal error in one of my
plugin's uninstall scripts. Not that I didn't have unit tests for uninstallation. I
did. But the uninstall tests were being run with the plugin already loaded. So I
never realized that I was calling one of the plugin's functions that wouldn't
normally be available. That's when I decided to create these testing tools, so my
uninstall tests would fail if I wasn't including all required dependencies in my
plugin's uninstall script.

In addition to providing a realistic uninstall testing environment, it also provides
some assertions to help you make sure that your plugin entirely cleaned up the
database.

# Installation #

## Composer ##
You can install this library with composer by adding it in your `package.json`:

```json
    "require-dev": {
        "jdgrimes/wp-plugin-uninstall-tester": "~0.4"
    }
```

# Set Up #

Once you have the testing tools installed, you need to make a few changes to your
bootstrap file for PHPUnit. We're going to assume that you have a bootstrap file
similar to the one in
[this tutorial](http://codesymphony.co/writing-wordpress-plugin-unit-tests/).

First, you need to include the `/includes/functions.php` file, so you can
use `running_wp_plugin_uninstall_tests()` to check if the uninstall tests are being
run. You need to be sure that you only load your plugin's files if the uninstall
tests aren't being run.

```php
/*
 * This needs to go after you include WordPress's unit test functions, but before
 * loading WordPress's bootstrap.php file.
 */

// Include the uninstall test tools functions.
include_once dirname( __FILE__ ) . '/uninstall/includes/functions.php';

// Check if the tests are running. Only load the plugin if they aren't.
if ( ! running_wp_plugin_uninstall_tests() ) {
	tests_add_filter( 'muplugins_loaded', 'my_plugin_activate' );
}
```

Secondly, you need to include the `bootstrap.php` file:

```php
/*
 * This needs to be included after loading WordPress's bootstrap.php, because the
 * uninstall testcase extends WordPress's WP_UnitTestCase class.
 */

include_once dirname( __FILE__ ) . '/uninstall/bootstrap.php';
```

Thirdly, you need to exclude the `uninstall` group from the tests in your PHPUnit XML
config file:

```xml
	<!-- This needs to go inside of the <phpunit></phpunit> tags -->
	<groups>
		<exclude>
			<group>uninstall</group>
		</exclude>
	</groups>
```

That will exclude the uninstall tests from running by default. To run them, you'll
need to do `phpunit --group=uninstall`.

# Usage #

Now, it's finally time to create a testcase. To do this, extend the `WP_Plugin_Uninstall_UnitTestCase`
class.

```php
<?php

/**
 * Test uninstallation.
 */

/**
 * Plugin uninstall test case.
 *
 * Be sure to add "@group uninstall", so that the test will run only as part of the
 * uninstall group.
 *
 * @group uninstall
 */
class My_Plugin_Uninstall_Test extends WP_Plugin_Uninstall_UnitTestCase {

	//
	// Protected properties.
	//

	/**
	 * The full path to the main plugin file.
	 *
	 * @type string $plugin_file
	 */
	protected $plugin_file;

	/**
	 * The plugin's install function.
	 *
	 * @type callable $install_function
	 */
	protected $install_function = 'my_plugin_activate';

	//
	// Public methods.
	//

	/**
	 * Set up for the tests.
	 */
	public function setUp() {

		// You must set the path to your plugin here.
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/myplugin.php';

		// Don't forget to call the parent's setUp(), or the plugin won't get installed.
		parent::setUp();
	}

	/**
	 * Test installation and uninstallation.
	 */
	public function test_uninstall() {

		/*
		 * First test that the plugin installed itself properly.
		 */

		// Check that a database table was added.
		$this->assertTableExists( $wpdb->prefix . 'myplugin_table' );

		// Check that an option was added to the database.
		$this->assertEquals( 'default', get_option( 'myplugin_option' ) );

		/*
		 * Now, test that it uninstalls itself properly.
		 */

		// You must call this to perform uninstallation.
		$this->uninstall();

		// Check that the table was deleted.
		$this->assertTableNotExists( $wpdb->prefix . 'myplugin_table' );

		// Check that all options with a prefix was deleted.
		$this->assertNoOptionsWithPrefix( 'myplugin' );

		// Same for usermeta and comment meta.
		$this->assertNoUserMetaWithPrefix( 'myplugin' );
		$this->assertNoCommentMetaWithPrefix( 'myplugin' );
	}
}

```

Save your testcase and you are all set!

# Plugin Usage Simulation #

The above example is a great first step in testing that your plugin is uninstalling
itself completely. However, you can probably do better. The above testcase is only
testing uninstallation from a fresh, clean install of your plugin. But what about
after the user has actually used your plugin for a while? It will probably have added
some more options to the database somewhere along the way. To have more robust and
complete uninstall tests, it is needful to simulate plugin usage.

The testcase has provided for this. To use this feature, write up a script that will
simulate your plugin being used. Call your various functions that add data to the
database, for example. Save your code in a file.

Now all you need to do for the testcase to run the simulation, is specify the path of
the file you just created in the `$simulation_file` class property (same as we did
with the main plugin file and the `$plugin_file` property above).

The plugin usage simulation script will now be run remotely before the plugin is
uninstalled. You can also run it before this if needed, by calling
`$this->simulate_usage()`.

# License #

This library is jointly licensed under the MIT and GPLv2.
