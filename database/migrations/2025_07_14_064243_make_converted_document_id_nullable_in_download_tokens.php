<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('download_tokens', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['converted_document_id']);
        });

        Schema::table('download_tokens', function (Blueprint $table) {
            // Make column nullable and re-add constraint
            $table->unsignedBigInteger('converted_document_id')->nullable()->change();
            $table->foreign('converted_document_id')
                  ->references('id')->on('converted_documents')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('download_tokens', function (Blueprint $table) {
            $table->dropForeign(['converted_document_id']);
            $table->unsignedBigInteger('converted_document_id')->nullable(false)->change();
            $table->foreign('converted_document_id')
                  ->references('id')->on('converted_documents')
                  ->onDelete('cascade');
        });
    }
};
