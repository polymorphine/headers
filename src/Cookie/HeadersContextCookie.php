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
use Polymorphine\Headers\Header\SetCookieHeader;
use DateTime;


class HeadersContextCookie implements Cookie
{
    public const DIRECTIVE_NAMES = ['Domain', 'Path', 'Expires', 'MaxAge', 'Secure', 'HttpOnly', 'SameSite'];

    private string          $name;
    private array           $directives;
    private ResponseHeaders $headers;

    private bool $sent = false;

    /**
     * @param string          $name
     * @param array           $directives
     * @param ResponseHeaders $headers
     */
    public function __construct(string $name, array $directives, ResponseHeaders $headers)
    {
        $this->name       = $this->validName($name);
        $this->directives = $directives + ['Path' => '/'];
        $this->headers    = $headers;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function send(string $value): void
    {
        if ($this->sent) {
            $message = 'Cannot overwrite `%s` cookie header';
            throw new Exception\CookieAlreadySentException(sprintf($message, $this->name));
        }

        $this->headers->push(new SetCookieHeader($this->compileHeader($value)));
        $this->sent = true;
    }

    public function revoke(): void
    {
        $this->directives['Expires'] = (new DateTime())->setTimestamp(0);
        $this->send('');
    }

    private function compileHeader(string $cookieValue): string
    {
        $this->synchronizeExpireDirectives();
        $this->setPrefixedNameDirectives();

        $header = $this->name . '=' . $this->validValue($cookieValue);

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

    private function validName(string $name): string
    {
        $pattern = '#[^a-zA-Z0-9' . Cookie::NAME_EXTRA_CHARS . ']#';
        if (preg_match($pattern, $name)) {
            throw new Exception\IllegalCharactersException('Illegal characters in Cookie name');
        }

        return $name;
    }

    private function validValue(string $value): string
    {
        $pattern = '#[^a-zA-Z0-9' . Cookie::VALUE_EXTRA_CHARS . ']#';
        if (preg_match($pattern, $value)) {
            throw new Exception\IllegalCharactersException('Illegal characters in Cookie value');
        }

        return $value;
    }
}
