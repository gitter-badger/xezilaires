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

namespace Xezilaires\Test\Model;

use Xezilaires\Nestable;

/**
 * Class Category.
 */
class Category implements Nestable
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var null|int
     */
    public $parent;

    /**
     * @var string
     */
    public $name;

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function hasParent(): bool
    {
        return null !== $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentIdentifier()
    {
        return $this->parent;
    }
}
