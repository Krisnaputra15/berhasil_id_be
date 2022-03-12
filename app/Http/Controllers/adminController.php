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
        $count_unvalid_premium = Premium_transaction::where('end_date','<',$date)->count();
        if($count_unvalid_premium > 0){
            $delete_unvalid_premium = Premium_transaction::where('end_date','<',$date)->delete();
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

    public function acceptPremiumPayment($id_transaction){
        $check_transaction = Premium_transaction::where('id', $id_transaction)->first();
        if(empty($check_transaction)){
            return response()->json([
                'success' => false,
                'message' => "data transaksi premium dengan id ".$id_transaction." tidak ditemukan",
            ]);
        }

        $end_date = strtotime(str_replace("/",".",'30+ days'));
        $update_transaction = Premium_transaction::where('id', $id_transaction)->update([
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', $end_date),
            'status' => 'accepted'
        ]);

        $update_user = Users::where('id',$check_transaction->id_user)->update([
            'premium_status' => 1
        ]);

        $check_transaction = Premium_transaction::where('id', $id_transaction)->first();

        return response()->json([
            'success' => true,
            'message' => "berhasil menerima konfirmasi pembayaran dari user dengan id ".$check_transaction->id_user,
            'data' => [
                'id_pembayaran' => $check_transaction->id,
                'id_user' => $check_transaction->id_user,
                'bukti_pembayaran' => $check_transaction->payment_proof,
                'tanggal_mulai' => $check_transaction->start_date,
                'tanggal_berakhir' => $check_transaction->end_date,
                'status' => $check_transaction->status
            ],
        ]);
    }

    public function declinePremiumPayment($id_transaction){
        $check_transaction = Premium_transaction::where('id', $id_transaction)->first();
        if(empty($check_transaction)){
            return response()->json([
                'success' => false,
                'message' => "data transaksi premium dengan id ".$id_transaction." tidak ditemukan",
            ]);
        }

        $update_transaction = Premium_transaction::where('id', $id_transaction)->update([
            'start_date' => null,
            'end_date' => null,
            'status' => 'declined'
        ]);

        $update_user = Users::where('id',$check_transaction->id_user)->update([
            'premium_status' => 0
        ]);

        $check_transaction = Premium_transaction::where('id', $id_transaction)->first();

        return response()->json([
            'success' => true,
            'message' => "berhasil menolak konfirmasi pembayaran dari user dengan id ".$check_transaction->id_user,
            'data' => [
                'id_pembayaran' => $check_transaction->id,
                'id_user' => $check_transaction->id_user,
                'bukti_pembayaran' => $check_transaction->payment_proof,
                'status' => $check_transaction->status
            ],
        ]);
    }
}
