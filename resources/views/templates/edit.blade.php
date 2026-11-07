<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Template: {{ $template->name }}
            </h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('templates.test', $template) }}" class="text-sm text-green-600 dark:text-green-400 hover:underline">Test with photo</a>
                <a href="{{ route('fonts.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Manage Fonts</a>
                <a href="{{ route('templates.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">← Back to Templates</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-(--breakpoint-xl) mx-auto sm:px-6 lg:px-8">

            @if($errors->any())
                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-sm">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('templates.update', $template) }}" enctype="multipart/form-data"
                  x-data="templateEditor({{ json_encode($template->fields) }}, {{ json_encode($fonts) }})">
                @csrf
                @method('PUT')
                @include('templates._builder', [
                    'submitLabel' => 'Save Changes',
                    'cancelRoute' => route('templates.index'),
                ])
            </form>
        </div>
    </div>
</x-app-layout>
