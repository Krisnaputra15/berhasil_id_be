<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use App\Models\Umkm_details;
use App\Models\umkm_investasi;
use App\Models\Umkm_posts;
use App\Models\Umkm_posts_ad;
use App\Models\Umkm_posts_files;
use App\Models\Umkm_posts_like;
use App\Models\Umkm_posts_comments;
use App\Models\Premium_transaction;
use Symfony\Component\HttpFoundation\Response;
use Validator;
use DB;

class investorController extends Controller
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

    public function proposeInvestation($id_investor, $id_umkm){
        $check_user = Users::where('id',$id_investor)->first();
        if(empty($check_user)){
            return response()->json([
                'success' => false,
                'message' => "tidak ditemukan data investor dengan id ".$id_investor,
            ],Response::HTTP_BAD_REQUEST);
        }
        if($check_user->id_level != 2){
            return response()->json([
                'success' => false,
                'message' => "maaf user yang dipilih bukan investor",
            ],Response::HTTP_BAD_REQUEST);
        }

        $check_invest = Umkm_investasi::where('id_umkm',$id_umkm)->where('id_investor',$id_investor)->count();
        if($check_invest > 0){
            if($check_invest->status == "waiting"){
                return response()->json([
                    'success' => false,
                    'message' => "silakan tunggu konfirmasi dari investor",
                ],Response::HTTP_BAD_REQUEST);
            }
            if($check_invest->status == "accepted"){
                return response()->json([
                    'success' => false,
                    'message' => "Investasi anda sudah diterima",
                ],Response::HTTP_BAD_REQUEST);
            }
        }
        if($check_invest == 0 || $check_invest->status == "declined"){
            $make_invest = Umkm_investasi::create([
                'id_umkm' => $id_umkm,
                'id_investor' => $id_investor,
                'proposer' => "investor",
                'status' => "waiting",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "berhasil mengajukan investasi ke umkm ".$id_umkm,
            'data' => $make_invest
        ]);
    }
}
