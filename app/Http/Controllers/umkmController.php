<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use App\Models\Umkm_details;
use App\Models\Umkm_posts;
use App\Models\Umkm_posts_ad;
use App\Models\Umkm_posts_files;
use App\Models\Umkm_posts_like;
use App\Models\Umkm_posts_comments;
use App\Models\Premium_transaction;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Validator;
use DB;

class umkmController extends Controller
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

    public function getUmkmProfile($id_umkm){
        $umkm_details = Umkm_details::where('id',$id_umkm)->first();
        if(count((array)$umkm_details) == 0){
            return response()->json([
                'success' => false,
                'message' => "tidak ditemukan data umkm dengan id ".$id_umkm,
            ],Response::HTTP_BAD_REQUEST);
        }
        
        $count_post = Umkm_posts::where('id_umkm',$umkm_details->id)->count();
        $get_post = null;
        $get_file = null;
        $get_like = null;
        $get_comment = null;
        $get_comment_reply = null;
        
        $hasil_post = array();
        $hasil_like = array();
        $hasil_file = array();
        $hasil_comment = array();
        $hasil_comment_reply = array();

        if($count_post > 0){
            if($count_post > 1){
                $get_post = DB::table('umkm_posts')->join('umkm_details','umkm_posts.id_umkm','umkm_details.id')
                                                   ->where('umkm_posts.id_umkm',$umkm_details->id)
                                                   ->select('umkm_posts.id','umkm_posts.id_umkm','umkm_details.umkm_name','title','post_desc','like_count','umkm_posts.created_at')
                                                   ->get();
                foreach($get_post as $gp){
                    $count_like = Umkm_posts_like::where('id_post',$gp->id)->count();
                    if($count_like > 0){
                        if($count_like == 1){
                            $get_like = DB::table('umkm_post_likes AS upl')->join('users','upl.id_user','users.id')
                                                                ->where('upl.id_post','=',$gp->id)
                                                                ->select('upl.id','upl.id_post','users.name')
                                                                ->first();
                            $hasil_like[] = array(
                                'id_like' => $get_like->id,
                                'id_post' => $get_like->id_post,
                                'nama_user' => $get_like->name
                            );
                        }
                        else{
                            $get_like = DB::table('umkm_post_likes AS upl')->join('users','upl.id_user','users.id')
                                                                    ->where('upl.id_post','=',$gp->id)
                                                                    ->select('upl.id','upl.id_post','users.name')
                                                                    ->get();
                            foreach($get_like as $gl){
                                $hasil_like[] = array(
                                    'id_like' => $gl->id,
                                    'id_post' => $gl->id_post,
                                    'nama_user' => $gl->name
                                );
                            }
                        }
                    }

                    $count_file = Umkm_posts_files::where('id_post',$gp->id)->count();
                    if($count_file > 0){
                        if($count_file == 1){
                            $get_file = DB::table('umkm_post_files AS upf')->where('upf.id_post','=',$gp->id)
                                                                        ->select('id','id_post','file_name')
                                                                        ->first();
                            $hasil_file[] = array(
                                'id_file' => $get_file->id,
                                'id_post' => $get_file->id_post,
                                'nama_file' => $get_file->file_name
                            );
                        }
                        else{
                            $get_file = DB::table('umkm_post_files AS upf')->where('upf.id_post','=',$gp->id)
                                                                            ->select('id','id_post','file_name')
                                                                            ->get();
                            foreach($get_file as $gf){
                                $hasil_file[] = array(
                                    'id_file' => $gf->id,
                                    'id_post' => $gf->id_post,
                                    'nama_file' => $gf->file_name
                                );
                            }
                        }   
                    }

                    $count_comment = Umkm_posts_comments::where('id_post',$gp->id)->where('replied_to',0)->count();
                    if($count_comment > 0){
                        if($count_comment == 1){
                            $get_comment = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                ->where('upc.id_post','=',$gp->id)
                                                                                ->where('upc.replied_to','=','0')
                                                                                ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment')
                                                                                ->first();
                            $count_comment_reply = Umkm_posts_comments::where('replied_to',$get_comment->id)->count();
                            if($count_comment_reply > 0){
                                if($count_comment_reply == 1){
                                    $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                    ->where('upc.replied_to','=',$get_comment->id)
                                                                                    ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                                    ->first();
                                    $hasil_comment_reply[] = array(
                                        'id_komen' => $get_comment_reply->id,
                                        'id_post' => $get_comment_reply->id_post,
                                        'id_user' => $get_comment_reply->id_user,
                                        'nama_user' => $get_comment_reply->name,
                                        'komentar' => $get_comment_reply->comment,
                                        'replied_to' => $get_comment_reply->replied_to      
                                    );
                                }
                                else{
                                    $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                    ->where('upc.replied_to','=',$get_comment->id)
                                                                                    ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                                    ->get();
                                    foreach($get_comment_reply as $gcr){
                                        $hasil_comment_reply[] = array(
                                            'id_komen' => $gcr->id,
                                            'id_post' => $gcr->id_post,
                                            'id_user' => $gcr->id_user,
                                            'nama_user' => $gcr->name,
                                            'komentar' => $gcr->comment,
                                            'replied_to' => $gcr->replied_to      
                                        );
                                    }
                                }
                            }
                            $hasil_comment[] = array(
                                'id_komen' => $gc->id,
                                'id_post' => $gc->id_post,
                                'id_user' => $gc->id_user,
                                'nama_user' => $gc->name,
                                'komentar' => $gc->comment,
                                'balasan' => $hasil_comment_reply
                            );
                        }
                        else{
                            $get_comment = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                    ->where('upc.id_post','=',$gp->id)
                                                                    ->where('upc.replied_to','=','0')
                                                                    ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment')
                                                                    ->get();
                            foreach($get_comment as $gc){
                                $count_comment_reply = Umkm_posts_comments::where('replied_to',$gc->id)->count();
                                if($count_comment_reply > 0){
                                    if($count_comment_reply == 1){
                                        $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                        ->where('upc.replied_to','=',$gc->id)
                                                                                        ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                                        ->first();
                                        $hasil_comment_reply[] = array(
                                            'id_komen' => $get_comment_reply->id,
                                            'id_post' => $get_comment_reply->id_post,
                                            'id_user' => $get_comment_reply->id_user,
                                            'nama_user' => $get_comment_reply->name,
                                            'komentar' => $get_comment_reply->comment,
                                            'replied_to' => $get_comment_reply->replied_to      
                                        );
                                    }
                                    else{
                                        $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                        ->where('upc.replied_to','=',$gc->id)
                                                                                        ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                                        ->get();
                                        foreach($get_comment_reply as $gcr){
                                            $hasil_comment_reply[] = array(
                                                'id_komen' => $gcr->id,
                                                'id_post' => $gcr->id_post,
                                                'id_user' => $gcr->id_user,
                                                'nama_user' => $gcr->name,
                                                'komentar' => $gcr->comment,
                                                'replied_to' => $gcr->replied_to      
                                            );
                                        }
                                    }
                                }
                                $hasil_comment[] = array(
                                    'id_komen' => $gc->id,
                                    'id_post' => $gc->id_post,
                                    'id_user' => $gc->id_user,
                                    'nama_user' => $gc->name,
                                    'komentar' => $gc->comment,
                                    'balasan' => $hasil_comment_reply
                                );
                                $hasil_comment_reply = (array) null;
                            }
                        }
                    }
                    $hasil_post[] = array(
                        'id_post' => $gp->id,
                        'id_umkm' => $gp->id_umkm,
                        'nama_umkm' => $gp->umkm_name,
                        'judul' => $gp->title,
                        'post_desc' => $gp->post_desc,
                        'jumlah_like' => $gp->like_count,
                        'created_at' => $gp->created_at,
                        'post_files' => $hasil_file,
                        'post_likers' => $hasil_like,
                        'post_comments' => $hasil_comment
                    );
                    $hasil_file = (array) null;
                    $hasil_like = (array) null;
                    $hasil_comment = (array) null;
                }
                return response()->json([
                    'success' => true,
                    'message' => "berhasil mendapatkan data umkm dengan id ".$id_umkm,
                    'data' => [
                        'id_user' => $umkm_details->id_user,
                        'nama_umkm' => $umkm_details->umkm_name,
                        'desc_umkm' => $umkm_details->umkm_desc,
                        'alamat_umkm' => $umkm_details->umkm_address,
                        'bidang_umkm' => $umkm_details->umkm_field,
                        'pendapatan_bulanan' => $umkm_details->umkm_monthly_revenue,
                        'pengalaman_umkm' => $umkm_details->umkm_years_experience." tahun",
                        'barang_terjual' => $umkm_details->sold_goods_quantity." barang",
                        'post' => [
                            $hasil_post,
                        ],
                    ] 
                ],Response::HTTP_OK);
        }
            else{
                $get_post = DB::table('umkm_posts')->join('umkm_details','umkm_posts.id_umkm','umkm_details.id')
                                                   ->where('umkm_posts.id_umkm',$umkm_details->id)
                                                   ->select('umkm_posts.id','umkm_posts.id_umkm','umkm_details.umkm_name','title','post_desc','like_count','umkm_posts.created_at')
                                                   ->first();
                $count_like = Umkm_posts_like::where('id_post',$get_post->id)->count();
                if($count_like > 0){
                    if($count_like == 1){
                        $get_like = DB::table('umkm_post_likes AS upl')->join('users','upl.id_user','users.id')
                                                            ->where('upl.id_post','=',$get_post->id)
                                                            ->select('upl.id','upl.id_post','users.name')
                                                            ->first();
                    }
                    else{
                        $get_like = DB::table('umkm_post_likes AS upl')->join('users','upl.id_user','users.id')
                                                                ->where('upl.id_post','=',$get_post->id)
                                                                ->select('upl.id','upl.id_post','users.name')
                                                                ->get();
                    }
                    foreach($get_like as $gl){
                        $hasil_like[] = array(
                            'id_like' => $gl->id,
                            'id_post' => $gl->id_post,
                            'nama_user' => $gl->name
                        );
                    }
                }

                $count_file = Umkm_posts_files::where('id_post',$get_post->id)->count();
                if($count_file > 0){
                    if($count_file == 1){
                        $get_file = DB::table('umkm_post_files AS upf')->where('upf.id_post','=',$get_post->id)
                                                                    ->select('id','id_post','file_name')
                                                                    ->first();
                    }
                    else{
                        $get_file = DB::table('umkm_post_files AS upf')->where('upf.id_post','=',$get_post->id)
                                                                        ->select('id','id_post','file_name')
                                                                        ->get();
                    }
                    foreach($get_file as $gf){
                        $hasil_file[] = array(
                            'id_file' => $gf->id,
                            'id_post' => $gf->id_post,
                            'nama_file' => $gf->file_name
                        );
                    }   
                }

                $count_comment = Umkm_posts_comments::where('id_post',$get_post->id)->where('replied_to',0)->count();
                if($count_comment > 0){
                    if($count_comment == 1){
                        $get_comment = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                            ->where('upc.id_post','=',$get_post->id)
                                                                            ->where('upc.replied_to','=',0)
                                                                            ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment')
                                                                            ->first();
                        $count_comment_reply = Umkm_posts_comments::where('replied_to',$get_comment->id)->count();
                        if($count_comment_reply > 0){
                            if($count_comment_reply == 1){
                                $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                ->where('upc.replied_to','=',$get_comment->id)
                                                                                ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                                ->first();
                                $hasil_comment_reply[] = array(
                                    'id_komen' => $get_comment_reply->id,
                                    'id_post' => $get_comment_reply->id_post,
                                    'id_user' => $get_comment_reply->id_user,
                                    'nama_user' => $get_comment_reply->name,
                                    'komentar' => $get_comment_reply->comment,
                                    'replied_to' => $get_comment_reply->replied_to
                                );
                            }
                            else{
                                $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                ->where('upc.replied_to','=',$get_comment->id)
                                                                                ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                                ->get();
                                foreach($get_comment_reply as $gcr){
                                    $hasil_comment_reply[] = array(
                                        'id_komen' => $gcr->id,
                                        'id_post' => $gcr->id_post,
                                        'id_user' => $gcr->id_user,
                                        'nama_user' => $gcr->name,
                                        'komentar' => $gcr->comment,
                                        'replied_to' => $gcr->replied_to
                                    );
                                }
                            }
                        }
                        $hasil_comment[] = array(
                            'id_komen' => $get_comment->id,
                            'id_post' => $get_comment->id_post,
                            'id_user' => $get_comment->id_user,
                            'nama_user' => $get_comment->name,
                            'komentar' => $get_comment->comment,
                            'balasan' => $hasil_comment_reply
                        );
                    }
                    else{
                        $get_comment = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                ->where('upc.id_post','=',$get_post->id)
                                                                ->where('upc.replied_to','=',0)
                                                                ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment')
                                                                ->get();
                        foreach($get_comment as $gc){
                            $count_comment_reply = Umkm_posts_comments::where('replied_to',$gc->id)->count();
                            if($count_comment_reply > 0){
                                if($count_comment_reply == 1){
                                    $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                    ->where('upc.replied_to','=',$gc->id)
                                                                                    ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                                    ->first();
                                }
                                else{
                                    $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                    ->where('upc.replied_to','=',$gc->id)
                                                                                    ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                                    ->get();
                                }
                                foreach($get_comment_reply as $gcr){
                                    $hasil_comment_reply[] = array(
                                        'id_komen' => $gcr->id,
                                        'id_post' => $gcr->id_post,
                                        'id_user' => $gcr->id_user,
                                        'nama_user' => $gcr->name,
                                        'komentar' => $gcr->comment,
                                        'replied_to' => $gcr->replied_to
                                    );
                                }
                            }
                            $hasil_comment[] = array(
                                'id_komen' => $gc->id,
                                'id_post' => $gc->id_post,
                                'id_user' => $gc->id_user,
                                'nama_user' => $gc->name,
                                'komentar' => $gc->comment,
                                'balasan' => $hasil_comment_reply
                            );
                            $hasil_comment_reply = (array) null;
                        }
                    }
                }
                $hasil_post[] = array(
                    'id_post' => $get_post->id,
                    'id_umkm' => $get_post->id_umkm,
                    'nama_umkm' => $get_post->umkm_name,
                    'judul' => $get_post->title,
                    'post_desc' => $get_post->post_desc,
                    'jumlah_like' => $get_post->like_count,
                    'created_at' => $get_post->created_at,
                    'post_files' => $hasil_file,
                    'post_likers' => $hasil_like,
                    'post_comments' => $hasil_comment
                );
                return response()->json([
                    'success' => true,
                    'message' => "berhasil mendapatkan data umkm dengan id ".$id_umkm,
                    'data' => [
                        'id_user' => $umkm_details->id_user,
                        'nama_umkm' => $umkm_details->umkm_name,
                        'desc_umkm' => $umkm_details->umkm_desc,
                        'alamat_umkm' => $umkm_details->umkm_address,
                        'bidang_umkm' => $umkm_details->umkm_field,
                        'pendapatan_bulanan' => $umkm_details->umkm_monthly_revenue,
                        'pengalaman_umkm' => $umkm_details->umkm_years_experience." tahun",
                        'barang_terjual' => $umkm_details->sold_goods_quantity." barang",
                        'post' => [
                            $hasil_post
                        ],
                    ] 
                ],Response::HTTP_OK);
            }
        }
        else{
            return response()->json([
                'success' => true,
                'message' => "berhasil mendapatkan data umkm dengan id ".$id_umkm,
                'data' => [
                    'id_user' => $umkm_details->id_user,
                    'nama_umkm' => $umkm_details->umkm_name,
                    'desc_umkm' => $umkm_details->umkm_desc,
                    'alamat_umkm' => $umkm_details->umkm_address,
                    'bidang_umkm' => $umkm_details->umkm_field,
                    'pendapatan_bulanan' => $umkm_details->umkm_monthly_revenue,
                    'pengalaman_umkm' => $umkm_details->umkm_years_experience." tahun",
                    'barang_terjual' => $umkm_details->sold_goods_quantity." barang",
                    'post' => $hasil_post,
                ] 
            ],Response::HTTP_OK);
        }
    }

    public function makeAd($id_post){
        $check_post = DB::table('umkm_posts')->join('umkm_details','umkm_posts.id_umkm','umkm_details.id')
                                             ->join('users', 'umkm_details.id_user','users.id')
                                             ->where('umkm_posts.id',$id_post)
                                             ->select('umkm_posts.id','umkm_posts.id_umkm','umkm_details.umkm_name','umkm_posts.title','umkm_posts.post_desc','users.premium_status','umkm_details.last_make_ad')
                                             ->first();
        if(empty($check_post)){
            return response()->json([
                'success' => false,
                'message' => "tidak ditemukan data post dengan id ".$id_post,
            ],Response::HTTP_BAD_REQUEST);
        }

        $check_ad = Umkm_posts_ad::where('id_post',$id_post)->count();
        if($check_ad > 0){
            return response()->json([
                'success' => false,
                'message' => "maaf anda sudah mengiklankan postingan ini",
            ],Response::HTTP_BAD_REQUEST);
        }
        if($check_post->premium_status == 0){
            if($check_post->last_make_ad != null){
                return response()->json([
                    'success' => false,
                    'message' => "maaf user dengan plan free hanya bisa membuat iklan sehari sekali",
                ],Response::HTTP_BAD_REQUEST);
            }
        }
        
        $tomorrow = strtotime('+1 day');

        $make_ad = Umkm_posts_ad::create([
            'id_umkm' => $check_post->id_umkm,
            'id_post' => $check_post->id,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', $tomorrow),
            'premium_status' => $check_post->premium_status
        ]);

        $update_umkm = Umkm_details::where('id',$check_post->id_umkm)->update([
            'last_make_ad' => date('Y-m-d', $tomorrow)
        ]);

        return response()->json([
            'success' => true,
            'message' => "berhasil mengiklankan postingan dengan id ".$id_post,
        ]);
    }

    public function update(Request $r,$id_umkm){
        $validator = Validator::make($r->all(), [
            'id_user' => 'required',
            'nama_umkm' => 'required',
            'bidang_umkm' => 'required',
            'pendapatan_bulanan' => 'required',
            'pengalaman_umkm' => 'required|numeric',
            'jumlah_barang_terjual' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => 'ada kesalahan pada saat anda menginput form',
                'error' => $validator->fails(),
            ],Response::HTTP_BAD_REQUEST);
        }

        $umkm_check_availability = Umkm_details::where('id',$id_umkm)->count();
        if($umkm_check_availability == 0){
            return response()->json([
                'success' => false,
                'message' => "tidak ditemukan data umkm dengan id ".$id_umkm,
            ],Response::HTTP_BAD_REQUEST);
        }

        $umkm_check = Umkm_details::where('id_user',$r->id_user)->first();
        if($umkm_check->id != $id_umkm){
            return response()->json([
                'success' => false,
                'message' => 'anda tidak bisa mengedit data umkm yang bukan milik anda',
            ],Response::HTTP_BAD_REQUEST);
        }

        $update = Umkm_details::where('id',$id_umkm)->update([
            'umkm_name' => $r->nama_umkm,
            'umkm_field' => $r->bidang_umkm,
            'umkm_monthly_revenue' => $r->pendapatan_bulanan,
            'umkm_years_experience' => $r->pengalaman_umkm,
            'sold_goods_quantity' => $r->jumlah_barang_terjual
        ]);

            return response()->json([
                'success' => true,
                'message' => "berhasil mengupdate data umkm dengan id ".$id_umkm,
                'data' => $r->all(),
            ],Response::HTTP_CREATED);
    }
}
