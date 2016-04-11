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
 * Provide Type system for PHUN
 * @author Van de Woestyne Xavier <xaviervdw@gmail.com>
 */
namespace phun\types;

// Kind of types
const free   = 0;
const string = 0;
const int    = 1;
const float  = 2;
const char   = 3;
const bool   = 4;
const file   = 5;

/**
 * Check if a type is allowed by the type system
 * @param a type
 * @return bool
 */
function is_valid($type) {
    if (is_array($type)) return count($type) == 1 && is_valid($type[0]);
    if (is_int($type))   return ($type >= 0 && $type <= 5);
    return @preg_match($type, null) !== false;
}

// free validator
function free_checker() {
    return function($value) { return true;};
}

// int validator
function int_checker() {
    return function($value) {
        if (is_int($value)) { return true; }
        return (preg_match('/^-?\d+$/', $value)) == true;
    };
}

// float validator
function float_checker() {
    return function($value) {
        if (is_float($value)) { return true; }
        return (preg_match('/^-?(\d+\.\d*)$/', $value)) == true;
    };
}

// Bool checker
function bool_checker() {
    return function($value) {
        if (is_bool($value)) { return true; }
        $value = trim(strtolower((string) $value));
        return $value == 'true' || $value == 'false';
    };
}

// File checker
function file_checker() {
    // This check is not really safe
    return function($value) {
        if (!is_array($value)) { return false; }
        return array_key_exists('name', $value)
            && array_key_exists('type', $value)
            && array_key_exists('tmp_name', $value)
            && array_key_exists('error', $value)
            && array_key_exists('size', $value);
    };
}

// Char checker
function char_checker() {
    return function($value) {
        if (!is_string($value)) { return false; }
        return strlen($value) == 1;
    };
}

// Checker for regexp type
function regex_checker($regex) {
    return function($value) use($regex) {
        return preg_match($regex, $value) == true;
    };
}

// Checker for an array
function array_checker($callback) {
    return function($value) use($callback) {
        if (!is_array($value)) { return false; }
        foreach($value as $elt)
            if (!$callback($elt)) return false;
        return true;
    };

}

