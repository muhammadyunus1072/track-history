<?php

namespace Sis\TrackHistory;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait HasTrackHistory
{
    public static function boot()
    {
        parent::boot();

        //-------------------------------------------
        //----------------- CREATE ------------------
        //-------------------------------------------
        self::creating(function ($model) {
            $userId = Auth::id();

            if ($userId) {
                //--- Created By User ---
                $model->created_by = $userId;
                $model->updated_by = $userId;
            } else {
                //--- Created By System ---
                $model->created_by = 0;
                $model->updated_by = 0;
            }
        });

        self::created(function ($model) {
            $model->insertHistory();
        });

        //-------------------------------------------
        //----------------- UPDATE ------------------
        //-------------------------------------------
        self::updating(function ($model) {
            $userId = Auth::id();
            if ($userId) {
                //--- Created By User ---
                $model->updated_by = $userId;
            } else {
                //--- Created By System ---
                $model->updated_by = 0;
            }
        });

        self::updated(function ($model) {
            $model->insertHistory();
        });

        //-------------------------------------------
        //----------------- DELETE ------------------
        //-------------------------------------------
        self::deleting(function ($model) {
            $userId = Auth::id();
            $model->deleted_by = $userId;
            $model->deleted_at = Carbon::now();
            $model->save();
        });

        self::onBoot();
    }

    protected static function onBoot()
    {}

    private function insertHistory()
    {
        $arrayObj = $this->getAttributes();
        $arrayObj['obj_id'] = $arrayObj['id'];

        unset($arrayObj['id']);
        unset($arrayObj['uuid']);

        $arrayObj['created_at'] = !empty($arrayObj['created_at']) ? Carbon::parse($arrayObj['created_at'])->format('Y-m-d H:i:s') : null;
        $arrayObj['updated_at'] = !empty($arrayObj['updated_at']) ? Carbon::parse($arrayObj['updated_at'])->format('Y-m-d H:i:s') : null;
        $arrayObj['deleted_at'] = !empty($arrayObj['deleted_at']) ? Carbon::parse($arrayObj['deleted_at'])->format('Y-m-d H:i:s') : null;

        $tableName = "_history_" . $this->getTable();
        $columns = "(" . implode(",", array_keys($arrayObj)) . ")";
        $binding = "(:" . implode(",:", array_keys($arrayObj)) . ")";
        DB::connection($this->connection)->insert("INSERT INTO $tableName $columns VALUES $binding", $arrayObj);
    }
}