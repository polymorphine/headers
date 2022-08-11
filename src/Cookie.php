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

use Polymorphine\Headers\Cookie\Exception\CookieAlreadySentException;
use Polymorphine\Headers\Cookie\Exception\IllegalCharactersException;


interface Cookie
{
    public const NAME_EXTRA_CHARS  = '!\#$%&\'*+\-.^_`|~';
    public const VALUE_EXTRA_CHARS = self::NAME_EXTRA_CHARS . '\/:=?\@()[\]{}<>';

    /**
     * @return string
     */
    public function name(): string;

    /**
     * Sends header with server response that orders given
     * value to be sent back within Cookie header in next
     * requests from client.
     *
     * @param string $value
     *
     * @throws CookieAlreadySentException|IllegalCharactersException
     */
    public function send(string $value): void;

    /**
     * Sends header with server response that orders given
     * cookie to be removed on client side and not sent back
     * with further requests.
     *
     * @throws CookieAlreadySentException
     */
    public function revoke(): void;
}
