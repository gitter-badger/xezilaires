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

namespace Xezilaires\Test;

use Xezilaires\Iterator;

/**
 * @internal
 */
class FakeIterator implements Iterator
{
    /**
     * @var \ArrayIterator
     */
    private $iterator;

    /**
     * @param array<int, int|string|object> $items
     */
    public function __construct(array $items)
    {
        $objects = [];
        foreach ($items as $idx => $item) {
            $objects[$idx] = (object) $item;
        }

        $this->iterator = new \ArrayIterator($objects);
    }

    /**
     * @param \Iterator $iterator
     *
     * @return array<int, int|string|object>
     */
    public static function toArray(\Iterator $iterator): array
    {
        $objects = iterator_to_array($iterator);

        $items = [];

        /**
         * @var int    $idx
         * @var object $object
         */
        foreach ($objects as $idx => $object) {
            if ($object instanceof \stdClass) {
                /** @var int|string $scalar */
                $scalar = $object->scalar;

                $items[$idx] = $scalar;
            } else {
                $items[$idx] = $object;
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->iterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    /**
     * {@inheritdoc}
     *
     * @return object
     */
    public function current()
    {
        /** @var object $current */
        $current = $this->iterator->current();

        return $current;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        /** @var int|string $key */
        $key = $this->iterator->key();

        if (false === \is_int($key)) {
            throw new \LogicException(sprintf('Key must be of type int, %1$s given', \gettype($key)));
        }

        return $key;
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $rowIndex): void
    {
        $this->iterator->seek($rowIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function prev(): void
    {
    }
}
