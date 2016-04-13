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

declare(strict_types=1);

/**
 * Provide Services for Phun
 * @author Van de Woestyne Xavier <xaviervdw@gmail.com>
 */
namespace phun\router;

/**
 * Service
 * Describe a resource of a PHUN application
 */
class Service {

    // Attributes
    protected $uid;
    protected $method;
    protected $parameters;
    protected $extended_parameters;
    protected $path;
    protected $mime;

    /**
     * Constructor of service
     * @param string method
     * @param string path
     * @return Instance of service (and record it)
     */
    public function __construct(string $method, string $path) {
        $this->uid = uniqid('service-');

        // Store the service
        $this->store();
    }

    /**
     * Store the service into the services list
     */
    protected function store() {
        self::$services[$this->uid] = $this;
    }

    // Static content
    protected static $services = [];



}
