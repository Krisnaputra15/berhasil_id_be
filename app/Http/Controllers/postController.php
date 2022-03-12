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
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Validator;
use DB;

class postController extends Controller
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

    public function createPost(Request $r, $id_umkm){
        $validator = Validator::make($r->all(), [
            'judul' => 'required',
            'post_desc' => 'required',
            'id_user' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => "Error ketika validasi data : ".$validator->messages(),
                'data' => $data,
            ],Response::HTTP_BAD_REQUEST);
        }

        $check_umkm = Umkm_details::where('id',$id_umkm)->count();
        if($check_umkm == 0){
            return response()->json([
                'success' => false,
                'message' => "Tidak ditemukan data umkm dengan id ".$id_umkm,
            ],Response::HTTP_BAD_REQUEST);
        }
        $check_umkm = Umkm_details::where('id_user',$r->id_user)->first();
        if($check_umkm->id != $id_umkm){
            return response()->json([
                'success' => false,
                'message' => "Tidak bisa membuat postingan di umkm yang bukan milik anda sendiri",
            ],Response::HTTP_BAD_REQUEST);
        }

        $create_post = Umkm_posts::create([
            'id_umkm' => $id_umkm,
            'title' => $r->judul,
            'post_desc' => $r->post_desc,
            'like_count' => 0,
            'created_at' => date('Y-m-d H:i:s'), 
        ]);

        $get_post = DB::table('umkm_posts')->join('umkm_details','umkm_posts.id_umkm','=','umkm_details.id')
                                           ->where('id_umkm','=',$id_umkm,'AND','post_desc','=',$r->post_desc)
                                           ->orderBy('created_at','DESC')
                                           ->select('umkm_posts.id','umkm_name','title','post_desc','like_count','created_at')
                                           ->first();
        
        if($r->file('files') != null){
            $allowedfileExtension=['jpg','jpeg','png','mp4','mov','wmv'];
            $files = $r->file('files'); 

            $i = 1;
            foreach($files as $file){
                $extension = $file->getClientOriginalExtension();
                $check = in_array($extension,$allowedfileExtension);
                if($check){
                        $file_name = "post ".$get_post->id." image ".$i.".".$file->getClientOriginalExtension();
                        $file->move('assets/post_assets', $file_name); 
                        $create_post_file = Umkm_posts_files::create([
                            'id_post' => $get_post->id,
                            'file_name' => 'assets/post_assets/'.$file_name,
                        ]);
                    $i++;
                }
                else {
                    return response()->json([
                        'success' => false,
                        'message' => "invalid file format"
                    ], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        $count_post_files = Umkm_posts_files::where('id_post',$get_post->id)->count();
        $get_post_files;
        if($count_post_files > 0){
            if($count_post_files == 1){
                $get_post_files = Umkm_posts_files::where('id_post',$get_post->id)->first();
                return response()->json([
                    'success' => true,
                    'message' => "berhasil membuat post",
                    'data' => [
                        'id_post' => $get_post->id,
                        'nama_umkm' => $get_post->umkm_name,
                        'title' => $get_post->title,
                        'post_desc' => $get_post->post_desc,
                        'post_files' => $get_post_files
                    ]
                ]);
            }
            else{
                $get_post_files = Umkm_posts_files::where('id_post',$get_post->id)->get();
                return response()->json([
                    'success' => true,
                    'message' => "berhasil membuat post",
                    'data' => [
                        'id_post' => $get_post->id,
                        'nama_umkm' => $get_post->umkm_name,
                        'title' => $get_post->title,
                        'post_desc' => $get_post->post_desc,
                        'post_files' => $get_post_files
                    ]
                ]);
            }
        }
        else{
            return response()->json([
                'success' => true,
                'message' => "berhasil membuat post",
                'data' => [
                    'id_post' => $get_post->id,
                    'nama_umkm' => $get_post->umkm_name,
                    'title' => $get_post->title,
                    'post_desc' => $get_post->post_desc,
                    'post_files' => null,
                ]
            ]);
        }  
    }

    public function deletePost($id_post){
        $count_post = Umkm_posts::where('id',$id_post)->count();
        if($count_post == 0){
            return response()->json([
                'success' => false,
                'message' => 'tidak ditemukan data postingan dengan id '.$id_post,
            ],Response::HTTP_BAD_REQUEST);
        }
        $check_file = Umkm_posts_files::where('id_post',$id_post)->count();
        if($check_file > 0){
            $delete_file = Umkm_posts_files::where('id_post',$id_post)->delete();
        }
        $check_comment = Umkm_posts_comments::where('id_post',$id_post)->count();
        if($check_comment > 0){
            $delete_comment = Umkm_posts_comments::where('id_post',$id_post)->delete();
        }
        $check_like = Umkm_posts_like::where('id_post',$id_post)->count();
        if($check_like > 0){
            $delete_like = Umkm_posts_like::where('id_post',$id_post)->delete();
        }
        $check_ads = Umkm_posts_ad::where('id_post',$id_post)->count();
        if($check_ads > 0){
            $delete_ads = Umkm_posts_ad::where('id_post',$id_post)->delete();
        }
        $delete_post = Umkm_posts::where('id',$id_post)->delete();

        if($delete_post){
            return response()->json([
                'success' => true,
                'message' => "berhasil menghapus post",
            ]);
        }
    }

    public function getPost($id_post){
        $check_post = Umkm_posts::where('id',$id_post)->count();
        if($check_post == 0){
            return response()->json([
                'success' => false,
                'message' => "Tidak ditemukan data post dengan id ".$id_post,
            ],Response::HTTP_BAD_REQUEST);
        }

        $hasil_post = array();
        $hasil_like = array();
        $hasil_file = array();
        $hasil_comment = array();
        $hasil_comment_reply = array();

        $get_post = DB::table('umkm_posts')->join('umkm_details','umkm_posts.id_umkm','umkm_details.id')
                                           ->where('umkm_posts.id','=',$id_post)
                                           ->select('umkm_posts.id','umkm_posts.id_umkm','umkm_details.umkm_name','umkm_details.umkm_field','title','post_desc','like_count','umkm_posts.created_at')
                                           ->first();
        $get_file = null;
        $get_like = null;
        $get_comment = null;
        $get_comment_reply = null;

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
                    'id_komen' => $get_comment->id,
                    'id_post' => $get_comment->id_post,
                    'id_user' => $get_comment->id_user,
                    'nama_user' => $get_comment->name,
                    'komentar' => $get_comment->comment,
                    'balasan' => $hasil_comment_reply
                );
                $hasil_comment_reply = (array) null;
            }
            else{
                $get_comment = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                        ->where('upc.id_post','=',$get_post->id)
                                                        ->where('upc.replied_to','=','0')
                                                        ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment')
                                                        ->get();
                foreach($get_comment as $gc){
                    $count_comment_reply = Umkm_posts_comments::where('replied_to',$gc->id)->count();
                    if($count_comment_reply > 0){
                        if($count_comment_reply == 1){
                            $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','=','users.id')
                                                                            ->where('upc.replied_to','=',$gc->id)
                                                                            ->select('upc.id','upc.id_post','upc.id_user','users.name','upc.comment','upc.replied_to')
                                                                            ->first();
                        }
                        else{
                            $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','=','users.id')
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
            'bidang_umkm' => $get_post->umkm_field,
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
            'message' => 'berhasil mendapatkan data post dengan id '.$id_post,
            'data' => [
                'post' => $hasil_post,
            ]
        ]);
    }

    public function like($id_user, $id_post){
        $check_post = Umkm_posts::where('id',$id_post)->count();
        if($check_post == 0){
            return response()->json([
                'success' => false,
                'message' => 'tidak ditemukan data postingan dengan id '.$id_post,
            ],Response::HTTP_BAD_REQUEST);
        }
        $check_user = Users::where('id',$id_user)->count();
        if($check_user == 0){
            return response()->json([
                'success' => false,
                'message' => 'tidak ditemukan data user dengan id '.$id_user,
            ],Response::HTTP_BAD_REQUEST);
        }
        $get_post = Umkm_posts::where('id',$id_post)->first();
        $check_like = Umkm_posts_like::where('id_post',$id_post)->where('id_user',$id_user)->count();
        if($check_like > 0){
            $delete_like = Umkm_posts_like::where('id_post',$id_post)->where('id_user',$id_user)->delete();
            $check_like_2 = Umkm_posts_like::where('id_post',$id_post)->count();
            $update_post = Umkm_posts::where('id',$id_post)->update([
                'like_count' => $check_like_2,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'berhasil unlike postingan dengan id '.$id_post,
                'data' => [
                    'id_post' => $get_post->id,
                    'post_desc' => $get_post->post_desc,
                    'jumlah_like' => $check_like_2,
                ]
            ],Response::HTTP_OK);
        }
        else{
            $like = Umkm_posts_like::create([
                'id_post' => $id_post,
                'id_user' => $id_user,
            ]);
            $check_like_2 = Umkm_posts_like::where('id_post',$id_post)->count();
            $update_post = Umkm_posts::where('id',$id_post)->update([
                'like_count' => $check_like_2,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'berhasil like postingan dengan id '.$id_post,
                'data' => [
                    'id_post' => $get_post->id,
                    'post_desc' => $get_post->post_desc,
                    'jumlah_like' => $check_like_2,
                ],
            ],Response::HTTP_OK);
        }
    }

    public function comment($id_post, Request $r){
        $check_post = Umkm_posts::where('id',$id_post)->count();
        if($check_post == 0){
            return response()->json([
                'success' => false,
                'message' => 'tidak ditemukan data postingan dengan id '.$id_post,
            ],Response::HTTP_BAD_REQUEST);
        }
        $check_user = Users::where('id',$r->id_user)->count();
        if($check_user == 0){
            return response()->json([
                'success' => false,
                'message' => 'tidak ditemukan data user dengan id '.$r->id_user,
            ],Response::HTTP_BAD_REQUEST);
        }
        if($r->replied_to != null){
            $check_replied_to = Umkm_posts_comments::where('id',$r->replied_to)->first();
            if($check_replied_to->replied_to != 0){
                return response()->json([
                    'success' => false,
                    'message' => 'maaf anda hanya bisa membalas komentar utama',
                ],Response::HTTP_BAD_REQUEST);
            }
        }
        $validator = Validator::make($r->all(), [
            'id_user' => 'required',
            'comment' => 'required',
        ]);
        $get_user_data = Users::where('id',$r->id_user)->first();
        $get_post_data = DB::table('umkm_posts')->join('umkm_details','umkm_posts.id_umkm','=','umkm_details.id')
                                           ->where('umkm_posts.id','=',$id_post)
                                           ->orderBy('created_at','DESC')
                                           ->select('umkm_posts.id','umkm_name','title','post_desc','like_count','created_at')
                                           ->first();
        if($r->replied_to != null){
            $check_reply = Umkm_posts_comments::where('id',$r->replied_to)->count();
            if($check_reply == 0){
                return response()->json([
                    'success' => false,
                    'message' => 'tidak ditemukan data komentar dengan id '.$r->replied_to,
                ],Response::HTTP_BAD_REQUEST);
            }
            $comment = Umkm_posts_comments::create([
                'id_post' => $id_post,
                'id_user' => $r->id_user,
                'comment' => $r->comment,
                'replied_to' => $r->replied_to,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $count_comment_reply = Umkm_posts_comments::where('replied_to',$r->replied_to)->count();
            $get_comment_reply = null;
            $get_parent_comment = Umkm_posts_comments::where('id',$r->replied_to)->first();
            if($count_comment_reply > 1){
                $get_comment_reply = DB::table('umkm_post_comments')->join('users','umkm_post_comments.id_user','=','users.id')
                                                                    ->where('umkm_post_comments.replied_to',$r->replied_to)
                                                                    ->select('umkm_post_comments.id','users.name','id_user','id_post','comment','replied_to')
                                                                    ->get();
            }
            else{
                $get_comment_reply = DB::table('umkm_post_comments')->join('users','umkm_post_comments.id_user','=','users.id')
                                                                    ->where('umkm_post_comments.replied_to',$r->replied_to)
                                                                    ->select('umkm_post_comments.id','users.name','id_user','id_post','comment','replied_to')
                                                                    ->first();
            }
            if($comment){
                return response()->json([
                    'success' => true,
                    'message' => 'berhasil berkomentar di postingan dengan id '.$id_post,
                    'data' => [
                        'id_post' => $get_post_data->id,
                        'nama_umkm' => $get_post_data->umkm_name,
                        'judul' => $get_post_data->title,
                        'post_desc' => $get_post_data->post_desc,
                        'jumlah_like' => $get_post_data->like_count,
                        'created_at' => $get_post_data->created_at,
                        'komentar' => [
                            'id_komentar' => $get_parent_comment->id,
                            'id_user' => $get_parent_comment->id_user,
                            'nama_user' => $get_user_data->name,
                            'komentar' => $get_parent_comment->comment,
                            'balasan' => [
                                $get_comment_reply
                            ]
                        ]
                    ]
                ],Response::HTTP_OK);
            }
        }
        else{
            $comment = Umkm_posts_comments::create([
                'id_post' => $id_post,
                'id_user' => $r->id_user,
                'comment' => $r->comment,
                'replied_to' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $get_comment_data = DB::table('umkm_post_comments')->where('id_post','=',$id_post,'AND','id_user','=',$r->id_user,'AND','comment','=',$r->comment,'AND','replied_to','=',$r->replied_to)
                                                               ->orderBy('created_at','DESC')
                                                               ->first();
            if($comment){
                return response()->json([
                    'success' => true,
                    'message' => 'berhasil berkomentar di postingan dengan id '.$id_post,
                    'data' => [
                        'id_post' => $get_post_data->id,
                        'nama_umkm' => $get_post_data->umkm_name,
                        'judul' => $get_post_data->title,
                        'post_desc' => $get_post_data->post_desc,
                        'jumlah_like' => $get_post_data->like_count,
                        'created_at' => $get_post_data->created_at,
                        'komentar' => [
                            'id_komentar' => $get_comment_data->id,
                            'id_user' => $comment->id_user,
                            'nama_user' => $get_user_data->name,
                            'komentar' => $comment->comment,
                        ]
                    ]
                ],Response::HTTP_OK);
            }
        }
    }

    public function deleteComment($id_comment){
        $check_comment = Umkm_posts_comments::where('id',$id_comment)->count();
        if($check_comment == 0){
            return response()->json([
                'success' => false,
                'message' => 'tidak ditemukan data komentar dengan id '.$id_comment,
            ],Response::HTTP_BAD_REQUEST);
        }
        $check_reply = Umkm_posts_comments::where('replied_to',$id_comment)->count();
        if($check_reply > 0){
            $delete_reply = Umkm_posts_comments::where('replied_to',$id_comment)->delete();
        }
        $delete_comment = Umkm_posts_comments::where('id',$id_comment)->delete();
        return response()->json([
            'success' => true,
            'message' => "berhasil menghapus komentar dengan id ".$id_comment,
        ],Response::HTTP_BAD_REQUEST);
    }

}
