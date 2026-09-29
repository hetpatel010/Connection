<?php

namespace App\Application\Services;

use DateTimeImmutable;
use DateTimeInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class ExcelExportWriter
{
    public const string TYPE_TEXT = 'text';

    public const string TYPE_NUMBER = 'number';

    public const string TYPE_DATE = 'date';

    public const string TYPE_CURRENCY = 'currency';

    public const string HEADER_BACKGROUND_COLOR = '1F4E78';

    private const float MIN_COLUMN_WIDTH = 10.0;

    private const float MAX_COLUMN_WIDTH = 50.0;

    private const float COLUMN_WIDTH_PADDING = 2.0;

    private const int FROZEN_HEADER_ROW = 2;

    private const string TEXT_FORMAT = '@';

    private const string DECIMAL_FORMAT = '#,##0.00';

    private const string DATE_FORMAT = 'dd-mm-yyyy';

    /**
     * Headers matching any of these are always written as Excel text, so identifiers
     * such as phone numbers, GSTINs and invoice numbers are never coerced to a
     * number (which would render as 9.87E+11 and drop leading zeros).
     *
     * @var list<string>
     */
    private const array TEXT_HEADER_PATTERNS = [
        'phone', 'mobile', 'gstin', 'pan', 'pincode', 'pin code', 'number',
        'hsn', 'sac', 'sku', 'barcode', 'code', 'ref', 'reference', 'email', 'ifsc',
    ];

    /**
     * Write a single formatted worksheet and return the same path.
     *
     * @param  array{
     *     sheetName: string,
     *     columns: list<array{header: string, key?: string, type?: string}>,
     *     rows: list<array<string, mixed>>
     * }  $export
     */
    public function createExcel(string $path, array $export): string
    {
        return $this->createWorkbook($path, [$export]);
    }

    /**
     * Write a multi-sheet workbook and return the same path.
     *
     * Each entry is a sheet spec. When it carries `columns` it is written as a
     * formatted table (auto-fit widths, styled header, frozen header, auto-filter);
     * otherwise its `rows` are written verbatim, which suits key/value layouts.
     *
     * @param  list<array{
     *     sheetName: string,
     *     columns?: list<array{header: string, key?: string, type?: string}>,
     *     rows: list<array<array-key, mixed>>
     * }>  $sheets
     */
    public function createWorkbook(string $path, array $sheets): string
    {
        $writer = $this->openWriter($path);

        foreach (array_values($sheets) as $index => $sheet) {
            $current = $index === 0
                ? $writer->getCurrentSheet()
                : $writer->addNewSheetAndMakeItCurrent();

            $current->setName($this->sanitizeSheetName((string) ($sheet['sheetName'] ?? 'Sheet')));

            $rows = $sheet['rows'] ?? [];
            $columns = $this->normalizeColumns($sheet['columns'] ?? []);

            if ($columns === []) {
                $this->writeRawRows($writer, $rows);

                continue;
            }

            $this->writeTable($writer, $current, $columns, $this->resolveColumnTypes($columns, $rows), $rows);
        }

        $writer->close();

        return $path;
    }

    public function tempPath(string $prefix = 'export'): string
    {
        return $this->tempFolder().'/'.uniqid($prefix.'_', true).'.xlsx';
    }

    public function cleanup(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function openWriter(string $path): Writer
    {
        $writer = new Writer(new Options(tempFolder: $this->tempFolder()));
        $writer->openToFile($path);

        return $writer;
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function writeRawRows(Writer $writer, array $rows): void
    {
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
    }

    private function tempFolder(): string
    {
        $tempDir = storage_path('app/temp/exports');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return $tempDir;
    }

    private function sanitizeSheetName(string $name): string
    {
        $sanitized = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $name) ?? 'Export';

        return mb_substr($sanitized, 0, 31);
    }

    /**
     * @param  list<array{header: string, key?: string, type?: string}>  $columns
     * @return list<array{header: string, key: string, type: string|null}>
     */
    private function normalizeColumns(array $columns): array
    {
        $normalized = [];
        $seen = [];

        foreach ($columns as $column) {
            $header = (string) ($column['header'] ?? '');
            $key = isset($column['key']) && $column['key'] !== ''
                ? (string) $column['key']
                : $this->keyFromHeader($header);

            $key = isset($seen[$key]) ? $key.'_'.$seen[$key] : $key;
            $seen[$key] = ($seen[$key] ?? 0) + 1;

            $type = isset($column['type']) ? (string) $column['type'] : null;

            $normalized[] = ['header' => $header, 'key' => $key, 'type' => $type];
        }

        return $normalized;
    }

    private function keyFromHeader(string $header): string
    {
        $key = strtolower(trim($header));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';

        return trim($key, '_');
    }

    /**
     * @param  list<array{header: string, key: string, type: string|null}>  $columns
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, string>
     */
    private function resolveColumnTypes(array $columns, array $rows): array
    {
        $types = [];

        foreach ($columns as $column) {
            $values = [];

            foreach ($rows as $row) {
                $values[] = $row[$column['key']] ?? null;
            }

            $types[$column['key']] = $this->resolveType($column, $values);
        }

        return $types;
    }

    /**
     * @param  array{header: string, key: string, type: string|null}  $column
     * @param  list<mixed>  $values
     */
    private function resolveType(array $column, array $values): string
    {
        $type = $column['type'];

        if ($type === self::TYPE_TEXT || $this->headerForcesText($column['header'])) {
            return self::TYPE_TEXT;
        }

        if (in_array($type, [self::TYPE_NUMBER, self::TYPE_CURRENCY, self::TYPE_DATE], true)) {
            return $type;
        }

        return $this->inferType($values);
    }

    private function headerForcesText(string $header): bool
    {
        foreach (self::TEXT_HEADER_PATTERNS as $pattern) {
            if (preg_match('/(?<![a-z0-9])'.$pattern.'(?![a-z0-9])/i', $header) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<mixed>  $values
     */
    private function inferType(array $values): string
    {
        $hasValue = false;

        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $hasValue = true;

            if (! is_int($value) && ! is_float($value)) {
                return self::TYPE_TEXT;
            }
        }

        return $hasValue ? self::TYPE_NUMBER : self::TYPE_TEXT;
    }

    /**
     * @param  list<array{header: string, key: string, type: string|null}>  $columns
     * @param  array<string, string>  $types
     * @param  list<array<string, mixed>>  $rows
     */
    private function writeTable(Writer $writer, Sheet $sheet, array $columns, array $types, array $rows): void
    {
        if ($columns === []) {
            return;
        }

        $writer->addRow(Row::fromValuesWithStyle(
            array_column($columns, 'header'),
            (new Style)
                ->withFontBold(true)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor(self::HEADER_BACKGROUND_COLOR)
                ->withCellVerticalAlignment(CellVerticalAlignment::CENTER),
        ));

        $styleCache = [];
        $dataStyles = [];
        $widths = [];

        foreach ($columns as $index => $column) {
            $type = $types[$column['key']];

            $styleCache[$type] ??= $this->dataStyleFor($type);
            $dataStyles[$index] = $styleCache[$type];

            $widths[$index] = mb_strlen($column['header']) + self::COLUMN_WIDTH_PADDING;
        }

        foreach ($rows as $row) {
            $values = [];

            foreach ($columns as $index => $column) {
                $value = $this->cellValue($row[$column['key']] ?? null, $types[$column['key']]);

                $values[$index] = $value;
                $widths[$index] = max($widths[$index], $this->displayWidth($value));
            }

            $writer->addRow(Row::fromValuesWithStyles($values, $dataStyles));
        }

        foreach ($widths as $index => $width) {
            $sheet->setColumnWidth(
                max(self::MIN_COLUMN_WIDTH, min(self::MAX_COLUMN_WIDTH, $width)),
                $index + 1,
            );
        }

        $sheet->setSheetView((new SheetView)->withFreezeRow(self::FROZEN_HEADER_ROW));
        $sheet->setAutoFilter(new AutoFilter(
            fromColumnIndex: 0,
            fromRow: 1,
            toColumnIndex: count($columns) - 1,
            toRow: max(1, count($rows) + 1),
        ));
    }

    private function dataStyleFor(string $type): Style
    {
        $format = match ($type) {
            self::TYPE_TEXT => self::TEXT_FORMAT,
            self::TYPE_NUMBER, self::TYPE_CURRENCY => self::DECIMAL_FORMAT,
            self::TYPE_DATE => self::DATE_FORMAT,
            default => null,
        };

        return $format === null ? new Style : (new Style)->withFormat($format);
    }

    private function cellValue(mixed $value, string $type): DateTimeInterface|bool|float|int|string|null
    {
        return match ($type) {
            self::TYPE_DATE => $this->normalizeDate($value),
            self::TYPE_NUMBER, self::TYPE_CURRENCY => $this->normalizeNumber($value),
            default => $this->normalizeText($value),
        };
    }

    private function normalizeText(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    private function normalizeNumber(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_bool($value) || ! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

    private function normalizeDate(mixed $value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd-m-Y', 'Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);

            if ($date !== false) {
                return $date;
            }
        }

        return null;
    }

    private function displayWidth(DateTimeInterface|bool|float|int|string|null $value): float
    {
        return match (true) {
            $value === null => 0.0,
            is_bool($value) => (float) mb_strlen($value ? 'Yes' : 'No'),
            $value instanceof DateTimeInterface => (float) mb_strlen($value->format('d-m-Y')),
            is_float($value) => (float) mb_strlen(number_format($value, 2, '.', ',')),
            default => (float) mb_strlen((string) $value),
        };
    }
}
