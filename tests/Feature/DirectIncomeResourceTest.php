<?php

namespace Tests\Feature;

use App\Filament\Resources\Finance\DirectIncomes\DirectIncomeResource;
use Tests\TestCase;

class DirectIncomeResourceTest extends TestCase
{
    public function test_relation_managers_resolve(): void
    {
        foreach (DirectIncomeResource::getRelations() as $relationManager) {
            $this->assertTrue(class_exists($relationManager), "{$relationManager} should exist.");
        }
    }
}
