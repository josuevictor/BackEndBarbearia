<?php

namespace App\Models\Agendamento;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agendamento extends Model
{
    protected $table = 'agendamentos';
    public $timestamps = false;
    use HasFactory;
}
