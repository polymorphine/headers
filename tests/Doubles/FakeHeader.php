<?php

/*
 * This file is part of Polymorphine/Headers package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Headers\Tests\Doubles;

use Polymorphine\Headers\Header;
use Psr\Http\Message\MessageInterface;


class FakeHeader implements Header
{
    private $name;
    private $value;

    public function __construct(string $name, string $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }

    public function addTo(MessageInterface $message): MessageInterface
    {
        return $message->withAddedHeader($this->name, $this->value);
    }
}
