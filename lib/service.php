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
  protected $variants;
  protected $view;
  protected $controller;
  protected $reflex_controller;
  protected $reflex_view;
  protected $booted;
  protected $http_code;

  /**
  * Constructor of service
  * @param string method
  * @param string path
  * @return Instance of service (and record it)
  */
  public function __construct(string $method, string $path) {
    $this->booted = false;
    $this->reflex_controller = null;
    $this->reflex_view = null;
    $this->controller = null;
    $this->view = null;
    $this->uid = uniqid('service-');
    $this->mime = 'text/html';
    $this->http_code = 200;
    $this->method = strtolower(trim($method));
    $this->parameters = [];
    $this->extra_parameters = [];
    $this->strict = true;
    $this->pathBuilder($path);
    // Store the service
    $this->store();
  }

  /**
   * Get a typed parameter
   * @param the container of the parameter
   * @param the http parameter name
   * @param the name of the parameter (as a string)
   * @param a typed value
   */
  protected function rawParam($container, $keyname,  $key) {
    $env    = Service::$environement;
    $params = $env['globals'][$keyname];
    if (!array_key_exists($key, $container)) {
      if ($this->strict) {
        $message = $key . ' is not allowed for this service';
        throw new E\InvalidParameter($message);
      }
      return $params[$key];
    }
    $parameter = $params[$key];
    $type = $container[$key][0];
    return T\coers($type, $parameter);
  }

/**
 * Get the parameter values by his key
 * @param the parameter 's name
 * @return a typed value
 */
  public function param($key) {
    return $this->rawParam(
      $this->parameters,
      $this->paramKey(),
      $key
    );
  }

  /**
   * Get the extra parameter values by his key
   * @param the parameter 's name
   * @return a typed value
   */
  public function get($key) {
    return $this->rawParam(
      $this->extra_parameters,
      'get',
      $key
    );
  }

  /**
   * Return all get Parameters
   */
  protected function getParameters() {
    return $this->extra_parameters;
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
    throw new E\InvalidParameter($name . ' already exists');
    if (!T\is_valid($type)) throw new E\InvalidType('Unknown type');
    return $name;
  }

  /**
   * Set an HTTP Code for the service (by default, 200)
   * @param int the new HTTP's code
   * @return the service (for chaining)
   */
  public function httpCode(int $code) {
    $this->http_code = $code;
    return $this;
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
  * Controll if a callback is according to the service
  * @param A callback
  * @return bool
  */
  public function controllingCallback($reflex) {
    $nbparams = $reflex->getNumberOfParameters();
    if ($this->strict && $nbparams > count($this->variants)) {
      $message = 'A closure as so much parameters';
      throw new E\InvalidClosure($message);
    }
    $clone_variants = $this->variants;
    foreach($reflex->getParameters() as $param) {
      $name = $param->getName();
      if (!array_key_exists($name, $clone_variants)) {
        $message = '$'.$name.' is invalid in the closure';
        throw new E\InvalidClosure($message);
      }
    }
  }

  /**
  * Set a controller to the current service
  * @param a callback
  * @return the current service for chaining
  */
  public function setController($callback) {
    $reflex = new \ReflectionFunction($callback);
    $this->controllingCallback($reflex);
    $this->controller        = $callback;
    $this->reflex_controller = $reflex;
    return $this;
  }

  /**
  * Set a view to the current service
  * @param a callback
  * @return the current service for chaining
  */
  public function setView($callback) {
    $reflex = new \ReflectionFunction($callback);
    $this->controllingCallback($reflex);
    $this->view        = $callback;
    $this->reflex_view = $reflex;
    return $this;
  }

  /**
  * Build the components of a Path
  * @return void
  */
  protected function pathBuilder(string $path) {
    if ($path == '*') {
      $this->path = [[".*"]];
      return;
    }
    $reg   = '/(\{.+?\})/';
    $flags = PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE;
    $split = preg_split($reg, $path, -1, $flags);
    return $this->computePath($split);
  }

  /**
  * Build the path and his variants
  * @param array of path member
  * @return void
  */
  protected function computePath($members) {
    $this->path = [];
    $this->variants = [];
    foreach($members as $member) {
      if (!$this->memberIsVariant($member)) {
        $this->path[] = [[$member]];
      } else {
        $this->path[] = $this->computeVariant($member);
      }
    }
  }

  /**
  * Check if a path member is a variant
  * @param string
  * @return bool
  */
  protected function memberIsVariant(string $elt) : bool {
    return $elt[0] == '{' && $elt[strlen($elt)-1] == '}';
  }

  /**
   * Check if a variant is not already allowed
   * @param a named variant
   */
  protected function checkVariantUnicity($variant) {
    if (array_key_exists($variant, $this->variants)) {
      $message = $variant . ' is already named';
      throw new E\InvalidPathMember($message);
    }
  }

  /**
  * Compute the type of a variant
  * @param string
  * @return the value of the type and the url variable
  */
  protected function computeVariant(string $member) {
    if (preg_match('/\{(.+?)\}/', $member, $match)) {
      $place  = $match[1];
      $result = explode(':', $place);
      $total  = count($result);
      if ($total > 0) { $this->checkVariantUnicity($result[0]); }
      if ($total == 1) {
        $datatype =  T\regexStaticType('string');
        $this->variants[$result[0]] = $datatype;
        return [$datatype, $place];
      }
      if ($total == 2) {
        $datatype = T\regexStaticType($result[1]);
        $this->variants[$result[0]] = $datatype;
        return [$datatype, $result[0]];
      }
    }
    throw new E\InvalidPathMember('The member:'.$member.' is invalid');
  }

  /**
  * Returns the global key for accessing super-global
  * @return string
  */
  protected function paramKey() : string {
    return 'input';
  }

  /**
  * Check if the service is bootable, according the URI
  * @param env the super-globals
  * @return bool
  */
  public function isBootable() {
    $env     = Service::computeGlobals();
    $method  = $env['method'];
    $globals = $env['globals'];
    $uri     = $env['uri'];
    return
      $this->validMethod($method) &&
      $this->validParameters($globals[$this->paramKey()]) &&
      $this->validExtraParameters($globals['get']) &&
      $this->validFormattedUrl($uri)
    ;
  }

  /**
   * Return an array for the linked callback
   * @param the reflection object
   * @param the uri data
   * @return an ordered array of arguments
   */
   protected function bindCallback($reflex, $uriData) {
     $result = [];
     $params = $reflex->getParameters();
     foreach($params as $param) {
       $name = $param->getName();
       $result[] = $uriData[$name];
     }
     return $result;
   }

   /**
    * Apply extracted parameters to a callback
    * @param the reflected function
    * @param the callback
    * @param the uri data
    * @return a flag of execution or not
    */
   protected function applyCallback($reflex, $callback, $uri) : bool {
     if ($callback === null) return false;
     $args = $this->bindCallback($reflex, $uri);
     $callback->call($this, ...$args);
     return true;
   }

  /**
   * Boot the current service
   */
  public function boot() {
    $this->booted = true;
    $uri = $this->extractUriData();
    $this->applyCallback($this->reflex_controller, $this->controller, $uri);
    http_response_code($this->http_code);
    header('Content-Type: ' . $this->mime);
    $flag = $this->applyCallback($this->reflex_view, $this->view, $uri);
    if ($flag === false) {
      $message = 'This service doesn\'t has view';
      throw new E\UnbindedService($message);
    }
  }

  /**
   * Extract variant from the url
   * @param the current url
   * @return extracted array
   */
  protected function extractUriData() {
    $uri = Service::$environement['uri'];
    $regex = '';
    foreach($this->path as $elt) {
      if (count($elt) == 1) { $regex .= $elt[0][0]; }
      else { $regex .= '(?P<'.$elt[1].'>'.$elt[0][0].')'; }
    }
    preg_match('#'.$regex.'#', $uri, $output);
    $flag = ARRAY_FILTER_USE_KEY;
    $var = array_filter($output, function($k) { return !is_int($k);}, $flag);
    return $this->coersVariant($var);
  }

  /**
   * Coers URL variant according to the types
   * @param the table of the variants
   * @return typed values
   */
  protected function coersVariant($var) {
    $result = [];
    foreach ($var as $key => $value) {
      $type = $this->variants[$key];
      $result[$key] = (count($type) == 2) ? T\coers($type[1], $value) : $value;
    }
    return $result;
  }

  /**
  * Check if the method is valid according the URI
  * @param the string of the method
  * @return bool
  */
  protected function validMethod(string $method) : bool {
    return $this->method == $method;
  }

  /**
  * Check if the parameters are valids according the URI
  * @param the container
  * @param the global arguments
  * @return bool
  */
  protected function validRawParameters($container, $arg) : bool {
    if ($this->strict && (count($arg) != count($container)))
    return false;
    foreach($container as $key => $value) {
      $type = $value[0];
      $callback = $value[1];
      if (!array_key_exists($key, $arg)) return false;
      if (!$callback($arg[$key])) return false;
    }
    return true;
  }

  /**
  * Check parameters
  * @param the global arguments
  * @return bool
  */
  protected function validParameters($arg) : bool {
    return $this->validRawParameters($this->parameters, $arg);
  }

  /**
  * Check extra parameters
  * @param the global arguments
  * @return bool
  */
  protected function validExtraParameters($arg) : bool {
    return $this->validRawParameters($this->extra_parameters, $arg);
  }

  /**
  * Check the url
  * @return bool
  */
  protected function validFormattedUrl($uri) {
    $members = array_map( function($elt) { return $elt[0][0]; }, $this->path);
    $regex = '#^'.(join('', $members)).'$#';
    return preg_match($regex, $uri);
  }

  /**
   * Generate a link of the service
   * @param variants : a list of variants
   * @param get : a list of get arguments
   * @return a string representing the link
   */
  public function link($variants = [], $get = []) : string {
    $path = $this->computeLinkPath($variants);
    $gets = $this->computeLinkGet($get);
    return $gets;
  }

  protected function computeLinkPath($variants) : string {
    return '';
  }

  /**
   * Compute get arguments
   * @param array of get values
   * @return http parameters as a string
   */
  protected function computeLinkGet($get) : string {
    $flag = $this->validRawParameters($this->getParameters(), $get);
    if (!$flag) throw new E\InvalidParameter('parameters are invalid');
    if (count($get) == 0) return '';
    $result = [];
    foreach($get as $name => $value) {
      $result[] = $name . '=' . $value;
    }
    return '?' . join('&', $result);
  }

  /**
  * Store the service into the services list
  */
  protected function store() {
    Service::$services[$this->uid] = $this;
  }

  // Static content
  protected static $services  = [];
  public static $environement = null;

  /**
  * Compute the current URL
  * @return a string corresponding of the current URL
  */
  protected static function retreivePath() : string {
    $base = pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];
    $r = explode('?', $_SERVER['REQUEST_URI']);
    return substr($r[0], strlen($base)+1);
  }

  /**
  * Compute all globals variables for a method
  * @return Returns a hashmap with static data
  */
  public static function computeGlobals() {
    if (Service::$environement === null) {
      $method = strtolower(trim($_SERVER['REQUEST_METHOD']));
      $global = [];
      $global['get']  = $_GET;
      $global['post'] = $_POST;
      parse_str(file_get_contents('php://input'), $input);
      $global['input'] = $input;
      Service::$environement = array(
        'globals' => $global,
        'method'  => $method,
        'uri'     => self::retreivePath()
      );
    }
    return Service::$environement;
  }

  /**
  * Returns the current Service (according Uri and parameters)
  * @return the current service
  */
  public static function getCurrent() : Service {
    Service::computeGlobals();
    foreach(Service::$services as $service) {
      if ($service->isBootable()) {
        return $service;
      }
    }
    $message = 'No services are founded for this uri';
    throw new E\NoServicesCandidates($message);
  }

}

/**
* Specification for GET services
*/
class GETService extends Service {
  /**
  * Constructor of service
  * @param string method
  * @param string path
  * @return Instance of service (and record it)
  */
  public function __construct(string $path) {
    parent::__construct('get', $path);
  }
  /**
  * Returns the global key for accessing super-global
  * @return string
  */
  protected function paramKey() : string {
    return 'get';
  }
  /**
  * Add a required extra - parameter
  * @param string name of the Parameter
  * @param type of the Parameter
  * @return the instance (for chaining)
  */
  public function withGET(string $name, $type = T\free) {
    $message = 'GET service could not has extra-parameters';
    throw new E\InvalidParameter($message);
  }
  /**
  * Check extra parameters
  * @param the global arguments
  * @return bool
  */
  protected function validExtraParameters($arg) : bool {
    return true;
  }
  /**
   * Get the extra parameter values by his key
   * @param the parameter 's name
   * @return a typed value
   */
  public function get($key) {
    return $this->param($key);
  }

  /**
   * Return all get Parameters
   */
  protected function getParameters() {
    return $this->parameters;
  }
}

/**
* Specification for POST services
*/
class POSTService extends Service {
  /**
  * Constructor of service
  * @param string method
  * @param string path
  * @return Instance of service (and record it)
  */
  public function __construct(string $path) {
    parent::__construct('post', $path);
  }
  /**
  * Returns the global key for accessing super-global
  * @return string
  */
  protected function paramKey() : string {
    return 'post';
  }
}
