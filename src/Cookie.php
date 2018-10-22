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

use LogicException;
use DateTime;


class Cookie
{
    private const MAX_TIME = 5 * 365 * 24 * 60 * 60;

    private $name;
    private $value;

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

    private $hostLock = false;

    /**
     * Creates cookie with given name and directives.
     *
     * Cookie directives can be set with $directives array keys
     * corresponding to self::$directives property definition and
     * setup method names.
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
        $this->setDirectives($directives);
    }

    public function __toString()
    {
        $header = $this->name . '=' . $this->value;

        if ($this->expires) {
            $this->directives['Expires'] = $this->expires->format(DateTime::COOKIE);
            $this->directives['MaxAge']  = $this->expires->getTimestamp() - time();
        }

        foreach ($this->directives as $name => $directive) {
            if (!$directive) { continue; }
            $header .= '; ' . $name . ($directive === true ? '' : '=' . $directive);
        }

        return $header;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function revoke(): self
    {
        $this->setMaxAge(-self::MAX_TIME);
        $this->value = '';
        return $this;
    }

    /**
     * Browser should remove this cookie after given date.
     *
     * @param DateTime $expires
     *
     * @return static
     */
    public function setExpires(DateTime $expires): self
    {
        $this->expires = $expires;
        return $this;
    }

    /**
     * Browser should remove this cookie after given number of seconds.
     *
     * @param int $seconds
     *
     * @return static
     */
    public function setMaxAge(int $seconds): self
    {
        $this->expires = (new DateTime())->setTimestamp(time() + $seconds);
        return $this;
    }

    /**
     * Browser should keep this cookie until explicitly deleted.
     *
     * @return static
     */
    public function setPermanent(): self
    {
        $this->setMaxAge(self::MAX_TIME);
        return $this;
    }

    /**
     * Browser should send this cookie only with requests to given domain.
     *
     * @param string $domain
     *
     * @return static
     */
    public function setDomain(string $domain): self
    {
        if ($this->hostLock) {
            throw new LogicException('Cannot set path in cookies with `__Host-` name prefix');
        }

        $this->directives['Domain'] = $domain;
        return $this;
    }

    /**
     * Browser should send this cookie only with requests to given path
     * or its subdirectories.
     *
     * @param string $path
     *
     * @return static
     */
    public function setPath(string $path): self
    {
        if ($this->hostLock) {
            throw new LogicException('Cannot set path in cookies with `__Host-` name prefix');
        }

        $this->directives['Path'] = $path ?: '/';
        return $this;
    }

    /**
     * Browser should not allow scripts to read this cookie.
     *
     * @return static
     */
    public function setHttpOnly(): self
    {
        $this->directives['HttpOnly'] = true;
        return $this;
    }

    /**
     * Browser should not send this cookie unencrypted (http protocol).
     *
     * @return static
     */
    public function setSecure(): self
    {
        $this->directives['Secure'] = true;
        return $this;
    }

    /**
     * Browser should send this cookie only when request was initiated
     * on cookie's domain.
     *
     * @return static
     */
    public function setSameSiteStrict(): self
    {
        $this->directives['SameSite'] = 'Strict';
        return $this;
    }

    /**
     * Browser should send this cookie only when request was initiated
     * on cookie's domain or by using url link (GET method).
     *
     * @return static
     */
    public function setSameSiteLax(): self
    {
        $this->directives['SameSite'] = 'Lax';
        return $this;
    }

    private function setDirectives(array $directives): void
    {
        $this->setPrefixedNameDirectives();

        isset($directives['Domain']) and $this->setDomain($directives['Domain']);
        isset($directives['Path']) and $this->setPath($directives['Path']);
        isset($directives['Expires']) and $directives['Expires']
            ? $this->setExpires($directives['Expires'])
            : $this->setPermanent();
        isset($directives['MaxAge']) and $directives['MaxAge']
            ? $this->setMaxAge($directives['MaxAge'])
            : $this->setPermanent();
        !empty($directives['HttpOnly']) and $this->setHttpOnly();
        !empty($directives['Secure']) and $this->setSecure();
        isset($directives['SameSite']) and $directives['SameSite'] === 'Strict'
            ? $this->setSameSiteStrict()
            : $this->setSameSiteLax();
    }

    private function setPrefixedNameDirectives(): void
    {
        if ($this->name[0] !== '_' || $this->name[1] !== '_') { return; }

        $secure = (stripos($this->name, '__Secure-') === 0);
        $host   = !$secure && (stripos($this->name, '__Host-') === 0);

        $this->directives['Secure'] = $secure || $host;
        $this->hostLock = $host;
    }
}
