<?php
// This mongodb is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * MongoDB unit tests.
 *
 * If you wish to use these unit tests all you need to do is add the following definition to
 * your config.php file.
 *
 * define('TEST_CACHESTORE_MONGODB_TESTSERVER', 'mongodb://localhost:27017');
 *
 * @package    cachestore_mongodb
 * @copyright  2013 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Include the necessary evils.
global $CFG;
require_once($CFG->dirroot.'/cache/tests/fixtures/stores.php');
require_once($CFG->dirroot.'/cache/stores/mongodb/lib.php');

/**
 * MongoDB unit test class.
 *
 * @package    cachestore_mongodb
 * @copyright  2013 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachestore_mongodb_test extends cachestore_tests {
    /**
     * Returns the MongoDB class name
     * @return string
     */
    protected function get_class_name() {
        return 'cachestore_mongodb';
    }

    /**
     * A small additional test to make sure definitions that hash a hash starting with a number work OK
     */
    public function test_collection_name() {
        // This generates a definition that has a hash starting with a number. MDL-46208.
        $definition = cache_definition::load_adhoc(cache_store::MODE_APPLICATION, 'cachestore_mongodb', 'abc');
        $instance = cachestore_mongodb::initialise_unit_test_instance($definition);

        if (!$instance) {
            $this->markTestSkipped();
        }

        $this->assertTrue($instance->set(1, 'alpha'));
        $this->assertTrue($instance->set(2, 'beta'));
        $this->assertEquals('alpha', $instance->get(1));
        $this->assertEquals('beta', $instance->get(2));
        $this->assertEquals(array(
            1 => 'alpha',
            2 => 'beta'
        ), $instance->get_many(array(1, 2)));
    }

    /**
     * Tests MongoDB in extended mode.
     *
     * @throws coding_exception
     */
    public function test_extended_mode() {
        if (!defined('TEST_CACHESTORE_MONGODB_TESTSERVER')) {
            $this->markTestSkipped('MongoDB has not been set up for testing');
            return false;
        }
        /** @var cache_config_phpunittest $config */
        $config = cache_config_phpunittest::instance();
        // Create a mongodb store instance with the test server config.
        $config->phpunit_add_store_with_config('mongodb_extendedmode', array(
            'name' => 'mongodb_extendedmode',
            'plugin' => 'mongodb',
            'configuration' => array(
                'extendedmode' => 1,
                'servers' => TEST_CACHESTORE_MONGODB_TESTSERVER,
                'usesafe' => 1
            ),
            'features' => cache_store::SUPPORTS_DATA_GUARANTEE + cache_store::SUPPORTS_MULTIPLE_IDENTIFIERS,
            'modes' => cache_store::MODE_APPLICATION + cache_store::MODE_SESSION,
            'mappingsonly' => false,
            'class' => 'cachestore_mongodb',
            'default' => false,
            'lock' => 'cachelock_file_default'
        ));
        // Create a file store (will be used as a secondary loader for advanced testing).
        $config->phpunit_add_file_store('file_extendedmode');
        // Create a definition to test against.
        $config->phpunit_add_definition('cachestore_mongodb/extendedmode', array(
            'mode' => cache_store::MODE_APPLICATION,
            'component' => 'cachestore_mongodb',
            'area' => 'extendedmode',
            'simplekeys' => true
        ), false);
        // Map the two stores to the definition.
        $config->phpunit_add_definition_mapping('cachestore_mongodb/extendedmode', 'mongodb_extendedmode', 1);
        $config->phpunit_add_definition_mapping('cachestore_mongodb/extendedmode', 'file_extendedmode', 2);

        // Initialise the cache and make sure it is what we think it is.
        $cache = cache::make('cachestore_mongodb', 'extendedmode');
        $this->assertInstanceOf('cache_phpunit_application', $cache);
        $this->assertSame('cachestore_mongodb', $cache->phpunit_get_store_class());

        // Start by purging - important because the store may have had a failed test in the past and still have data.
        $this->assertTrue($cache->purge());

        // Test the cache.
        $this->assertFalse($cache->get('test'));
        $this->assertTrue($cache->set('test', 'test'));
        $this->assertEquals('test', $cache->get('test'));
        $this->assertTrue($cache->delete('test'));
        $this->assertFalse($cache->get('test'));
        $this->assertTrue($cache->set('test', 'test'));
        $this->assertTrue($cache->purge());
        $this->assertFalse($cache->get('test'));

        // Test the many commands.
        $this->assertEquals(3, $cache->set_many(array('a' => 'A', 'b' => 'B', 'c' => 'C')));
        $result = $cache->get_many(array('a', 'b', 'c'));
        $this->assertInternalType('array', $result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('b', $result);
        $this->assertArrayHasKey('c', $result);
        $this->assertEquals('A', $result['a']);
        $this->assertEquals('B', $result['b']);
        $this->assertEquals('C', $result['c']);
        $this->assertEquals($result, $cache->get_many(array('a', 'b', 'c')));
        $this->assertEquals(2, $cache->delete_many(array('a', 'c')));
        $result = $cache->get_many(array('a', 'b', 'c'));
        $this->assertInternalType('array', $result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('b', $result);
        $this->assertArrayHasKey('c', $result);
        $this->assertFalse($result['a']);
        $this->assertEquals('B', $result['b']);
        $this->assertFalse($result['c']);

        // Test the many commands with a single use.
        $this->assertEquals(1, $cache->set_many(array('d' => 'D')));
        $result = $cache->get_many(array('d'));
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('d', $result);
        $this->assertEquals('D', $result['d']);
        $this->assertEquals($result, $cache->get_many(array('d')));
        $this->assertEquals(1, $cache->delete_many(array('d')));
        $result = $cache->get_many(array('d'));
        $this->assertInternalType('array', $result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('d', $result);
        $this->assertFalse($result['d']);

        // Test non-recursive deletes.
        $this->assertTrue($cache->set('test', 'test'));
        $this->assertSame('test', $cache->get('test'));
        $this->assertTrue($cache->delete('test', false));
        // We should still have it on a deeper loader.
        $this->assertSame('test', $cache->get('test'));
        // Test non-recusive with many functions.
        $this->assertSame(3, $cache->set_many(array(
            'one' => 'one',
            'two' => 'two',
            'three' => 'three'
        )));
        $this->assertSame('one', $cache->get('one'));
        $this->assertSame(array('two' => 'two', 'three' => 'three'), $cache->get_many(array('two', 'three')));
        $this->assertSame(3, $cache->delete_many(array('one', 'two', 'three'), false));
        $this->assertSame('one', $cache->get('one'));
        $this->assertSame(array('two' => 'two', 'three' => 'three'), $cache->get_many(array('two', 'three')));

        // Finally purge the cache to clean up.
        $this->assertTrue($cache->purge());
    }

    /**
     * This is a test with extended mode on and simple keys off.
     * Extended mode should not be enabled and we should be able to insert all sorts of stuff.
     */
    public function test_extendedmode_without_simplekeys() {
        if (!defined('TEST_CACHESTORE_MONGODB_TESTSERVER')) {
            $this->markTestSkipped('MongoDB has not been set up for testing');
            return false;
        }
        /** @var cache_config_phpunittest $config */
        $config = cache_config_phpunittest::instance();
        // Create a mongodb store instance with the test server config.
        $config->phpunit_add_store_with_config('mongodb_extendedmode', array(
            'name' => 'mongodb_extendedmode',
            'plugin' => 'mongodb',
            'configuration' => array(
                'extendedmode' => 1,
                'servers' => TEST_CACHESTORE_MONGODB_TESTSERVER,
                'usesafe' => 1
            ),
            'features' => cache_store::SUPPORTS_DATA_GUARANTEE + cache_store::SUPPORTS_MULTIPLE_IDENTIFIERS,
            'modes' => cache_store::MODE_APPLICATION + cache_store::MODE_SESSION,
            'mappingsonly' => false,
            'class' => 'cachestore_mongodb',
            'default' => false,
            'lock' => 'cachelock_file_default'
        ));
        // Create a file store (will be used as a secondary loader for advanced testing).
        $config->phpunit_add_file_store('file_extendedmode');
        // Create a definition to test against.
        $config->phpunit_add_definition('cachestore_mongodb/extendedmode', array(
            'mode' => cache_store::MODE_APPLICATION,
            'component' => 'cachestore_mongodb',
            'area' => 'extendedmode'
        ), false);
        // Map the two stores to the definition.
        $config->phpunit_add_definition_mapping('cachestore_mongodb/extendedmode', 'mongodb_extendedmode', 1);
        $config->phpunit_add_definition_mapping('cachestore_mongodb/extendedmode', 'file_extendedmode', 2);

        // Initialise the cache and make sure it is what we think it is.
        $cache = cache::make('cachestore_mongodb', 'extendedmode');
        $this->assertInstanceOf('cache_phpunit_application', $cache);
        $this->assertSame('cachestore_mongodb', $cache->phpunit_get_store_class());

        // Start by purging - important because the store may have had a failed test in the past and still have data.
        $this->assertTrue($cache->purge());

        // Test the cache.
        $this->assertFalse($cache->get('test'));
        $this->assertTrue($cache->set('test', 'test'));
        $this->assertEquals('test', $cache->get('test'));
        $this->assertTrue($cache->delete('test'));
        $this->assertFalse($cache->get('test'));
        $this->assertTrue($cache->set('test', 'test'));
        $this->assertTrue($cache->purge());
        $this->assertFalse($cache->get('test'));

        // Now test with non-sense keys.
        $key1 = str_repeat('what a key! ', 100);
        $this->assertFalse($cache->get($key1));
        $this->assertTrue($cache->set($key1, 'test'));
        $this->assertEquals('test', $cache->get($key1));

        $key2 = 196.6e32;
        $this->assertFalse($cache->get($key2));
        $this->assertTrue($cache->set($key2, 'test'));
        $this->assertEquals('test', $cache->get($key2));

        // Test get many.
        $result = $cache->get_many(array($key1, $key2));
        $this->assertInternalType('array', $result);
        $this->assertCount(2, $result);
        $this->assertEquals('test', $result[$key1]);
        $this->assertEquals('test', $result[$key2]);
    }
}