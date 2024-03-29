# Polymorphine/Headers
[![Latest stable release](https://poser.pugx.org/polymorphine/headers/version)](https://packagist.org/packages/polymorphine/headers)
[![Build status](https://github.com/polymorphine/headers/workflows/build/badge.svg)](https://github.com/polymorphine/headers/actions)
[![Coverage status](https://coveralls.io/repos/github/polymorphine/headers/badge.svg?branch=develop)](https://coveralls.io/github/polymorphine/headers?branch=develop)
[![PHP version](https://img.shields.io/packagist/php-v/polymorphine/headers.svg)](https://packagist.org/packages/polymorphine/headers)
[![LICENSE](https://img.shields.io/github/license/polymorphine/headers.svg?color=blue)](LICENSE)
### HTTP Response headers middleware

### Installation with [Composer](https://getcomposer.org/)
```bash
composer require polymorphine/headers
```

### Basic usage

##### *Set-Cookie header*
1. Instantiate a cookie builder using `ResponseHeaders` context:
   ```php
   $headers     = new ResponseHeaders();
   $cookieSetup = new CookieSetup($headers);
   ```
   Alternatively, instantiating `CookieSetup` is possible with `ResponseHeaders` method:
   ```php
   $cookieSetup = $context->cookieSetup();
   ```
2. Configure cookie with array of its directives/attributes
   (see [`CookieSetup::directives()`](/src/Cookie/CookieSetup.php#L51) method):
   ```php
   $cookieSetup->directives([
       'Domain'   => 'example.com',
       'Path'     => '/admin',
       'Expires'  => new DateTime(...),
       'MaxAge'   => 1234,
       'Secure'   => true,
       'HttpOnly' => true,
       'SameSite' => 'Strict'
   ]);
   ```
   Modifying setup object is also possible with its builder methods:
   ```php
   $cookieSetup->domain('example.com')
               ->path('/admin')
               ->expires(new DateTime(...))
               ->maxAge(1234)
               ->secure()
               ->httpOnly()
               ->sameSite('Strict');
   ```
3. Instantiate [`Cookie`](/src/Cookie.php) type object with its name:
   ```php
   $cookie = $cookieSetup->cookie('MyCookie');
   ```
4. Send value:
   ```php
   $cookie->send('value');
   ```
   or order to revoke cookie, so that it should not be sent with future requests:   
   ```php
   $cookie->revoke();
   ```       
   Each cookie can send/revoke header only once

##### Directives and Attributes

Directives are used according to [RFC6265](https://tools.ietf.org/html/rfc6265#section-4.1.2)
section about Set-Cookie header attributes (except relatively new `SameSite` directive). Their
description might also be found at [Mozilla Developer Network](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie).
Concise description with additional class logic is explained in docBlocks of mutator methods
of [`CookieSetup`](src/Cookie/CookieSetup.php) class.

Here are some class-specific rules for setting those directives:
- Empty values and root path (`/`) might be omitted as they're same as default.
- `SameSite` allowed values are `Strict` or `Lax`, but `Lax` will be set for any non-empty value given.
- `Expires` and `MaxAge` are different ways to set the same cookie's expiry date.
  If both directives will be passed into constructor or `directivesArray()` method,
  last value will be used due to overwrite.

##### Cookie with predefined directives

`CookieSetup` has two alternative methods creating `Cookie` instance: `CookieSetup::permanentCookie()` and
`CookieSetup::sessionCookie()`.
- *Permanent* constructor sets long (5 years) expiry values (`Expires` and `MaxAge`) 
- *Session* constructor sets security directives (`HttpOnly` and `SameSite=Lax`)
