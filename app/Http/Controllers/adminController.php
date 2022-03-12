<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use App\Models\Umkm_details;
use App\Models\Umkm_posts;
use App\Models\Umkm_posts_like;
use App\Models\Umkm_posts_files;
use App\Models\Umkm_posts_comments;
use App\Models\Umkm_posts_ad;
use App\Models\Premium_transaction;
use Validator;
use DB;

class adminController extends Controller
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

    public function showPremiumPayment(){
        $count_payment = DB::table('premium_transaction AS pt')->join('users AS u','pt.id_user','u.id')
                                                               ->join('umkm_details AS ud','ud.id_user','u.id')
                                                               ->count();
        $get_payment = null;
        if($count_payment > 0){
            if($count_payment == 1){
                $get_payment = DB::table('premium_transaction AS pt')->join('users AS u','pt.id_user','u.id')
                                                                     ->join('umkm_details AS ud','ud.id_user','u.id')
                                                                     ->select('u.id AS id_user','u.name AS nama_user','ud.umkm_name AS nama_umkm','pt.payment_proof AS bukti_pembayaran','pt.start_date AS tanggal_mulai','pt.end_date AS tanggal_berakhir','pt.status')
                                                                     ->first();
            }
            else{
                $get_payment = DB::table('premium_transaction AS pt')->join('users AS u','pt.id_user','u.id')
                                                                     ->join('umkm_details AS ud','ud.id_user','u.id')
                                                                     ->select('u.id AS id_user','u.name AS nama_user','ud.umkm_name AS nama_umkm','pt.payment_proof AS bukti_pembayaran','pt.start_date AS tanggal_mulai','pt.end_date AS tanggal_berakhir','pt.status')
                                                                     ->get();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "berhasil mendapatkan data pembayaran premium",
            'data' => $get_payment
        ]);
    }

    public function acceptPremiumPayment(){
        
    }
}
