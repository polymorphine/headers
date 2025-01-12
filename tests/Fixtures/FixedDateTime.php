<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Headers package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Headers\Tests\Fixtures;

use DateTime;


class FixedDateTime extends DateTime
{
    public const TIMESTAMP = 1525132800;

    public static function withOffset(int $seconds = 0): self
    {
        return (new self())->setTimestamp(self::TIMESTAMP + $seconds);
    }
}
