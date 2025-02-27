<?php

namespace App\Models\gestorBarbearia;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject; // Adicione esta linha

class Barbearia extends Model implements JWTSubject // Implemente a interface
{
    use HasFactory;

    protected $table = 'agendamento.barbearias';
    protected $primaryKey = 'id';

    protected $fillable = ['nome', 'status', 'data_vencimento', 'email', 'senha'];
    protected $hidden = ['senha'];

    protected function casts(): array
    {
        return [
            'email' => 'datetime', // Isso pode ser removido se não for necessário
        ];
    }

    /**
     * Retorna o identificador do usuário para o JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Retorna o ID da barbearia
    }

    /**
     * Retorna claims personalizados para o JWT.
     */
    public function getJWTCustomClaims()
    {
        return []; // Nenhum claim personalizado
    }

    public $timestamps = false;
}
