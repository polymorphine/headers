# Polymorphine/Headers
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-headers.svg?branch=develop)](https://travis-ci.org/shudd3r/polymorphine-headers)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-headers/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/polymorphine-headers?branch=develop)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/polymorphine/headers/dev-develop.svg)](https://packagist.org/packages/polymorphine/headers)
[![Packagist](https://img.shields.io/packagist/l/polymorphine/headers.svg)](https://packagist.org/packages/polymorphine/headers)
### HTTP Response headers middleware

### Installation with [Composer](https://getcomposer.org/)
    php composer.phar require polymorphine/headers

### Basic usage

1. Instantiate a cookie setup with `ResponseHeaders` context and (*optional*) array
   of directives/attributes (see [`resetDirectives()`](/src/Cookie/CookieSetup.php#L54) method):

       $context = new ResponseHeaders();
       $cookieSetup = new CookieSetup($context, [
           'Domain'   => 'example.com',
           'Path'     => '/admin',
           'Expires'  => new DateTime(...),
           'MaxAge'   => 1234,
           'Secure'   => true,
           'HttpOnly' => true,
           'SameSite' => 'Strict'
       ]);

   Modifying setup object is possible with its mutator methods.

2. Instantiate [`Cookie`](/src/Cookie.php) type object with its name:

       $cookie = $cookieSetup->cookie('MyCookie');

   Although its value is not sent within `Set-Cookie` header `Cookie` object is immutable.
   However, it is possible to create cookie with another name and the same set of attributes
   with `Cookie::withName()` method:
   
       $badCookie = $cookie->withName('BadCookie');

3. Send value or order to remove cookie:

       $cookie->send('value');
       $badCookie->revoke();

   Each cookie can send/revoke header only once
       

### Directives and Attributes

Directives are used according to [RFC6265](https://tools.ietf.org/html/rfc6265#section-4.1.2)
section about Set-Cookie header attributes (except relatively new `SameSite` directive). Their
description might also be found at [Mozilla Developer Network](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie).
Concise description with additional class logic is explained in docBlocks of mutator methods
of [`CookieSetup`](src/CookieSetup.php) class.

Here are some class-specific rules for setting those directives:
* Empty values and root path (`/`) might be omitted as they're same as default.
* `SameSite` allowed values are `Strict` or `Lax`, but `Lax` will be set for any non-empty value given.
* `Expires` and `MaxAge` are different ways to set the same cookie's expiry date.
  If both directives will be passed into constructor or `directivesArray()` method,
  last value will be used due to overwrite.

### Cookie with predefined directives

`CookieSetup` has two alternative methods creating `Cookie` instance: `CookieSetup::permanentCookie()` and
`CookieSetup::sessionCookie()`.
* *Permanent* constructor sets long (5 years) expiry values (`Expires` and `MaxAge`) 
* *Session* constructor sets security directives (`HttpOnly` and `SameSite=Lax`)
