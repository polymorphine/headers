<?php

/*
 * This file is part of Polymorphine/Headers package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Headers;

use Psr\Http\Message\MessageInterface;
use DateTime;


class SetCookieHeader implements Header
{
    /** @var int Five years time equivalent in seconds */
    private const MAX_TIME = 157680000;

    private $name;
    private $header;
    private $context;

    /** @var DateTime */
    private $expires;

    private $directives = [
        'Domain'   => null,
        'Path'     => '/',
        'Expires'  => null,
        'MaxAge'   => null,
        'Secure'   => false,
        'HttpOnly' => false,
        'SameSite' => false
    ];

    /**
     * Creates cookie with given name and directives.
     *
     * Cookie directives can be set with $directives array where
     * keys are corresponding to self::$directives properties and
     * setup methods (see: protected setter methods for more info)
     *
     * Prefixed name will force following settings:
     * __Secure- force: Secure
     * __Host-   force: Secure, Domain (current) & Path (root)
     *
     * @param string          $name
     * @param array           $directives
     * @param ResponseHeaders $context
     */
    public function __construct($name, $directives = [], ResponseHeaders $context = null)
    {
        $this->name    = $name;
        $this->context = $context;

        foreach (array_keys($this->directives) as $name) {
            if (!$value = $directives[$name] ?? null) { continue; }
            $setMethod = 'set' . $name;
            $this->{$setMethod}($value);
        }
    }

    /**
     * Creates cookie that should be sent back util explicitly revoked.
     * In detail this cookie is created with very long expiration
     * time (5 years) which makes it practically permanent.
     *
     * Beside overriding expiry directives parameters are handled the same
     * as in default constructor.
     *
     * @param $name
     * @param array $directives
     *
     * @return SetCookieHeader
     */
    public static function permanent($name, $directives = []): self
    {
        return new self($name, ['Expires' => null, 'MaxAge' => self::MAX_TIME] + $directives);
    }

    /**
     * Creates cookie with directives that if omitted will default
     * to those usually applied by sessions (HttpOnly, SameSite Lax).
     *
     * Beside fallback option this (named constructor) method parameters
     * are handled the same as in default constructor.
     *
     * @param $name
     * @param array $directives
     *
     * @return SetCookieHeader
     */
    public static function session($name, $directives = []): self
    {
        return new self($name, $directives + ['HttpOnly' => true, 'SameSite' => 'Lax']);
    }

    public function addToMessage(MessageInterface $message): MessageInterface
    {
        return $this->header
            ? $message->withAddedHeader('Set-Cookie', $this->header)
            : $message;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * Creates new Cookie instance with its directives and name
     * given as parameter.
     *
     * NOTE: Cookie name determines its entity. It means that if
     * instance name is the same as given parameter same object
     * will be returned.
     *
     * @param string $name
     *
     * @return SetCookieHeader
     */
    public function withName(string $name): self
    {
        if ($name === $this->name) { return $this; }

        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * Header will request this cookie to be sent with given value.
     *
     * @param string $value
     *
     * @return SetCookieHeader
     */
    public function set(string $value): self
    {
        $this->compileHeader($value);
        return $this;
    }

    /**
     * Header will request this cookie to be removed.
     *
     * @return SetCookieHeader
     */
    public function revoke(): self
    {
        $this->setMaxAge(-self::MAX_TIME);
        $this->compileHeader('');
        return $this;
    }

    /**
     * Constructor directive setter:
     * This cookie should be removed after given date.
     *
     * NOTE: If both Expires and MaxAge directives are provided
     * MaxAge values are set.
     *
     * @param DateTime $expires
     */
    protected function setExpires(DateTime $expires): void
    {
        $this->expires = $expires;
    }

    /**
     * Constructor directive setter:
     * This cookie should be removed after given number of seconds.
     *
     * NOTE: This directive takes precedence over Expires directive
     * if both are provided.
     *
     * @param int $seconds
     */
    protected function setMaxAge(int $seconds): void
    {
        $this->expires = (new DateTime())->setTimestamp(time() + $seconds);
    }

    /**
     * Constructor directive setter:
     * This cookie should be sent only with requests to given domain.
     *
     * @param string $domain
     */
    protected function setDomain(string $domain): void
    {
        $this->directives['Domain'] = $domain;
    }

    /**
     * Constructor directive setter:
     * This cookie should be sent only with requests to given path
     * or its subdirectories.
     *
     * @param string $path
     */
    protected function setPath(string $path): void
    {
        $this->directives['Path'] = $path;
    }

    /**
     * Constructor directive setter:
     * This cookie shouldn't be read or modified by client-side scripts
     * and only sent back with next http request.
     */
    protected function setHttpOnly(): void
    {
        $this->directives['HttpOnly'] = true;
    }

    /**
     * Constructor directive setter:
     * This cookie should not be sent with unencrypted (http) protocol.
     */
    protected function setSecure(): void
    {
        $this->directives['Secure'] = true;
    }

    /**
     * Constructor directive setter:
     * With either 'Strict' or 'Lax' value this cookie should be sent
     * only when request was initiated on cookie's domain.
     * Additionally 'Lax' allows this cookie to be sent when external
     * link was used (all GET method requests).
     *
     * @param string $value Strict|Lax
     */
    protected function setSameSite(string $value): void
    {
        $this->directives['SameSite'] = ($value === 'Strict') ? 'Strict' : 'Lax';
    }

    private function compileHeader(string $value): void
    {
        $this->setPrefixedNameDirectives();

        $header = $this->name . '=' . $value;

        if ($this->expires) {
            $this->directives['Expires'] = $this->expires->format(DateTime::COOKIE);
            $this->directives['MaxAge']  = $this->expires->getTimestamp() - time();
        }

        foreach ($this->directives as $directive => $value) {
            if (!$value) { continue; }
            $header .= '; ' . $directive . ($value === true ? '' : '=' . $value);
        }

        $this->header = $header;
        if ($this->context) { $this->context->push($this); }
    }

    private function setPrefixedNameDirectives(): void
    {
        if ($this->name[0] !== '_' || $this->name[1] !== '_') { return; }

        $secure = (stripos($this->name, '__Secure-') === 0);
        $host   = !$secure && (stripos($this->name, '__Host-') === 0);

        $this->directives['Secure'] = $secure || $host;

        if ($host) {
            $this->directives['Domain'] = null;
            $this->directives['Path']   = '/';
        }
    }
}
