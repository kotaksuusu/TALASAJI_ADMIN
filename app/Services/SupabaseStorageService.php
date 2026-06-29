<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    protected string $projectUrl;
    protected string $serviceKey;
    protected string $bucket;

    public function __construct()
    {
        $this->projectUrl = config('supabase.url');
        $this->serviceKey = config('supabase.service_key');
        $this->bucket = config('supabase.storage_bucket', 'talasaji');
    }

    public function upload(string $path, UploadedFile $file, ?string $filename = null): ?string
    {
        $filename ??= Str::random(20) . '.' . $file->getClientOriginalExtension();
        $filePath = trim($path, '/') . '/' . $filename;

        $response = Http::withToken($this->serviceKey)
            ->withHeaders(['Content-Type' => $file->getMimeType()])
            ->withBody($file->getContent(), $file->getMimeType())
            ->post("{$this->projectUrl}/storage/v1/object/{$this->bucket}/{$filePath}");

        if ($response->successful()) {
            return "{$this->projectUrl}/storage/v1/object/public/{$this->bucket}/{$filePath}";
        }

        return null;
    }

    public function uploadFromPath(string $path, string $localFilePath, ?string $filename = null): ?string
    {
        $filename ??= basename($localFilePath);
        $filePath = trim($path, '/') . '/' . $filename;

        $response = Http::withToken($this->serviceKey)
            ->attach('file', file_get_contents($localFilePath), $filename)
            ->post("{$this->projectUrl}/storage/v1/object/{$this->bucket}/{$filePath}");

        if ($response->successful()) {
            return "{$this->projectUrl}/storage/v1/object/public/{$this->bucket}/{$filePath}";
        }

        return null;
    }

    public function delete(string $publicUrl): bool
    {
        $prefix = "{$this->projectUrl}/storage/v1/object/public/{$this->bucket}/";
        if (!str_starts_with($publicUrl, $prefix)) {
            return false;
        }
        $filePath = str_replace($prefix, '', $publicUrl);

        $response = Http::withToken($this->serviceKey)
            ->delete("{$this->projectUrl}/storage/v1/object/{$this->bucket}/{$filePath}");

        return $response->successful();
    }

    public function getPublicUrl(string $filePath): string
    {
        return "{$this->projectUrl}/storage/v1/object/public/{$this->bucket}/" . trim($filePath, '/');
    }
}
