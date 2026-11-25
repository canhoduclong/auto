<?php

use App\Http\Controllers\CompanyAssetController;
use App\Http\Controllers\CompanyAssetReportController;
use Illuminate\Support\Facades\Route;

Route::get('assets/report', [CompanyAssetReportController::class, 'index'])->name('assets.report');

Route::prefix('assets/assistant')->name('assets.ai.')->controller(\App\Http\Controllers\CompanyAssetAssistantController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/drafts/{draft}', 'show')->whereNumber('draft')->name('show');
    Route::post('/keys', 'createKey')->name('keys.create');
    Route::delete('/keys/{key}', 'revokeKey')->whereNumber('key')->name('keys.revoke');
});

Route::prefix('assets')->name('assets.')->controller(CompanyAssetController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('new');
    Route::get('/items/{asset}', 'showAsset')->whereNumber('asset')->name('items.show');
    Route::post('/items/{asset}/documents', 'storeAssetDocuments')->whereNumber('asset')->name('items.documents.store');
    Route::get('/items/{asset}/documents/{document}', 'downloadAssetDocument')->whereNumber(['asset', 'document'])->name('items.documents.download');
    Route::get('/{kind}/create', 'form')->whereIn('kind', ['assets', 'partners', 'contracts'])->name('create');
    Route::get('/{kind}/{record}/edit', 'form')->whereIn('kind', ['assets', 'partners', 'contracts'])->whereNumber('record')->name('edit');
    Route::post('/{kind}', 'save')->whereIn('kind', ['assets', 'partners', 'contracts'])->name('store');
    Route::patch('/{kind}/{record}', 'save')->whereIn('kind', ['assets', 'partners', 'contracts'])->whereNumber('record')->name('update');
    Route::get('/contracts/{contract}', 'showContract')->whereNumber('contract')->name('contracts.show');
    Route::post('/contracts/{contract}/installments', 'saveInstallment')->whereNumber('contract')->name('installments.store');
    Route::patch('/contracts/{contract}/installments/{installment}', 'saveInstallment')->whereNumber(['contract', 'installment'])->name('installments.update');
    Route::patch('/contracts/{contract}/payments/{payment}', 'storePayment')->whereNumber(['contract', 'payment'])->name('payments.update');
    Route::post('/contracts/{contract}/payments', 'storePayment')->whereNumber('contract')->name('payments.store');
    Route::post('/contracts/{contract}/documents', 'storeDocuments')->whereNumber('contract')->name('documents.store');
    Route::get('/contracts/{contract}/documents/{document}', 'downloadDocument')->whereNumber(['contract', 'document'])->name('documents.download');
});
