<?php
/**
 * Imbo
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
 * @package Imbo
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Resource;

use Imbo\Exception as ImboException;
use Imbo\Http\Request\RequestInterface;
use Imbo\Http\Response\ResponseInterface;
use Imbo\Database\DatabaseInterface;
use Imbo\Storage\StorageInterface;
use Imbo\Image\Image as ImageObject;
use Imbo\Image\Exception as ImageException;
use Imbo\Image\ImageInterface;
use Imbo\Image\ImagePreparation;
use Imbo\Image\ImagePreparationInterface;
use Imbo\Database\Exception as DatabaseException;
use Imbo\Image\Transformation\Convert;

/**
 * Image resource
 *
 * @package Imbo
 * @subpackage Resources
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011, Christer Edvartsen
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class Image extends Resource implements ResourceInterface {
    /**
     * Image for the client
     *
     * @var Imbo\Image\ImageInterface
     */
    private $image;

    /**
     * Image prepation instance
     *
     * @var Imbo\Image\ImagePreparation
     */
    private $imagePreparation;

    /**
     * Class constructor
     *
     * @param Imbo\Image\ImageInterface $image An image instance
     * @param Imbo\Image\ImagePreparationInterface $imagePreparation An image preparation instance
     */
    public function __construct(ImageInterface $image = null, ImagePreparationInterface $imagePreparation = null) {
        if ($image === null) {
            $image = new ImageObject();
        }

        if ($imagePreparation === null) {
            $imagePreparation = new ImagePreparation();
        }

        $this->image = $image;
        $this->imagePreparation = $imagePreparation;
    }

    /**
     * @see Imbo\Resource\ResourceInterface::getAllowedMethods()
     */
    public function getAllowedMethods() {
        return array(
            RequestInterface::METHOD_GET,
            RequestInterface::METHOD_HEAD,
            RequestInterface::METHOD_DELETE,
            RequestInterface::METHOD_PUT,
        );
    }

    /**
     * @see Imbo\Resource\ResourceInterface::put()
     */
    public function put(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        try {
            // Prepare the image based on the input stream in the request
            $this->imagePreparation->prepareImage($request, $this->image);
        } catch (ImageException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        try {
            // Insert the image to the database
            $database->insertImage($publicKey, $imageIdentifier, $this->image);

            // Store the image
            $storage->store($publicKey, $imageIdentifier, $this->image);
        } catch (ImboException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        // Populate the response object
        $response->setStatusCode(201)
                 ->setBody(array('imageIdentifier' => $imageIdentifier));
    }

    /**
     * @see Imbo\Resource\ResourceInterface::delete()
     */
    public function delete(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();

        try {
            $database->deleteImage($publicKey, $imageIdentifier);
            $storage->delete($publicKey, $imageIdentifier);
        } catch (ImboException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @see Imbo\Resource\ResourceInterface::get()
     */
    public function get(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        $publicKey       = $request->getPublicKey();
        $imageIdentifier = $request->getImageIdentifier();
        $serverContainer = $request->getServer();
        $requestHeaders  = $request->getHeaders();
        $responseHeaders = $response->getHeaders();

        try {
            // Fetch information from the database (injects mime type, width and height to the
            // image instance)
            $database->load($publicKey, $imageIdentifier, $this->image);

            // Generate ETag using public key, image identifier, and the redirect url
            $etag = md5($publicKey . $imageIdentifier . $serverContainer->get('REQUEST_URI'));

            // Fetch last modified timestamp from the storage driver
            $lastModified = date('r', $storage->getLastModified($publicKey, $imageIdentifier));

            if (
                $lastModified === $requestHeaders->get('if-modified-since') &&
                $etag === $requestHeaders->get('if-none-match')
            ) {
                $response->setStatusCode(304);
                return;
            }

            // Load the image data (injects the blob into the image instance)
            $storage->load($publicKey, $imageIdentifier, $this->image);

            // Fetch the requested resource to see if we have to convert the image
            $resource = $request->getResource();
            $originalMimeType = $this->image->getMimeType();

            if (isset($resource[32])) {
                // We have a requested image type
                $extension = substr($resource, 33);

                $convert = new Convert($extension);
                $convert->applyToImage($this->image);
            }

            // Set some response headers
            $responseHeaders->set('Last-Modified', $lastModified)
                            ->set('ETag', $etag)
                            ->set('Content-Type', $this->image->getMimeType())
                            ->set('X-Imbo-OriginalMimeType', $originalMimeType)
                            ->set('X-Imbo-OriginalWidth', $this->image->getWidth())
                            ->set('X-Imbo-OriginalHeight', $this->image->getHeight())
                            ->set('X-Imbo-OriginalFileSize', $this->image->getFileSize());

            // Apply transformations
            $transformationChain = $request->getTransformations();
            $transformationChain->applyToImage($this->image);
        } catch (ImboException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        // Store the image in the response
        $response->setBody($this->image);
    }

    /**
     * @see Imbo\Resource\ResourceInterface::head()
     */
    public function head(RequestInterface $request, ResponseInterface $response, DatabaseInterface $database, StorageInterface $storage) {
        return $this->get($request, $response, $database, $storage);
    }
}