<x-mail::message>
{{-- Saludo --}}
@if (! empty($greeting))
# {{ $greeting }}
@elseif ($level === 'error')
# Algo salió mal
@else
# ¡Hola!
@endif

{{-- Texto antes del boton --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Boton --}}
@isset($actionText)
<x-mail::button :url="$actionUrl" :color="match ($level) { 'success', 'error' => $level, default => 'primary' }">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Texto despues del boton --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Despedida --}}
@if (! empty($salutation))
{{ $salutation }}
@else
Saludos,<br>
El equipo de {{ config('app.name') }}
@endif

{{-- Enlace por si el boton no funciona --}}
@isset($actionText)
<x-slot:subcopy>
Si el botón "{{ $actionText }}" no funciona, copia y pega este enlace en tu navegador: <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
