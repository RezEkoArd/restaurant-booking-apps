<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Table::insert([
            ['table_no' => 'T01', 'status' => 'available'],
            ['table_no' => 'T02', 'status' => 'occupied'],
            ['table_no' => 'T03', 'status' => 'reserved'],
            ['table_no' => 'T04', 'status' => 'available'],
            ['table_no' => 'T05', 'status' => 'maintenance'],
            ['table_no' => 'T06', 'status' => 'available'],
            ['table_no' => 'T07', 'status' => 'occupied'],
            ['table_no' => 'T08', 'status' => 'available'],
            ['table_no' => 'T09', 'status' => 'reserved'],
            ['table_no' => 'T10', 'status' => 'available'],
            ['table_no' => 'E01', 'status' => 'available'],
            ['table_no' => 'E02', 'status' => 'reserved'],
            ['table_no' => 'E03', 'status' => 'occupied'],
            ['table_no' => 'E04', 'status' => 'available'],
            ['table_no' => 'E05', 'status' => 'maintenance'],
        ]);
    }
}
