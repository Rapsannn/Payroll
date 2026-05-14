<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class Payroll extends Component
{
    public $user_id;
    public $start_date;
    public $end_date;

    public $pegawai;
    public $total_duration;
    public $total_hours = 0;
    public $total_salary = 0;
    public $leave_pay = 0;

    // public $cek;

    public $rate_per_hour = 35000;

    public function render()
    {
        $users = User::all();

        return view('livewire.payroll', compact('users'))->layout('layouts.main');
    }

    public function calculate()
    {
        $this->validate([
            'user_id' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $this->pegawai = User::find($this->user_id);

        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = Carbon::parse($this->end_date)->endOfDay();

        /* 
        =============================
        TOTAL DETIK ATTENDANCES
        =============================
        */
        $attendances = Attendance::where('user_id', $this->user_id)
                    ->whereBetween('created_at', [$start, $end])
                    ->whereNotNull('duration')
                    ->get();

        $attendanceSeconds = $attendances->sum(function ($attendance){
            return strtotime($attendance->duration) - strtotime('00:00:00');
        });

        /* 
        =============================
        TOTAL DETIK CUTI
        =============================
        */
        $schedule = Schedule::where('user_id', $this->user_id)->first();

        $scheduleStart = Carbon::parse($schedule->shift->start_time);
        $scheduleEnd = Carbon::parse($schedule->shift->end_time);

        // total jam kerja per hari dari shift
        $scheduleSeconds = $scheduleStart->diffInSeconds($scheduleEnd);

        // ambil cuti
        $cutis = Leave::where('user_id', $this->user_id)
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('status', ['approved', 'pending'])
                ->get();

        // total hari cuti
        $totalLeaveDays = $cutis->count();

        // total detik cuti dibayar
        $leaveSeconds = $totalLeaveDays * $scheduleSeconds;
        // $this->cek = $leaveSeconds;

        // ubah detik cuto ke jam
        $leaveHours = $leaveSeconds / 3600;

        // total tambahan    cuti
        $this->leave_pay = $leaveHours * $this->rate_per_hour;

        /* 
        =============================
        HITUNG GAJI
        =============================
        */
        $totalSeconds = $attendanceSeconds + $leaveSeconds;
        
        //convert ke jam menit, detik
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        $this->total_duration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        // hitung jam dalam bentuk desimal
        $this->total_hours = $totalSeconds / 3600;

        // hitung total gaji
        $this->total_salary = $this->total_hours * $this->rate_per_hour + $this->leave_pay;
    }

    public function getFormattedDurationProperty()
    {
        if (! $this->total_duration) {
            return null;
        }

        [$jam, $menit, $detik] = explode(':', $this->total_duration);

        return (int)$jam . ' jam ' . (int)$menit . ' menit ' . (int)$detik . ' detik';
    }
}
