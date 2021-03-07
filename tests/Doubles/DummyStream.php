<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Headers package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Headers\Tests\Doubles;

use Psr\Http\Message\StreamInterface;


class DummyStream implements StreamInterface
{
    public function __toString(): string
    {
        return 'Hello world';
    }

    public function close(): void
    {
    }

    public function detach()
    {
    }

    public function getSize(): ?int
    {
        return 10;
    }

    public function tell(): int
    {
        return 5;
    }

    public function eof(): bool
    {
        return false;
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
    }

    public function rewind(): void
    {
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write($string): void
    {
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        return 'Hello';
    }

    public function getContents(): string
    {
        return ' World';
    }

    public function getMetadata($key = null): array
    {
        return [];
    }
}
