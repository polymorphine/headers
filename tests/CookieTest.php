<?php

/*
 * This file is part of Polymorphine/Cookie package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Cookie\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\Cookie\Cookie;

require_once __DIR__ . '/Fixtures/time-functions.php';


class CookieTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Cookie::class, $this->cookie('new'));
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
        $header = $data['value'] ? $cookie->valueHeader($data['value']) : $cookie->revokeHeader();
        $this->assertEquals($expectedHeader, $header);
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

        $header = $data['value'] ? $newCookie->valueHeader($data['value']) : $newCookie->revokeHeader();
        $this->assertEquals('new-' . $expectedHeader, $header);
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
                'Expires'  => (new \DateTime())->setTimestamp(\Polymorphine\Cookie\time() + 3600),
                'HttpOnly' => true,
                'Domain'   => 'example.com',
                'Path'     => '/directory/',
                'SameSite' => true
            ]]
        ];
    }

    public function testGivenSameName_WithNameMethod_ReturnsSameInstance()
    {
        $oldCookie = $this->cookie('name');
        $newCookie = $oldCookie->withName('name');

        $this->assertSame($oldCookie, $newCookie);
    }

    public function testPermanentConstructor()
    {
        $expectedHeader = 'permanentCookie=hash-3284682736487236; Path=/; Expires=Sunday, 30-Apr-2023 00:00:00 UTC; MaxAge=157680000; HttpOnly; SameSite=Strict';
        $cookie = Cookie::permanent('permanentCookie', [
            'Expires'  => (new \DateTime())->setTimestamp(\Polymorphine\Cookie\time() + 3600),
            'MaxAge'   => 3600,
            'HttpOnly' => true,
            'Path'     => '',
            'SameSite' => 'Strict'
        ]);
        $this->assertSame($expectedHeader, $cookie->valueHeader('hash-3284682736487236'));
    }

    public function testSessionConstructor()
    {
        $expectedHeader = 'SessionId=1234567890; Path=/; HttpOnly; SameSite=Lax';
        $cookie         = Cookie::session('SessionId');
        $this->assertSame($expectedHeader, $cookie->valueHeader('1234567890'));
    }

    public function testSecureAndHostNamePrefixWillForceSecureDirective()
    {
        $cookie     = $this->cookie('__SECURE-name', ['Domain' => 'example.com', 'Path' => '/test'])->valueHeader('test');
        $headerLine = '__SECURE-name=test; Domain=example.com; Path=/test; Secure';
        $this->assertEquals($headerLine, $cookie);

        $cookie     = $this->cookie('__host-name')->valueHeader('test');
        $headerLine = '__host-name=test; Path=/; Secure';
        $this->assertEquals($headerLine, $cookie);
    }

    public function testHostNamePrefixWillForceRootPathAndDomain()
    {
        $cookie     = $this->cookie('__Host-name', ['Domain' => 'example.com', 'Path' => '/test'])->valueHeader('test');
        $headerLine = '__Host-name=test; Path=/; Secure';
        $this->assertEquals($headerLine, $cookie);
    }

    private function cookie(string $name, array $attributes = [])
    {
        return new Cookie($name, $attributes);
    }
}
