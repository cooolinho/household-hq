@php
    use Illuminate\Support\Facades\Storage;

    // Use authenticated view route instead of direct storage URL
    $fileUrl = route('admin.documents.view', $document);
    $fileExtension = null;
    $mimeType = $document->mime_type;
    $canPreview = false;
    $isImage = false;
    $isPdf = false;
    $isText = false;
    $textContent = null;

    if ($document && $document->path) {
        if ($document->filename) {
            $fileExtension = strtolower(pathinfo($document->filename, PATHINFO_EXTENSION));
        }

        // Check if file can be previewed
        $previewableMimes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'text/plain',
            'text/html',
            'text/csv',
        ];

        $previewableExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'txt', 'html', 'csv'];

        $canPreview = in_array($mimeType, $previewableMimes) || in_array($fileExtension, $previewableExtensions);

        $isImage = str_starts_with($mimeType ?? '', 'image/') || in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        $isPdf = $mimeType === 'application/pdf' || $fileExtension === 'pdf';
        $isText = str_starts_with($mimeType ?? '', 'text/') || in_array($fileExtension, ['txt', 'html', 'csv']);

        if ($isText) {
            try {
                $disk = Storage::disk(\App\Models\Document::STORAGE_DISK);
                if ($disk->exists($document->path)) {
                    $textContent = $disk->get($document->path);
                }
            } catch (\Exception $e) {
                // Fallback
            }
        }
    }
@endphp

<div class="space-y-4">
    {{-- Document Info --}}
    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $document->filename ?? 'Dokument' }}
            </h3>
            @if($document->description)
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $document->description }}</p>
            @endif
            <div class="flex gap-2 mt-2 text-xs">
                @if($document->mime_type)
                    <span class="px-2 py-1 bg-white dark:bg-gray-700 rounded text-gray-700 dark:text-gray-300">
                        {{ $document->mime_type }}
                    </span>
                @endif
                @if($document->file_size)
                    <span class="px-2 py-1 bg-white dark:bg-gray-700 rounded text-gray-700 dark:text-gray-300">
                        {{ number_format($document->file_size / 1024, 2) }} KB
                    </span>
                @endif
                @if($fileExtension)
                    <span class="px-2 py-1 bg-white dark:bg-gray-700 rounded text-gray-700 dark:text-gray-300 uppercase">
                        .{{ $fileExtension }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Download Button --}}
        <a
                href="{{ route('admin.documents.download', $document->id) }}"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition"
        >
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Download
        </a>
    </div>

    {{-- Preview Area --}}
    @if($canPreview && $fileUrl)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
            @if($isImage)
                {{-- Image Preview --}}
                <div class="flex items-center justify-center p-6 bg-gray-50 dark:bg-gray-800">
                    <img
                            src="{{ $fileUrl }}"
                            alt="{{ $document->filename }}"
                            class="max-w-full max-h-[70vh] object-contain rounded"
                    />
                </div>

            @elseif($isPdf)
                {{-- PDF Preview --}}
                <div class="w-full" style="height: 70vh; min-height: 600px;">
                    <iframe
                            src="{{ $fileUrl }}#toolbar=1&navpanes=1&scrollbar=1"
                            class="w-full h-full border-0"
                            title="{{ $document->filename }}"
                    ></iframe>
                </div>

            @elseif($isText && $textContent)
                {{-- Text Preview --}}
                <div class="p-6 overflow-auto" style="max-height: 70vh;">
                    <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-mono">{{ $textContent }}</pre>
                </div>
            @endif
        </div>
    @else
        {{-- No Preview Available --}}
        <div class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
            <svg class="w-20 h-20 text-gray-400 dark:text-gray-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <p class="text-lg text-gray-600 dark:text-gray-400 mb-4 text-center">
                Vorschau für diesen Dateityp nicht verfügbar
            </p>
            <a
                    href="{{ route('admin.documents.download', $document->id) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition"
            >
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Datei herunterladen
            </a>
        </div>
    @endif
</div>
