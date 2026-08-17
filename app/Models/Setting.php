<?php

namespace App\Models;

use App\Support\Performance\DatabaseAvailability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    private static ?Collection $requestSettingsByName = null;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Add a settings value
     *
     * @param  string  $type
     * @return bool
     */
    public static function add($key, $val, $type = 'string')
    {
        $key = trim((string) $key);
        if ($key === '') {
            return false;
        }

        if (! self::tableExists()) {
            return false;
        }

        if (self::has($key)) {
            return self::set($key, $val, $type);
        }

        return self::create(['name' => $key, 'val' => $val, 'type' => $type]) ? $val : false;
    }

    /**
     * Check if setting exists
     *
     * @return bool
     */
    public static function has($key)
    {
        $key = trim((string) $key);
        if ($key === '') {
            return false;
        }

        return (bool) self::getAllSettings()->whereStrict('name', $key)->count();
    }

    /**
     * Determine whether settings can be read safely during application boot.
     *
     * Composer's package discovery and Artisan commands boot Laravel before
     * migrations may have created the settings table. A successful database
     * connection alone therefore does not mean the table is queryable.
     */
    public static function tableExists(): bool
    {
        return DatabaseAvailability::tableExists((new static)->getTable());
    }

    /**
     * Get all the settings
     *
     * @return Collection<int, static>
     */
    public static function getAllSettings(): Collection
    {
        if (! self::tableExists()) {
            return collect();
        }

        // During very early bootstrap (eg, inside a service provider register),
        // the cache binding may not exist yet.
        if (! app()->bound('cache')) {
            return self::all();
        }

        return Cache::rememberForever('settings.all', function () {
            return self::all();
        });
    }

    private static function settingsByName(): Collection
    {
        return self::$requestSettingsByName ??= self::getAllSettings()->keyBy('name');
    }

    /**
     * Set a value for setting
     *
     * @param  string  $type
     * @return bool
     */
    public static function set($key, $val, $type = 'string')
    {
        $key = trim((string) $key);
        if ($key === '') {
            return false;
        }

        if ($setting = self::getAllSettings()->where('name', $key)->first()) {
            return $setting->update([
                'name' => $key,
                'val' => $val,
                'type' => $type
            ]) ? $val : false;
        }

        return self::add($key, $val, $type);
    }

    /**
     * Remove a setting
     *
     * @return bool
     */
    public static function remove($key)
    {
        $key = trim((string) $key);
        if ($key === '') {
            return false;
        }

        if (self::has($key)) {
            return self::whereName($key)->delete();
        }

        return false;
    }

    /**
     * Get the validation rules for setting fields
     *
     * @return array ;;
     */
    public static function getValidationRules($section)
    {
        return self::getDefinedSettingFields($section)->pluck('rules', 'name')
            ->reject(function ($val) {
                return is_null($val);
            })->toArray();
    }

    /**
     * Get all the settings fields from config ;;
     *
     * @return Collection
     */
    private static function getDefinedSettingFields($section)
    {
        $section = trim((string) $section);
        $configuration = config('setting', []);

        if ($section === '' || ! is_array($configuration)) {
            return collect();
        }

        $elements = $configuration[$section]['elements'] ?? [];

        return collect(is_array($elements) ? $elements : []);
    }

    /**
     * Get the data type of a setting
     *
     * @return mixed ;;
     */
    public static function getDataType($field, $section)
    {
        $type = self::getDefinedSettingFields($section)
            ->pluck('data', 'name')
            ->get($field);

        return is_null($type) ? 'string' : $type;
    }

    /**
     * Get a settings value
     *
     * @param  null  $default
     * @return bool|int|mixed
     */
    public static function get($key, $section = null, $default = null)
    {
        $key = trim((string) $key);
        if ($key === '') {
            return value($default);
        }

        // A setting() call is used throughout views, middleware and APIs. Do a
        // single cached collection lookup instead of has() + getAllSettings(),
        // and fall back to config while the database/table is unavailable.
        $setting = self::settingsByName()->get($key);
        if ($setting) {
            return self::castValue($setting->val, $setting->type);
        }

        return self::getDefaultValue($key, $section, $default);
    }

    /**
     * caste value into respective type
     *
     * @return bool|int
     */
    private static function castValue($val, $castTo)
    {
        switch ($castTo) {
            case 'int':
            case 'integer':
                return intval($val);
                break;

            case 'bool':
            case 'boolean':
                if (is_bool($val)) {
                    return $val;
                }

                if (is_int($val) || is_float($val)) {
                    return (int) $val === 1;
                }

                return in_array(strtolower(trim((string) $val)), [
                    '1', 'true', 'yes', 'on', 'enabled', 'active',
                ], true);

            default:
                return $val;
        }
    }

    /**
     * Get default value from config if no value passed
     *
     * @return mixed
     */
    private static function getDefaultValue($key, $section, $default)
    {
        return is_null($default) ? self::getDefaultValueForField($key, $section) : $default;
    }

    /**
     * Get default value for a setting
     *
     * @return mixed
     */
    public static function getDefaultValueForField($field, $section)
    {
        $field = trim((string) $field);
        if ($field === '') {
            return null;
        }

        $section = trim((string) $section);
        if ($section !== '') {
            return self::getDefinedSettingFields($section)
                ->pluck('value', 'name')
                ->get($field);
        }

        // Legacy code frequently calls setting('key') without its config
        // section. If the database record is not available yet, locate that
        // field across the configured sections instead of indexing the config
        // array with an empty key.
        $configuration = config('setting', []);
        if (! is_array($configuration)) {
            return null;
        }

        foreach (array_keys($configuration) as $configuredSection) {
            $definedField = self::getDefinedSettingFields($configuredSection)
                ->firstWhere('name', $field);

            if (is_array($definedField) && array_key_exists('value', $definedField)) {
                return $definedField['value'];
            }
        }

        return null;
    }

    /**
     * The "booting" account of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function () {
            self::flushCache();
        });

        static::created(function () {
            self::flushCache();
        });
    }

    /**
     * Flush the cache
     */
    public static function flushCache()
    {
        self::$requestSettingsByName = null;

        if (app()->bound('cache')) {
            Cache::forget('settings.all');
        }
    }
}
