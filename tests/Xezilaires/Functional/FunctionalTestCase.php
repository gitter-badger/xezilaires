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

namespace Xezilaires\Test\Functional;

use PHPUnit\Framework\TestCase;
use Xezilaires\Metadata\Annotation\AnnotationDriver;
use Xezilaires\Metadata\ArrayReference;
use Xezilaires\Metadata\ColumnReference;
use Xezilaires\Metadata\HeaderReference;
use Xezilaires\Metadata\Mapping;
use Xezilaires\Spreadsheet;
use Xezilaires\SpreadsheetIterator;
use Xezilaires\Test\FixtureTrait;
use Xezilaires\Test\IteratorMatcherTrait;
use Xezilaires\Test\Model\Product;

abstract class FunctionalTestCase extends TestCase
{
    use FixtureTrait;
    use IteratorMatcherTrait;

    public function testCanLoadFlatFixtureWithColumnReference(): void
    {
        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products.xls')),
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

        self::assertIteratorMatches([
            ['name' => 'The Very Hungry Caterpillar', 'price' => '6.59'],
            ['name' => 'Brown Bear, Brown Bear, What Do You See?', 'price' => '6.51'],
        ], $iterator);
    }

    public function testCanLoadFlatFixtureWithHeaderReference(): void
    {
        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products.xls')),
            new Mapping(
                Product::class,
                [
                    'price' => new HeaderReference('Price USD'),
                    'name' => new HeaderReference('Name'),
                ],
                [
                    'header' => 1,
                    'start' => 2,
                ]
            )
        );

        self::assertIteratorMatches([
            ['name' => 'The Very Hungry Caterpillar', 'price' => '6.59'],
            ['name' => 'Brown Bear, Brown Bear, What Do You See?', 'price' => '6.51'],
        ], $iterator);
    }

    public function testCanLoadFlatFixtureWithArrayReference(): void
    {
        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products.xls')),
            new Mapping(
                Product::class,
                [
                    'all' => new ArrayReference([
                        new HeaderReference('Name'),
                        new HeaderReference('Price USD'),
                    ]),
                ],
                [
                    'header' => 1,
                    'start' => 2,
                ]
            )
        );

        self::assertIteratorMatches([
            ['all' => ['The Very Hungry Caterpillar', '6.59']],
            ['all' => ['Brown Bear, Brown Bear, What Do You See?', '6.51']],
        ], $iterator);
    }

    public function testCanLoadSparseFixtureWithHeaderReference(): void
    {
        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products-sparse.xls')),
            new Mapping(
                Product::class,
                [
                    'price' => new HeaderReference('Price USD'),
                    'name' => new HeaderReference('Name'),
                ],
                [
                    'header' => 1,
                    'start' => 2,
                ]
            )
        );

        self::assertIteratorMatches([
            ['name' => 'The Very Hungry Caterpillar', 'price' => '6.59'],
            ['name' => 'Brown Bear, Brown Bear, What Do You See?', 'price' => '6.51'],
        ], $iterator);
    }

    /**
     * @uses \Xezilaires\Metadata\Annotation\AnnotationDriver
     *
     * @throws \RuntimeException
     */
    public function testCanLoadSparseFixtureWithAnnotations(): void
    {
        $driver = new AnnotationDriver();
        $mapping = $driver->getMetadataMapping(Product::class);

        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products-sparse.xls')),
            $mapping
        );

        self::assertIteratorMatches([
            ['all' => ['The Very Hungry Caterpillar', '6.59'], 'name' => 'The Very Hungry Caterpillar', 'price' => '6.59'],
            ['all' => ['Brown Bear, Brown Bear, What Do You See?', '6.51'], 'name' => 'Brown Bear, Brown Bear, What Do You See?', 'price' => '6.51'],
        ], $iterator);
    }

    public function testCannotLoadFixtureWithDuplicateHeaderReference(): void
    {
        $this->expectException(\Xezilaires\Exception\MappingException::class);
        $this->expectExceptionMessage('Duplicate header "Name"');

        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products-duplicate-header.xls')),
            new Mapping(
                Product::class,
                [
                    'price' => new HeaderReference('Price USD'),
                    'name' => new HeaderReference('Name'),
                ],
                [
                    'header' => 1,
                    'start' => 2,
                ]
            )
        );

        iterator_to_array($iterator);
    }

    public function testCannotLoadFixtureWithInvalidHeaderReference(): void
    {
        $this->expectException(\Xezilaires\Exception\MappingException::class);
        $this->expectExceptionMessage('Invalid header "No such name"');

        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products.xls')),
            new Mapping(
                Product::class,
                [
                    'price' => new HeaderReference('Price USD'),
                    'name' => new HeaderReference('No such name'),
                ],
                [
                    'header' => 1,
                    'start' => 2,
                ]
            )
        );

        iterator_to_array($iterator);
    }

    public function testCannotLoadFlatFixtureWithNestedArrayReference(): void
    {
        $this->expectException(\Xezilaires\Exception\MappingException::class);
        $this->expectExceptionMessage('Unexpected reference type');

        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products.xls')),
            new Mapping(
                Product::class,
                [
                    'all' => new ArrayReference([
                        new ArrayReference([
                            new HeaderReference('Name'),
                            new HeaderReference('Price USD'),
                        ]),
                    ]),
                ],
                [
                    'header' => 1,
                    'start' => 2,
                ]
            )
        );

        iterator_to_array($iterator);
    }

    public function testCannotLoadUnreachableFile(): void
    {
        $this->expectException(\Xezilaires\Exception\SpreadsheetException::class);
        $this->expectExceptionMessage('No spreadsheet path given');

        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->invalidFixture('products.xls')),
            new Mapping(Product::class, ['name' => new ColumnReference('A')])
        );

        iterator_to_array($iterator);
    }

    public function testCanFetchCurrentIteratorItem(): void
    {
        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products.xls')),
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

        $iterator->seek(1);
        static::assertEquals(1, $iterator->key());

        $current = new Product();
        $current->name = 'Brown Bear, Brown Bear, What Do You See?';
        $current->price = '6.51';
        static::assertEquals($current, $iterator->current());
    }

    public function testCanRewindIterator(): void
    {
        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products.xls')),
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

        $iterator->seek(1);
        static::assertEquals(1, $iterator->key());

        $iterator->rewind();
        $current = new Product();
        $current->name = 'The Very Hungry Caterpillar';
        $current->price = '6.59';
        static::assertEquals($current, $iterator->current());
        static::assertEquals(0, $iterator->key());
    }

    /**
     * @uses \Xezilaires\ReverseIterator
     */
    public function testCanLoadFlatFixtureInReverse(): void
    {
        $iterator = new SpreadsheetIterator(
            $this->getSpreadsheet($this->fixture('products.xls')),
            new Mapping(
                Product::class,
                [
                    'name' => new ColumnReference('A'),
                    'price' => new ColumnReference('B'),
                ],
                [
                    'start' => 2,
                    'reverse' => true,
                ]
            )
        );

        self::assertIteratorMatches([
            ['name' => 'Brown Bear, Brown Bear, What Do You See?', 'price' => '6.51'],
            ['name' => 'The Very Hungry Caterpillar', 'price' => '6.59'],
        ], $iterator);
    }

    /**
     * @param \SplFileObject $file
     *
     * @return Spreadsheet
     */
    abstract protected function getSpreadsheet(\SplFileObject $file): Spreadsheet;
}
