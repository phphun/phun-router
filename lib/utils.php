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
 * Useful functions
 * @author Van de Woestyne Xavier <xaviervdw@gmail.com>
 */
namespace phun\lib;

/**
 * Concat an url
 * @param the left part of the url
 * @param the right part of the url
 * @return a concatenation of the two part of the url
 */
function join_url_members(string $left, string $right) {
    $sep = DIRECTORY_SEPARATOR;
    $a = rtrim($left, $sep);
    $b = ltrim($right, $sep);
    return $a . '/' . $b;
}

/**
 * Returns the Base path of a Phun application
 * @return an Array with all path member
 */
function base_path() {
    $r = pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];
    return array_values(array_filter(preg_split('/\/|\?|\&/', $r)));
}

/**
 * Returns the root of the architecture
 * @return a String represent the url root of the host
 */
function url_root() {
    $host = $_SERVER['HTTP_HOST'];
    $base = join(base_path(), '/');
    return '//' . $host . '/' . $base . '/';
}

/**
 * Relativize an Url (for the url rewritting)
 * An absolute url is not relativized
 * @param string the url to be relativized
 * @return string a new url (relativized or not if absolute gived)
 */
function relativize_url(string $url) {
    $parsed = parse_url($url);
    if (!array_key_exists('host', $parsed)) {
        return join_url_members(url_root(), $url);
    } return $url;
}