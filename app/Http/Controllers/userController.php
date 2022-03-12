<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Users;
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
use App\Models\Premium_transaction;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Validator;
use DB;

class userController extends Controller
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

    public function register(Request $request){
        $data = $request->only('nama','email','password');
        $validator = Validator::make($data, [
            'nama' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Error ketika validasi data : ".$validator->messages(),
                'data' => $data,
            ]);
        }

        $check_email = Users::where('email',$data['email'])->count();
        if($check_email > 0){
            return response()->json([
                'success' => false,
                'message' => "email sudah terdaftar",
                'data' => $data,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = Users::create([
            'name' => $data['nama'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'premium_status' => false,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $credentials = $request->only('email','password');

        //create token
        try{
            if(!$token = JWTAuth::attempt($credentials)){
                return response()->json([
                	'success' => false,
                	'message' => 'Login credentials are invalid.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }
        catch(\JWTException $e){
            return $credentials;
            return response()->json([
                	'success' => false,
                	'message' => 'Tidak bisa membuat token',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if($user) {
            return response()->json([
                'success' => true,
                'message' => "berhasil membuat data user",
                'data' => [
                    'id_user' => $user->id,
                    'nama' => $user->name,
                    'email' => $user->email,
                    'created at' => $user->created_at,
                    'token' => $token,
                ],  
            ], Response::HTTP_CREATED);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => "error ketika mengakses database",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(Request $request){
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Error ketika validasi data",
                'data' => $validator->messages(),
            ]);
        }

        //Create token
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                	'success' => false,
                	'message' => 'Username atau password salah',
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (JWTException $e) {
            return $credentials;
                return response()->json([
                        'success' => false,
                        'message' => 'Could not create token.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = Users::where('email',$request->email)->first();
        if(count((array)$user) < 1){
            return response()->json([
                'success' => false,
                'message' => "email tidak terdaftar",
                'data' => [
                    'email' => $request->email,
                ],
            ], Response::HTTP_BAD_REQUEST);
        }
        if(Hash::check($request->password, $user->password)){
            return response()->json([
                'success' => true,
                'message' => "berhasil login",
                'data' => [
                    'id_user' => $user->id,
                    'nama' => $user->name,
                    'email' => $user->email,
                    'token' => $token,
                ],
            ], Response::HTTP_OK);
        }
        else{
            return response()->json([
                'success' => false,
                'message' => "password yang anda masukkan salah",
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function updateFirstTime($id_user,Request $request){
        $user = Users::where('id',$id_user)->first();
        if(count((array)$user) == 0){
            return response()->json([
                'success' => false,
                'message' => "tidak ada data user dengan id ".$id_user,
            ], Response::HTTP_BAD_REQUEST);
        }
        if($request->id_level == 1){
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string',
                'tanggal_lahir' => 'required|date',
                'alamat' => 'required|string',
                'nomer_telepon' => 'required|string',
                'pekerjaan' => 'required|string',
                'gaji' => 'required|numeric',
                'id_level' => 'required|numeric',
                'umkm_nama' => 'required',
                'umkm_bidang' => 'required',
                'umkm_pendapatan_bulanan' => 'required',
                'umkm_laporan_keuangan' => 'mimes:pdf,word,xsl',
                'umkm_pengalaman' => 'required',
                'jumlah_barang_terjual' => 'required',
                'bukti_barang_terjual' => 'image',
                'umkm_prestasi' => 'image',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => "Error ketika validasi data : ".$validator->messages(),
                    'data' => $request->all(),
                ]);
            }

            $update_profile = User::where('id', $id_user)->update([
                'id_level' => $request->id_level,
                'name' => $request->nama,
                'birth_date' => $request->tanggal_lahir,
                'address' => $request->alamat,
                'phone_number' => $request->nomer_telepon,
                'job' => $request->pekerjaan,
                'salary' => $request->gaji,
            ]);
            $create_umkm = Umkm_details::create([
                'id_user' => $id_user,
                'umkm_name' => $request->umkm_nama,
                'umkm_address' => $request->umkm_alamat,
                'umkm_field' => $request->umkm_bidang,
                'umkm_monthly_revenue' => $request->umkm_pendapatan_bulanan,
                'umkm_years_experience' => $request->umkm_pengalaman,
                'sold_goods_quantity' => $request->jumlah_barang_terjual,
            ]);
            $umkm_id = Umkm_details::where('id_user',$id_user)->first();
            
            $fs_get = null;
            if($r->umkm_laporan_keuangan != null){
                $fs_proof = $request->file('umkm_laporan_keuangan');
                $fs_proof_count = Umkm_fs::count();
                $fs_filename = "umkm ".$umkm_id->id."financial statement file ".$fs_proof_count." ".date('D d M Y', strtotime(date('Y-m-d'))).".".$fs_proof->getClientOriginalExtension();
                $create_fs_proof = Umkm_fs::create([
                    'id_umkm' => $umkm_id->id,
                    'file_name' => $fs_filename,
                ]);
                $fs_proof->move('assets/laporan_keuangan', $fs_filename);
                $fs_get = Umkm_fs::where('id_umkm',$umkm_id->id)->first();
            }

            $sg_get = null;
            if($r->bukti_barang_terjual != null){
                $sg_proof = $request->file('bukti_barang_terjual');
                $sg_proof_count = Umkm_sold_goods_proof::count();
                $sg_filename = "umkm ".$umkm_id->id."sg proof ".$sg_proof_count.".".$sg_proof->getClientOriginalExtension();
                $create_sg_proof = Umkm_sold_goods_proof::create([
                    'id_umkm' => $umkm_id->id,
                    'file_name' => $sg_filename,
                ]);
                $sg_proof->move('assets/bukti_barang_terjual', $sg_filename);
                $sg_get = Umkm_sold_goods_proof::where('id_umkm',$umkm_id->id)->first();
            }

            $achievement_get = null;
            if($r->umkm_prestasi != null){
                $achievement_proof = $request->file('umkm_prestasi');
                $achievement_proof_count = Umkm_achievements::count();
                $achievement_filename = "umkm ".$umkm_id->id."achievement proof ".$achievement_proof_count.".".$achievement_proof->getClientOriginalExtension();
                $create_achievement_proof = Umkm_achievements::create([
                    'id_umkm' => $umkm_id->id,
                    'file_name' => $achievement_filename,
                ]);
                $achievement_proof->move('assets/bukti_prestasi', $achievement_filename);
                $achievement_get = Umkm_achievements::where('id_umkm',$umkm_id->id)->first();
            }

            $user = Users::where('id',$id_user)->first();
            if($update_profile || $create_umkm || $create_sg_proof || $create_achievement_proof){
                return response()->json([
                    'success' => true,
                    'message' => "berhasil update data user dan umkm",
                    'data' => [
                        'data_user' => [
                            'id_user' => $user->id,
                            'email' => $user->email,
                            'id_level' => $user->id_level,
                            'nama' => $user->name,
                            'tanggal_lahir' => $user->birth_date,
                            'alamat' => $user->address,
                            'nomer_telepon' => $user->phone_number,
                            'pekerjaan' => $user->job,
                            'gaji' => "Rp".$user->salary
                        ],
                        'data_umkm' => [
                            $umkm_id,
                            'bukti_prestasi' => $achievement_get,
                            'bukti_barang_terjual' => $sg_get,
                            'laporan_keuangan' => $fs_get,
                        ],
                    ],
                ], Response::HTTP_CREATED);
            }

        }
        else{
            $validator = Validator::make($request->all(), [
                'nama' => 'required|string',
                'tanggal_lahir' => 'required|date',
                'alamat' => 'required|string',
                'nomer_telepon' => 'required|string',
                'pekerjaan' => 'required|string',
                'gaji' => 'required|numeric',
                'id_level' => 'required|numeric',
            ]);

            if($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => "Error ketika validasi data : ".$validator->messages(),
                    'data' => $data,
                ]);
            }

            $update_profile = User::where('id', $id_user)->update([
                'id_level' => $request->id_level,
                'name' => $request->nama,
                'birth_date' => $request->tanggal_lahir,
                'address' => $request->alamat,
                'phone_number' => $request->nomer_telepon,
                'job' => $request->pekerjaan,
                'salary' => $request->gaji,
            ]);

            $user = Users::where('id',$id_user)->first();

            return response()->json([
                'success' => true,
                'message' => "berhasil update data user dan umkm",
                'data' => [
                    'data_user' => [
                        'id_user' => $user->id,
                        'email' => $user->email,
                        'id_level' => $user->id_level,
                        'nama' => $user->name,
                        'tanggal_lahir' => $user->birth_date,
                        'alamat' => $user->address,
                        'nomer_telepon' => $user->phone_number,
                        'pekerjaan' => $user->job,
                        'gaji' => "Rp".$user->salary
                    ],
                ]
            ],Response::HTTP_CREATED);
            }
        
    }

    public function update(Request $request, $id_user){
        $user = Users::where('id',$id_user)->first();
        if(count((array)$user) == 0){
            return response()->json([
                'success' => false,
                'message' => "tidak ada data user dengan id ".$id_user,
            ], Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'alamat' => 'required|string',
            'nomer_telepon' => 'required|string',
            'pekerjaan' => 'required|string',
            'gaji' => 'required|numeric',
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Error ketika validasi data : ".$validator->messages(),
                'data' => $data,
            ]);
        }

        $update_profile = User::where('id', $id_user)->update([
            'name' => $request->nama,
            'birth_date' => $request->tanggal_lahir,
            'address' => $request->alamat,
            'phone_number' => $request->nomer_telepon,
            'job' => $request->pekerjaan,
            'salary' => $request->gaji,
        ]);

        $user = Users::where('id',$id_user)->first();

        return response()->json([
            'success' => true,
            'message' => "berhasil update data user",
            'data' => [
                'data_user' => [
                    'id_user' => $user->id,
                    'email' => $user->email,
                    'id_level' => $user->id_level,
                    'nama' => $user->name,
                    'tanggal_lahir' => $user->birth_date,
                    'alamat' => $user->address,
                    'nomer_telepon' => $user->phone_number,
                    'pekerjaan' => $user->job,
                    'gaji' => "Rp".$user->salary
                ],
            ]
        ],Response::HTTP_CREATED);

    }

    public function getProfile($id_user){
        $get_user = Users::where('id',$id_user)->first(['id','email','id_level','name','birth_date','address','phone_number','job','salary']);
        if(count((array)$get_user) == 0){
            return response()->json([
                'success' => false,
                'message' => "tidak ditemukan data milik user dengan id ".$id_user,
            ],Response::HTTP_BAD_REQUEST);
        }

        if($get_user->id_level == 1){
            $get_umkm = Umkm_details::where('id_user', $get_user->id)->first();
            $count_post = Umkm_posts::where('id_umkm',$get_umkm->id)->count();
            $get_post = null;
            return response()->json([
                'success' => true,
                'message' => "berhasil mendapatkan data user",
                'data' => [
                    'data_user' => [
                        'id_user' => $get_user->id,
                        'email' => $get_user->email,
                        'id_level' => $get_user->id_level,
                        'nama' => $get_user->name,
                        'tanggal_lahir' => $get_user->birth_date,
                        'alamat' => $get_user->address,
                        'nomer_telepon' => $get_user->phone_number,
                        'pekerjaan' => $get_user->job,
                        'gaji' => "Rp".$get_user->salary
                    ],
                    'data_umkm' => [
                        'id_umkm' => $get_umkm->id,
                        'nama_umkm' => $get_umkm->umkm_name,
                        'bidang_umkm' => $get_umkm->umkm_field,
                        'jumlah_postingan' => $count_post
                    ],
                ],
            ]);
        }
        else{
            return response()->json([
                'success' => true,
                'message' => "berhasil mendapatkan data user",
                'data' => [
                    'data_user' => [
                        'id_user' => $get_user->id,
                        'email' => $get_user->email,
                        'id_level' => $get_user->id_level,
                        'nama' => $get_user->name,
                        'tanggal_lahir' => $get_user->birth_date,
                        'alamat' => $get_user->address,
                        'nomer_telepon' => $get_user->phone_number,
                        'pekerjaan' => $get_user->job,
                        'gaji' => "Rp".$get_user->salary
                    ],
                ],
            ]);
        }
    }

    public function getRecommendedUmkm(){
        $check_premium_user = Users::where('premium_status',1)->where('id_level',1)->count();
        $check_free_user = Users::where('premium_status',0)->where('id_level',1)->count();
        $save_umkm = array();
        $get_umkm_premium = null;
        $get_umkm_free = null;

        if($check_premium_user > 0){
            if($check_premium_user == 1){
                $get_umkm_premium = DB::table('umkm_details AS ud')->join('users AS u','ud.id_user','u.id')
                                                                   ->where('u.premium_status',1)
                                                                   ->where('u.id_level',1)
                                                                   ->select('ud.id','ud.umkm_name','ud.umkm_field')
                                                                   ->first();
                $save_umkm[] = array(
                    'id_umkm' => $get_umkm_premium->id,
                    'nama_umkm' => $get_umkm_premium->umkm_name,
                    'bidang_umkm' => $get_umkm_premium->umkm_field
                );
            }
            elseif($check_premium_user <= 3){
                $get_umkm_premium = DB::table('umkm_details AS ud')->join('users AS u','ud.id_user','u.id')
                                                                   ->where('u.premium_status',1)
                                                                   ->where('u.id_level',1)
                                                                   ->select('ud.id','ud.umkm_name','ud.umkm_field')
                                                                   ->orderByRaw('RAND()')
                                                                   ->get();
                foreach($get_umkm_premium as $gup){
                    $save_umkm[] = array(
                        'id_umkm' => $gup->id,
                        'nama_umkm' => $gup->umkm_name,
                        'bidang_umkm' => $gup->umkm_field
                    );
                }
            }
            else{
                $get_umkm_premium = DB::table('umkm_details AS ud')->join('users AS u','ud.id_user','u.id')
                                                                   ->where('u.premium_status',1)
                                                                   ->where('u.id_level',1)
                                                                   ->select('ud.id','ud.umkm_name','ud.umkm_field')
                                                                   ->orderByRaw('RAND()')
                                                                   ->limit(3)
                                                                   ->get();
                foreach($get_umkm_premium as $gup){
                    $save_umkm[] = array(
                        'id_umkm' => $gup->id,
                        'nama_umkm' => $gup->umkm_name,
                        'bidang_umkm' => $gup->umkm_field
                    );
                }
            }
        }
        
        if($check_free_user > 0){
            if($check_free_user == 1){
                $get_umkm_free = DB::table('umkm_details AS ud')->join('users AS u','ud.id_user','u.id')
                                                                   ->where('u.premium_status',0)
                                                                   ->where('u.id_level',1)
                                                                   ->select('ud.id','ud.umkm_name','ud.umkm_field')
                                                                   ->first();
                $save_umkm[] = array(
                    'id_umkm' => $get_umkm_free->id,
                    'nama_umkm' => $get_umkm_free->umkm_name,
                    'bidang_umkm' => $get_umkm_free->umkm_field
                );
            }
            elseif($check_free_user <= 4){
                $get_umkm_free = DB::table('umkm_details AS ud')->join('users AS u','ud.id_user','u.id')
                                                                   ->where('u.premium_status',0)
                                                                   ->where('u.id_level',1)
                                                                   ->select('ud.id','ud.umkm_name','ud.umkm_field')
                                                                   ->orderByRaw('RAND()')
                                                                   ->get();
                foreach($get_umkm_free as $guf){
                    $save_umkm[] = array(
                        'id_umkm' => $guf->id,
                        'nama_umkm' => $guf->umkm_name,
                        'bidang_umkm' => $guf->umkm_field
                    );
                }
            }
            else{
                $get_umkm_free = DB::table('umkm_details AS ud')->join('users AS u','ud.id_user','u.id')
                                                                   ->where('u.premium_status',0)
                                                                   ->where('u.id_level',1)
                                                                   ->select('ud.id','ud.umkm_name','ud.umkm_field')
                                                                   ->orderByRaw('RAND()')
                                                                   ->limit(4)
                                                                   ->get();
                foreach($get_umkm_free as $guf){
                    $save_umkm[] = array(
                        'id_umkm' => $guf->id,
                        'nama_umkm' => $guf->umkm_name,
                        'bidang_umkm' => $guf->umkm_field
                    );
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'berhasil mendapatkan data rekomendasi umkm',
            'data' => [
                'rekomendasi_umkm' => $save_umkm,
            ]
        ]);
    }

    public function getAds(){
        $count_ad_premium = DB::table('umkm_post_ads AS upa')->join('umkm_posts AS up','upa.id_post','up.id')
                                                             ->join('umkm_details AS ud','upa.id_umkm','ud.id')
                                                             ->join('users AS u','ud.id_user','u.id')
                                                             ->where('upa.premium_status',1)
                                                             ->where('u.id_level',1)
                                                             ->select('up.id','up.title','up.post_desc')
                                                             ->count();
        $count_ad_free = DB::table('umkm_post_ads AS upa')->join('umkm_posts AS up','upa.id_post','up.id')
                                                          ->join('umkm_details AS ud','upa.id_umkm','ud.id')
                                                          ->join('users AS u','ud.id_user','u.id')
                                                          ->where('upa.premium_status',0)
                                                          ->where('u.id_level',1)
                                                          ->select('up.id','up.title','up.post_desc')
                                                          ->count();
        
        $save_ads = array();
        $get_ad_premium = null;
        $get_ad_free = null;

        if($count_ad_premium > 0){
            if($count_ad_premium == 1){
                $get_ad_premium = DB::table('umkm_post_ads AS upa')->join('umkm_posts AS up','upa.id_post','up.id')
                                                                   ->join('umkm_details AS ud','upa.id_umkm','ud.id')
                                                                   ->join('users AS u','ud.id_user','u.id')
                                                                   ->where('upa.premium_status',1)
                                                                   ->where('u.id_level',1)
                                                                   ->select('up.id','up.title','up.post_desc')
                                                                   ->first();
                $save_ads[] = array(
                    'id_post' => $get_ad_premium->id,
                    'title' => $get_ad_premium->title,
                    'post_desc' => $get_ad_premium->post_desc,
                );
            }
            elseif($count_ad_premium <= 3){
                $get_ad_premium = DB::table('umkm_post_ads AS upa')->join('umkm_posts AS up','upa.id_post','up.id')
                                                                   ->join('umkm_details AS ud','upa.id_umkm','ud.id')
                                                                   ->join('users AS u','ud.id_user','u.id')
                                                                   ->where('upa.premium_status',1)
                                                                   ->where('u.id_level',1)
                                                                   ->select('up.id','up.title','up.post_desc')
                                                                   ->orderByRaw('RAND()')
                                                                   ->limit(3)
                                                                   ->get();
                foreach($get_ad_premium as $gap){
                    $save_ads[] = array(
                        'id_post' => $gap->id,
                        'title' => $gap->title,
                        'post_desc' => $gap->post_desc,
                    );
                }
            }
            else{
                $get_ad_premium = DB::table('umkm_post_ads AS upa')->join('umkm_posts AS up','upa.id_post','up.id')
                                                                   ->join('umkm_details AS ud','upa.id_umkm','ud.id')
                                                                   ->join('users AS u','ud.id_user','u.id')
                                                                   ->where('upa.premium_status',1)
                                                                   ->where('u.id_level',1)
                                                                   ->select('up.id','up.title','up.post_desc')
                                                                   ->orderByRaw('RAND()')
                                                                   ->limit(3)
                                                                   ->get();
                foreach($get_ad_premium as $gap){
                    $save_ads[] = array(
                        'id_post' => $gap->id,
                        'title' => $gap->title,
                        'post_desc' => $gap->post_desc,
                    );
                }
            }
        }

        if($count_ad_free > 0){
            if($count_ad_free == 1){
                $get_ad_free = DB::table('umkm_post_ads AS upa')->join('umkm_posts AS up','upa.id_post','up.id')
                                                                ->join('umkm_details AS ud','upa.id_umkm','ud.id')
                                                                ->join('users AS u','ud.id_user','u.id')
                                                                ->where('upa.premium_status',0)
                                                                ->where('u.id_level',1)
                                                                ->select('up.id','up.title','up.post_desc')
                                                                ->first();
                $save_ads[] = array(
                    'id_post' => $get_ad_free->id,
                    'title' => $get_ad_free->title,
                    'post_desc' => $get_ad_free->post_desc,
                );
            }
            elseif($count_ad_free <= 3){
                $get_ad_free = DB::table('umkm_post_ads AS upa')->join('umkm_posts AS up','upa.id_post','up.id')
                                                                ->join('umkm_details AS ud','upa.id_umkm','ud.id')
                                                                ->join('users AS u','ud.id_user','u.id')
                                                                ->where('upa.premium_status',0)
                                                                ->where('u.id_level',1)
                                                                ->select('up.id','up.title','up.post_desc')
                                                                ->orderByRaw('RAND()')
                                                                ->get();
                foreach($get_ad_free as $gaf){
                        $save_ads[] = array(
                            'id_post' => $gaf->id,
                            'title' => $gaf->title,
                            'post_desc' => $gaf->post_desc,
                        );
                    }
                }
            else{
                $get_ad_free = DB::table('umkm_post_ads AS upa')->join('umkm_posts AS up','upa.id_post','up.id')
                                                                ->join('umkm_details AS ud','upa.id_umkm','ud.id')
                                                                ->join('users AS u','ud.id_user','u.id')
                                                                ->where('upa.premium_status',0)
                                                                ->where('u.id_level',1)
                                                                ->select('up.id','up.title','up.post_desc')
                                                                ->orderByRaw('RAND()')
                                                                ->limit(3)
                                                                ->get();
                foreach($get_ad_free as $gaf){
                    $save_ads[] = array(
                        'id_post' => $gaf->id,
                        'title' => $gaf->title,
                        'post_desc' => $gaf->post_desc,
                    );
                }   
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'berhasil mendapatkan data iklan',
            'data' => [
                'iklan_postingan' => $save_ads,
            ]
        ]);;
    }

    public function getPosts(){
        $count_post = Umkm_posts::count();
        $get_post = null;
        $get_file = null;
        $get_like = null;
        $get_comment = null;
        $get_comment_reply = null;
        $i = 0;
        
        $hasil_post = array();
        $hasil_like = array();
        $hasil_file = array();
        $hasil_comment = array();
        $hasil_comment_reply = array();

        if($count_post > 0){
            if($count_post > 1){
                $get_post = DB::table('umkm_posts')->join('umkm_details','umkm_posts.id_umkm','umkm_details.id')
                                                   ->select('umkm_posts.id','umkm_posts.id_umkm','umkm_details.umkm_name','umkm_details.umkm_field','title','post_desc','like_count','umkm_posts.created_at')
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
                                'id_like' => $gl->id,
                                'id_post' => $gl->id_post,
                                'nama_user' => $gl->name
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
                                $i++;
                            }
                        }
                        $hasil_comment_reply = (array) null;
                    }
                    $hasil_post[] = array(
                        'id_post' => $gp->id,
                        'id_umkm' => $gp->id_umkm,
                        'nama_umkm' => $gp->umkm_name,
                        'bidang_umkm' => $gp->umkm_field,
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
            }
            else{
                $get_post = DB::table('umkm_posts')->join('umkm_details','umkm_posts.id_umkm','umkm_details.id')
                                                   ->select('umkm_posts.id','umkm_posts.id_umkm','umkm_details.umkm_name','umkm_details.umkm_field','title','post_desc','like_count','umkm_posts.created_at')
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
                                
                            }
                            else{
                                $get_comment_reply = DB::table('umkm_post_comments AS upc')->join('users','upc.id_user','users.id')
                                                                                ->where('upc.replied_to','=',$get_comment->id)
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
                        }
                    }
                    foreach($get_comment as $gc){
                        $hasil_comment[] = array(
                            'id_komen' => $gc->id,
                            'id_post' => $gc->id_post,
                            'id_user' => $gc->id_user,
                            'nama_user' => $gc->name,
                            'komentar' => $gc->comment,
                            'balasan' => $hasil_comment_reply
                        );
                    }
                    $hasil_comment_reply = (array) null;
                }
                $hasil_post[] = array(
                    'id_post' => $get_post->id,
                    'id_umkm' => $get_post->id_umkm,
                    'nama_umkm' => $get_post->umkm_name,
                    'bidang_umkm' => $get_post->field,
                    'judul' => $get_post->title,
                    'post_desc' => $get_post->post_desc,
                    'jumlah_like' => $get_post->like_count,
                    'created_at' => $get_post->created_at,
                    'post_files' => $hasil_file,
                    'post_likers' => $hasil_like,
                    'post_comments' => $hasil_comment
                );
            }
            return response()->json([
                'success' => true,
                'message' => 'berhasil mendapatkan data post',
                'data' => [
                    'post' => $hasil_post,
                ]
            ]);
        }
        else{
            return response()->json([
                'success' => true,
                'message' => 'berhasil mendapatkan data post',
                'data' => [
                    'post' => $get_post,
                ]
            ]);
        }
    }

    public function search(Request $r){
        $validator = Validator::make($r->all(), [
            'nama' => 'required',
            'golongan_search' => 'required',
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'message' => "Error ketika memasukkan data",
                'error' => $validator->fails(),
            ],Response::HTTP_BAD_REQUEST);
        }

        if(strcasecmp($r->golongan_search, "umkm") == 0){
            $count_umkm = DB::table('umkm_details')->where('umkm_name','LIKE','%'.$r->nama.'%')
                                                   ->count();
            if($count_umkm > 0){
                if($count_umkm == 1){
                    $search_umkm = DB::table('umkm_details')->where('umkm_name','LIKE','%'.$r->nama.'%')
                                                    ->select('id','umkm_name','umkm_field')
                                                    ->first();
                }
                else{
                    $search_umkm = DB::table('umkm_details')->where('umkm_name','LIKE','%'.$r->nama.'%')
                                                            ->select('id','umkm_name','umkm_field')
                                                            ->get();
                }
            }
            else{
                $search_umkm = null;
            }

            if($search_umkm == null){
                $search_umkm = "Tidak ditemukan data UMKM dengan nama ".$r->nama;
            }
            
            return response()->json([
                'success' => true,
                'message' => "Pencarian data telah selesai",
                'data' => [
                    'hasil' => $search_umkm,
                ]
            ]);
        }
        else{
            $count_investor = DB::table('users')->where('name','LIKE','%'.$r->nama.'%')
                                                 ->where('id_level',2)
                                                 ->count();
            if($count_investor > 0){
                if($count_investor == 1){
                    $search_investor = DB::table('users')->where('name','LIKE','%'.$r->nama.'%')
                                                         ->where('id_level',2)
                                                         ->select('id','umkm_name','umkm_field')
                                                         ->first();
                }
                else{
                    $search_investor = DB::table('users')->where('name','LIKE','%'.$r->nama.'%')
                                                         ->where('id_level',2)
                                                         ->select('id','umkm_name','umkm_field')
                                                         ->get();
                }
            }
            else{
                $search_investor = null;
            }
            
            if($search_investor == null){
                $search_investor = "Tidak ditemukan data investor dengan nama ".$r->nama;
            }
            
            return response()->json([
                'success' => true,
                'message' => "Pencarian data telah selesai",
                'data' => [
                    'hasil' => $search_investor,
                ]
            ]);
        }
    }
}
