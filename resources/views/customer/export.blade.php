<table class="table table-hover">
    <thead>
    <tr>
        <th>#</th>
        <th>Name</th>
        <th>phone</th>

    </tr>
    </thead>
    <tbody>
    @forelse($data as $key=>$item)
        <tr>
            <td>{{$key+1}}</td>
            <td>{{@$item->display_name}}</td>
            <td>"{{@$item->contact_number}}"</td>
        </tr>
    @empty
        <tr>
            <td colspan="200">
                <h5>{{ __('dashboard.No_Data') }}</h5>
            </td>
        </tr>
    @endforelse

    </tbody>
</table>


