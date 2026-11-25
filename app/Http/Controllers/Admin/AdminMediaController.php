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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Media sits on a private disk; admins read a file through this route.
     */
    public function show(MediaLibrary $mediaLibrary): StreamedResponse
    {
        $disk = Storage::disk($mediaLibrary->disk);

        abort_unless($disk->exists($mediaLibrary->path), 404);

        return $disk->response($mediaLibrary->path);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            // `image` checks the content, not the name, and does not accept SVG.
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
            'folder' => ['nullable', 'string', 'max:100'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'uploads');
        $disk = config('filesystems.uploads');
        // The extension comes from the content, never from the uploaded name.
        $filename = Str::uuid().'.'.$file->extension();
        $path = $file->storeAs($folder, $filename, $disk);

        $media = MediaLibrary::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            // Not used: MediaLibrary::url() builds the address from the route.
            'url' => '',
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

        $before = $mediaLibrary->only(['alt', 'folder']);
        $mediaLibrary->update($validated);
        $this->audit->logModel('media.updated', $mediaLibrary, $before, $mediaLibrary->only(['alt', 'folder']));

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
