<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('task_code_sequences',function(Blueprint $t){$t->string('date',8)->primary();$t->unsignedInteger('value')->default(0);});}
 public function down():void {Schema::dropIfExists('task_code_sequences');}
};
