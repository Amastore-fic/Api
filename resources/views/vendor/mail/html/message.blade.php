<x-mail::layout>
{{-- Encabezado: enlaza a la tienda, no a la API --}}
<x-slot:header>
<x-mail::header :url="config('app.frontend_url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Contenido --}}
{!! $slot !!}

{{-- Texto bajo el contenido --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Pie --}}
<x-slot:footer>
<x-mail::footer>
Este es un correo automático, por favor no lo respondas.

© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
