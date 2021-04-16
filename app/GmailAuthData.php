<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GmailAuthData extends Model
{
    protected $fillable = ['user_id', 'admin_id', 'gmail_access_token', 'gmail_refresh_token', 'config', 'expires_in', 'token_type', 'email'];
}
