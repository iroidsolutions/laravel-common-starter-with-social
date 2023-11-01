<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appversion;
use Illuminate\Http\Request;

class CommonController extends Controller
{
    //

    public function appVersion(Request $request){

        $type=$request->type;
        $version=$request->version;
        if($type!="iOS" && $type!="Android"){
            return \response()->json(['message' => "invalid device type"], 422);
        }
        $checkData=Appversion::where('platform',$request->type)->first();
        $minversion=$checkData->minversion;
        $currentversion=$checkData->version;
        $data=new stdClass();
        $data->applink=$checkData->applink;
        if($minversion>$version){
            $data->status=1;
            $data->message='Update Forcefully';
            return \response()->json(['data' => $data], 200);
        }else if($currentversion>$version){
            $data->message='Recommend to update';
            $data->status=2;
            return \response()->json(['data' => $data], 200);
        }else{
            $data->message='Allready up to date';
            $data->status=0;
            return \response()->json(['data' => $data], 200);
        }

    }
}
