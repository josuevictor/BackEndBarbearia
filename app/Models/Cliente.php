<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'agendamento.clientes';
    protected $primaryKey = 'cliente_id';
    public $timestamps = false;
    use HasFactory;
}
