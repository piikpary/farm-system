<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;

trait WithFormulaCache
{
    public function preCalculateFormulas(): bool
    {
        return true;
    }

    public function registerFormulaCacheEvents(AfterSheet $event): void
    {
        $spreadsheet = $event->sheet->getDelegate()->getParent();

        try {
            $spreadsheet->getCalculationEngine()->clearCalculationCache();
        } catch (\Throwable $e) {
            //
        }

        try {
            $spreadsheet->getCalculationProperties()->setCalcMode('auto');
        } catch (\Throwable $e) {
            //
        }
    }
}