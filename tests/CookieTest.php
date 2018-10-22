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
use LogicException;

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
     * @param string $headerLine
     * @param array  $data
     */
    public function testSetupMethods(string $headerLine, array $data)
    {
        $cookie = $this->cookie($data['name']);

        if (isset($data['MaxAge'])) {
            empty($data['MaxAge']) ? $cookie->setPermanent() : $cookie->setMaxAge($data['MaxAge']);
        }
        if (isset($data['Expires'])) {
            empty($data['Expires']) ? $cookie->setPermanent() : $cookie->setExpires($data['Expires']);
        }
        if (isset($data['Domain'])) { $cookie->setDomain($data['Domain']); }
        if (isset($data['Path'])) { $cookie->setPath($data['Path']); }
        if (isset($data['Secure'])) { $cookie->setSecure(); }
        if (isset($data['HttpOnly'])) { $cookie->setHttpOnly(); }
        if (isset($data['SameSite'])) {
            $data['SameSite'] === 'Strict' ? $cookie->setSameSiteStrict() : $cookie->setSameSiteLax();
        }

        $data['value'] ? $cookie->setValue($data['value']) : $cookie->revoke();
        $this->assertEquals($headerLine, (string) $cookie);
    }

    /**
     * @dataProvider cookieData
     *
     * @param string $headerLine
     * @param array  $data
     */
    public function testArrayConstructors(string $headerLine, array $data)
    {
        $name   = $data['name'];
        $cookie = $this->cookie($name, $data);
        $data['value'] ? $cookie->setValue($data['value']) : $cookie->revoke();
        $this->assertEquals($headerLine, (string) $cookie);
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
            ]],
            ['permanentCookie=hash-3284682736487236; Path=/; Expires=Sunday, 30-Apr-2023 00:00:00 UTC; MaxAge=157680000; HttpOnly; SameSite=Strict', [
                'name'     => 'permanentCookie',
                'value'    => 'hash-3284682736487236',
                'MaxAge'   => false,
                'HttpOnly' => true,
                'Path'     => '',
                'SameSite' => 'Strict'
            ]],
            ['permanentCookie=hash-3284682736487237; Path=/; Expires=Sunday, 30-Apr-2023 00:00:00 UTC; MaxAge=157680000; HttpOnly; SameSite=Strict', [
                'name'     => 'permanentCookie',
                'value'    => 'hash-3284682736487237',
                'Expires'  => false,
                'HttpOnly' => true,
                'Path'     => '',
                'SameSite' => 'Strict'
            ]]
        ];
    }

    public function testSecureAndHostNamePrefixWillSetSecureDirectiveImplicitly()
    {
        $cookie = $this->cookie('__SECURE-name')
                       ->setPath('/test')
                       ->setDomain('example.com')
                       ->setValue('test');
        $headerLine = '__SECURE-name=test; Domain=example.com; Path=/test; Secure';
        $this->assertEquals($headerLine, (string) $cookie);

        $cookie     = $this->cookie('__host-name')->setValue('test');
        $headerLine = '__host-name=test; Path=/; Secure';
        $this->assertEquals($headerLine, (string) $cookie);
    }

    public function testSettingPathForHostNamePrefixedCookie_ThrowsException()
    {
        $cookie = $this->cookie('__Host-name');
        $this->expectException(LogicException::class);
        $cookie->setPath('/test');
    }

    public function testSettingDomainForHostNamePrefixedCookie_ThrowsException()
    {
        $cookie = $this->cookie('__Host-name');
        $this->expectException(LogicException::class);
        $cookie->setDomain('example.com');
    }

    private function cookie(string $name, array $attributes = [])
    {
        return new Cookie($name, $attributes);
    }
}
