<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class OrdersExport implements FromView, ShouldAutoSize, WithTitle
{
    protected $orders;
    protected array $summary;

    public function __construct($orders, array $summary)
    {
        $this->orders = $orders;
        $this->summary = $summary;
    }

    public function view(): View
    {
        return view('order.exports.excel', [
            'orders' => $this->orders,
            'summary' => $this->summary,
        ]);
    }

    public function title(): string
    {
        return 'Orders';
    }
}
