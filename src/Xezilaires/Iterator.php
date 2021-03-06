<?php

declare(strict_types=1);

/*
 * This file is part of the xezilaires project.
 *
 * (c) Dalibor Karlović <dalibor@flexolabs.io>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xezilaires;

/**
 * Interface Iterator.
 */
interface Iterator extends \Iterator
{
    /**
     * @return object
     */
    public function current();

    /**
     * @return int
     */
    public function key(): int;

    /**
     * @param int $index
     */
    public function seek(int $index): void;

    public function prev(): void;
}
