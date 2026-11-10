<x-app-layout :title="'Edit '.$template->name">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="'Edit “'.$template->name.'”'"
                          description="Change the overlay or text fields, then try it on one of your photos."
                          :back="route('templates.index')" back-label="All templates">
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="language" :href="route('fonts.index')">Manage fonts</x-ui.button>
                <x-ui.button variant="secondary" icon="beaker" :href="route('templates.test', $template)">Try on a photo</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        @if($errors->any())
            <x-ui.alert tone="red" title="Please fix the following">
                <ul class="list-disc space-y-0.5 pl-5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('templates.update', $template) }}" enctype="multipart/form-data"
              x-data="templateEditor({{ json_encode($template->fields) }}, {{ json_encode($fonts) }})">
            @csrf
            @method('PUT')
            @include('templates._builder', [
                'submitLabel' => 'Save changes',
                'cancelRoute' => route('templates.index'),
            ])
        </form>
    </div>
</x-app-layout>
