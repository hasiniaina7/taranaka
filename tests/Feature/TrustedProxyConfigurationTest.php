<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('forwarded protocol is trusted when generating URLs', function (): void {
    Route::get('/_test/trusted-proxy', fn (): array => [
        'secure' => request()->secure(),
        'url'    => url()->current(),
    ]);

    test()->withHeaders([
        'X-Forwarded-Proto' => 'https',
    ])->get('/_test/trusted-proxy')
        ->assertOk()
        ->assertJson([
            'secure' => true,
        ])
        ->assertJsonPath('url', 'https://localhost/_test/trusted-proxy');
});
