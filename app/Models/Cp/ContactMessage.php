<?php

namespace App\Models\Cp;

use App\Core\Traits\Auditable;
use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use Auditable, BelongsToTenant;

    protected $table = 'cp_contact_messages';

    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'subject', 'message', 'status', 'ip',
    ];
}
