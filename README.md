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

       $cookie = new Cookie('myCookie', [
           'Domain'   => 'example.com',
           'Path'     => '/admin',
           'Expires'  => new DateTime(...),
           'MaxAge'   => 1234,
           'Secure'   => true,
           'HttpOnly' => true,
           'SameSite' => 'Strict'
       ]);

2. Get Header string that sets value or requests cookie to be removed (from browser)

       $cookieHeader = $cookie->valueHeader('value');
       $cookieHeader = $cookie->revokeHeader();

3. Add `Set-Cookie` header to your response. For example Psr-7 `ResponseInterface`
   using one of its [`MessageInterface`](https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface)
   methods.

       return $response->withAddedHeader('Set-Cookie', $cookieHeader);

### Directives and Attributes

...

### Alternative constructors

...

### Reuse Attributes for another cookie

...
