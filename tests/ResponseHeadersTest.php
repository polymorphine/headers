<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Headers package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Headers\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\Headers\Cookie\CookieSetup;
use Polymorphine\Headers\ResponseHeaders;
use Polymorphine\Headers\Header;
use Polymorphine\Headers\Tests\Doubles\FakeHeader;
use Polymorphine\Headers\Tests\Doubles\FakeRequestHandler;
use Polymorphine\Headers\Tests\Doubles\FakeServerRequest;
use Polymorphine\Headers\Tests\Doubles\FakeResponse;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;


class ResponseHeadersTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ResponseHeaders::class, $this->middleware());
    }

    public function testAddHeaders()
    {
        $headers = $this->middleware(new FakeHeader('Set-Cookie', 'default=value'));
        $headers->push(new FakeHeader('Set-Cookie', 'name=value'));
        $this->assertSame(['Set-Cookie' => ['default=value', 'name=value']], $this->response($headers)->getHeaders());
    }

    public function testCookieSetup()
    {
        $this->assertInstanceOf(CookieSetup::class, $this->middleware()->cookieSetup());
    }

    private function middleware(Header ...$headers)
    {
        return new ResponseHeaders(...$headers);
    }

    private function response(MiddlewareInterface $headers): ResponseInterface
    {
        return $headers->process(new FakeServerRequest(), new FakeRequestHandler(new FakeResponse()));
    }
}
