<x-app-layout title="New plan">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header title="New plan" :back="route('admin.plans.index')" back-label="All plans" />

        <form action="{{ route('admin.plans.store') }}" method="POST" class="space-y-6">
            @csrf

            @include('admin.plans.form', ['plan' => null])

            <div class="flex justify-end gap-3">
                <x-ui.button variant="secondary" :href="route('admin.plans.index')">Cancel</x-ui.button>
                <x-ui.button type="submit">Create plan</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
