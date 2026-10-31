<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\Games\GameOperationsService;
use App\Services\Games\GameScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class GameOperationsController extends Controller
{
    public function __construct(
        protected GameOperationsService $operationsService,
        protected GameScannerService $scannerService,
    ) {}

    /**
     * Rescan physical game assets and refresh technical metadata.
     */
    public function rescan(Game $game): RedirectResponse
    {
        $res = $this->operationsService->rescan($game);

        if (! $res['success']) {
            return back()->with('error', $res['message']);
        }

        return back()->with('success', $res['message']);
    }

    /**
     * Run validation rules against game files on disk.
     */
    public function revalidate(Game $game): JsonResponse
    {
        $report = $this->operationsService->revalidate($game);

        return response()->json($report);
    }

    /**
     * Regenerate game thumbnail image from package.
     */
    public function regenerateThumbnail(Game $game): RedirectResponse
    {
        $res = $this->operationsService->regenerateThumbnail($game);

        if (! $res['success']) {
            return back()->with('error', $res['message']);
        }

        return back()->with('success', $res['message']);
    }

    /**
     * Refresh title/description/instructions from entry file HTML.
     */
    public function refreshMetadata(Game $game): RedirectResponse
    {
        $res = $this->operationsService->refreshMetadata($game);

        if (! $res['success']) {
            return back()->with('error', $res['message']);
        }

        return back()->with('success', $res['message']);
    }

    /**
     * Safely replace game package with updated files from staging.
     */
    public function replacePackage(Request $request, Game $game): RedirectResponse
    {
        $validated = $request->validate([
            'staging_folder' => ['required', 'string', 'max:255'],
        ]);

        $res = $this->operationsService->replacePackage($game, $validated['staging_folder']);

        if (! $res['success']) {
            return back()->with('error', $res['message']);
        }

        return back()->with('success', $res['message']);
    }

    /**
     * Securely stream game assets for authenticated admin preview.
     */
    public function serveStagedAsset(Request $request, Game $game, ?string $path = null): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isStaff() || ! $user->isActive()) {
            abort(403, 'Unauthorized staff access required.');
        }

        $dir = $this->operationsService->resolveGameDirectory($game);
        if ($dir === null || ! is_dir($dir)) {
            abort(404, 'Game package directory not found on disk.');
        }

        $relativePath = $path ?: ($game->entry_file ?: 'index.html');

        // Iterative URL decode to catch single, double, or multi-encoded sequences
        $decoded = $relativePath;
        $prev = '';
        while ($decoded !== $prev) {
            $prev = $decoded;
            $decoded = rawurldecode($decoded);
        }

        // Null-byte inspection
        if (str_contains($relativePath, "\0") || str_contains($decoded, "\0")) {
            abort(403, 'Null byte traversal detected.');
        }

        // Explicit traversal or backslash detection
        if (
            str_contains($relativePath, '..') ||
            str_contains($decoded, '..') ||
            str_contains($relativePath, '\\') ||
            str_contains($decoded, '\\')
        ) {
            abort(403, 'Path traversal detected.');
        }

        // Absolute path and Windows drive prefix detection
        if (
            str_starts_with($relativePath, '/') ||
            str_starts_with($decoded, '/') ||
            preg_match('/^[a-zA-Z]:/', $relativePath) ||
            preg_match('/^[a-zA-Z]:/', $decoded)
        ) {
            abort(403, 'Absolute path traversal detected.');
        }

        $realDir = realpath($dir);
        if ($realDir === false || ! is_dir($realDir)) {
            abort(404, 'Game package directory not found on disk.');
        }

        // Canonical confinement check
        $targetPath = $realDir.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $candidateFile = realpath($targetPath);

        if ($candidateFile === false) {
            abort(404, "Asset '{$relativePath}' not found.");
        }

        // Must be a regular file, rejecting directory traversal
        if (is_dir($candidateFile) || ! is_file($candidateFile)) {
            abort(404, 'Directory requests are forbidden.');
        }

        $normalizedCandidate = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $candidateFile), DIRECTORY_SEPARATOR);
        $normalizedRoot = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $realDir), DIRECTORY_SEPARATOR);

        if (! str_starts_with($normalizedCandidate, $normalizedRoot.DIRECTORY_SEPARATOR)) {
            abort(403, 'Path traversal or symlink escape detected.');
        }

        $mimeType = File::mimeType($candidateFile);
        $ext = strtolower(pathinfo($candidateFile, PATHINFO_EXTENSION));

        // Refine common web game MIME types
        $mimeMap = [
            'html' => 'text/html; charset=UTF-8',
            'htm' => 'text/html; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'mjs' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'wasm' => 'application/wasm',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'wav' => 'audio/wav',
        ];

        if (isset($mimeMap[$ext])) {
            $mimeType = $mimeMap[$ext];
        }

        $headers = [
            'Content-Type' => $mimeType,
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            // Enforce sandbox without allow-same-origin to isolate staging game JS from admin session
            'Content-Security-Policy' => "sandbox allow-scripts allow-forms allow-pointer-lock; default-src 'self' 'unsafe-inline' data: blob:;",
            'Cache-Control' => 'no-cache, private',
        ];

        return new BinaryFileResponse($candidateFile, 200, $headers);
    }
}
