<?php

namespace DestruidorPT\LaravelSQRLAuth\App;

use Illuminate\Database\Eloquent\Model;

class Sqrl_nonce extends Model
{
    protected $fillable = ['nonce', 'type', 'ip_address', 'url', 'can', 'verified', 'orig_nonce', 'question', 'btn_answer', 'sqrl_pubkey_id'];
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $hidden = [];

    public function sqrl_pubkey()
    {
        return $this->belongsTo('DestruidorPT\LaravelSQRLAuth\App\Sqrl_pubkey');
    }
}
