<x-profile-layout :user="$photographer">
    @include('photos.partials.order-result', [
        'heading' => 'Payment cancelled',
        'body' => "You haven't been charged. You can go back and try again whenever you're ready.",
        'tone' => 'neutral',
    ])
</x-profile-layout>
