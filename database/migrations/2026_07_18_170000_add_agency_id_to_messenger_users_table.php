<?php

use App\Models\Agency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messenger_users', function (Blueprint $table) {
            $table->foreignIdFor(Agency::class)->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('messenger_users', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Agency::class);
        });
    }
};
