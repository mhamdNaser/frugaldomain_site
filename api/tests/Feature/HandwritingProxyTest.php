<?php

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

test('handwriting recognition proxies the image with the server-only key', function () {
    config([
        'cache.default' => 'array',
        'handwriting.url' => 'https://handwriting.test',
        'handwriting.key' => 'test-handwriting-key',
        'handwriting.space_token' => 'hf-test-token',
    ]);

    Http::fake([
        'https://handwriting.test/api/handwriting/health' => Http::response([
            'status' => 'ok',
        ]),
        'https://handwriting.test/api/handwriting/recognize' => Http::response([
            'text' => 'مرحبا',
            'model' => 'test-model',
        ]),
    ]);

    $response = $this->post('/api/handwriting/recognize', [
        'image' => UploadedFile::fake()->create('board.png', 10, 'image/png'),
    ]);

    $response->assertOk()
        ->assertJson([
            'text' => 'مرحبا',
            'model' => 'test-model',
        ]);

    Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://handwriting.test/api/handwriting/recognize'
        && $request->hasHeader('X-Handwriting-Key', 'test-handwriting-key')
        && $request->hasHeader('Authorization', 'Bearer hf-test-token'));
    Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://handwriting.test/api/handwriting/health'
        && $request->hasHeader('Authorization', 'Bearer hf-test-token'));
    Http::assertSentCount(2);
});

test('handwriting recognition fails explicitly when the service is not configured', function () {
    config([
        'handwriting.url' => '',
        'handwriting.key' => '',
        'handwriting.space_token' => '',
    ]);

    $this->post('/api/handwriting/recognize', [
        'image' => UploadedFile::fake()->create('board.png', 10, 'image/png'),
    ])->assertServiceUnavailable()
        ->assertJsonPath('message', 'Handwriting recognition is not configured on this site yet.');
});
