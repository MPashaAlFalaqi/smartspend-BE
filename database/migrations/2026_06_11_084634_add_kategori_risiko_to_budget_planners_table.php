public function up(): void
{
    Schema::table('budget_planners', function (Blueprint $table) {
        // Tambahkan kolom kategori_risiko setelah kolom yang sudah ada (opsional)
        $table->string('kategori_risiko')->nullable()->after('user_id'); 
    });
}

public function down(): void
{
    Schema::table('budget_planners', function (Blueprint $table) {
        $table->dropColumn('kategori_risiko');
    });
}