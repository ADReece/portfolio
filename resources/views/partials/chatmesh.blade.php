{{--
    ChatMesh support chat for Portfol.io's own site (marketing, legal, sign-in
    and pricing pages). Never include it on photographers' public portfolios,
    galleries or client download pages: those belong to the photographer.
    Renders nothing until CHATMESH_KEY is set.
--}}
@if(config('services.chatmesh.key'))
    <script src="{{ rtrim(config('services.chatmesh.url'), '/') }}/widget.js"
            data-chatmesh-key="{{ config('services.chatmesh.key') }}"
            data-color="#4f46e5"
            async></script>
    @auth
        <script>
            (window.ChatMesh ? Promise.resolve() : new Promise(function (resolve) {
                var t = setInterval(function () { if (window.ChatMesh) { clearInterval(t); resolve(); } }, 200);
                setTimeout(function () { clearInterval(t); }, 15000);
            })).then(function () {
                return window.ChatMesh.ready;
            }).then(function () {
                window.ChatMesh.identify(@json(['name' => auth()->user()->name, 'email' => auth()->user()->email]));
            }).catch(function () {});
        </script>
    @endauth
@endif
