<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Agent\Agent;

class LoginActivities extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $appends = ['browser', 'platform'];

    /**
     * Cache schema support for the current request. This keeps deployments
     * backward-compatible when application code is uploaded before migrations
     * are executed, without repeatedly querying information_schema.
     */
    private static ?bool $hasClientInfoColumns = null;

    public static function add($id)
    {
        $model = new static;
        $model->user_id = $id ?? Auth::id();
        $model->ip = request()->ip();
        $model->location = getLocation()->name;
        $model->agent = request()->userAgent();

        if (self::hasClientInfoColumns()) {
            $client = self::parseClientInfo($model->agent);
            $model->setRawAttributes(array_merge($model->getAttributes(), [
                'browser' => $client['browser'],
                'platform' => $client['platform'],
            ]));
        }

        $model->save();

        return $model;
    }

    public static function parseClientInfo(?string $userAgent): array
    {
        if (blank($userAgent)) {
            return ['browser' => null, 'platform' => null];
        }

        $agent = new Agent;
        $agent->setUserAgent($userAgent);

        return [
            'browser' => self::normalizeClientLabel($agent->browser()),
            'platform' => self::normalizeClientLabel($agent->platform()),
        ];
    }

    private static function normalizeClientLabel($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? mb_substr($value, 0, 100) : null;
    }

    private static function hasClientInfoColumns(): bool
    {
        if (self::$hasClientInfoColumns !== null) {
            return self::$hasClientInfoColumns;
        }

        try {
            return self::$hasClientInfoColumns = Schema::hasColumns('login_activities', ['browser', 'platform']);
        } catch (\Throwable) {
            return self::$hasClientInfoColumns = false;
        }
    }

    protected function browser(): Attribute
    {
        return Attribute::make(get: function ($value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }

            return self::parseClientInfo($this->agent)['browser'];
        });
    }

    protected function platform(): Attribute
    {
        return Attribute::make(get: function ($value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }

            return self::parseClientInfo($this->agent)['platform'];
        });
    }
}
