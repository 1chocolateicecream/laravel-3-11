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
        Schema::create('groups', function (Blueprint $table) {
            $table->id(); // Это твой group_id (Laravel по дефолту называет первичный ключ id)
            $table->string('name', 45);
            $table->timestamps(); // Автоматически создаст поля created_at и updated_at
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->timestamps();
        });

        Schema::create('internships', function (Blueprint $table) {
            $table->id(); // По заданию internship_id
            $table->string('name', 45);
            $table->text('goals')->nullable();
            // Тип оценки: баллы, зачет/незачет или проценты
            $table->enum('evaluation_type', ['balles', 'i/ni', 'procenti']);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->enum('role', ['student', 'school_supervisor', 'company_supervisor']);
            $table->foreignId('group_id')->nullable()->constrained('groups'); // Связь с группами
            $table->foreignId('company_id')->nullable()->constrained('companies'); // Та самая связь с компанией
            $table->timestamps();
        });

        Schema::create('group_internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('internship_id')->constrained()->onDelete('cascade');
            $table->date('start_at');
            $table->date('end_at');
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('group_id')->constrained();
            $table->foreignId('internship_id')->constrained();
            $table->foreignId('company_id')->constrained();
            $table->timestamp('approved_at')->nullable(); // Дата одобрения
            $table->text('motivation_letter'); // То самое письмо
            $table->enum('status', ['pending', 'approved', 'rejected', 'terminated'])->default('pending');
            $table->timestamps();
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            // Связь с конкретной попыткой практики в конкретной фирме
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->text('comment')->nullable();
            $table->string('grade', 10); // Сама оценка (строка, чтобы влезло и "10", и "i")
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internship_tables');
    }
};
