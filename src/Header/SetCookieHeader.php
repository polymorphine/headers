<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Headers package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Headers\Header;

use Polymorphine\Headers\Header;
use Psr\Http\Message\MessageInterface;


class SetCookieHeader implements Header
{
    private string $headerValue;

    /**
     * @param string $headerValue
     */
    public function __construct(string $headerValue)
    {
        $this->headerValue = $headerValue;
    }

    public function addToMessage(MessageInterface $message): MessageInterface
    {
        return $message->withAddedHeader('Set-Cookie', $this->headerValue);
    }
}
