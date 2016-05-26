<?php
/*
  This file is a part of Phun Project
  The MIT License (MIT)
  Copyright (c) 2015 Pierre Ruyter and Xavier Van de Woestyne
  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:
  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.
  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE.
*/

declare (strict_types=1);

namespace phun\router;

// Library inclusion
require_once 'lib/exceptions.php';
require_once 'lib/utils.php';
require_once 'lib/types.php';
require_once 'lib/service.php';

/**
  * Create a GET service
  * @param the pathinfo
  * @return a GET Service
  */
function get(string $path = '') : GETService
{
    return new GETService($path);
}

/**
 * Create a POST service
 * @param the pathinfo
 * @return a GET Service
 */
function post(string $path = '') : POSTService
{
    return new POSTService($path);
}

/**
 * Create a PUT service
 * @param the pathinfo
 * @return a GET Service
 */
function put(string $path = '') : Service
{
    return new Service('put', $path);
}

/**
 * Create a DELETE service
 * @param the pathinfo
 * @return a GET Service
 */
function delete(string $path = '') : Service
{
    return new Service('delete', $path);
}

/**
 * Start the routing procedure
 */
function start()
{
    $service = Service::getCurrent();
    $service->boot();
}
