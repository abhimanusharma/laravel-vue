<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GmailTemplate extends Model
{
    protected $fillable = ['name', 'subject', 'admin_id', 'body', 'user_id'];

    public function created_by()
    {
        return $this->belongsTo('App\User');
    }
}
