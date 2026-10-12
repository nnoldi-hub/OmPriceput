<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AgendaTag extends Model
{
    protected $fillable = ['name', 'color'];

    public function agendaItems(): BelongsToMany
    {
        return $this->belongsToMany(AgendaItem::class, 'agenda_item_tag', 'agenda_tag_id', 'agenda_item_id');
    }
}
