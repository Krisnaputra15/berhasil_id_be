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
    public function createPremiumPayment(Request $r){
        $validator = Validator::make($r->all(), [
            'id_user' => 'required|numeric',
            'id_pack' => 'required|numeric',
            'bukti_pembayaran' => 'required|mimes:jpeg,jpg,png',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => "Error ketika validasi data",
                'error' => $validator->messages(),
            ]);
        }

        $check_user = User::where('id',$r->id_user)->first();
        if(empty($check_user)){
            return response()->json([
                'success' => false,
                'message' => 'tidak ditemukan data milik user dengan id '.$r->id_user,
            ]);
        }
        if($check_user->id_level != 1){
            return response()->json([
                'success' => false,
                'message' => 'fitur premium untuk sementara hanya tersedia bagi umkm'
            ]);
        }

        $check_payment = Premium_transaction::where('id_user',$r->id_user)->first();
        if(empty($check_payment) == false && $check_payment->status == "waiting"){
            return response()->json([
                'success' => false,
                'message' => 'mohon menunggu pembayaran sebelumnya untuk diverifikasi'
            ]);
        }
        if(empty($check_payment) == false && $check_payment->status == "accepted"){
            return response()->json([
                'success' => false,
                'message' => 'anda sudah premium'
            ]);
        }

        $file = $r->file('bukti_pembayaran');
        $file_name = "user_".$check_user->id."_premium_payment_".date('Y-m-d').".".$file->getClientOriginalExtension();
        $file->move('assets/premium_payment_proof', $file_name); 

        $create_payment = Premium_transaction::create([
            'id_user' => $check_user->id,
            'id_premium' => $r->id_pack,
            'payment_proof' => 'assets/premium_payment_proof/'.$file_name,
            'status' => 'waiting',
        ]);

        return response()->json([
            'success' => true,
            'message' => "berhasil membuat data pembayaran premium untuk user ".$r->id_user,
            'data' => [
                'id_user' => $create_payment->id_user,
                'id_pack' => $create_payment->id_premium,
                'bukti_pembayaran' => $file_name,
                'file_path' => $create_payment->payment_proof,
                'status' => $create_payment->status
            ],
        ]);
    }
}
