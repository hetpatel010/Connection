<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesAccountingContext;
use Tests\CreatesBusinessContext;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->use(CreatesBusinessContext::class)
    ->use(CreatesAccountingContext::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Spreadsheet Helpers
|--------------------------------------------------------------------------
|
| Small readers used by the Excel export tests. They deliberately avoid pulling
| in a second spreadsheet library: a .xlsx is a zip of XML, which is enough to
| assert that an export really is formatted.
|
*/

const BUILT_IN_NUMBER_FORMATS = [
    0 => 'General',
    1 => '0',
    2 => '0.00',
    3 => '#,##0',
    4 => '#,##0.00',
    9 => '0%',
    49 => '@',
];

function xlsxEntry(string $path, string $entry): string
{
    $zip = new ZipArchive;
    $zip->open($path);
    $contents = $zip->getFromName($entry);
    $zip->close();

    return $contents === false ? '' : $contents;
}

function xlsxSheet(string $path, int $index = 1): string
{
    return xlsxEntry($path, 'xl/worksheets/sheet'.$index.'.xml');
}

/**
 * The inner XML of a single cell, e.g. "B2".
 */
function xlsxCell(string $sheetXml, string $reference): string
{
    preg_match('/<c r="'.$reference.'"(?:.*?<\/c>|[^>]*\/>)/s', $sheetXml, $matches);

    return $matches[0] ?? '';
}

/**
 * Every distinct number format referenced by the workbook's cell styles, resolved
 * to its format code whether it is built into Excel or declared in <numFmts>.
 *
 * @return list<string>
 */
function xlsxNumberFormats(string $path): array
{
    $styles = xlsxEntry($path, 'xl/styles.xml');

    $custom = [];

    preg_match_all('/<numFmt numFmtId="(\d+)" formatCode="([^"]*)"/', $styles, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $custom[(int) $match[1]] = $match[2];
    }

    preg_match('/<cellXfs[^>]*>(.*?)<\/cellXfs>/s', $styles, $cellXfs);
    preg_match_all('/<xf [^>]*numFmtId="(\d+)"/', $cellXfs[1] ?? '', $used, PREG_SET_ORDER);

    $formats = [];

    foreach ($used as $match) {
        $id = (int) $match[1];

        $formats[] = $custom[$id] ?? BUILT_IN_NUMBER_FORMATS[$id] ?? 'General';
    }

    return array_values(array_unique($formats));
}
