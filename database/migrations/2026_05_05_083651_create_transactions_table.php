public function up()
{
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Hubungkan ke user logged-in
        $table->string('nama');
        $table->date('tanggal'); // Menyimpan tanggal lengkap (YYYY-MM-DD)
        $table->string('kategori');
        $table->enum('tipe', ['pemasukan', 'pengeluaran']);
        $table->integer('jumlah');
        $table->string('icon')->nullable();
        $table->string('color')->nullable();
        $table->timestamps();
    });
}