<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Premium_transaction;
use App\Models\Premium_features;
use App\Models\Premium_pack;
use App\Models\User;
use App\Models\Umkm_details;
use App\Models\Umkm_sold_goods_proof;
use App\Models\Umkm_achievements;
use App\Models\Umkm_fs;
use App\Models\Umkm_posts;
use App\Models\Umkm_posts_like;
use App\Models\Umkm_posts_files;
use App\Models\Umkm_posts_comments;
use App\Models\Umkm_posts_ad;
use DB;
use Validator;

class premiumController extends Controller
{
    public function __construct(){
        $date = date('Y-m-d');
        $count_unvalid_ads = Umkm_posts_ad::where('end_date','<',$date)->count();
        if($count_unvalid_ads > 0){
            $delete_unvalid_ads = Umkm_posts_ad::where('end_date','<',$date)->delete();
        }
        $count_unvalid_premium = premium_transaction::where('end_date','<',$date)->count();
        if($count_unvalid_premium > 0){
            $delete_unvalid_premium = premium_transaction::where('end_date','<',$date)->delete();
        }
        $check_user = Umkm_details::where('last_make_ad','<',$date)->update([
            'last_make_ad' => null
        ]);
    }

    public function showPremiumPack(){
        $get_pack = Premium_pack::all();
        if(count((array)$get_pack) == 0){
            return response()->json([
                'success' => false,
                'message' => "tidak ada fitur premium di database",
            ],Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $save_pack = array();
        $save_feature = array();
        foreach($get_pack as $gp){
            $get_feature = Premium_features::where('id_pack',$gp->id)->get();
            foreach($get_feature as $gf){
                $save_feature[] = array(
                    $gf->feature
                );
            }
            $save_pack[] = array(
                'id_paket' => $gp->id,
                'nama_paket' => $gp->pack_name,
                'fitur' => $save_feature
            );
            $save_feature = (array) null;
        }

        return response()->json([
            'success' => true,
            'message' => "berhasil mendapatkan data premium",
            'data' => [
                'daftar_paket' => $save_pack,
            ]
        ]);
        
    }
    public function createPembayaran(){

    }
}
