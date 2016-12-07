<?php
/**
 * HomeControllerTest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

namespace Admin;

use TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-12-07 at 18:50:31.
 */
class HomeControllerTest extends TestCase
{


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\HomeController::index
     * Implement testIndex().
     */
    public function testIndex()
    {
        $this->be($this->user());
        $this->call('GET', route('admin.index'));
        $this->assertResponseStatus(200);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
}
