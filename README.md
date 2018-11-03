# Polymorphine/Headers
[![Build Status](https://travis-ci.org/shudd3r/polymorphine-headers.svg?branch=develop)](https://travis-ci.org/shudd3r/polymorphine-headers)
[![Coverage Status](https://coveralls.io/repos/github/shudd3r/polymorphine-headers/badge.svg?branch=develop)](https://coveralls.io/github/shudd3r/polymorphine-headers?branch=develop)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/polymorphine/headers/dev-develop.svg)](https://packagist.org/packages/polymorphine/headers)
[![Packagist](https://img.shields.io/packagist/l/polymorphine/headers.svg)](https://packagist.org/packages/polymorphine/headers)
### HTTP Response headers middleware

### Installation with [Composer](https://getcomposer.org/)
    php composer.phar require polymorphine/headers

### Basic usage

1. Instantiate a cookie with its name and array of **optional** directives and attributes:

       $cookie = new SetCookieHeader('myCookie', [
           'Domain'   => 'example.com',
           'Path'     => '/admin',
           'Expires'  => new DateTime(...),
           'MaxAge'   => 1234,
           'Secure'   => true,
           'HttpOnly' => true,
           'SameSite' => 'Strict'
       ]);

2. Set value or revoke request:

       $cookie->set('value');
       $cookie->revoke();

3. Add `Set-Cookie` header to your application's [`Psr-7`](https://www.php-fig.org/psr/psr-7/) response:

       $response = $cookie->addTo($response);

### Directives and Attributes

Directives are the same as in [RFC6265](https://tools.ietf.org/html/rfc6265#section-4.1.2)
section about Set-Cookie header attributes (except relatively new `SameSite` directive) and
their description might be found in many online documentations. Concise description with
additional class logic is also explained in docBlocks of constructor setter methods
of [`SetCookieHeader`](src/SetCookieHeader.php) class.

Here are some class-specific rules for setting those directives:
* Empty values and root path (`/`) might be omitted as they're same as default.
* `SameSite` allowed values are `Strict` or `Lax`, but `Lax` will be set for any non-empty value given.
* `Expires` and `MaxAge` are different ways to set the same cookie's expiry date.
  If both given value of `MaxAge` will be used.

### Alternative constructors

Cookie class has two named constructors: `SetCookieHeader::permanent()` and `SetCookieHeader::session()`
* **Permanent** constructor _forces_ long (5 years) expiry values (`Expires` and `MaxAge`) 
* **Session** constructor sets security directives (`HttpOnly` and `SameSite=Lax`) as default.
  Unlike for "permanent cookie" these directives can be overridden by given parameters.

### Reuse Attributes for another cookie

Cookie object can be used as prototype for multiple Set-Cookie headers that can be created using
`SetCookieHeader::withName()` method that will clone current cookie attributes with given name.
When current name is passed new instance is not created, because cookie with that name will be
overwritten by last header if defined multiple times.
