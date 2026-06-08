<?php

use App\Http\Controllers\Siprakar\PekerjaanController;
use App\Http\Controllers\Siprakar\ProgramKerja\ConvertToPekerjaanController;
use App\Http\Controllers\Siprakar\ProgramKerjaController;
use App\Http\Controllers\Siprakar\RabController;
use App\Http\Controllers\Siprakar\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tugas-saya', [PekerjaanController::class, 'tasks'])->name('pekerjaan.tasks');

    Route::get('program-kerja', [ProgramKerjaController::class, 'index'])->name('program-kerja.index');
    Route::get('program-kerja/create', [ProgramKerjaController::class, 'create'])->name('program-kerja.create');
    Route::post('program-kerja', [ProgramKerjaController::class, 'store'])->name('program-kerja.store');
    Route::get('program-kerja/{programKerja}', [ProgramKerjaController::class, 'show'])->name('program-kerja.show');
    Route::get('program-kerja/{programKerja}/edit', [ProgramKerjaController::class, 'edit'])->name('program-kerja.edit');
    Route::put('program-kerja/{programKerja}', [ProgramKerjaController::class, 'update'])->name('program-kerja.update');
    Route::patch('program-kerja/{programKerja}', [ProgramKerjaController::class, 'update']);
    Route::delete('program-kerja/{programKerja}', [ProgramKerjaController::class, 'destroy'])->name('program-kerja.destroy');
    Route::post('program-kerja/{programKerja}/to-pekerjaan', [ConvertToPekerjaanController::class, '__invoke'])->name('program-kerja.to-pekerjaan');

    Route::get('pekerjaan', [PekerjaanController::class, 'index'])->name('pekerjaan.index');
    Route::get('pekerjaan/archive', [PekerjaanController::class, 'archive'])->name('pekerjaan.archive');
    Route::post('pekerjaan/archive/{id}/restore', [PekerjaanController::class, 'restore'])->name('pekerjaan.restore');
    Route::delete('pekerjaan/archive/{id}/force', [PekerjaanController::class, 'forceDestroy'])->name('pekerjaan.force-destroy');
    Route::get('pekerjaan/create', [PekerjaanController::class, 'create'])->name('pekerjaan.create');
    Route::post('pekerjaan', [PekerjaanController::class, 'store'])->name('pekerjaan.store');
    Route::get('pekerjaan/{pekerjaan}', [PekerjaanController::class, 'show'])->name('pekerjaan.show');
    Route::get('pekerjaan/{pekerjaan}/edit', [PekerjaanController::class, 'edit'])->name('pekerjaan.edit');
    Route::put('pekerjaan/{pekerjaan}', [PekerjaanController::class, 'update'])->name('pekerjaan.update');
    Route::patch('pekerjaan/{pekerjaan}', [PekerjaanController::class, 'update']);
    Route::delete('pekerjaan/{pekerjaan}', [PekerjaanController::class, 'destroy'])->name('pekerjaan.destroy');
    Route::post('pekerjaan/{pekerjaan}/progress', [PekerjaanController::class, 'storeProgress'])->name('pekerjaan.progress.store');
    Route::patch('pekerjaan/{pekerjaan}/checklist/{checklist}', [PekerjaanController::class, 'toggleChecklist'])->name('pekerjaan.checklist.toggle');

    Route::get('rab', [RabController::class, 'index'])->name('rab.index');
    Route::get('rab/create', [RabController::class, 'create'])->name('rab.create');
    Route::post('rab', [RabController::class, 'store'])->name('rab.store');
    Route::get('rab/{rab}', [RabController::class, 'show'])->name('rab.show');
    Route::get('rab/{rab}/edit', [RabController::class, 'edit'])->name('rab.edit');
    Route::put('rab/{rab}', [RabController::class, 'update'])->name('rab.update');
    Route::patch('rab/{rab}', [RabController::class, 'update']);
    Route::delete('rab/{rab}', [RabController::class, 'destroy'])->name('rab.destroy');
    Route::post('rab/{rab}/submit', [RabController::class, 'submit'])->name('rab.submit');
    Route::post('rab/{rab}/approve', [RabController::class, 'approve'])->name('rab.approve');
    Route::post('rab/{rab}/revise', [RabController::class, 'revise'])->name('rab.revise');
    Route::post('rab/{rab}/reject', [RabController::class, 'reject'])->name('rab.reject');
    Route::post('rab/{rab}/items', [RabController::class, 'storeItem'])->name('rab.items.store');
    Route::put('rab-items/{detail}', [RabController::class, 'updateItem'])->name('rab.items.update');
    Route::delete('rab-items/{detail}', [RabController::class, 'destroyItem'])->name('rab.items.destroy');

    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports', ReportController::class)->name('reports.index');
});
