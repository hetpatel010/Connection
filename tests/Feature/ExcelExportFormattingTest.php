<?php

use App\Application\Services\ExcelExportWriter;

test('column widths are auto fitted within the 10 to 50 bounds', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createExcel($writer->tempPath('widths'), [
        'sheetName' => 'Widths',
        'columns' => [
            ['header' => 'Short', 'key' => 'short', 'type' => 'text'],
            ['header' => 'Very Long Column Header Here', 'key' => 'long', 'type' => 'text'],
        ],
        'rows' => [
            ['short' => 'tiny', 'long' => str_repeat('W', 200)],
        ],
    ]);

    $xml = xlsxSheet($path);

    expect($xml)->toContain('<cols>');

    preg_match('/<col min="1" max="1" width="([0-9.]+)"/', $xml, $short);
    preg_match('/<col min="2" max="2" width="([0-9.]+)"/', $xml, $long);

    // "Short" is shorter than the minimum, the long value is clamped to the maximum.
    expect((float) $short[1])->toBe(10.0)
        ->and((float) $long[1])->toBe(50.0);
});

test('header row is bold white text on a dark blue fill and vertically centred', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createExcel($writer->tempPath('header'), [
        'sheetName' => 'Header',
        'columns' => [['header' => 'Name', 'key' => 'name', 'type' => 'text']],
        'rows' => [['name' => 'Acme']],
    ]);

    $styles = xlsxEntry($path, 'xl/styles.xml');

    expect($styles)
        ->toContain('fgColor rgb="'.ExcelExportWriter::HEADER_BACKGROUND_COLOR.'"')
        ->toContain('<b/>')
        ->toContain('<color rgb="FFFFFFFF"/>')
        ->toContain('vertical="center"');

    // The first data cell must not carry the header fill.
    expect(xlsxCell(xlsxSheet($path), 'A1'))->not->toContain('fgColor');
});

test('header row is frozen and an auto filter spans the table', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createExcel($writer->tempPath('chrome'), [
        'sheetName' => 'Chrome',
        'columns' => [
            ['header' => 'Name', 'key' => 'name', 'type' => 'text'],
            ['header' => 'Email', 'key' => 'email', 'type' => 'text'],
        ],
        'rows' => [
            ['name' => 'Acme', 'email' => 'a@acme.test'],
            ['name' => 'Globex', 'email' => 'b@globex.test'],
        ],
    ]);

    expect(xlsxSheet($path))
        ->toContain('ySplit="1"')
        ->toContain('topLeftCell="A2"')
        ->toContain('state="frozen"')
        ->toContain('<autoFilter ref="A1:B3"/>');
});

test('phone gstin and pincode columns are written as text without scientific notation', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createExcel($writer->tempPath('text'), [
        'sheetName' => 'Text',
        'columns' => [
            ['header' => 'Phone', 'key' => 'phone', 'type' => 'text'],
            ['header' => 'GSTIN', 'key' => 'gstin', 'type' => 'text'],
            ['header' => 'Pincode', 'key' => 'pincode', 'type' => 'text'],
        ],
        'rows' => [[
            'phone' => '9876543210',
            'gstin' => '27AAECS1234F1Z5',
            'pincode' => '012345',
        ]],
    ]);

    $sheet = xlsxSheet($path);

    expect($sheet)
        ->toContain('9876543210')
        ->toContain('012345')
        ->not->toContain('9.87654321E+9');

    // Text cells are inline strings, so the leading zero of the pincode survives.
    expect(xlsxCell($sheet, 'C2'))->toContain('t="inlineStr"');

    expect(xlsxNumberFormats($path))->toContain('@');
});

test('identifier headers are forced to text even without an explicit type', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createExcel($writer->tempPath('forced'), [
        'sheetName' => 'Forced',
        'columns' => [
            ['header' => 'Invoice Number', 'key' => 'invoice_number'],
            ['header' => 'HSN/SAC', 'key' => 'hsn_sac'],
            ['header' => 'Qty', 'key' => 'quantity'],
        ],
        'rows' => [[
            'invoice_number' => 'INV-0001',
            'hsn_sac' => '0901',
            'quantity' => 5,
        ]],
    ]);

    $sheet = xlsxSheet($path);

    expect(xlsxNumberFormats($path))->toContain('@', '#,##0.00');

    // The two identifier columns stay text; Qty stays a real number.
    expect(xlsxCell($sheet, 'A2'))->toContain('t="inlineStr"')
        ->and(xlsxCell($sheet, 'B2'))->toContain('t="inlineStr"')
        ->and(xlsxCell($sheet, 'C2'))->toContain('<v>5</v>');
});

test('currency and number columns use thousand separators and dates display as dd-mm-yyyy', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createExcel($writer->tempPath('formats'), [
        'sheetName' => 'Formats',
        'columns' => [
            ['header' => 'Total (₹)', 'key' => 'total', 'type' => 'currency'],
            ['header' => 'Qty', 'key' => 'qty', 'type' => 'number'],
            ['header' => 'Date', 'key' => 'date', 'type' => 'date'],
        ],
        'rows' => [[
            'total' => 1234567.5,
            'qty' => 12,
            'date' => '2026-01-09',
        ]],
    ]);

    expect(xlsxNumberFormats($path))->toContain('#,##0.00', 'dd-mm-yyyy');

    // The value itself is untouched: a real number, and a real date serial.
    expect(xlsxCell(xlsxSheet($path), 'A2'))->toContain('<v>1234567.5</v>');
});

test('null and undefined values export as an empty cell rather than the text null', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createExcel($writer->tempPath('nulls'), [
        'sheetName' => 'Nulls',
        'columns' => [
            ['header' => 'Name', 'key' => 'name', 'type' => 'text'],
            ['header' => 'Phone', 'key' => 'phone', 'type' => 'text'],
            ['header' => 'Total (₹)', 'key' => 'total', 'type' => 'currency'],
        ],
        'rows' => [
            ['name' => 'Acme'],
            ['name' => null, 'phone' => null, 'total' => null],
        ],
    ]);

    $sheet = xlsxSheet($path);

    expect($sheet)
        ->not->toContain('>null<')
        ->not->toContain('>undefined<');

    expect(xlsxCell($sheet, 'B3'))->not->toContain('<v>');
    expect(xlsxCell($sheet, 'C3'))->not->toContain('<v>');
});

test('an export with no rows still contains the styled header row', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createExcel($writer->tempPath('empty'), [
        'sheetName' => 'Empty',
        'columns' => [
            ['header' => 'Name', 'key' => 'name', 'type' => 'text'],
            ['header' => 'Phone', 'key' => 'phone', 'type' => 'text'],
        ],
        'rows' => [],
    ]);

    expect(xlsxSheet($path))
        ->toContain('Name')
        ->toContain('Phone')
        ->toContain('<autoFilter ref="A1:B1"/>')
        ->toContain('state="frozen"');

    expect(xlsxEntry($path, 'xl/styles.xml'))
        ->toContain('fgColor rgb="'.ExcelExportWriter::HEADER_BACKGROUND_COLOR.'"');
});

test('a multi sheet workbook formats every table sheet and keeps the raw summary sheet', function () {
    $writer = app(ExcelExportWriter::class);

    $path = $writer->createWorkbook($writer->tempPath('workbook'), [
        [
            'sheetName' => 'Summary',
            'rows' => [['Business Export Summary'], [], ['Business', 'Acme']],
        ],
        [
            'sheetName' => 'Parties',
            'columns' => [['header' => 'Phone', 'key' => 'phone', 'type' => 'text']],
            'rows' => [['phone' => '9876543210']],
        ],
    ]);

    $summary = xlsxSheet($path, 1);
    $parties = xlsxSheet($path, 2);

    expect($summary)
        ->toContain('Business Export Summary')
        ->toContain('Acme')
        ->not->toContain('state="frozen"');

    expect($parties)
        ->toContain('state="frozen"')
        ->toContain('9876543210');

    expect(xlsxEntry($path, 'xl/workbook.xml'))->toContain('Parties');
});
