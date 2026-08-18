<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class BookingsExport implements FromView, ShouldAutoSize, WithTitle
{
    protected $bookings;
    protected array $summary;

    public function __construct($bookings, array $summary)
    {
        $this->bookings = $bookings;
        $this->summary = $summary;
    }

    public function view(): View
    {
        return view('booking.exports.excel', [
            'bookings' => $this->bookings,
            'summary' => $this->summary,
        ]);
    }

    public function title(): string
    {
        return 'Orders';
    }
}
