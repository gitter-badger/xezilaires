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

namespace Xezilaires\Bridge\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as PhpSpreadsheetReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet as PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Xezilaires\Exception\SpreadsheetException;
use Xezilaires\Iterator;
use Xezilaires\Spreadsheet as SpreadsheetInterface;

/**
 * Class Loader.
 */
class Spreadsheet implements SpreadsheetInterface
{
    private const CELL_NO_AUTO_CREATE = false;

    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var null|PhpSpreadsheet
     */
    private $spreadsheet;

    /**
     * @var null|Iterator
     */
    private $iterator;

    /**
     * @param \SplFileObject $file
     */
    public function __construct(\SplFileObject $file)
    {
        $this->file = $file;
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

        $this->iterator = new RowIterator($sheet->getRowIterator($startRowIndex));
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
    public function getCurrentRow(): array
    {
        /** @var Row $row */
        $row = $this->getIterator()->current();

        return $this->getRow($row->getRowIndex());
    }

    /**
     * {@inheritdoc}
     */
    public function getRow(int $rowIndex): array
    {
        $data = [];
        $worksheet = $this->getActiveWorksheet();
        $columnIterator = $worksheet->getColumnIterator();

        foreach ($columnIterator as $column) {
            $columnName = $column->getColumnIndex();
            $data[$columnName] = $this->fetchCell($columnName, $rowIndex);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getHighestRow(): int
    {
        return $this->getActiveWorksheet()->getHighestRow();
    }

    /**
     * @return PhpSpreadsheet
     */
    private function getSpreadsheet(): PhpSpreadsheet
    {
        if (null === $this->spreadsheet) {
            $path = $this->file->getRealPath();
            if (false === $path) {
                throw SpreadsheetException::noSpreadsheetFound();
            }

            try {
                $reader = IOFactory::createReaderForFile($path);
                $this->spreadsheet = $reader->load($path);
            } catch (PhpSpreadsheetReaderException $exception) {
                throw SpreadsheetException::invalidSpreadsheet($exception);
            }
        }

        return $this->spreadsheet;
    }

    /**
     * @return Worksheet
     */
    private function getActiveWorksheet(): Worksheet
    {
        try {
            return $this->getSpreadsheet()->getActiveSheet();
        } catch (PhpSpreadsheetException $exception) {
            throw SpreadsheetException::failedFetchingActiveWorksheet($exception);
        }
    }

    /**
     * @param string $columnName
     * @param int    $rowIndex
     *
     * @return null|string|int|float
     */
    private function fetchCell(string $columnName, int $rowIndex)
    {
        $worksheet = $this->getActiveWorksheet();
        $columnIndex = Coordinate::columnIndexFromString($columnName);

        /** @var null|Cell $cell */
        $cell = $worksheet->getCellByColumnAndRow($columnIndex, $rowIndex, self::CELL_NO_AUTO_CREATE);
        if (null === $cell) {
            return null;
        }

        /** @var null|string|int|float $value */
        $value = $cell->getValue();

        return $value;
    }
}
