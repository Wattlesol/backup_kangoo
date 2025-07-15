
<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ __('messages.region') }}</h5>
                            <a href="{{ route('region.index') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: center;
            }
            th {
                background-color: #f4f4f4;
            }
            .off-day {
                text-align: center;
            }
        </style>
        <form class="mt-4" action="{{route('region.update',$data->id)}}" method="post" enctype="multipart/form-data">
            {{csrf_field()}}
            @method('put')
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="form-label">@lang('messages.name') <span style="color: red">*</span></label>
                        <input type="text" class="form-control" value="{{@old('name',$data->name)}}"  name="name" aria-describedby="emailHelp"
                               placeholder="@lang('messages.name')">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <h1>Weekly Schedule</h1>
                        <table>
                            <thead>
                            <tr>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Off Day</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Monday (1)</td>
                                <td><input type="time" name="start_at[1]"></td>
                                <td><input type="time" name="end_at[1]"></td>
                                <td class="off-day"><input value="1" type="checkbox" name="off[1]"></td>
                            </tr>
                            <tr>
                                <td>Tuesday (2)</td>
                                <td><input type="time" name="start_at[2]"></td>
                                <td><input type="time" name="end_at[2]"></td>
                                <td class="off-day"><input value="1" type="checkbox" name="off[2]"></td>
                            </tr>
                            <tr>
                                <td>Wednesday (3)</td>
                                <td><input type="time" name="start_at[3]"></td>
                                <td><input type="time" name="end_at[3]"></td>
                                <td class="off-day"><input value="1" type="checkbox" name="off[3]"></td>
                            </tr>
                            <tr>
                                <td>Thursday (4)</td>
                                <td><input type="time" name="start_at[4]"></td>
                                <td><input type="time" name="end_at[4]"></td>
                                <td class="off-day"><input value="1" type="checkbox" name="off[4]"></td>
                            </tr>
                            <tr>
                                <td>Friday (5)</td>
                                <td><input type="time" name="start_at[5]"></td>
                                <td><input type="time" name="end_at[5]"></td>
                                <td class="off-day"><input value="1" type="checkbox" name="off[5]"></td>
                            </tr>
                            <tr>
                                <td>Saturday (6)</td>
                                <td><input type="time" name="start_at[6]"></td>
                                <td><input type="time" name="end_at[6]"></td>
                                <td class="off-day"><input value="1" type="checkbox" name="off[6]"></td>
                            </tr>
                            <tr>
                                <td>Sunday (7)</td>
                                <td><input type="time" name="start_at[7]"></td>
                                <td><input type="time" name="end_at[7]"></td>
                                <td class="off-day"><input value="1" type="checkbox" name="off[7]"></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">{{ __('messages.edit') }}</button>
        </form>

    </div>
    </div>
</x-master-layout>
