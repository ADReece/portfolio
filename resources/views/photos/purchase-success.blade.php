<x-profile-layout :user="$photographer">
    @include('photos.partials.order-result', [
        'heading' => 'Thank you, your order is in',
        'body' => "Your payment went through and your print is being prepared. We'll contact you at {$order->customer_email} if we need anything.",
        'tone' => 'success',
    ])
</x-profile-layout>
