<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_IndiaGST1 extends Migration
{
	public function up(): void
	{
		execute_script(APPPATH . 'migrations/sqlscripts/3.3.0_indiagst1.sql');

		error_log('Fix definition of Supplier.Tax Id');

		error_log('Definition of Supplier.Tax Id corrected');
	}

	public function down(): void
	{
	}

}
?>
