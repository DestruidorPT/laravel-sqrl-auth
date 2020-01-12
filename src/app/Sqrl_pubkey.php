<?php

namespace DestruidorPT\LaravelSQRLAuth\App;

use Illuminate\Database\Eloquent\Model;

class Sqrl_pubkey extends Model
{
    protected $fillable = ['public_key', 'user_id', 'vuk', 'suk', 'disabled', 'sqrl_only_allowed', 'hardlock'];
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $hidden = [];
    
    public function sqrl_nonces()
    {
        return $this->hasMany('DestruidorPT\LaravelSQRLAuth\App\Sqrl_nonce');
    }
}
