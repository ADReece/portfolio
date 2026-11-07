<x-app-layout title="Edit plan">
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-ui.page-header :title="'Edit '.$plan->name" :back="route('admin.plans.index')" back-label="All plans" />

        <form action="{{ route('admin.plans.update', $plan) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            @include('admin.plans.form', ['plan' => $plan])

            <div class="flex justify-end gap-3">
                <x-ui.button variant="secondary" :href="route('admin.plans.index')">Cancel</x-ui.button>
                <x-ui.button type="submit">Save changes</x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
