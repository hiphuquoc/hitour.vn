<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AirLocation;
use App\Models\AirDeparture;

class AirPort extends Model {
    use HasFactory;
    protected $table        = 'air_port';
    protected $fillable     = [
        'name', 
        'address',
        'district_id',
        'province_id',
        'region_id'
    ];
    public $timestamps      = true;

    public static function insertItem($params){
        $id             = 0;
        if(!empty($params)){
            $model      = new AirPort();
            foreach($params as $key => $value) $model->{$key}  = $value;
            $model->save();
            $id         = $model->id;
        }
        return $id;
    }

    public static function updateItem($id, $params){
        $flag           = false;
        if(!empty($id)&&!empty($params)){
            $model      = self::find($id);
            foreach($params as $key => $value) $model->{$key}  = $value;
            $flag       = $model->update();
        }
        return $flag;
    }

    public static function getAirPortByAirDepartureId($airLocationId){
        $result         = [];
        if(!empty($airLocationId)){
            $tmp        = AirDeparture::select('province_id')
                            ->where('id', $airLocationId)
                            ->first();
            $idProvinceOfPort   = $tmp->province_id ?? 0;
            $result     = self::select('*')
                            ->where('province_id', $idProvinceOfPort)
                            ->get();
        }
        return $result;
    }

    public static function getAirPortByAirLocationId($airLocationId){
        $result         = [];
        if(!empty($airLocationId)){
            $tmp        = AirLocation::select('province_id')
                            ->where('id', $airLocationId)
                            ->first();
            $idProvinceOfPort   = $tmp->province_id ?? 0;
            $result     = self::select('*')
                            ->where('province_id', $idProvinceOfPort)
                            ->get();
        }
        return $result;
    }

    public function region(){
        return $this->hasOne(\App\Models\Region::class, 'id', 'region_id');
    }

    public function province(){
        return $this->hasOne(\App\Models\Province::class, 'id', 'province_id');
    }

    public function district(){
        return $this->hasOne(\App\Models\District::class, 'id', 'district_id');
    }
}
