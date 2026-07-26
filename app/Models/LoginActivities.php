<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jenssegers\Agent\Agent;

class LoginActivities extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = ['browser', 'platform'];

    public static function add($id)
    {
        $model = new static;
        $model->user_id = $id ?? Auth::id();
        $model->ip = request()->ip();
        $model->location = getLocation()->name;
        $model->agent = request()->userAgent();
        $model->save();

        return $model;
    }

    private function getAgent($show)
    {
        $agent = new Agent;
        $agent->setUserAgent($this->agent);

        return $agent->$show();
    }

    protected function browser(): Attribute
    {
        return Attribute::make(get: function () {
            return self::getAgent('browser');
        });
    }

    protected function platform(): Attribute
    {
        return Attribute::make(get: function () {
            return self::getAgent('platform');
        });
    }
}
