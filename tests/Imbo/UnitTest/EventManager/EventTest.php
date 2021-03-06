<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 */

namespace Imbo\UnitTest\EventManager;

use Imbo\EventManager\Event;

/**
 * @package TestSuite\UnitTests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/imbo/imbo
 * @covers Imbo\EventManager\Event
 */
class EventTest extends \PHPUnit_Framework_TestCase {
    /**
     * @covers Imbo\EventManager\Event::__construct
     * @covers Imbo\EventManager\Event::getName
     * @covers Imbo\EventManager\Event::getRequest
     * @covers Imbo\EventManager\Event::getResponse
     * @covers Imbo\EventManager\Event::getDatabase
     * @covers Imbo\EventManager\Event::getStorage
     * @covers Imbo\EventManager\Event::getImage
     */
    public function testEvent() {
        $name = 'some.event.name';
        $request = $this->getMock('Imbo\Http\Request\RequestInterface');
        $response = $this->getMock('Imbo\Http\Response\ResponseInterface');
        $database = $this->getMock('Imbo\Database\DatabaseInterface');
        $storage = $this->getMock('Imbo\Storage\StorageInterface');
        $image = $this->getMock('Imbo\Image\ImageInterface');

        $event = new Event($name, $request, $response, $database, $storage, $image);

        $this->assertSame($name, $event->getName());
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($response, $event->getResponse());
        $this->assertSame($database, $event->getDatabase());
        $this->assertSame($storage, $event->getStorage());
        $this->assertSame($image, $event->getImage());
    }

    /**
     * @covers Imbo\EventManager\Event::__construct
     * @covers Imbo\EventManager\Event::getImage
     */
    public function testEventWithNoImageInstance() {
        $event = new Event('some name',
                           $this->getMock('Imbo\Http\Request\RequestInterface'),
                           $this->getMock('Imbo\Http\Response\ResponseInterface'),
                           $this->getMock('Imbo\Database\DatabaseInterface'),
                           $this->getMock('Imbo\Storage\StorageInterface'));

        $this->assertNull($event->getImage());
    }
}
