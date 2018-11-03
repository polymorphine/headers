<?php

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
use Polymorphine\Headers\SetCookieHeader as Cookie;
use Polymorphine\Headers\ResponseHeaders;
use DateTime;

require_once __DIR__ . '/Fixtures/time-functions.php';


class SetCookieHeaderTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Cookie::class, $this->cookie('new'));
        $this->assertInstanceOf(Cookie::class, Cookie::session('new'));
        $this->assertInstanceOf(Cookie::class, Cookie::permanent('new'));
    }

    public function testPermanentConstructor()
    {
        $expectedHeader = 'name=value; Path=/; Expires=Sunday, 30-Apr-2023 00:00:00 UTC; MaxAge=157680000';
        $standardHeader = 'name=value; Path=/; Expires=Tuesday, 01-May-2018 02:00:00 UTC; MaxAge=7200';

        $directive = ['Expires' => $this->fixedDate(7200)];

        $this->assertSame([$expectedHeader], $this->responseHeader(Cookie::permanent('name', $directive)->set('value')));
        $this->assertSame([$standardHeader], $this->responseHeader($this->cookie('name', $directive)->set('value')));
    }

    public function testSessionConstructor()
    {
        $expectedHeader = 'SessionId=1234567890; Path=/; HttpOnly; SameSite=Lax';
        $this->assertSame([$expectedHeader], $this->responseHeader(Cookie::session('SessionId')->set('1234567890')));
    }

    /**
     * @dataProvider cookieData
     *
     * @param string $expectedHeader
     * @param array  $data
     */
    public function testConstructorDirectivesSetting(string $expectedHeader, array $data)
    {
        $name   = $data['name'];
        $cookie = $this->cookie($name, $data);
        $cookie = $data['value'] ? $cookie->set($data['value']) : $cookie->revoke();
        $this->assertEquals([$expectedHeader], $this->responseHeader($cookie));
    }

    public function testHeadersAreAdded()
    {
        $cookie1 = Cookie::session('session1')->set('val1');
        $cookie2 = Cookie::session('session2')->set('val2');

        $response = new Doubles\FakeResponse();
        $cookie1->addToMessage($response);
        $cookie2->addToMessage($response);
        $this->assertCount(2, $response->getHeader('Set-Cookie'));
    }

    public function testHeadersArePassedToContext()
    {
        $context = new ResponseHeaders();

        $cookieHeader = $this->responseHeader((new Cookie('test', [], $context))->set('value'));
        $contextResponse = $context->process(
            new Doubles\FakeServerRequest(),
            new Doubles\FakeRequestHandler(new Doubles\FakeResponse())
        );

        $this->assertSame($cookieHeader, $contextResponse->getHeader('Set-Cookie'));
    }

    public function testNamePropertyAccessor()
    {
        $this->assertSame('nameOfTheCookie', $this->cookie('nameOfTheCookie')->name());
    }

    /**
     * @dataProvider cookieData
     *
     * @param string $expectedHeader
     * @param array  $data
     */
    public function testNameChange(string $expectedHeader, array $data)
    {
        $name      = $data['name'];
        $oldCookie = $this->cookie($name, $data);
        $newCookie = $oldCookie->withName('new-' . $name);

        $this->assertNotSame($oldCookie, $newCookie);

        $cookie = $data['value'] ? $newCookie->set($data['value']) : $newCookie->revoke();
        $this->assertEquals(['new-' . $expectedHeader], $this->responseHeader($cookie));
    }

    public function testGivenSameName_WithNameMethod_ReturnsSameInstance()
    {
        $oldCookie = $this->cookie('name');
        $newCookie = $oldCookie->withName('name');

        $this->assertSame($oldCookie, $newCookie);
    }

    public function testGivenBothExpiryDirectives_MaxAgeTakesPrecedence()
    {
        $expectedHeader = 'name=value; Path=/; Expires=Tuesday, 01-May-2018 00:01:40 UTC; MaxAge=100';
        $directives     = ['MaxAge' => 100, 'Expires' => $this->fixedDate(3600)];
        $this->assertSame([$expectedHeader], $this->responseHeader($this->cookie('name', $directives)->set('value')));
    }

    public function testSecureAndHostNamePrefixWillForceSecureDirective()
    {
        $cookie   = $this->cookie('__SECURE-name', ['Domain' => 'example.com', 'Path' => '/test'])->set('test');
        $expected = '__SECURE-name=test; Domain=example.com; Path=/test; Secure';
        $this->assertEquals([$expected], $this->responseHeader($cookie));

        $cookie   = $this->cookie('__host-name')->set('test');
        $expected = '__host-name=test; Path=/; Secure';
        $this->assertEquals([$expected], $this->responseHeader($cookie));
    }

    public function testHostNamePrefixWillForceRootPathAndDomain()
    {
        $cookie   = $this->cookie('__Host-name', ['Domain' => 'example.com', 'Path' => '/test'])->set('test');
        $expected = '__Host-name=test; Path=/; Secure';
        $this->assertEquals([$expected], $this->responseHeader($cookie));
    }

    public function cookieData()
    {
        return [
            ['myCookie=; Path=/; Expires=Thursday, 02-May-2013 00:00:00 UTC; MaxAge=-157680000', [
                'name'  => 'myCookie',
                'value' => null
            ]],
            ['fullCookie=foo; Domain=example.com; Path=/directory/; Expires=Tuesday, 01-May-2018 01:00:00 UTC; MaxAge=3600; Secure; HttpOnly; SameSite=Lax', [
                'name'     => 'fullCookie',
                'value'    => 'foo',
                'Secure'   => true,
                'MaxAge'   => 3600,
                'HttpOnly' => true,
                'Domain'   => 'example.com',
                'Path'     => '/directory/',
                'SameSite' => true
            ]],
            ['fullCookie=foo; Domain=example.com; Path=/directory/; Expires=Tuesday, 01-May-2018 01:00:00 UTC; MaxAge=3600; Secure; HttpOnly; SameSite=Lax', [
                'name'     => 'fullCookie',
                'value'    => 'foo',
                'Secure'   => true,
                'Expires'  => $this->fixedDate(3600),
                'HttpOnly' => true,
                'Domain'   => 'example.com',
                'Path'     => '/directory/',
                'SameSite' => true
            ]]
        ];
    }

    private function fixedDate(int $secondsFromNow = 0): DateTime
    {
        return (new DateTime())->setTimestamp(\Polymorphine\Headers\time() + $secondsFromNow);
    }

    private function cookie(string $name, array $attributes = [])
    {
        return new Cookie($name, $attributes);
    }

    private function responseHeader(Cookie $cookie): array
    {
        return $cookie->addToMessage(new Doubles\FakeResponse())->getHeader('Set-Cookie');
    }
}
