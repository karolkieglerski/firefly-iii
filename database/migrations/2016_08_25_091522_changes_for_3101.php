<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class ChangesFor3101
 */
class ChangesFor3101 extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
    }

    /**
     * Run the migrations.
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public function up()
    {
        Schema::table(
            'import_jobs',
            function (Blueprint $table) {
                $table->text('extended_status')->nullable();
            }
        );
    }
}
