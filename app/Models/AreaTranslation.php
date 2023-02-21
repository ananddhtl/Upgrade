<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaTranslation extends Model
{
  protected $fillable = ['name', 'lang', 'area_id'];

  public function area(){
    return $this->belongsTo(Area::class);
  }
}
