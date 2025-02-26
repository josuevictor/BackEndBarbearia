<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'agendamento.clientes';
    protected $primaryKey = 'cliente_id';

    protected $fillable = [
        'email',
        'senha',
    ];

    protected $hidden = [
        'senha'
    ];

    protected function casts(): array
    {
        return [
            'email' => 'datetime',
            'senha' => 'hashed',
        ];
    }

    public function getAuthPassword()
    {
        return $this->senha;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Implementação do JWTSubject: retorna qualquer claim extra no JWT (nesse caso, nenhum).
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
