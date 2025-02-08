<?php

namespace App\Models\gestorBarbearia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barbearia extends Model
{
    protected $table = 'barbearias';
    public $timestamps = false;

    use HasFactory;
}
