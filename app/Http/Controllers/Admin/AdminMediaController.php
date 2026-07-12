<?php

namespace App\Http\Controllers\Admin;

use App\Models\MediaLibrary;
use App\Services\AdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminMediaController extends AdminController
{
    public function __construct(private readonly AdminAuditService $audit) {}

    public function index(Request $request): Response
    {
        $query = MediaLibrary::with('uploader');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('original_filename', 'like', "%{$search}%");
        }

        if ($request->filled('folder')) {
            $query->where('folder', $request->string('folder'));
        }

        $media = $query->latest()->paginate(24)->withQueryString();
        $folders = MediaLibrary::distinct()->pluck('folder')->filter()->values();

        return Inertia::render('admin/media/Index', [
            'media' => $media,
            'folders' => $folders,
            'filters' => $request->only(['search', 'folder']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'folder' => ['nullable', 'string', 'max:100'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'uploads');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');

        $media = MediaLibrary::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'folder' => $folder,
            'alt' => $request->input('alt'),
            'uploaded_by' => $request->user()->id,
        ]);

        $this->audit->logModel('media.uploaded', $media, null, ['filename' => $media->original_filename]);

        return back()->with('success', 'File uploaded successfully.');
    }

    public function update(Request $request, MediaLibrary $mediaLibrary): RedirectResponse
    {
        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:100'],
        ]);

        $mediaLibrary->update($validated);

        return back()->with('success', 'Media updated.');
    }

    public function destroy(MediaLibrary $mediaLibrary): RedirectResponse
    {
        Storage::disk($mediaLibrary->disk)->delete($mediaLibrary->path);
        $this->audit->logModel('media.deleted', $mediaLibrary, ['filename' => $mediaLibrary->original_filename]);
        $mediaLibrary->delete();

        return back()->with('success', 'File deleted.');
    }
}
