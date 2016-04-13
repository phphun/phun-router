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
use \phun\types as T;
use \phun\Exceptions as E;

/**
 * Service
 * Describe a resource of a PHUN application
 */
class Service {

    // Attributes
    protected $uid;
    protected $method;
    protected $parameters;
    protected $extra_parameters;
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
        $this->method = strtolower(trim($method));
        $this->path = $path;
        $this->parameters = [];
        $this->extra_parameters = [];
        $this->strict = true;

        // Store the service
        $this->store();
    }

    /**
     * Check if a parameter has a valid name and a valid type
     * @param string name of the Parameter
     * @param int type of the Parameter
     * @param array raw container
     * @return a trimmed name
     */
    protected function checkParameter(string $name, $type, $container) {
        $name = trim($name);
        if (array_key_exists($name, $container) && $name != '')
            throw new E\InvalidParameterName($name . ' already exists');
        if (!T\is_valid($type)) throw new E\InvalidType('Unknown type');
        return $name;
    }

    /**
     * Add a required parameter
     * @param string name of the Parameter
     * @param type of the Parameter
     * @return the instance (for chaining)
     */
    public function with(string $name, $type = T\free) {
        $name = $this->checkParameter($name, $type, $this->parameters);
        $this->parameters[$name] = [
            $type, T\getCheckerFunction($type, $this->method)
        ];
        return $this;

    }

    /**
     * Add a required extra - parameter
     * @param string name of the Parameter
     * @param type of the Parameter
     * @return the instance (for chaining)
     */
    public function withGET(string $name, $type = T\free) {
        $name = $this->checkParameter($name, $type, $this->extra_parameters);
        $this->extra_parameters[$name] = [
            $type, T\getCheckerFunction($type, $this->method)
        ];
        return $this;
    }

    /**
     * Returns the global key for accessing super-global
     * @return string
     */
    protected function paramKey() : string {
        if ($this->method == 'post' || $this->method == 'get')
            return $this->method;
        return 'input';
    }

    /**
     * Check if the service is bootable, according the URI
     * @param env the super-globals
     * @return bool
     */
    public function isBootable($env) {
        $method = $env[0];
        $globals = $env[1];
        return
            $this->validMethod($method) &&
            $this->validParameters($globals)
            ;
    }

    /**
     * Check if the method is valid according the URI
     * @param the string of the method
     * @return bool
     */
    protected function validMethod(string $method) {
        return $this->method == $method;
    }

    /**
     * Check if the parameters are valids according the URI
     * @return bool
     */
    protected function validParameters() {
        return true;
    }

    /**
     * Store the service into the services list
     */
    protected function store() {
        self::$services[$this->uid] = $this;
    }

    // Static content
    protected static $services = [];

    /**
     * Compute all globals variables for a method
     */
    public static function computeGlobals() {
        $method = strtolower(trim($_SERVER['REQUEST_METHOD']));
        $global = [];
        $global['get']  = $_GET;
        $global['post'] = $_POST;
        parse_str(file_get_contents('php://input'), $input);
        $global['input'] = $input;
        return [$method, $global];
    }



}
