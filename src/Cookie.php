<?php

/*
 * This file is part of Polymorphine/Cookie package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Cookie;

use DateTime;


class Cookie
{
    /** @var int Five years time equivalent in seconds */
    private const MAX_TIME = 5 * 365 * 24 * 60 * 60;

    private $name;

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
     * __Secure- force: secure
     * __Host-   force & lock: secure, domain (current) & path (root)
     *
     * @param $name
     * @param array $directives
     */
    public function __construct($name, $directives = [])
    {
        $this->name = $name;

        foreach (array_keys($this->directives) as $name) {
            if (!$value = $directives[$name] ?? null) { continue; }
            $setMethod = 'set' . $name;
            $this->{$setMethod}($value);
        }
    }

    public static function permanent($name, $directives = []): self
    {
        unset($directives['Expires']);
        $directives['MaxAge'] = self::MAX_TIME;

        return new self($name, $directives);
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
     * @return Cookie
     */
    public function withName(string $name): self
    {
        if ($name === $this->name) { return $this; }

        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * Header line that requests this cookie to be sent with given value.
     *
     * @param string $value
     *
     * @return string
     */
    public function valueHeader(string $value): string
    {
        return $this->header($value);
    }

    /**
     * Header line that requests this cookie to be removed.
     *
     * @return string
     */
    public function revokeHeader(): string
    {
        $this->setMaxAge(-self::MAX_TIME);
        return $this->header('');
    }

    /**
     * Constructor directive setter:
     * This cookie should be removed after given date.
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

    private function header(string $value): string
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

        return $header;
    }

    private function setPrefixedNameDirectives(): void
    {
        if ($this->name[0] !== '_' || $this->name[1] !== '_') { return; }

        $secure = (stripos($this->name, '__Secure-') === 0);
        $host   = !$secure && (stripos($this->name, '__Host-') === 0);

        $this->directives['Secure'] = $secure || $host;
    }
}