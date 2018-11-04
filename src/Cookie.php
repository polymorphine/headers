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

use Polymorphine\Headers\Header\SetCookieHeader;
use Polymorphine\Headers\Exception\CookieAlreadySentException;
use DateTime;


class Cookie
{
    const DIRECTIVE_NAMES = ['Domain', 'Path', 'Expires', 'MaxAge', 'Secure', 'HttpOnly', 'SameSite'];

    private $name;
    private $directives;
    private $context;

    private $sent = false;

    public function __construct(string $name, array $directives, ResponseHeaders $context)
    {
        $this->name       = $name;
        $this->directives = $directives + ['Path' => '/'];
        $this->context    = $context;
    }

    public function __clone()
    {
        $this->sent = false;
    }

    public function withName(string $name): self
    {
        if ($name === $this->name) { return $this; }

        $cookie = clone $this;
        $cookie->name = $name;
        return $cookie;
    }

    /**
     * Cookie will be sent back with given value.
     *
     * @param string $value
     *
     * @throws CookieAlreadySentException
     */
    public function send(string $value): void
    {
        if ($this->sent) {
            $message = 'Cannot overwrite `%s` cookie header';
            throw new CookieAlreadySentException(sprintf($message, $this->name));
        }

        $this->context->push(new SetCookieHeader($this->compileHeader($value)));
        $this->sent = true;
    }

    /**
     * Cookie will be removed.
     *
     * @throws CookieAlreadySentException
     */
    public function revoke(): void
    {
        $this->directives['Expires'] = (new DateTime())->setTimestamp(0);
        $this->send('');
    }

    private function compileHeader(string $cookieValue): string
    {
        $this->synchronizeExpireDirectives();
        $this->setPrefixedNameDirectives();

        $header = $this->name . '=' . $cookieValue;

        foreach (self::DIRECTIVE_NAMES as $name) {
            if (!$value = $this->directives[$name] ?? null) { continue; }
            $header .= '; ' . $name . ($value === true ? '' : '=' . $value);
        }

        return $header;
    }

    private function synchronizeExpireDirectives(): void
    {
        if (!isset($this->directives['Expires'])) { return; }

        $expires = $this->directives['Expires'];
        $this->directives['Expires'] = $expires->format(DateTime::COOKIE);
        $this->directives['MaxAge']  = $expires->getTimestamp() - time();
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
