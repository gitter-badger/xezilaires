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

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Box\Spout\Reader\IteratorInterface;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use Xezilaires\Exception\SpreadsheetException;
use Xezilaires\Iterator;
use Xezilaires\Spreadsheet as SpreadsheetInterface;

/**
 * Class Spreadsheet.
 */
class Spreadsheet implements SpreadsheetInterface
{
    /**
     * @var array<int, string> $indexCache
     */
    private static $indexCache = [];

    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var string
     */
    private $type;

    /**
     * @var null|ReaderInterface
     */
    private $reader;

    /**
     * @var null|RowIterator
     */
    private $iterator;

    public function __construct(\SplFileObject $file, string $type = Type::XLSX)
    {
        $this->file = $file;
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function createIterator(int $startRowIndex): void
    {
        if (null !== $this->iterator) {
            throw SpreadsheetException::iteratorAlreadyCreated();
        }

        $sheet = $this->getActiveWorksheet();

        /** @var IteratorInterface $iterator */
        $iterator = $sheet->getRowIterator();

        $this->iterator = new RowIterator($iterator, $startRowIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Iterator
    {
        if (null === $this->iterator) {
            throw SpreadsheetException::noIterator();
        }

        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function getRow(int $rowIndex): array
    {
        $iterator = $this->getIterator();
        $iterator->seek($rowIndex);

        return $this->getCurrentRow();
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentRow(): array
    {
        /** @var \ArrayObject $rowArrayObject */
        $rowArrayObject = $this->getIterator()->current();

        /** @var array<string, null|string|int|float> $row */
        $row = [];

        /**
         * @var int                   $columnIndex
         * @var null|string|int|float $columnValue
         */
        foreach ($rowArrayObject as $columnIndex => $columnValue) {
            $columnName = self::stringFromColumnIndex($columnIndex + 1);

            $row[$columnName] = '' !== $columnValue ? $columnValue : null;
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function getHighestRow(): int
    {
        if (null === $this->iterator) {
            throw SpreadsheetException::noIterator();
        }

        return $this->iterator->getHighestRow();
    }

    /**
     * @author https://github.com/phpoffice/PhpSpreadsheet
     */
    private static function stringFromColumnIndex(int $columnIndex): string
    {
        if (false === isset(self::$indexCache[$columnIndex])) {
            $indexValue = $columnIndex;
            $base26 = '';
            do {
                $characterValue = ($indexValue % 26) ?: 26;
                $indexValue = (int) (($indexValue - $characterValue) / 26);
                $base26 = \chr($characterValue + 64).($base26 ?: '');
            } while ($indexValue > 0);

            self::$indexCache[$columnIndex] = $base26;
        }

        return self::$indexCache[$columnIndex];
    }

    private function getReader(): ReaderInterface
    {
        if (null === $this->reader) {
            $path = $this->file->getRealPath();
            if (false === $path) {
                throw SpreadsheetException::noSpreadsheetFound();
            }

            try {
                $this->reader = ReaderFactory::create($this->type);
                $this->reader->open($path);
            } catch (UnsupportedTypeException | IOException $exception) {
                throw SpreadsheetException::invalidSpreadsheet($exception);
            }
        }

        return $this->reader;
    }

    private function getActiveWorksheet(): SheetInterface
    {
        try {
            /** @var SheetInterface $sheet */
            foreach ($this->getReader()->getSheetIterator() as $sheet) {
                return $sheet;
            }
            throw new \RuntimeException('No sheets found');
        } catch (ReaderNotOpenedException | \RuntimeException $exception) {
            throw SpreadsheetException::failedFetchingActiveWorksheet($exception);
        }
    }
}
