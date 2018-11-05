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

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class ResponseHeaders implements MiddlewareInterface, HeadersContext
{
    private $headers = [];

    /**
     * @param Header[] $headers
     */
    public function __construct(Header ...$headers)
    {
        $this->headers = $headers;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        foreach ($this->headers as $header) {
            $response = $header->addToMessage($response);
        }
        return $response;
    }

    public function push(Header $header): void
    {
        $this->headers[] = $header;
    }
}
