<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/* Private PDFs are streamed with inline headers and byte ranges for the in-page viewer. */
final class PrivatePdfResponse
{
    public function make(string $path, string $filename, Request $request): StreamedResponse
    {
        $size = filesize($path);
        abort_if($size === false, 404);

        $start = 0;
        $end = max(0, $size - 1);
        $status = 200;
        $range = $request->header('Range');

        if ($range !== null && preg_match('/\Abytes=(\d*)-(\d*)\z/D', trim($range), $matches)
            && ($matches[1] !== '' || $matches[2] !== '')) {
            if ($size === 0) {
                return $this->unsatisfiable($size);
            }

            if ($matches[1] === '' && $matches[2] !== '') {
                $suffixLength = (int) $matches[2];
                if ($suffixLength < 1) return $this->unsatisfiable($size);
                $start = max(0, $size - $suffixLength);
            } else {
                $start = (int) $matches[1];
                if ($matches[2] !== '') $end = min($end, (int) $matches[2]);
            }

            if ($start >= $size || $end < $start) return $this->unsatisfiable($size);
            $status = 206;
        }

        $length = $end - $start + 1;
        $headers = [
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => HeaderUtils::makeDisposition('inline', $filename),
            'Content-Length' => (string) $length,
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex',
        ];
        if ($status === 206) $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";

        return new StreamedResponse(static function () use ($path, $start, $length): void {
            $handle = @fopen($path, 'rb');
            if (! $handle) return;
            try {
                fseek($handle, $start);
                $remaining = $length;
                while ($remaining > 0 && ! feof($handle)) {
                    $chunk = fread($handle, min(8192, $remaining));
                    if ($chunk === false || $chunk === '') break;
                    echo $chunk;
                    $remaining -= strlen($chunk);
                }
            } finally {
                fclose($handle);
            }
        }, $status, $headers);
    }

    private function unsatisfiable(int $size): StreamedResponse
    {
        return new StreamedResponse(static function (): void {}, 416, [
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store',
            'Content-Range' => "bytes */{$size}",
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
