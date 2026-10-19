<?php

namespace Tests\Unit;

use App\Support\PrivatePdfResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

/* Verify private PDF delivery supports bounded byte ranges and safe response headers. */
class PrivatePdfResponseTest extends TestCase
{
    public function test_it_streams_requested_byte_ranges_with_private_inline_headers(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'private-pdf-');
        file_put_contents($path, '%PDF-0123456789');

        try {
            $request = Request::create('/documents/1/view', 'GET', server: ['HTTP_RANGE' => 'bytes=5-9']);
            $response = app(PrivatePdfResponse::class)->make($path, 'sample.pdf', $request);
            ob_start();
            $response->sendContent();
            $content = ob_get_clean();

            $this->assertSame(206, $response->getStatusCode());
            $this->assertSame('bytes 5-9/15', $response->headers->get('Content-Range'));
            $this->assertSame('5', $response->headers->get('Content-Length'));
            $this->assertSame('inline; filename=sample.pdf', $response->headers->get('Content-Disposition'));
            $this->assertSame('no-store, private', $response->headers->get('Cache-Control'));
            $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
            $this->assertSame('01234', $content);
        } finally {
            @unlink($path);
        }
    }

    public function test_it_marks_out_of_bounds_ranges_unsatisfiable(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'private-pdf-');
        file_put_contents($path, '%PDF-0123456789');

        try {
            $request = Request::create('/documents/1/view', 'GET', server: ['HTTP_RANGE' => 'bytes=99-']);
            $response = app(PrivatePdfResponse::class)->make($path, 'sample.pdf', $request);

            $this->assertSame(416, $response->getStatusCode());
            $this->assertSame('bytes */15', $response->headers->get('Content-Range'));
        } finally {
            @unlink($path);
        }
    }
}
