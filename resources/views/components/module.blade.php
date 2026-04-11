@if (module($attributes->get('name'))->active())
    {{ $slot }}
@else
    <span {{ $attributes->except('name', 'title')->merge(['class' => 'text-center py-10 px-4']) }}>
        The {{ $attributes->get('title', $attributes->get('name')) }} module was not found
    </span>
@endif
