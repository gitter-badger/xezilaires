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

namespace Xezilaires\Exception;

use Xezilaires\Exception;

/**
 * Class ReferenceException.
 */
class ReferenceException extends \InvalidArgumentException implements Exception
{
    /**
     * @return self
     */
    public static function invalidReference(): self
    {
        return new self('Invalid reference');
    }
}