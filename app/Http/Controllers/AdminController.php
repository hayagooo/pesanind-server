<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Whoops\Run;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class AdminController extends Controller
{

    public $data;
    public $dimen;
    public $path;
    public $type;

    public function __construct(Admin $data)
    {
        $this->data = $data;
        $this->dimen = 750;
        $this->path = public_path().'/img/admin/';
        $this->type = 'Admin';
    }

    public function index(Request $request)
    {
        $query = $this->data->query();
        if($request->get('username') != null && $request->get('username')) {
            $data = $query->where('username', 'LIKE', '%'.$request->get('username').'%');
        }
        if($request->get('pagination') != null && $request->get('pagination')) {
            $data = $query->paginate($request->get('pagination'));
        } else {
            $data = $this->data->get();
        }
        return $this->onSuccess($this->type, $data, 'Founded');
    }

    public function store(Request $request)
    {
        try {
            $data = new Admin();
            $data->username = $request->username;
            $data->email = $request->email;
            $data->password = $request->password;
            $data->api_token = Str::random(60);
            $data->status = 'offline';
            $data->save();
            $userAvatar = Admin::find($data->id);
            $file = $request->file('avatar');
            $filename = str_replace(' ', '_', $request->username).'-'.time().'-'.uniqid().'.'.$file->extension();
            $img = Image::make($file->path());
            if(!File::isDirectory($this->path)) {
                File::makeDirectory($this->path, 0777, true);
            }
            $img->resize($this->dimen, $this->dimen, function($constraint) {
                $constraint->aspectRatio();
            })->save($this->path.$filename);
            $userAvatar->avatar = $filename;
            $userAvatar->save();
            return $this->onSuccess($this->type, $data, 'Stored');
        } catch (\Exception $e) {
            return $this->onError($e);
        }
    }

    public function show($id)
    {
        $data = $this->data->find($id);
        return $this->onSuccess($this->type, $data, 'Founded');
    }

    public function update(Request $request, $id)
    {
        try {
            $data = Admin::find($id);
            $data->username = $request->username;
            $data->email = $request->email;
            $data->password = $request->password;
            $data->api_token = Str::random(60);
            $data->status = 'offline';
            if($request->file('avatar') && $request->file('avatar') != null) {
                $file = $request->file('avatar');
                $filename = str_replace(' ', '_', $request->username).'-'.time().'-'.uniqid().'.'.$file->extension();
                $img = Image::make($file->path());
                if(!File::isDirectory($this->path)) {
                    File::makeDirectory($this->path, 0777, true);
                }
                if(File::exists($this->path.$data->avatar)) {
                    unlink($this->path.$data->avatar);
                }
                $img->resize($this->dimen, $this->dimen, function($constraint) {
                    $constraint->aspectRatio();
                })->save($this->path.$filename);
                $data->avatar = $filename;
            }
            $data->save();
            return $this->onSuccess($this->type, $data, 'Stored');
        } catch (\Exception $e) {
            return $this->onError($e);
        }
    }

    public function destroy($id)
    {
        try {
            $data = $this->data->find($id);
            if(File::exists($this->path.$data->avatar)) {
                unlink($this->path.$data->avatar);
            }
            $destroy = $this->data->destroy($id);
            return $this->onSuccess($this->type, $data, 'Destroyed');
        } catch (\Exception $e) {
            return $this->onError($e);
        }
    }

    public function login(Request $request)
    {
        try {
            if(Auth::guard('admin')->attempt($request->only('email', 'password'))) {
                $data = Auth::guard('admin')->user();
                return $this->onSuccess($this->type, $data, 'Login Success');
            } else {
                return $this->onSuccess($this->type, null);
            }
        } catch (\Exception $e) {
            return $this->onError($e);
        }
    }
}
