<?php
namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
class UserView implements FromView,WithBatchInserts, WithChunkReading
{
    public function __construct($data){

        $this->data = $data;
    }
    public function view(): View {

        return view('customer.export', [
            'data' => $this->data
        ]);
    }
    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
