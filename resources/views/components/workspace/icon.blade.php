@props(['name'])

{{-- Keep existing workspace calls routed through the single shared SVG map. --}}
<x-icon :name="$name" {{ $attributes->class(['workspace-icon']) }} />
