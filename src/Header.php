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


interface Header
{
    /**
     * @param MessageInterface $message
     *
     * @return MessageInterface New Message instance with modified headers
     */
    public function addToMessage(MessageInterface $message): MessageInterface;
}
