@props([
    "route" => "#",
    "title" => "Edit",
    "class" => "btn btn-sm btn-primary",
    "icon" => "fas fa-edit"
])

<a href="{{ $route }}" class="{{ $class }}" title="{{ $title }}">
    <i class="{{ $icon }}"></i> {{ $title }}
</a>