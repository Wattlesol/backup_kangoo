@props([
    "route" => "#",
    "title" => "Delete",
    "class" => "btn btn-sm btn-danger",
    "icon" => "fas fa-trash",
    "method" => "DELETE",
    "confirm" => true
])

<form action="{{ $route }}" method="POST" style="display: inline-block;" 
      @if($confirm) onsubmit="return confirm('Are you sure you want to delete this item?')" @endif>
    @csrf
    @method($method)
    <button type="submit" class="{{ $class }}" title="{{ $title }}">
        <i class="{{ $icon }}"></i> {{ $title }}
    </button>
</form>