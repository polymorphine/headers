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
use Polymorphine\Headers\Cookie\AssembledCookie;
use Polymorphine\Headers\Cookie\CookieSetup;
use Polymorphine\Headers\Cookie\Exception;
use Polymorphine\Headers\ResponseHeaders;
use DateTime;

require_once __DIR__ . '/Fixtures/time-functions.php';


class AssembledCookieTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(CookieSetup::class, $setup = $this->cookieSetup());
        $this->assertInstanceOf(AssembledCookie::class, $setup->cookie('new'));
        $this->assertInstanceOf(AssembledCookie::class, $setup->permanentCookie('new'));
        $this->assertInstanceOf(AssembledCookie::class, $setup->sessionCookie('new'));
    }

    public function testStandardSetup()
    {
        $this->cookieSetup($context, ['Expires' => $this->fixedDate(7200)])
             ->cookie('name')
             ->send('value');

        $expected = ['name=value; Path=/; Expires=Tuesday, 01-May-2018 02:00:00 UTC; MaxAge=7200'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function testPermanentSetup()
    {
        $this->cookieSetup($context, ['Expires' => $this->fixedDate(7200)])
             ->permanentCookie('name')
             ->send('value');

        $expected = ['name=value; Path=/; Expires=Sunday, 30-Apr-2023 00:00:00 UTC; MaxAge=157680000'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function testSessionSetup()
    {
        $this->cookieSetup($context)
             ->sessionCookie('SessionId')
             ->send('1234567890');

        $expected = ['SessionId=1234567890; Path=/; HttpOnly; SameSite=Lax'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function testRenamingCookie_ReturnsNewInstance()
    {
        $firstCookie  = $this->cookieSetup($context)->cookie('FirstName');
        $secondCookie = $firstCookie->withName('SecondName');

        $this->assertNotSame($firstCookie, $secondCookie);

        $firstCookie->send('1');
        $secondCookie->send('2');
        $expected = ['FirstName=1; Path=/', 'SecondName=2; Path=/'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function testSettingSameName_ReturnsSameInstance()
    {
        $firstCookie  = $this->cookieSetup($context)->cookie('FirstName');
        $secondCookie = $firstCookie->withName('FirstName');

        $this->assertSame($firstCookie, $secondCookie);
    }

    /**
     * @dataProvider cookieData
     *
     * @param string $expectedHeader
     * @param array  $data
     */
    public function testConstructorDirectivesSetting(string $expectedHeader, array $data)
    {
        $cookie = $this->cookieSetup($context, $data)
                       ->cookie($data['name']);
        $data['value'] ? $cookie->send($data['value']) : $cookie->revoke();
        $this->assertEquals([$expectedHeader], $this->responseHeader($context));
    }

    public function testHeadersAreAdded()
    {
        $this->cookieSetup($context)
             ->sessionCookie('cookie1')
             ->send('session');
        $this->cookieSetup($context)
             ->cookie('cookie2')
             ->send('value');

        $this->assertCount(2, $this->responseHeader($context));
    }

    public function testGivenBothExpiryDirectivesToSetupConstructor_FirstOneIsOverwritten()
    {
        $this->cookieSetup($context, ['Expires' => $this->fixedDate(3600), 'MaxAge' => 100])
             ->cookie('name')
             ->send('value');

        $expected = ['name=value; Path=/; Expires=Tuesday, 01-May-2018 00:01:40 UTC; MaxAge=100'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function testSecureNamePrefix_ForcesSecureDirective()
    {
        $this->cookieSetup($context, ['Domain' => 'example.com', 'Path' => '/test'])
             ->cookie('__SECURE-name')
             ->send('test');

        $expected = ['__SECURE-name=test; Domain=example.com; Path=/test; Secure'];
        $this->assertEquals($expected, $this->responseHeader($context));
    }

    public function testHostNamePrefix_ForceSecureRootPathDirectivesWithoutDomain()
    {
        $this->cookieSetup($context, ['Domain' => 'example.com', 'Path' => '/test'])
             ->cookie('__host-name')
             ->send('test');

        $expected = ['__host-name=test; Path=/; Secure'];
        $this->assertEquals($expected, $this->responseHeader($context));
    }

    /**
     * @dataProvider invalidAndValidNames
     *
     * @param string $invalidName
     * @param string $validName
     */
    public function testInvalidCharacterInCookieName_ThrowsException(string $invalidName, string $validName)
    {
        $cookie = $this->cookieSetup()->cookie($validName);
        $this->expectException(Exception\IllegalCharactersException::class);
        $cookie->withName($invalidName);
    }

    /**
     * @dataProvider invalidAndValidValues
     *
     * @param string $invalidValue
     * @param string $validValue
     */
    public function testInvalidCharacterInCookieValue_ThrowsException(string $invalidValue, string $validValue)
    {
        $cookie = $this->cookieSetup()->cookie('testValid');
        $cookie->send($validValue);
        $cookie = $cookie->withName('testInvalid');
        $this->expectException(Exception\IllegalCharactersException::class);
        $cookie->send($invalidValue);
    }

    public function testGivenCookieWasSent_SendCookie_ThrowsException()
    {
        $cookie = $this->cookieSetup($context)->cookie('name');

        $cookie->send('value');
        $this->expectException(Exception\CookieAlreadySentException::class);
        $cookie->send('value');
    }

    public function cookieData()
    {
        return [
            ['myCookie=; Path=/; Expires=Thursday, 01-Jan-1970 00:00:00 UTC; MaxAge=-1525132800', [
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

    public function invalidAndValidNames(): array
    {
        return [['foo=bar', 'foo.bar'], ['gówno', 'g%C3%B3wno'], ['foo{bar}', 'foo|bar|']];
    }

    public function invalidAndValidValues(): array
    {
        return [['foo\bar', 'foo=bar'], ['gówno', 'g%C3%B3wno'], ['foo;bar', 'foo(bar)']];
    }

    private function fixedDate(int $secondsFromNow = 0): DateTime
    {
        return (new DateTime())->setTimestamp(\Polymorphine\Headers\Cookie\time() + $secondsFromNow);
    }

    private function cookieSetup(&$context = null, array $attributes = []): CookieSetup
    {
        $context or $context = new ResponseHeaders();
        return new CookieSetup($context, $attributes);
    }

    private function responseHeader(ResponseHeaders $context): array
    {
        $request = new Doubles\FakeServerRequest();
        $handler = new Doubles\FakeRequestHandler(new Doubles\FakeResponse());

        return $context->process($request, $handler)->getHeader('Set-Cookie');
    }
}
