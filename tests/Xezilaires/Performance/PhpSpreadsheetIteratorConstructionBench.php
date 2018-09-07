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

namespace Xezilaires\Test\Performance;

use PhpBench\Benchmark\Metadata\Annotations as Bench;
use Xezilaires\Metadata\ColumnReference;
use Xezilaires\Metadata\Mapping;
use Xezilaires\PhpSpreadsheetIterator;
use Xezilaires\Test\FixtureLoaderTrait;
use Xezilaires\Test\Model\Product;

/**
 * @Bench\BeforeMethods({"setUp"})
 */
class PhpSpreadsheetIteratorConstructionBench
{
    use FixtureLoaderTrait;

    /**
     * @var \SplFileObject
     */
    private $fixture;

    public function setUp(): void
    {
        $this->fixture = $this->fixture('products.xls');
    }

    /**
     * @Bench\Assert(stat="mean", value="10")
     * @Bench\Revs(1000)
     */
    public function benchIteratorConstruction(): void
    {
        new PhpSpreadsheetIterator(
            $this->fixture,
            new Mapping(
                Product::class,
                [
                    'name' => new ColumnReference('A'),
                    'price' => new ColumnReference('B'),
                ],
                [
                    'start' => 2,
                ]
            )
        );
    }

    /**
     * @Bench\Assert(stat="mean", value="1500")
     * @Bench\Revs(100)
     */
    public function benchIteratorIteration(): void
    {
        $iterator = new PhpSpreadsheetIterator(
            $this->fixture,
            new Mapping(
                Product::class,
                [
                    'name' => new ColumnReference('A'),
                    'price' => new ColumnReference('B'),
                ],
                [
                    'start' => 2,
                ]
            )
        );

        iterator_to_array($iterator);
    }
}
