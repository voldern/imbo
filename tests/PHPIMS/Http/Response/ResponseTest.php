<?php
/**
 * PHPIMS
 *
 * Copyright (c) 2011 Christer Edvartsen <cogo@starzinger.net>
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
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */

namespace PHPIMS\Http\Response;

use PHPIMS\Exception;

/**
 * @package PHPIMS
 * @subpackage Unittests
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/phpims
 */
class ResponseTest extends \PHPUnit_Framework_TestCase {
    /**
     * Response instance
     *
     * @var PHPIMS\Http\Response\Response
     */
    private $response;

    /**
     * Set up method
     */
    public function setUp() {
        $this->response = new Response();
    }

    /**
     * Tear down method
     */
    public function tearDown() {
        $this->response = null;
    }

    public function testSetGetStatusCode() {
        $code = 404;
        $this->response->setStatusCode($code);
        $this->assertSame($code, $this->response->getStatusCode());
    }

    public function testSetGetHeaders() {
        $headers = array(
            'Location' => 'http://foo/bar',
            'x-Some' => 'Value',
        );
        $this->response->setHeaders($headers);
        $this->assertSame($headers, $this->response->getHeaders());
    }

    public function testSetGetHeader() {
        $headers = array(
            'Location' => 'http://foo/bar',
            'X-Some'   => 'Value',
        );

        foreach ($headers as $name => $value) {
            $this->response->setHeader($name, $value);
        }

        $this->assertSame($headers, $this->response->getHeaders());
    }

    public function testSetGetBody() {
        $body = 'some content';
        $this->response->setBody($body);
        $this->assertSame($body, $this->response->getBody());
    }

    public function testRemoveHeader() {
        $this->response->setHeader('Location', 'http://foobar');
        $this->assertArrayHasKey('Location', $this->response->getHeaders());
        $this->response->removeHeader('Location');
        $this->assertArrayNotHasKey('Location', $this->response->getHeaders());
    }

    public function testSetGetProtocolVersion() {
        // Assert default version
        $this->assertSame('1.1', $this->response->getProtocolVersion());
        $this->response->setProtocolVersion('1.0');
        $this->assertSame('1.0', $this->response->getProtocolVersion());
    }
}
