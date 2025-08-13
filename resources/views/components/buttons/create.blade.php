@props([
    "route" => "#",
    "title" => "Create",
    "class" => "btn btn-primary",
    "icon" => "fas fa-plus"
])

<a href="{{ $route }}" class="{{ $class }}" title="{{ $title }}">
    <i class="{{ $icon }}"></i> {{ $title }}
</a>