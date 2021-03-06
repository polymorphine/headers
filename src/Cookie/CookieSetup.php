<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Headers package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Headers\Cookie;

use Polymorphine\Headers\Cookie;
use Polymorphine\Headers\ResponseHeaders;
use DateTime;


class CookieSetup
{
    private const FIVE_YEARS_IN_SEC = 157680000;

    private ResponseHeaders $responseHeaders;
    private array           $directives;

    /**
     * @param ResponseHeaders $responseHeaders
     */
    public function __construct(ResponseHeaders $responseHeaders)
    {
        $this->responseHeaders = $responseHeaders;
        $this->directives();
    }

    /**
     * Compound mutator with $directives array parameter in which
     * keys should correspond to self::$directives properties and
     * other concrete directive setter methods.
     *
     * Directives are set to default before passed options are
     * applied, so that state produced by directives array was
     * always the same.
     *
     * NOTE: If both Expires and MaxAge directives are provided
     * MaxAge values will be set.
     *
     * @param array $directives
     *
     * @return static
     */
    public function directives(array $directives = []): self
    {
        $this->directives = [
            'Domain'   => null,
            'Path'     => '/',
            'Expires'  => null,
            'MaxAge'   => null,
            'Secure'   => false,
            'HttpOnly' => false,
            'SameSite' => null
        ];

        foreach (HeadersContextCookie::DIRECTIVE_NAMES as $name) {
            if (empty($directives[$name])) { continue; }
            $setMethod = lcfirst($name);
            $this->{$setMethod}($directives[$name]);
        }

        return $this;
    }

    /**
     * Creates Cookie with given name and current directives.
     *
     * NOTE: Prefixed name will force following settings:
     * __Secure- force: Secure
     * __Host-   force: Secure, current Domain & root Path
     *
     * @param string $name
     *
     * @return Cookie
     */
    public function cookie(string $name): Cookie
    {
        return new HeadersContextCookie($name, $this->directives, $this->responseHeaders);
    }

    /**
     * Creates cookie that should be sent back util explicitly revoked.
     * In detail this cookie is created with very long expiration
     * time (5 years) which makes it practically permanent.
     *
     * NOTE: prefixed name explained in cookie() method.
     *
     * @param string $name
     *
     * @return Cookie
     */
    public function permanentCookie(string $name): Cookie
    {
        $this->maxAge(self::FIVE_YEARS_IN_SEC);
        return $this->cookie($name);
    }

    /**
     * Creates cookie with typical session directives (HttpOnly, SameSite Lax).
     *
     * NOTE: prefixed name explained in cookie() method.
     *
     * @param string $name
     *
     * @return Cookie
     */
    public function sessionCookie(string $name): Cookie
    {
        $this->httpOnly();
        $this->sameSite('Lax');
        return $this->cookie($name);
    }

    /**
     * This cookie should be removed after given date.
     *
     * @param DateTime $expires
     *
     * @return static
     */
    public function expires(DateTime $expires): self
    {
        $this->directives['Expires'] = $expires;
        return $this;
    }

    /**
     * This cookie should be removed after given number of seconds.
     *
     * @param int $seconds
     *
     * @return static
     */
    public function maxAge(int $seconds): self
    {
        $this->directives['Expires'] = (new DateTime())->setTimestamp(time() + $seconds);
        return $this;
    }

    /**
     * This cookie should be sent only with requests to given domain.
     *
     * @param string $domain
     *
     * @return static
     */
    public function domain(string $domain): self
    {
        $this->directives['Domain'] = $domain;
        return $this;
    }

    /**
     * This cookie should be sent only with requests to given path
     * or its subdirectories.
     *
     * @param string $path
     *
     * @return static
     */
    public function path(string $path): self
    {
        $this->directives['Path'] = $path;
        return $this;
    }

    /**
     * This cookie shouldn't be read or modified by client-side scripts
     * and only sent back with next http request.
     *
     * @return static
     */
    public function httpOnly(): self
    {
        $this->directives['HttpOnly'] = true;
        return $this;
    }

    /**
     * This cookie should not be sent with unencrypted (http) protocol.
     *
     * @return static
     */
    public function secure(): self
    {
        $this->directives['Secure'] = true;
        return $this;
    }

    /**
     * With either 'Strict' or 'Lax' value this cookie should be sent
     * only when request was initiated on cookie's domain.
     * Additionally 'Lax' allows this cookie to be sent with all
     * GET method requests (even from external link).
     *
     * @param string $value Strict|Lax
     *
     * @return static
     */
    public function sameSite(string $value): self
    {
        $this->directives['SameSite'] = ($value === 'Strict') ? 'Strict' : 'Lax';
        return $this;
    }
}
