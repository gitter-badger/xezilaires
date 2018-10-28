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

namespace Xezilaires\Bridge\Spout;

use Box\Spout\Reader\IteratorInterface;
use Xezilaires\Iterator;

/**
 * Class RowIterator.
 */
class RowIterator implements Iterator
{
    /**
     * @var IteratorInterface
     */
    private $iterator;

    /**
     * @var int
     */
    private $firstRow;

    /**
     * @var null|int
     */
    private $highestRow;

    public function __construct(IteratorInterface $iterator, int $firstRow)
    {
        $iterator->rewind();

        $this->iterator = $iterator;
        $this->firstRow = $firstRow;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress MissingReturnType Cannot type-hint object here because of 7.1 compat
     */
    public function current()
    {
        /** @var array<int, null|string|int|float> $current */
        $current = $this->iterator->current();

        return new \ArrayObject($current);
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
    public function key(): int
    {
        return (int) $this->iterator->key();
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

        $this->seek($this->firstRow);
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $rowIndex): void
    {
        $currentIndex = $this->key();
        --$rowIndex;

        if ($currentIndex > $rowIndex) {
            $this->iterator->rewind();
        } else {
            $rowIndex -= $currentIndex - 1;
        }

        for ($x = 0; $x < $rowIndex; ++$x) {
            $this->next();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prev(): void
    {
        $this->seek($this->key() - 1);
    }

    public function getHighestRow(): int
    {
        if (null === $this->highestRow) {
            $this->highestRow = 0;

            $this->iterator->rewind();
            while ($this->iterator->valid()) {
                ++$this->highestRow;
                $this->iterator->next();
            }
        }

        return $this->highestRow;
    }
}
