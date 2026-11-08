<?php

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

test('document conversion retries a waking service and forwards the shared key', function () {
    config([
        'cache.default' => 'array',
        'convert.url' => 'https://converter.test',
        'convert.key' => 'test-convert-key',
    ]);

    Http::fake([
        'https://converter.test/health' => Http::sequence()
            ->push('Space is starting', 503)
            ->push(['ready' => true], 200),
        'https://converter.test/convert/document' => Http::response(
            '%PDF-1.7 test',
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="report.pdf"',
            ],
        ),
    ]);

    $response = $this->post('/api/convert/document', [
        'file' => UploadedFile::fake()->create('report.docx', 1),
        'target' => 'pdf',
    ]);

    $response->assertOk();
    expect($response->streamedContent())->toBe('%PDF-1.7 test');

    Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://converter.test/health');
    Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://converter.test/convert/document'
        && $request->hasHeader('X-Convert-Key', 'test-convert-key'));
    Http::assertSentCount(3);
});

test('opening a converter page wakes the service once a minute', function () {
    config([
        'cache.default' => 'array',
        'convert.url' => 'https://converter.test',
        'convert.key' => 'test-convert-key',
    ]);

    Http::fake([
        'https://converter.test/health' => Http::response('Space is starting', 503),
    ]);

    $this->post('/api/convert/wake')->assertStatus(202)->assertJson(['ready' => false]);
    $this->post('/api/convert/wake')->assertStatus(202)->assertJson(['ready' => null]);

    Http::assertSentCount(1);
});
