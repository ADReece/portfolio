<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-10 sm:px-6 lg:px-8">
        <x-ui.page-header title="UI components" description="Reference for the component kit in resources/views/components/ui. Local environment only." back="{{ route('dashboard') }}" back-label="Dashboard">
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="eye">Secondary</x-ui.button>
                <x-ui.button icon="plus">Primary action</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card title="Buttons" description="primary, secondary, danger, danger-outline, ghost · sm, md, lg">
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button>Primary</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="danger" icon="trash">Danger</x-ui.button>
                <x-ui.button variant="danger-outline">Danger outline</x-ui.button>
                <x-ui.button variant="ghost" icon="cog-6-tooth">Ghost</x-ui.button>
                <x-ui.button size="sm">Small</x-ui.button>
                <x-ui.button size="lg" icon-trailing="arrow-right">Large</x-ui.button>
                <x-ui.button disabled>Disabled</x-ui.button>
                <x-ui.button href="#" variant="secondary" icon="arrow-top-right-on-square">Link</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="Form fields">
            <form class="grid gap-5 sm:grid-cols-2" onsubmit="event.preventDefault()">
                <x-ui.input name="demo_name" label="Collection name" placeholder="Smith wedding" help="Clients see this on the gallery." />
                <x-ui.input name="demo_email" type="email" label="Client email" optional />
                <x-ui.select name="demo_status" label="Status" :options="['Draft' => 'Draft', 'Published' => 'Published']" selected="Draft" />
                <x-ui.textarea name="demo_message" label="Message" optional />
                <x-ui.checkbox name="demo_private" label="Private gallery" description="Clients need the password or a personal link." />
                <x-ui.checkbox name="demo_watermark" label="Watermark photos" checked />
            </form>
        </x-ui.card>

        <x-ui.card title="Badges and alerts">
            <div class="space-y-4">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge tone="green" icon="check">Published</x-ui.badge>
                    <x-ui.badge>Draft</x-ui.badge>
                    <x-ui.badge tone="brand" icon="lock-closed">Private</x-ui.badge>
                    <x-ui.badge tone="amber">Watermarked</x-ui.badge>
                    <x-ui.badge tone="red">Failed</x-ui.badge>
                    <x-ui.badge tone="blue">Hidden from portfolio</x-ui.badge>
                </div>
                <x-ui.alert title="Clients can't see this collection yet">Publish it to get a link you can share. <a href="#">Learn more</a></x-ui.alert>
                <x-ui.alert tone="amber" title="You've used 95% of your photo storage">Upgrade to keep uploading.</x-ui.alert>
                <x-ui.alert tone="red">That password isn't right.</x-ui.alert>
            </div>
        </x-ui.card>

        <x-ui.card :padding="false">
            <x-ui.empty-state icon="photo" title="No photos in this set yet" description="Upload photos to start sharing them with your client.">
                <x-ui.button icon="arrow-up-tray">Upload photos</x-ui.button>
            </x-ui.empty-state>
        </x-ui.card>

        <x-ui.card title="Dialogs">
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="danger" x-data x-on:click="confirmAction({ title: 'Delete 3 photos?', message: 'This can’t be undone.', confirmText: 'Delete', isDanger: true })">Confirm (danger)</x-ui.button>
                <x-ui.button variant="secondary" x-data x-on:click="promptUser({ title: 'Email the download link', message: 'We’ll send a link that works for 24 hours.', inputType: 'email', placeholder: 'you@example.com', confirmText: 'Send link' })">Prompt</x-ui.button>
                <form data-confirm="This is the data-confirm form helper." data-confirm-title="Submit this form?" onsubmit="event.preventDefault(); showSuccessToast('Submitted')">
                    <x-ui.button type="submit" variant="secondary">data-confirm form</x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
