<?php

use App\Http\Controllers\Siprakar\PekerjaanController;
use App\Http\Controllers\Siprakar\ProgramKerja\ConvertToPekerjaanController;
use App\Http\Controllers\Siprakar\ProgramKerjaController;
use App\Http\Controllers\Siprakar\RabController;
use App\Http\Controllers\Siprakar\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tugas-saya', [PekerjaanController::class, 'tasks'])
        ->middleware('permission:pekerjaan.progress')
        ->name('pekerjaan.tasks');

    Route::get('program-kerja', [ProgramKerjaController::class, 'index'])->middleware('permission:program_kerja.view')->name('program-kerja.index');
    Route::get('program-kerja/create', [ProgramKerjaController::class, 'create'])->middleware('permission:program_kerja.create')->name('program-kerja.create');
    Route::post('program-kerja', [ProgramKerjaController::class, 'store'])->middleware('permission:program_kerja.create')->name('program-kerja.store');
    Route::get('program-kerja/{programKerja}', [ProgramKerjaController::class, 'show'])->middleware('permission:program_kerja.show')->name('program-kerja.show');
    Route::get('program-kerja/{programKerja}/edit', [ProgramKerjaController::class, 'edit'])->middleware('permission:program_kerja.edit')->name('program-kerja.edit');
    Route::put('program-kerja/{programKerja}', [ProgramKerjaController::class, 'update'])->middleware('permission:program_kerja.edit')->name('program-kerja.update');
    Route::patch('program-kerja/{programKerja}', [ProgramKerjaController::class, 'update'])->middleware('permission:program_kerja.edit');
    Route::delete('program-kerja/{programKerja}', [ProgramKerjaController::class, 'destroy'])->middleware('permission:program_kerja.delete')->name('program-kerja.destroy');
    Route::post('program-kerja/{programKerja}/to-pekerjaan', [ConvertToPekerjaanController::class, '__invoke'])->middleware('permission:pekerjaan.create')->name('program-kerja.to-pekerjaan');

    Route::get('pekerjaan', [PekerjaanController::class, 'index'])->middleware('permission:pekerjaan.view')->name('pekerjaan.index');
    Route::get('pekerjaan/archive', [PekerjaanController::class, 'archive'])->middleware('permission:pekerjaan.view')->name('pekerjaan.archive');
    Route::post('pekerjaan/archive/{id}/restore', [PekerjaanController::class, 'restore'])->middleware('permission:pekerjaan.edit')->name('pekerjaan.restore');
    Route::delete('pekerjaan/archive/{id}/force', [PekerjaanController::class, 'forceDestroy'])->middleware('permission:pekerjaan.delete')->name('pekerjaan.force-destroy');
    Route::get('pekerjaan/create', [PekerjaanController::class, 'create'])->middleware('permission:pekerjaan.create')->name('pekerjaan.create');
    Route::post('pekerjaan', [PekerjaanController::class, 'store'])->middleware('permission:pekerjaan.create')->name('pekerjaan.store');
    Route::get('pekerjaan/{pekerjaan}', [PekerjaanController::class, 'show'])->middleware('permission:pekerjaan.show')->name('pekerjaan.show');
    Route::get('pekerjaan/{pekerjaan}/edit', [PekerjaanController::class, 'edit'])->middleware('permission:pekerjaan.edit')->name('pekerjaan.edit');
    Route::put('pekerjaan/{pekerjaan}', [PekerjaanController::class, 'update'])->middleware('permission:pekerjaan.edit')->name('pekerjaan.update');
    Route::patch('pekerjaan/{pekerjaan}', [PekerjaanController::class, 'update'])->middleware('permission:pekerjaan.edit');
    Route::delete('pekerjaan/{pekerjaan}', [PekerjaanController::class, 'destroy'])->middleware('permission:pekerjaan.delete')->name('pekerjaan.destroy');
    Route::post('pekerjaan/{pekerjaan}/progress', [PekerjaanController::class, 'storeProgress'])->middleware('permission:pekerjaan.progress')->name('pekerjaan.progress.store');
    Route::patch('pekerjaan/{pekerjaan}/checklist/{checklist}', [PekerjaanController::class, 'toggleChecklist'])->middleware('permission:pekerjaan.progress')->name('pekerjaan.checklist.toggle');

    Route::get('rab', [RabController::class, 'index'])->middleware('permission:rab.view')->name('rab.index');
    Route::get('rab/create', [RabController::class, 'create'])->middleware('permission:rab.create')->name('rab.create');
    Route::post('rab', [RabController::class, 'store'])->middleware('permission:rab.create')->name('rab.store');
    Route::get('rab/{rab}', [RabController::class, 'show'])->middleware('permission:rab.view')->name('rab.show');
    Route::get('rab/{rab}/edit', [RabController::class, 'edit'])->middleware('permission:rab.edit')->name('rab.edit');
    Route::put('rab/{rab}', [RabController::class, 'update'])->middleware('permission:rab.edit')->name('rab.update');
    Route::patch('rab/{rab}', [RabController::class, 'update'])->middleware('permission:rab.edit');
    Route::delete('rab/{rab}', [RabController::class, 'destroy'])->middleware('permission:rab.delete')->name('rab.destroy');
    Route::post('rab/{rab}/submit', [RabController::class, 'submit'])->middleware('permission:rab.edit')->name('rab.submit');
    Route::post('rab/{rab}/approve', [RabController::class, 'approve'])->middleware('permission:rab.edit')->name('rab.approve');
    Route::post('rab/{rab}/revise', [RabController::class, 'revise'])->middleware('permission:rab.edit')->name('rab.revise');
    Route::post('rab/{rab}/reject', [RabController::class, 'reject'])->middleware('permission:rab.edit')->name('rab.reject');
    Route::post('rab/{rab}/items', [RabController::class, 'storeItem'])->middleware('permission:rab.edit')->name('rab.items.store');
    Route::put('rab-items/{detail}', [RabController::class, 'updateItem'])->middleware('permission:rab.edit')->name('rab.items.update');
    Route::delete('rab-items/{detail}', [RabController::class, 'destroyItem'])->middleware('permission:rab.edit')->name('rab.items.destroy');

    Route::get('reports/export', [ReportController::class, 'export'])->middleware('permission:reports.view')->name('reports.export');
    Route::get('reports', ReportController::class)->middleware('permission:reports.view')->name('reports.index');
});
