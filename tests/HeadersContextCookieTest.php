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
use Polymorphine\Headers\Cookie\HeadersContextCookie;
use Polymorphine\Headers\Cookie\CookieSetup;
use Polymorphine\Headers\Cookie\Exception;
use Polymorphine\Headers\ResponseHeaders;
use Polymorphine\Headers\Tests\Fixtures\FixedDateTime;

require_once __DIR__ . '/Fixtures/time-functions.php';


class HeadersContextCookieTest extends TestCase
{
    public function test_Instantiation()
    {
        $this->assertInstanceOf(CookieSetup::class, $setup = $this->cookieSetup());
        $this->assertInstanceOf(HeadersContextCookie::class, $setup->cookie('new'));
        $this->assertInstanceOf(HeadersContextCookie::class, $setup->permanentCookie('new'));
        $this->assertInstanceOf(HeadersContextCookie::class, $setup->sessionCookie('new'));
    }

    public function test_StandardSetup()
    {
        $this->cookieSetup($context)
             ->expires(FixedDateTime::withOffset(7200))
             ->secure()
             ->cookie('name')
             ->send('value');

        $expected = ['name=value; Path=/; Expires=Tuesday, 01-May-2018 02:00:00 UTC; MaxAge=7200; Secure'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function test_PermanentSetup()
    {
        $this->cookieSetup($context)
             ->directives(['Expires' => FixedDateTime::withOffset(7200)])
             ->permanentCookie('name')
             ->send('value');

        $expected = ['name=value; Path=/; Expires=Sunday, 30-Apr-2023 00:00:00 UTC; MaxAge=157680000'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function test_SessionSetup()
    {
        $this->cookieSetup($context)
             ->sessionCookie('SessionId')
             ->send('1234567890');

        $expected = ['SessionId=1234567890; Path=/; HttpOnly; SameSite=Lax'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function test_Name_ReturnsCookieName()
    {
        $this->assertSame('cookieName', $this->cookieSetup($context)->cookie('cookieName')->name());
    }

    /** @dataProvider cookieData */
    public function test_ConstructorDirectivesSetting(string $expectedHeader, array $data)
    {
        $cookie = $this->cookieSetup($context)
                       ->directives($data)
                       ->cookie($data['name']);
        $data['value'] ? $cookie->send($data['value']) : $cookie->revoke();
        $this->assertEquals([$expectedHeader], $this->responseHeader($context));
    }

    public function test_HeadersAreAdded()
    {
        $this->cookieSetup($context)
             ->sessionCookie('cookie1')
             ->send('session');
        $this->cookieSetup($context)
             ->cookie('cookie2')
             ->send('value');

        $this->assertCount(2, $this->responseHeader($context));
    }

    public function test_GivenBothExpiryDirectivesToSetupConstructor_FirstOneIsOverwritten()
    {
        $this->cookieSetup($context)
             ->directives(['Expires' => FixedDateTime::withOffset(3600), 'MaxAge' => 100])
             ->cookie('name')
             ->send('value');

        $expected = ['name=value; Path=/; Expires=Tuesday, 01-May-2018 00:01:40 UTC; MaxAge=100'];
        $this->assertSame($expected, $this->responseHeader($context));
    }

    public function test_SecureNamePrefix_ForcesSecureDirective()
    {
        $this->cookieSetup($context)
             ->directives(['Domain' => 'example.com', 'Path' => '/test'])
             ->cookie('__SECURE-name')
             ->send('test');

        $expected = ['__SECURE-name=test; Domain=example.com; Path=/test; Secure'];
        $this->assertEquals($expected, $this->responseHeader($context));
    }

    public function test_HostNamePrefix_ForceSecureRootPathDirectivesWithoutDomain()
    {
        $this->cookieSetup($context)
             ->directives(['Domain' => 'example.com', 'Path' => '/test'])
             ->cookie('__host-name')
             ->send('test');

        $expected = ['__host-name=test; Path=/; Secure'];
        $this->assertEquals($expected, $this->responseHeader($context));
    }

    /** @dataProvider invalidNames */
    public function test_InvalidCharacterInCookieName_ThrowsException(string $invalidName)
    {
        $this->expectException(Exception\IllegalCharactersException::class);
        $this->cookieSetup()->cookie($invalidName);
    }

    /** @dataProvider invalidValues */
    public function test_InvalidCharacterInCookieValue_ThrowsException(string $invalidValue)
    {
        $cookie = $this->cookieSetup()->cookie('testValue');
        $this->expectException(Exception\IllegalCharactersException::class);
        $cookie->send($invalidValue);
    }

    public function test_GivenCookieWasSent_SendCookie_ThrowsException()
    {
        $cookie = $this->cookieSetup($context)->cookie('name');

        $cookie->send('value');
        $this->expectException(Exception\CookieAlreadySentException::class);
        $cookie->send('value');
    }

    public static function cookieData(): iterable
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
                'SameSite' => 'Lax'
            ]],
            ['fullCookie=foo; Domain=example.com; Path=/directory/; Expires=Tuesday, 01-May-2018 01:00:00 UTC; MaxAge=3600; Secure; HttpOnly; SameSite=Lax', [
                'name'     => 'fullCookie',
                'value'    => 'foo',
                'Secure'   => true,
                'Expires'  => FixedDateTime::withOffset(3600),
                'HttpOnly' => true,
                'Domain'   => 'example.com',
                'Path'     => '/directory/',
                'SameSite' => 'Lax'
            ]]
        ];
    }

    public static function invalidNames(): iterable
    {
        return [['foo=bar'], ['żółty'], ['foo{bar}']];
    }

    public static function invalidValues(): iterable
    {
        return [['foo\bar'], ['żółty'], ['foo;bar']];
    }

    private function cookieSetup(?ResponseHeaders &$context = null): CookieSetup
    {
        return new CookieSetup($context ??= new ResponseHeaders());
    }

    private function responseHeader(ResponseHeaders $context): array
    {
        $request = new Doubles\DummyServerRequest();
        $handler = new Doubles\FakeRequestHandler(new Doubles\FakeResponse());

        return $context->process($request, $handler)->getHeader('Set-Cookie');
    }
}
