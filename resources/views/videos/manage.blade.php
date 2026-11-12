{{-- Films in a set (Videographer plan): straight-to-bucket uploader and the list. --}}
@php
    $owner = auth()->user();
    $limits = $owner->videoLimits();
    $videos = $set->videos()->get();
    $mb = fn (int $b) => $b >= 1073741824 ? round($b / 1073741824, 1).' GB' : max(1, round($b / 1048576)).' MB';
@endphp
<x-ui.card title="Films" :description="$limits ? 'MP4 or MOV, H.264 or H.265, up to '.$mb($limits['max_file']).' each. A poster frame is taken automatically.' : null" id="films">
    @if(! $limits)
        <p class="text-sm text-gray-600 dark:text-gray-400">Add films and reels to your galleries on the Videographer plan.</p>
        <div class="mt-3"><x-ui.button size="sm" :href="route('pricing')">See plans</x-ui.button></div>
    @else
        <div data-video-uploader data-set="{{ $set->id }}"
             data-start="{{ route('videos.start') }}" data-base="{{ url('/videos') }}">
            <label for="video-file" class="block text-sm font-medium text-gray-800 dark:text-gray-200">Upload films</label>
            <input id="video-file" type="file" multiple accept=".mp4,.m4v,.mov,video/mp4,video/quicktime" class="mt-1 block text-sm" aria-describedby="video-help">
            <p id="video-help" class="mt-1 text-xs text-gray-500 dark:text-gray-400">Uploads go straight to storage; keep this page open until they finish. {{ $mb((int) $owner->videos()->sum('size')) }} of {{ $mb($limits['max_total']) }} used.</p>
            <ul data-video-progress role="list" class="mt-3 space-y-2" aria-live="polite"></ul>
        </div>

        @if($videos->isNotEmpty())
            <ul role="list" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($videos as $video)
                    <li class="overflow-hidden rounded-lg ring-1 ring-gray-200 dark:ring-gray-700">
                        <div class="relative aspect-video bg-gray-900">
                            @if($video->isReady() && $video->posterUrl())
                                <img src="{{ $video->posterUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                                @if($video->durationLabel())<span class="absolute right-2 bottom-2 rounded bg-black/70 px-1.5 py-0.5 text-xs font-medium text-white">{{ $video->durationLabel() }}</span>@endif
                            @else
                                <div class="flex size-full items-center justify-center p-4 text-center text-sm text-gray-200">
                                    {{ match ($video->status) { 'uploading' => 'Uploading…', 'processing' => 'Preparing…', default => $video->processing_error ?: 'Couldn\'t be prepared.' } }}
                                </div>
                            @endif
                        </div>
                        <div class="space-y-2 p-3">
                            <form method="POST" action="{{ route('videos.update', $video) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                <label for="video-title-{{ $video->id }}" class="sr-only">Title of {{ $video->displayTitle() }}</label>
                                <input id="video-title-{{ $video->id }}" name="title" value="{{ $video->title }}" placeholder="{{ $video->displayTitle() }}" maxlength="120"
                                       class="min-w-0 flex-1 rounded-control border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                                <x-ui.button type="submit" size="sm" variant="secondary">Save</x-ui.button>
                            </form>
                            <form method="POST" action="{{ route('videos.destroy', $video) }}" data-confirm="This film is deleted for good." data-confirm-title="Delete {{ $video->displayTitle() }}?" data-confirm-button="Delete" data-confirm-danger>
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" size="sm" variant="ghost">Delete<span class="sr-only"> {{ $video->displayTitle() }}</span></x-ui.button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($videos->contains(fn ($v) => $v->status === 'processing'))
                <div x-data x-init="setTimeout(() => location.reload(), 10000)"></div>
            @endif
        @endif
    @endif
</x-ui.card>

@if($limits)
<script>
(function () {
    var root = document.querySelector('[data-video-uploader]');
    if (!root) return;
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var list = root.querySelector('[data-video-progress]');
    var post = function (url, body) {
        return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body || {}) })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (d) {
                if (!r.ok) throw new Error((d.errors && Object.values(d.errors)[0][0]) || d.message || 'Upload failed.');
                return d;
            }); });
    };
    // One part straight to the bucket; resolves with its ETag.
    var putPart = function (url, blob, onProgress) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('PUT', url);
            xhr.upload.onprogress = function (e) { onProgress(e.loaded); };
            xhr.onload = function () {
                var etag = xhr.getResponseHeader('ETag');
                if (xhr.status >= 200 && xhr.status < 300 && etag) resolve(etag);
                else reject(new Error(etag ? 'Upload failed (' + xhr.status + ').' : 'Storage didn\'t return the part\'s ETag (check the bucket\'s CORS settings).'));
            };
            xhr.onerror = function () { reject(new Error('Network error while uploading.')); };
            xhr.send(blob);
        });
    };
    async function upload(file) {
        var row = document.createElement('li');
        row.className = 'text-sm';
        row.innerHTML = '<div class="flex justify-between gap-3"><span class="truncate"></span><span data-pct>0%</span></div><div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"><div data-bar class="h-full bg-brand-600" style="width:0%"></div></div>';
        row.querySelector('span').textContent = file.name;
        list.appendChild(row);
        var setPct = function (p) { row.querySelector('[data-pct]').textContent = p + '%'; row.querySelector('[data-bar]').style.width = p + '%'; };
        var video;
        try {
            video = await post(root.dataset.start, { set_id: root.dataset.set, filename: file.name, size: file.size, type: file.type });
            var size = video.part_size, total = video.parts, done = {}, etags = [];
            var report = function () { var sent = Object.values(done).reduce(function (a, b) { return a + b; }, 0); setPct(Math.min(99, Math.floor(sent / file.size * 100))); };
            var queue = Array.from({ length: total }, function (_, i) { return i + 1; });
            var worker = async function () {
                while (queue.length) {
                    var batch = queue.splice(0, 1);
                    var urls = (await post(root.dataset.base + '/' + video.id + '/parts', { part_numbers: batch })).urls;
                    for (var n of batch) {
                        var blob = file.slice((n - 1) * size, Math.min(n * size, file.size));
                        var etag = await putPart(urls[n], blob, function (loaded) { done[n] = loaded; report(); });
                        done[n] = blob.size; report();
                        etags.push({ part_number: n, etag: etag });
                    }
                }
            };
            await Promise.all([worker(), worker(), worker()]); // three parts at a time
            await post(root.dataset.base + '/' + video.id + '/complete', { parts: etags });
            setPct(100);
            row.querySelector('[data-pct]').textContent = 'Uploaded, preparing…';
        } catch (e) {
            row.querySelector('[data-pct]').textContent = e.message;
            row.querySelector('[data-pct]').className = 'text-red-700 dark:text-red-400';
            if (video && video.id) post(root.dataset.base + '/' + video.id + '/abort').catch(function () {});
            throw e;
        }
    }
    root.querySelector('input[type=file]').addEventListener('change', async function (e) {
        var files = Array.from(e.target.files);
        e.target.value = '';
        var ok = true;
        for (var f of files) { try { await upload(f); } catch (err) { ok = false; } }
        if (ok) setTimeout(function () { location.reload(); }, 1500);
    });
})();
</script>
@endif
